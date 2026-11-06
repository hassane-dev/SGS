<?php
/**
 * Test Suite: Promotion Thresholds Module Comprehensive Integration & Security Tests
 * Covers all 20 scenarios (28 individual assertions) of the Promotion Thresholds module.
 */

putenv('APP_ENV=development');
putenv('DB_DRIVER=sqlite');
$_ENV['APP_ENV'] = 'development';
$_ENV['DB_DRIVER'] = 'sqlite';

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/ParamPromotionSeuil.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Serie.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/services/PromotionThresholdService.php';
require_once __DIR__ . '/../src/controllers/PromotionThresholdController.php';
require_once __DIR__ . '/../db/migrations/20240115_37_create_param_promotion_seuils.php';

echo "========================================================\n";
echo "RUNNING SUITE: Promotion Thresholds Module Tests (20 Scenarios / 30 Assertions)\n";
echo "========================================================\n";

$db = Database::getInstance();

// Run Migration 37
migrate_37($db);

$GLOBALS['totalAssertions'] = 0;
$GLOBALS['passedAssertions'] = 0;

function assert_test(bool $condition, string $scenarioName, string $details = ''): void {
    $GLOBALS['totalAssertions']++;
    if (!$condition) {
        echo "❌ FAILED: {$scenarioName}" . ($details ? " - {$details}" : "") . "\n";
        throw new RuntimeException("Assertion failed for: {$scenarioName}");
    }
    $GLOBALS['passedAssertions']++;
    echo "✅ PASSED: {$scenarioName}\n";
}

// Setup isolated school tenants and cycles
$lyceeA = 901;
$lyceeB = 902;

$db->exec("DELETE FROM param_promotion_seuils WHERE lycee_id IN ($lyceeA, $lyceeB)");
$db->exec("DELETE FROM classes WHERE lycee_id IN ($lyceeA, $lyceeB)");
$db->exec("DELETE FROM series WHERE lycee_id IN ($lyceeA, $lyceeB)");
$db->exec("DELETE FROM cycles WHERE lycee_id IN ($lyceeA, $lyceeB)");
$db->exec("DELETE FROM param_lycee WHERE id IN ($lyceeA, $lyceeB)");

$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ($lyceeA, 'Lycée Test Alpha', 'public'), ($lyceeB, 'Lycée Test Beta', 'prive')");

// Cycles for Lycée A
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin, utilise_series) VALUES
    (910, $lyceeA, 'Collège A', '6e', '3e', 0),
    (920, $lyceeA, 'Lycée A', '2nde', 'Terminale', 1),
    (930, $lyceeA, 'Cycle Unconfigured A', '6e', 'Terminale', NULL)
");

// Cycles for Lycée B
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin, utilise_series) VALUES
    (940, $lyceeB, 'Collège B', '6e', '3e', 0)
");

// Series for Lycée A
$db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES
    (951, 'A', 'Littéraire', $lyceeA),
    (952, 'C', 'Scientifique', $lyceeA)
");

// -------------------------------------------------------------
// Scenario 11: Absence de règle et statut "not_configured"
// -------------------------------------------------------------
$res11 = PromotionThresholdService::resolveThresholds($lyceeA, 920, 'Terminale', 'C');
assert_test($res11['status'] === 'not_configured', 'Scénario 11: Absence de règle et statut not_configured');

// -------------------------------------------------------------
// Scenario 1: Résolution de la règle générale de l'établissement
// -------------------------------------------------------------
$ruleGenId = PromotionThresholdService::saveThreshold([
    'lycee_id' => $lyceeA,
    'portee_type' => 'etablissement',
    'seuil_admission_directe' => 10.00,
    'seuil_deliberation_min'  => 9.00,
    'seuil_deliberation_max'  => 9.99,
    'seuil_redoublement_max'  => 8.99,
    'seuil_exclusion_max'     => null
]);
assert_test($ruleGenId > 0, 'Scénario 1a: Enregistrement règle générale établissement');

$res1 = PromotionThresholdService::resolveThresholds($lyceeA, 910, '6e');
assert_test(
    $res1['status'] === 'configured' &&
    $res1['portee_type'] === 'etablissement' &&
    $res1['inherited'] === true &&
    $res1['seuil_admission_directe'] === 10.00,
    'Scénario 1b: Résolution de la règle générale héritée par défaut'
);

// -------------------------------------------------------------
// Scenario 2: Priorité d'une règle de cycle sur la règle générale
// -------------------------------------------------------------
$ruleCycleLycId = PromotionThresholdService::saveThreshold([
    'lycee_id' => $lyceeA,
    'cycle_id' => 920,
    'portee_type' => 'cycle',
    'seuil_admission_directe' => 10.50,
    'seuil_deliberation_min'  => 9.50,
    'seuil_deliberation_max'  => 10.49,
    'seuil_redoublement_max'  => 9.49,
    'seuil_exclusion_max'     => null
]);
assert_test($ruleCycleLycId > 0, 'Scénario 2a: Enregistrement règle de cycle');

$res2Lyc = PromotionThresholdService::resolveThresholds($lyceeA, 920, '2nde');
assert_test(
    $res2Lyc['portee_type'] === 'cycle' &&
    $res2Lyc['seuil_admission_directe'] === 10.50,
    'Scénario 2b: Priorité règle de cycle (Lycée = 10.50)'
);

$res2Col = PromotionThresholdService::resolveThresholds($lyceeA, 910, '5e');
assert_test(
    $res2Col['portee_type'] === 'etablissement' &&
    $res2Col['seuil_admission_directe'] === 10.00,
    'Scénario 2c: Cycle sans règle continue d\'hériter de l\'établissement (Collège = 10.00)'
);

// -------------------------------------------------------------
// Scenario 3: Priorité d'une règle de niveau sur celle du cycle
// -------------------------------------------------------------
$ruleNiveauTleId = PromotionThresholdService::saveThreshold([
    'lycee_id' => $lyceeA,
    'cycle_id' => 920,
    'niveau' => 'Terminale',
    'portee_type' => 'niveau',
    'seuil_admission_directe' => 11.00,
    'seuil_deliberation_min'  => 10.00,
    'seuil_deliberation_max'  => 10.99,
    'seuil_redoublement_max'  => 9.99,
    'seuil_exclusion_max'     => 6.00
]);
assert_test($ruleNiveauTleId > 0, 'Scénario 3a: Enregistrement règle niveau Terminale');

$res3Tle = PromotionThresholdService::resolveThresholds($lyceeA, 920, 'Terminale');
assert_test(
    $res3Tle['portee_type'] === 'niveau' &&
    $res3Tle['inherited'] === false &&
    $res3Tle['seuil_admission_directe'] === 11.00 &&
    $res3Tle['seuil_exclusion_max'] === 6.00,
    'Scénario 3b: Priorité règle niveau (Terminale = 11.00)'
);

$res3Prem = PromotionThresholdService::resolveThresholds($lyceeA, 920, '1ère');
assert_test(
    $res3Prem['portee_type'] === 'cycle' &&
    $res3Prem['inherited'] === true &&
    $res3Prem['seuil_admission_directe'] === 10.50,
    'Scénario 3c: Autre niveau du même cycle hérite de la règle de cycle (1ère = 10.50)'
);

// -------------------------------------------------------------
// Scenario 4: Priorité d'une règle niveau + série sur celle du niveau
// -------------------------------------------------------------
$ruleSerieCId = PromotionThresholdService::saveThreshold([
    'lycee_id' => $lyceeA,
    'cycle_id' => 920,
    'niveau' => 'Terminale',
    'serie' => 'C',
    'portee_type' => 'niveau_serie',
    'seuil_admission_directe' => 12.00,
    'seuil_deliberation_min'  => 11.00,
    'seuil_deliberation_max'  => 11.99,
    'seuil_redoublement_max'  => 10.99,
    'seuil_exclusion_max'     => 7.00
]);
assert_test($ruleSerieCId > 0, 'Scénario 4a: Enregistrement règle Terminale C');

$res4TleC = PromotionThresholdService::resolveThresholds($lyceeA, 920, 'Terminale', 'C');
assert_test(
    $res4TleC['portee_type'] === 'niveau_serie' &&
    $res4TleC['inherited'] === false &&
    $res4TleC['seuil_admission_directe'] === 12.00,
    'Scénario 4b: Priorité règle niveau + série (Terminale C = 12.00)'
);

$res4TleA = PromotionThresholdService::resolveThresholds($lyceeA, 920, 'Terminale', 'A');
assert_test(
    $res4TleA['portee_type'] === 'niveau' &&
    $res4TleA['inherited'] === true &&
    $res4TleA['seuil_admission_directe'] === 11.00,
    'Scénario 4c: Terminale A hérite de la règle niveau Terminale (11.00)'
);

// -------------------------------------------------------------
// Scenario 5: Absence de fuite entre deux cycles
// -------------------------------------------------------------
$res5 = PromotionThresholdService::resolveThresholds($lyceeA, 910, 'Terminale');
assert_test(
    $res5['portee_type'] === 'etablissement',
    'Scénario 5: Règle de niveau du Lycée non appliquée au cycle Collège'
);

// -------------------------------------------------------------
// Scenario 6: Absence de fuite entre deux établissements
// -------------------------------------------------------------
$res6 = PromotionThresholdService::resolveThresholds($lyceeB, 940, '6e');
assert_test(
    $res6['status'] === 'not_configured',
    'Scénario 6: Règles du Lycée A non visibles pour le Lycée B'
);

// -------------------------------------------------------------
// Scenario 7: Rejet des doublons de périmètre
// -------------------------------------------------------------
$threwDup = false;
try {
    PromotionThresholdService::saveThreshold([
        'lycee_id' => $lyceeA,
        'portee_type' => 'etablissement',
        'seuil_admission_directe' => 10.00,
        'seuil_deliberation_min'  => 9.00,
        'seuil_deliberation_max'  => 9.99,
        'seuil_redoublement_max'  => 8.99
    ]);
} catch (InvalidArgumentException $e) {
    $threwDup = true;
}
assert_test($threwDup, 'Scénario 7: Rejet des doublons de périmètre exact');

// -------------------------------------------------------------
// Scenario 8: Rejet des intervalles incohérents
// -------------------------------------------------------------
$threwIncoherent = false;
try {
    // Delib min > Delib max
    PromotionThresholdService::saveThreshold([
        'lycee_id' => $lyceeA,
        'cycle_id' => 910,
        'portee_type' => 'cycle',
        'seuil_admission_directe' => 10.00,
        'seuil_deliberation_min'  => 9.50,
        'seuil_deliberation_max'  => 9.00, // Error
        'seuil_redoublement_max'  => 8.99
    ]);
} catch (InvalidArgumentException $e) {
    $threwIncoherent = true;
}
assert_test($threwIncoherent, 'Scénario 8: Rejet des intervalles avec borne min >= borne max');

// -------------------------------------------------------------
// Scenario 9: Rejet d'un niveau hors cycle
// -------------------------------------------------------------
$threwLevelOutOfCycle = false;
try {
    // 6e does not belong to Lycée (2nde to Terminale)
    PromotionThresholdService::saveThreshold([
        'lycee_id' => $lyceeA,
        'cycle_id' => 920,
        'niveau' => '6e',
        'portee_type' => 'niveau',
        'seuil_admission_directe' => 10.00,
        'seuil_deliberation_min'  => 9.00,
        'seuil_deliberation_max'  => 9.99,
        'seuil_redoublement_max'  => 8.99
    ]);
} catch (InvalidArgumentException $e) {
    $threwLevelOutOfCycle = true;
}
assert_test($threwLevelOutOfCycle, 'Scénario 9: Rejet d\'un niveau hors des bornes du cycle');

// -------------------------------------------------------------
// Scenario 10: Rejet d'une série invalide
// -------------------------------------------------------------
$threwInvalidSerie = false;
try {
    // Serie Z does not exist in school
    PromotionThresholdService::saveThreshold([
        'lycee_id' => $lyceeA,
        'cycle_id' => 920,
        'niveau' => 'Terminale',
        'serie' => 'Z_INCONNUE',
        'portee_type' => 'niveau_serie',
        'seuil_admission_directe' => 12.00,
        'seuil_deliberation_min'  => 11.00,
        'seuil_deliberation_max'  => 11.99,
        'seuil_redoublement_max'  => 10.99
    ]);
} catch (InvalidArgumentException $e) {
    $threwInvalidSerie = true;
}
assert_test($threwInvalidSerie, 'Scénario 10: Rejet d\'une série non reconnue');

// -------------------------------------------------------------
// Scenario 12: Cycle avec utilise_series = 0
// -------------------------------------------------------------
$threwSeriesOnZero = false;
try {
    // Cycle 910 has utilise_series = 0 (Collège)
    PromotionThresholdService::saveThreshold([
        'lycee_id' => $lyceeA,
        'cycle_id' => 910,
        'niveau' => '3e',
        'serie' => 'A',
        'portee_type' => 'niveau_serie',
        'seuil_admission_directe' => 10.00,
        'seuil_deliberation_min'  => 9.00,
        'seuil_deliberation_max'  => 9.99,
        'seuil_redoublement_max'  => 8.99
    ]);
} catch (InvalidArgumentException $e) {
    $threwSeriesOnZero = true;
}
assert_test($threwSeriesOnZero, 'Scénario 12: Interdiction des séries sur un cycle utilise_series = 0');

// -------------------------------------------------------------
// Scenario 13: Cycle avec utilise_series = 1
// -------------------------------------------------------------
$rule13Id = PromotionThresholdService::saveThreshold([
    'lycee_id' => $lyceeA,
    'cycle_id' => 920,
    'niveau' => '1ère',
    'serie' => 'A',
    'portee_type' => 'niveau_serie',
    'seuil_admission_directe' => 10.50,
    'seuil_deliberation_min'  => 9.50,
    'seuil_deliberation_max'  => 10.49,
    'seuil_redoublement_max'  => 9.49
]);
assert_test($rule13Id > 0, 'Scénario 13: Autorisation des règles par série sur cycle utilise_series = 1');

// -------------------------------------------------------------
// Scenario 14: Cycle avec utilise_series = NULL
// -------------------------------------------------------------
$threwSeriesOnNull = false;
try {
    // Cycle 930 has utilise_series = NULL
    PromotionThresholdService::saveThreshold([
        'lycee_id' => $lyceeA,
        'cycle_id' => 930,
        'niveau' => '3e',
        'serie' => 'A',
        'portee_type' => 'niveau_serie',
        'seuil_admission_directe' => 10.00,
        'seuil_deliberation_min'  => 9.00,
        'seuil_deliberation_max'  => 9.99,
        'seuil_redoublement_max'  => 8.99
    ]);
} catch (InvalidArgumentException $e) {
    $threwSeriesOnNull = true;
}
assert_test($threwSeriesOnNull, 'Scénario 14: Blocage explicite si utilise_series est NULL');

// -------------------------------------------------------------
// Scenario 15: Contrôle des permissions RBAC
// -------------------------------------------------------------
$db->exec("DELETE FROM utilisateurs WHERE id_user = 9999");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif) VALUES (9999, 'Test', 'Student', 'test_stu@test.com', 8, $lyceeA, 1)");

$_SESSION['user'] = [
    'id' => 9999,
    'id_user' => 9999,
    'role_id' => 8, // Student
    'role_name' => 'eleve',
    'lycee_id' => $lyceeA,
    'auth_version' => 1,
    'permissions' => []
];

$ctl = new PromotionThresholdController();
$threwPermission = false;
try {
    $ctl->store();
} catch (Exception $e) {
    $threwPermission = true;
}
assert_test($threwPermission, 'Scénario 15: Rejet accès si permission promotion_threshold:manage absente');
$db->exec("DELETE FROM utilisateurs WHERE id_user = 9999");

// -------------------------------------------------------------
// Scenario 16: Vérification des actions CRUD (Création, Lecture, Modification, Suppression)
// -------------------------------------------------------------
$ruleCrudId = PromotionThresholdService::saveThreshold([
    'lycee_id' => $lyceeA,
    'cycle_id' => 910,
    'niveau' => '6e',
    'portee_type' => 'niveau',
    'seuil_admission_directe' => 10.20,
    'seuil_deliberation_min'  => 9.20,
    'seuil_deliberation_max'  => 10.19,
    'seuil_redoublement_max'  => 9.19
]);

$fetched = ParamPromotionSeuil::findById($ruleCrudId);
assert_test($fetched && (float)$fetched['seuil_admission_directe'] === 10.20, 'Scénario 16a: CRUD Lecture');

// Update
PromotionThresholdService::saveThreshold([
    'id' => $ruleCrudId,
    'lycee_id' => $lyceeA,
    'cycle_id' => 910,
    'niveau' => '6e',
    'portee_type' => 'niveau',
    'seuil_admission_directe' => 10.30,
    'seuil_deliberation_min'  => 9.30,
    'seuil_deliberation_max'  => 10.29,
    'seuil_redoublement_max'  => 9.29
]);
$updated = ParamPromotionSeuil::findById($ruleCrudId);
assert_test($updated && (float)$updated['seuil_admission_directe'] === 10.30, 'Scénario 16b: CRUD Modification');

// Delete
$delOk = PromotionThresholdService::deleteThreshold($ruleCrudId, $lyceeA);
$deleted = ParamPromotionSeuil::findById($ruleCrudId);
assert_test($delOk && $deleted === null, 'Scénario 16c: CRUD Suppression');

// -------------------------------------------------------------
// Scenario 17: Vérification de la matrice des règles effectives
// -------------------------------------------------------------
$matrix = PromotionThresholdService::getEffectiveMatrix($lyceeA);
assert_test(
    !empty($matrix['general_rule']) &&
    isset($matrix['cycle_rules'][920]) &&
    isset($matrix['niveau_rules']['920_Terminale']) &&
    isset($matrix['niveau_serie_rules']['920_Terminale_C']),
    'Scénario 17: Matrice d\'affichage de l\'héritage complète'
);

// -------------------------------------------------------------
// Scenario 18: Vérification du cloisonnement multi-établissements
// -------------------------------------------------------------
$rulesLyceeA = ParamPromotionSeuil::findByLycee($lyceeA);
$rulesLyceeB = ParamPromotionSeuil::findByLycee($lyceeB);
assert_test(count($rulesLyceeA) > 0 && count($rulesLyceeB) === 0, 'Scénario 18: Cloisonnement strict multi-établissements');



// -------------------------------------------------------------
// Scenario 19 (Validation-Seuils-2): Pure Cycle Level Boundaries Fallback (No classes created)
// -------------------------------------------------------------
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin, utilise_series) VALUES
    (960, $lyceeA, 'Cycle Sans Classes', '6e', '3e', 0)
");
// Ensure 0 classes exist for cycle 960
$db->exec("DELETE FROM classes WHERE cycle_id = 960");

// We should be able to create rules for levels within bounds (e.g. 5e)
$rule960Id = PromotionThresholdService::saveThreshold([
    'lycee_id' => $lyceeA,
    'cycle_id' => 960,
    'niveau' => '5e',
    'portee_type' => 'niveau',
    'seuil_admission_directe' => 10.00,
    'seuil_deliberation_min'  => 9.00,
    'seuil_deliberation_max'  => 9.99,
    'seuil_redoublement_max'  => 8.99
]);
assert_test($rule960Id > 0, 'Scénario 19a: Règle de niveau créée avec succès sur cycle vide de classes via bornes canoniques');

// Attempting level outside cycle bounds (e.g. Seconde) must still be rejected
$threwBoundsOnEmptyCycle = false;
try {
    PromotionThresholdService::saveThreshold([
        'lycee_id' => $lyceeA,
        'cycle_id' => 960,
        'niveau' => 'Seconde',
        'portee_type' => 'niveau',
        'seuil_admission_directe' => 10.00,
        'seuil_deliberation_min'  => 9.00,
        'seuil_deliberation_max'  => 9.99,
        'seuil_redoublement_max'  => 8.99
    ]);
} catch (InvalidArgumentException $e) {
    $threwBoundsOnEmptyCycle = true;
}
assert_test($threwBoundsOnEmptyCycle, 'Scénario 19b: Rejet du niveau hors bornes même en l\'absence de classes');

$db->exec("DELETE FROM param_promotion_seuils WHERE cycle_id = 960");
$db->exec("DELETE FROM cycles WHERE id_cycle = 960");

// -------------------------------------------------------------
// Scenario 20 (Validation-Seuils-2): Strict Autonomy Verification (Zero Side Effects on Students/Studies/Bulletins)
// -------------------------------------------------------------
$stmtElevesCountBefore = $db->query("SELECT COUNT(*) FROM eleves")->fetchColumn();
$stmtEtudesCountBefore = $db->query("SELECT COUNT(*) FROM etudes")->fetchColumn();
$stmtBulletinsCountBefore = $db->query("SELECT COUNT(*) FROM bulletins")->fetchColumn();

// Perform resolution and save operations
$testAutoRuleId = PromotionThresholdService::saveThreshold([
    'lycee_id' => $lyceeA,
    'cycle_id' => 910,
    'niveau' => '4e',
    'portee_type' => 'niveau',
    'seuil_admission_directe' => 10.00,
    'seuil_deliberation_min'  => 9.00,
    'seuil_deliberation_max'  => 9.99,
    'seuil_redoublement_max'  => 8.99
]);
$resAutonomy = PromotionThresholdService::resolveThresholds($lyceeA, 910, '4e');
PromotionThresholdService::deleteThreshold($testAutoRuleId, $lyceeA);

$stmtElevesCountAfter = $db->query("SELECT COUNT(*) FROM eleves")->fetchColumn();
$stmtEtudesCountAfter = $db->query("SELECT COUNT(*) FROM etudes")->fetchColumn();
$stmtBulletinsCountAfter = $db->query("SELECT COUNT(*) FROM bulletins")->fetchColumn();

assert_test(
    $stmtElevesCountBefore === $stmtElevesCountAfter &&
    $stmtEtudesCountBefore === $stmtEtudesCountAfter &&
    $stmtBulletinsCountBefore === $stmtBulletinsCountAfter,
    'Scénario 20: Autonomie stricte confirmée (Zéro modification sur élèves, études ou bulletins)'
);



// Final Clean up test data
$db->exec("DELETE FROM param_promotion_seuils WHERE lycee_id IN ($lyceeA, $lyceeB)");
$db->exec("DELETE FROM series WHERE lycee_id IN ($lyceeA, $lyceeB)");
$db->exec("DELETE FROM cycles WHERE lycee_id IN ($lyceeA, $lyceeB)");
$db->exec("DELETE FROM param_lycee WHERE id IN ($lyceeA, $lyceeB)");

$tot = $GLOBALS['totalAssertions'];
$pas = $GLOBALS['passedAssertions'];
echo "\n========================================================\n";
echo "TEST SUITE SUMMARY: ALL 20 SCENARIOS PASSED ({$pas}/{$tot} ASSERTIONS PASSED, 0 FAILED)\n";
echo "========================================================\n";
