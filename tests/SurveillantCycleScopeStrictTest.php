<?php
/**
 * Test Suite: Strict Cycle Scope Enforcement for Supervisor Assignments
 */

putenv('APP_ENV=development');
putenv('DB_DRIVER=sqlite');
$_ENV['APP_ENV'] = 'development';
$_ENV['DB_DRIVER'] = 'sqlite';

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/SurveillantAssignmentService.php';
require_once __DIR__ . '/../src/models/SurveillantAffectation.php';
require_once __DIR__ . '/../src/models/SurveillantGroupe.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../db/migrations/20240115_10_create_personnel_cycles.php';
require_once __DIR__ . '/../db/migrations/20240115_33_create_surveillant_affectations_and_groupes.php';

echo "========================================================\n";
echo "RUNNING SUITE: Strict Cycle Scope Enforcement Test\n";
echo "========================================================\n";

$db = Database::getInstance();

// Run migrations to ensure clean schema
migrate_10($db);
migrate_33($db);

$lyceeId = 881;

// Clean up test data
$db->exec("DELETE FROM surveillant_affectations WHERE lycee_id = $lyceeId");
$db->exec("DELETE FROM surveillant_groupe_classes WHERE groupe_id IN (SELECT id FROM surveillant_groupes WHERE lycee_id = $lyceeId)");
$db->exec("DELETE FROM surveillant_groupes WHERE lycee_id = $lyceeId");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (8811, 8812, 8813, 8814)");
$db->exec("DELETE FROM classes WHERE lycee_id = $lyceeId");
$db->exec("DELETE FROM cycles WHERE lycee_id = $lyceeId");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (8811, 8812, 8813, 8814)");
$db->exec("DELETE FROM param_lycee WHERE id = $lyceeId");
$db->exec("DELETE FROM annees_academiques WHERE id = 881");

// Seed test establishment & year
$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee, surveillant_config_version, surveillant_migration_status) VALUES ($lyceeId, 'Lycée Test Scope Strict', 'public', 1, 'migre_valide')");
$db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (881, '2025-2026', '2025-09-01', '2026-06-30', 1)");

// Seed Cycles: CEG (88101) & Lycée (88102)
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, utilise_series) VALUES (88101, $lyceeId, 'CEG', 0)");
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, utilise_series) VALUES (88102, $lyceeId, 'Lycée', 1)");

// Seed Classes: 6e 1 (CEG 8810101), 3e 1 (CEG 8810102), Seconde A (Lycée 8810201), Terminale C (Lycée 8810202)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8810101, $lyceeId, 88101, '6e', NULL, '1')");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8810102, $lyceeId, 88101, '3e', NULL, '1')");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8810201, $lyceeId, 88102, 'Seconde', 'A', '1')");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8810202, $lyceeId, 88102, 'Terminale', 'C', '1')");

// Seed Supervisors
// User 8811: Surveillant CEG Only
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif) VALUES (8811, $lyceeId, 5, 'Dupont', 'Jean', 'jean.ceg@test.com', 1)");
// User 8812: Surveillant Lycée Only
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif) VALUES (8812, $lyceeId, 5, 'Koffi', 'Awa', 'awa.lycee@test.com', 1)");
// User 8813: Surveillant Multi-cycle (CEG + Lycée)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif) VALUES (8813, $lyceeId, 5, 'Traore', 'Moussa', 'moussa.multi@test.com', 1)");
// User 8814: Surveillant Expired Cycle
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif) VALUES (8814, $lyceeId, 5, 'Sow', 'Fatou', 'fatou.expired@test.com', 1)");

// Seed personnel_cycles_assignments
$today = date('Y-m-d');
$past_start = date('Y-m-d', strtotime('-30 days'));
$past_end = date('Y-m-d', strtotime('-1 day'));

// User 8811 -> CEG (88101) Active
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (8811, 88101, '$past_start', NULL, 1)");
// User 8812 -> Lycée (88102) Active
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (8812, 88102, '$past_start', NULL, 1)");
// User 8813 -> CEG (88101) & Lycée (88102) Active
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (8813, 88101, '$past_start', NULL, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (8813, 88102, '$past_start', NULL, 1)");
// User 8814 -> CEG (88101) Expired
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif) VALUES (8814, 88101, '$past_start', '$past_end', 1)");

// ========================================================
// TEST 1: Affectation d'un surveillant CEG à une classe CEG (Succès)
// ========================================================
echo "\n--- TEST 1: Affectation CEG -> Classe CEG ---\n";
try {
    SurveillantAssignmentService::validateCycleCompliance([
        'lycee_id' => $lyceeId,
        'surveillant_id' => 8811,
        'classe_id' => 8810101 // 6e 1 (CEG)
    ]);
    echo "[PASS] 1a. Supervisor 8811 authorized on CEG class 8810101.\n";
} catch (Exception $e) {
    echo "[FAIL] 1a. Unexpected failure: " . $e->getMessage() . "\n";
}

// ========================================================
// TEST 2: Rejet d'une affectation d'un surveillant CEG à une classe Lycée
// ========================================================
echo "\n--- TEST 2: Rejet CEG -> Classe Lycée ---\n";
try {
    SurveillantAssignmentService::validateCycleCompliance([
        'lycee_id' => $lyceeId,
        'surveillant_id' => 8811,
        'classe_id' => 8810201 // Seconde A (Lycée)
    ]);
    echo "[FAIL] 2a. Expected rejection for CEG supervisor on Lycée class.\n";
} catch (InvalidArgumentException $e) {
    echo "[PASS] 2a. Rejection properly caught: " . $e->getMessage() . "\n";
}

// ========================================================
// TEST 3: Surveillant multi-cycle (CEG + Lycée) autorisé dans les deux
// ========================================================
echo "\n--- TEST 3: Surveillant Multi-cycle ---\n";
try {
    SurveillantAssignmentService::validateCycleCompliance([
        'lycee_id' => $lyceeId,
        'surveillant_id' => 8813,
        'classe_id' => 8810101 // CEG
    ]);
    SurveillantAssignmentService::validateCycleCompliance([
        'lycee_id' => $lyceeId,
        'surveillant_id' => 8813,
        'classe_id' => 8810201 // Lycée
    ]);
    echo "[PASS] 3a. Multi-cycle supervisor 8813 authorized on both CEG and Lycée classes.\n";
} catch (Exception $e) {
    echo "[FAIL] 3a. Unexpected failure for multi-cycle supervisor: " . $e->getMessage() . "\n";
}

// ========================================================
// TEST 4: Blocage pour autorisation de cycle expirée
// ========================================================
echo "\n--- TEST 4: Rejet Autorisation de Cycle Expirée ---\n";
try {
    SurveillantAssignmentService::validateCycleCompliance([
        'lycee_id' => $lyceeId,
        'surveillant_id' => 8814,
        'classe_id' => 8810101
    ]);
    echo "[FAIL] 4a. Expected rejection for expired cycle authorization.\n";
} catch (InvalidArgumentException $e) {
    echo "[PASS] 4a. Rejection properly caught for expired authorization: " . $e->getMessage() . "\n";
}

// ========================================================
// TEST 5: Filtrage par cycle dans getSupervisorsByCycle
// ========================================================
echo "\n--- TEST 5: Method getSupervisorsByCycle ---\n";
$survCeg = SurveillantAssignmentService::getSupervisorsByCycle(88101, $lyceeId);
$survLycee = SurveillantAssignmentService::getSupervisorsByCycle(88102, $lyceeId);

$cegIds = array_map('intval', array_column($survCeg, 'id_user'));
$lyceeIds = array_map('intval', array_column($survLycee, 'id_user'));

if (in_array(8811, $cegIds, true) && !in_array(8811, $lyceeIds, true)) {
    echo "[PASS] 5a. getSupervisorsByCycle correctly returns supervisor 8811 for CEG only.\n";
} else {
    echo "[FAIL] 5a. getSupervisorsByCycle failed for CEG filtering.\n";
}

if (in_array(8812, $lyceeIds, true) && !in_array(8812, $cegIds, true)) {
    echo "[PASS] 5b. getSupervisorsByCycle correctly returns supervisor 8812 for Lycée only.\n";
} else {
    echo "[FAIL] 5b. getSupervisorsByCycle failed for Lycée filtering.\n";
}

if (in_array(8813, $cegIds, true) && in_array(8813, $lyceeIds, true)) {
    echo "[PASS] 5c. getSupervisorsByCycle returns multi-cycle supervisor 8813 in both CEG and Lycée.\n";
} else {
    echo "[FAIL] 5c. getSupervisorsByCycle failed for multi-cycle supervisor.\n";
}

// ========================================================
// TEST 6: Répartition Automatique (proposeDistribution) avec filtrage par cycle
// ========================================================
echo "\n--- TEST 6: Répartition Automatique par Cycle ---\n";
$propCEG = SurveillantAssignmentService::proposeDistribution(
    $lyceeId,
    881,
    [8810101, 8810102, 8810201, 8810202],
    [8811, 8812, 8813],
    'equitable',
    null,
    88101 // CEG cycle
);

$classesProp = array_column($propCEG['distribution'], 'classes');
$survProp = array_column($propCEG['distribution'], 'surveillant');
$assignedSurvIds = array_map('intval', array_column($survProp, 'id_user'));

if (!in_array(8812, $assignedSurvIds, true)) {
    echo "[PASS] 6a. proposeDistribution under CEG cycle strictly excludes Lycée supervisor 8812.\n";
} else {
    echo "[FAIL] 6a. proposeDistribution included out-of-scope supervisor 8812.\n";
}

// ========================================================
// TEST 7: Rejet à l'application (applyDistribution) si altération côté client
// ========================================================
echo "\n--- TEST 7: Rejet de tentative d'altération du payload applyDistribution ---\n";
$tamperedPayload = [
    'state_hash' => $propCEG['state_hash'],
    'lycee_id' => $lyceeId,
    'annee_academique_id' => 881,
    'apply_mode' => 'replace',
    'selected_classe_ids' => [8810101],
    'distribution' => [
        [
            'surveillant_id' => 8811, // CEG supervisor
            'classe_ids' => [8810201] // Lycée class (Tampered!)
        ]
    ]
];

try {
    SurveillantAssignmentService::applyDistribution($tamperedPayload, 8811);
    echo "[FAIL] 7a. Expected applyDistribution to reject tampered out-of-scope assignment.\n";
} catch (InvalidArgumentException $e) {
    echo "[PASS] 7a. Tampered payload properly caught and rejected: " . $e->getMessage() . "\n";
}

// Clean up
$db->exec("DELETE FROM surveillant_affectations WHERE lycee_id = $lyceeId");
$db->exec("DELETE FROM surveillant_groupe_classes WHERE groupe_id IN (SELECT id FROM surveillant_groupes WHERE lycee_id = $lyceeId)");
$db->exec("DELETE FROM surveillant_groupes WHERE lycee_id = $lyceeId");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (8811, 8812, 8813, 8814)");
$db->exec("DELETE FROM classes WHERE lycee_id = $lyceeId");
$db->exec("DELETE FROM cycles WHERE lycee_id = $lyceeId");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (8811, 8812, 8813, 8814)");
$db->exec("DELETE FROM param_lycee WHERE id = $lyceeId");
$db->exec("DELETE FROM annees_academiques WHERE id = 881");

echo "\n========================================================\n";
echo "SUCCESS: ALL STRICT CYCLE SCOPE TESTS PASSED!\n";
echo "========================================================\n";
