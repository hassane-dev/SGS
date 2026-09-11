<?php
/**
 * Automated Integration Test Suite for SGS Report Card Global Validation Workflow.
 *
 * Validates:
 * 1. Sequence closure generates provisional bulletins (`statut = 'provisoire'`).
 * 2. Validation attempted on an open sequence is strictly rejected.
 * 3. Validation on incomplete bulletins (missing evaluations) is strictly rejected and incomplete bulletins are isolated without converting missing notes to 0.00.
 * 4. Successful bulk validation for Cycle / Niveau / Classe updates `statut = 'valide'`, populates `valide_le` and `valide_par`, and preserves `appreciation_conseil_classe`.
 * 5. Re-running validation preserves existing validated and published bulletins without retroactive degradation.
 * 6. Multi-tenant and cycle RBAC security strictly rejects scope tampering across establishments.
 * 7. Institutional dynamic appreciation returns exact scale values.
 */

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/Bulletin.php';
require_once __DIR__ . '/../src/services/SequenceClosureService.php';
require_once __DIR__ . '/../src/services/BulletinValidationService.php';
require_once __DIR__ . '/../src/services/EvaluationCalculationService.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';

echo "=====================================================================\n";
echo "STARTING TEST SUITE: Report Card Global Validation Workflow\n";
echo "=====================================================================\n\n";

$db = Database::getInstance();

// -------------------------------------------------------------------
// HELPER ASSERTIONS
// -------------------------------------------------------------------
function assert_val_test($condition, $message) {
    if ($condition) {
        echo "  [PASS] {$message}\n";
    } else {
        echo "  [FAIL] {$message}\n";
        throw new Exception("Test Assertion Failed: {$message}");
    }
}

try {
    // Set up test environment data
    $testLyceeId = 1;
    $testUserId = 1;

    // Clean up test data before starting
    $db->exec("DELETE FROM evaluations WHERE eleve_id >= 9980");
    $db->exec("DELETE FROM bulletin_details WHERE bulletin_id IN (SELECT id FROM bulletins WHERE eleve_id >= 9980)");
    $db->exec("DELETE FROM bulletins WHERE eleve_id >= 9980");
    $db->exec("DELETE FROM etudes WHERE eleve_id >= 9980");
    $db->exec("DELETE FROM eleves WHERE id_eleve >= 9980");
    $db->exec("DELETE FROM classe_matieres WHERE classe_id >= 9980");
    $db->exec("DELETE FROM classes WHERE id_classe >= 9980");
    $db->exec("DELETE FROM cycles WHERE id_cycle >= 9980");
    $db->exec("DELETE FROM sequences WHERE id >= 9980");

    // 1. Create test cycle, classes, subjects
    $db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (9980, {$testLyceeId}, 'Cycle Test Validation')");
    $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (9980, '6ème', 'A', 1, 9980, {$testLyceeId})");
    $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (9981, '6ème', 'B', 1, 9980, {$testLyceeId})");

    // Delete matieres 9980 if exists
    $db->exec("DELETE FROM matieres WHERE id_matiere = 9980");
    // Attach subject to class 9980
    $db->exec("INSERT INTO matieres (id_matiere, nom_matiere, lycee_id) VALUES (9980, 'Maths Test', {$testLyceeId})");
    $db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (9980, 9980, 3.00)");
    $db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (9981, 9980, 3.00)");

    // Create 2 test students for class 9980
    $db->exec("INSERT INTO eleves (id_eleve, nom, prenom, identifiant_public, lycee_id) VALUES (9980, 'Valideur', 'Jean', 'MAT9980', {$testLyceeId})");
    $db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active) VALUES (9980, 9980, {$testLyceeId}, 1, 1)");

    $db->exec("INSERT INTO eleves (id_eleve, nom, prenom, identifiant_public, lycee_id) VALUES (9981, 'Incomplet', 'Paul', 'MAT9981', {$testLyceeId})");
    $db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active) VALUES (9981, 9980, {$testLyceeId}, 1, 1)");

    // Create active sequence 9980
    $db->exec("INSERT INTO sequences (id, nom, lycee_id, annee_academique_id, statut) VALUES (9980, 'Séquence Test Validation', {$testLyceeId}, 1, 'ouverte')");

    // Authenticate user
    $_SESSION['user'] = [
        'id_user' => $testUserId,
        'lycee_id' => $testLyceeId,
        'role_name' => 'super_admin_createur',
        'permissions' => [
            'bulletin' => ['generate', 'validate', 'edit_appreciation_conseil'],
            'lycee' => ['view_all_lycees'],
            'cycle' => ['view_all_cycles']
        ]
    ];

    // -------------------------------------------------------------------
    // TEST 1: Open Sequence Rejection
    // -------------------------------------------------------------------
    echo "--- Test 1: Validation on Open Sequence MUST be Rejected ---\n";
    try {
        BulletinValidationService::executeValidation($testLyceeId, 9980, 'classe', 9980, $testUserId);
        assert_val_test(false, "Validation on open sequence should have thrown LogicException!");
    } catch (LogicException $e) {
        assert_val_test(strpos($e->getMessage(), 'ouverte') !== false, "Open sequence validation properly blocked: " . $e->getMessage());
    }

    // -------------------------------------------------------------------
    // TEST 2: Sequence Closure Creates 'provisoire' Bulletins
    // -------------------------------------------------------------------
    echo "\n--- Test 2: Sequence Closure Produces 'provisoire' Status ---\n";

    // Add evaluation for student 9980 only
    $db->exec("INSERT INTO evaluations (eleve_id, classe_id, matiere_id, sequence_id, annee_academique_id, lycee_id, type_evaluation_id, type, numero_evaluation, note, bareme_snapshot, coefficient) VALUES (9980, 9980, 9980, 9980, 1, {$testLyceeId}, 1, 'devoir', 1, 15.00, 20.00, 3.00)");

    // Close sequence 9980
    $closure = SequenceClosureService::closeSequence(9980, $testUserId);
    assert_val_test($closure['success'] === true, "Sequence closure executed successfully.");

    $stmtBul1 = $db->query("SELECT statut FROM bulletins WHERE eleve_id = 9980 AND sequence_id = 9980");
    $bul1 = $stmtBul1->fetch(PDO::FETCH_ASSOC);
    assert_val_test($bul1['statut'] === 'provisoire', "Closed sequence produced bulletin with statut = 'provisoire'.");

    // -------------------------------------------------------------------
    // TEST 3: Detection and Isolation of Incomplete Bulletins
    // -------------------------------------------------------------------
    echo "\n--- Test 3: Incomplete Bulletin Detection & Protection ---\n";

    $summary = BulletinValidationService::getValidationSummary($testLyceeId, 9980, 'classe', 9980);
    assert_val_test($summary['bloque_count'] === 1, "Incomplete student #9981 detected as blocked.");
    assert_val_test(count($summary['bloque_details']) === 1, "Blocked student details populated.");
    assert_val_test($summary['bloque_details'][0]['eleve_id'] === 9981, "Student #9981 identified as blocked.");

    // Verify execution is blocked
    try {
        BulletinValidationService::executeValidation($testLyceeId, 9980, 'classe', 9980, $testUserId);
        assert_val_test(false, "Validation with blocked incomplete student should have thrown LogicException!");
    } catch (LogicException $e) {
        assert_val_test(strpos($e->getMessage(), 'incomplets') !== false, "Incomplete bulletin execution properly blocked: " . $e->getMessage());
    }

    // -------------------------------------------------------------------
    // TEST 4: Successful Bulk Validation
    // -------------------------------------------------------------------
    echo "\n--- Test 4: Successful Bulk Validation for Complete Scope ---\n";

    // Add evaluation for student 9981 to make class 9980 complete
    $db->exec("INSERT INTO evaluations (eleve_id, classe_id, matiere_id, sequence_id, annee_academique_id, lycee_id, type_evaluation_id, type, numero_evaluation, note, bareme_snapshot, coefficient) VALUES (9981, 9980, 9980, 9980, 1, {$testLyceeId}, 1, 'devoir', 1, 12.00, 20.00, 3.00)");

    // Save Class Council Appreciation for student 9980
    Bulletin::saveAppreciationConseil(9980, 9980, 1, $testLyceeId, "Très bon élève, félicitations.");

    // Re-run closure to update snapshot
    $db->exec("UPDATE sequences SET statut = 'ouverte' WHERE id = 9980");
    SequenceClosureService::closeSequence(9980, $testUserId);

    // Verify summary is now 100% ready
    $summary2 = BulletinValidationService::getValidationSummary($testLyceeId, 9980, 'classe', 9980);
    assert_val_test($summary2['bloque_count'] === 0, "0 blocked students after completing evaluations.");
    assert_val_test($summary2['provisoire_count'] === 2, "2 provisional bulletins ready for validation.");

    // Execute validation
    $execResult = BulletinValidationService::executeValidation($testLyceeId, 9980, 'classe', 9980, $testUserId);
    assert_val_test($execResult['success'] === true && $execResult['validated_count'] === 2, "Bulk validation executed for 2 students.");

    // Check DB state
    $stmtBulVal = $db->query("SELECT * FROM bulletins WHERE eleve_id = 9980 AND sequence_id = 9980");
    $bulVal = $stmtBulVal->fetch(PDO::FETCH_ASSOC);
    assert_val_test($bulVal['statut'] === 'valide', "Bulletin statut updated to 'valide'.");
    assert_val_test(!empty($bulVal['valide_le']), "valide_le timestamp populated.");
    assert_val_test((int)$bulVal['valide_par'] === $testUserId, "valide_par user ID populated.");
    assert_val_test($bulVal['appreciation_conseil_classe'] === "Très bon élève, félicitations.", "Class Council Appreciation preserved intact.");

    // -------------------------------------------------------------------
    // TEST 5: Non-retrogradation of Validated & Published Bulletins
    // -------------------------------------------------------------------
    echo "\n--- Test 5: Re-running Validation Preserves Statuses ---\n";

    // Set student 9981 to 'publie'
    $db->exec("UPDATE bulletins SET statut = 'publie' WHERE eleve_id = 9981 AND sequence_id = 9980");

    $summary3 = BulletinValidationService::getValidationSummary($testLyceeId, 9980, 'classe', 9980);
    assert_val_test($summary3['valide_count'] === 1, "1 validated bulletin counted.");
    assert_val_test($summary3['publie_count'] === 1, "1 published bulletin counted.");
    assert_val_test($summary3['provisoire_count'] === 0, "0 provisional bulletins left.");

    $execResult2 = BulletinValidationService::executeValidation($testLyceeId, 9980, 'classe', 9980, $testUserId);
    assert_val_test($execResult2['validated_count'] === 0, "Re-running validation does not re-validate or alter count.");

    $stmtBulPub = $db->query("SELECT statut FROM bulletins WHERE eleve_id = 9981 AND sequence_id = 9980");
    assert_val_test($stmtBulPub->fetchColumn() === 'publie', "Published bulletin status remains 'publie' without retrogradation.");

    // -------------------------------------------------------------------
    // TEST 6: Multi-Tenant & Cycle Scope Security
    // -------------------------------------------------------------------
    echo "\n--- Test 6: Multi-Tenant Scope Security ---\n";

    $_SESSION['user']['lycee_id'] = 999; // Different school
    $_SESSION['user']['permissions'] = ['bulletin' => ['validate']]; // Restrict permissions

    try {
        BulletinValidationService::getValidationSummary(999, 9980, 'cycle', 9980);
        assert_val_test(false, "Cross-tenant scope resolution should have thrown InvalidArgumentException!");
    } catch (InvalidArgumentException $e) {
        assert_val_test(true, "Cross-tenant access properly rejected: " . $e->getMessage());
    }

    // -------------------------------------------------------------------
    // TEST 7: Institutional Dynamic Appreciation Verification
    // -------------------------------------------------------------------
    echo "\n--- Test 7: Institutional Dynamic Appreciation Thresholds ---\n";

    assert_val_test(EvaluationCalculationService::getInstitutionalAppreciation(17.50) === 'Très Bien', "17.50 -> Très Bien");
    assert_val_test(EvaluationCalculationService::getInstitutionalAppreciation(15.00) === 'Bien', "15.00 -> Bien");
    assert_val_test(EvaluationCalculationService::getInstitutionalAppreciation(13.25) === 'Assez Bien', "13.25 -> Assez Bien");
    assert_val_test(EvaluationCalculationService::getInstitutionalAppreciation(10.50) === 'Passable', "10.50 -> Passable");
    assert_val_test(EvaluationCalculationService::getInstitutionalAppreciation(9.00) === 'Insuffisant', "9.00 -> Insuffisant");
    assert_val_test(EvaluationCalculationService::getInstitutionalAppreciation(5.50) === 'Médiocre', "5.50 -> Médiocre");
    assert_val_test(EvaluationCalculationService::getInstitutionalAppreciation(null) === '', "null -> empty string");

    echo "\n=====================================================================\n";
    echo "ALL INTEGRATION TESTS PASSED SUCCESSFULLY!\n";
    echo "=====================================================================\n";

} catch (Exception $e) {
    echo "\n[ERROR] Test suite failed with exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
} finally {
    // Cleanup
    $db->exec("DELETE FROM evaluations WHERE eleve_id >= 9980");
    $db->exec("DELETE FROM bulletin_details WHERE bulletin_id IN (SELECT id FROM bulletins WHERE eleve_id >= 9980)");
    $db->exec("DELETE FROM bulletins WHERE eleve_id >= 9980");
    $db->exec("DELETE FROM etudes WHERE eleve_id >= 9980");
    $db->exec("DELETE FROM eleves WHERE id_eleve >= 9980");
    $db->exec("DELETE FROM classe_matieres WHERE classe_id >= 9980");
    $db->exec("DELETE FROM classes WHERE id_classe >= 9980");
    $db->exec("DELETE FROM cycles WHERE id_cycle >= 9980");
    $db->exec("DELETE FROM sequences WHERE id >= 9980");
}
?>