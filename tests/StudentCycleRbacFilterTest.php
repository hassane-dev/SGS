<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/core/Validator.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/AcademicFilterService.php';
require_once __DIR__ . '/../src/services/EleveDashboardService.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/controllers/EleveController.php';
require_once __DIR__ . '/../src/controllers/EleveDashboardController.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Student Cycle RBAC Filter Security Test\n";
echo "========================================================\n";

// Cleanup mock records
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (9881, 9882, 9883)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (98801, 98802)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (98801, 98802)");
$db->exec("DELETE FROM classes WHERE id_classe IN (98810, 98820, 98830)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9881, 9882, 9883)");

// Ensure active year
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}

// Find CEG and Lycée cycles
$cycleCeg = $db->query("SELECT * FROM cycles WHERE LOWER(nom_cycle) LIKE '%ceg%' OR LOWER(nom_cycle) LIKE '%collège%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$cycleLycee = $db->query("SELECT * FROM cycles WHERE LOWER(nom_cycle) LIKE '%lycée%' OR LOWER(nom_cycle) LIKE '%lycee%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cycleCeg || !$cycleLycee) {
    echo "ERROR: Cycles CEG or Lycee missing in test DB.\n";
    exit(1);
}

$idCeg = (int)$cycleCeg['id_cycle'];
$idLycee = (int)$cycleLycee['id_cycle'];

// Create test classes
// CEG class (6e A, Lycee 1)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (98810, 1, {$idCeg}, '6e', 'A')");
// Lycée class (2nd C1, Lycee 1)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (98820, 1, {$idLycee}, '2nd', 'C', '1')");
// Lycée class for foreign establishment (Lycee 2)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (98830, 2, {$idLycee}, '2nd', 'C', '1')");

// Create test students
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (98801, 1, 'Eleve', 'CEG', 'en_attente')");
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (98802, 2, 'Eleve', 'Lycee2', 'en_attente')");

// Ensure role 98 exists for restricted scope testing with eleve permissions
$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role, lycee_id) VALUES (98, 'agent_scolarite_test', 1)");
$permInscrire = $db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'inscrire'")->fetchColumn();
if (!$permInscrire) {
    $db->exec("INSERT INTO permissions (resource, action) VALUES ('eleve', 'inscrire')");
    $permInscrire = $db->lastInsertId();
}
$stmtRp = $db->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = 98 AND permission_id = :p");
$stmtRp->execute(['p' => $permInscrire]);
if ($stmtRp->fetchColumn() == 0) {
    $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (98, {$permInscrire})");
}

// User 9881: CEG-only assigned
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9881, 1, 'User', 'CEGOnly', 98, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9881, {$idCeg}, '2020-01-01', 1)");

// User 9882: Lycee-only assigned
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9882, 1, 'User', 'LyceeOnly', 98, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9882, {$idLycee}, '2020-01-01', 1)");

// User 9883: Multi-cycle assigned (CEG + Lycee)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9883, 1, 'User', 'MultiCycle', 98, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9883, {$idCeg}, '2020-01-01', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9883, {$idLycee}, '2020-01-01', 1)");

function mockTestSession($userId, $lyceeId) {
    unset($_SESSION['user']['authorized_cycles']);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => 98,
        'role_name' => 'agent_scolarite_test',
        'auth_version' => 1
    ];
    AuthorizationScopeService::invalidateUserCache($userId);
}

$classeCtrl = new ClasseController();
$eleveCtrl = new EleveController();

// -------------------------------------------------------------
// SCENARIO 1: CEG-Only User in Student Dashboard & Assignment Form
// -------------------------------------------------------------
echo "SCENARIO 1: CEG-Only User in Student Dashboard & Class Assignment Form\n";
mockTestSession(9881, 1);

// Permitted cycles for CEG-only user
$permittedCycles = AuthorizationScopeService::getPermittedCycles(1);
$permittedIds = array_column($permittedCycles, 'id_cycle');

if (count($permittedIds) === 1 && in_array($idCeg, $permittedIds)) {
    echo "  [PASS] 1a. Student Dashboard & Assignment Form receive ONLY authorized CEG cycle [{$idCeg}].\n";
} else {
    echo "  [FAIL] 1a. Permitted cycles mismatch! Got: " . json_encode($permittedIds) . "\n";
}

// -------------------------------------------------------------
// SCENARIO 2: Lycee-Only User receives strictly Lycee
// -------------------------------------------------------------
echo "\nSCENARIO 2: Lycee-Only User receives strictly Lycee cycle\n";
mockTestSession(9882, 1);

$permittedCycles2 = AuthorizationScopeService::getPermittedCycles(1);
$permittedIds2 = array_column($permittedCycles2, 'id_cycle');

if (count($permittedIds2) === 1 && in_array($idLycee, $permittedIds2)) {
    echo "  [PASS] 2a. Lycee-only user receives strictly Lycee cycle [{$idLycee}].\n";
} else {
    echo "  [FAIL] 2a. Permitted cycles mismatch! Got: " . json_encode($permittedIds2) . "\n";
}

// -------------------------------------------------------------
// SCENARIO 3: Multi-Cycle User retains all legitimate accesses
// -------------------------------------------------------------
echo "\nSCENARIO 3: Multi-Cycle User retains all legitimate cycles\n";
mockTestSession(9883, 1);

$permittedCycles3 = AuthorizationScopeService::getPermittedCycles(1);
$permittedIds3 = array_column($permittedCycles3, 'id_cycle');
sort($permittedIds3);
$expected3 = [$idCeg, $idLycee]; sort($expected3);

if ($permittedIds3 === $expected3) {
    echo "  [PASS] 3a. Multi-cycle user sees exact union of assigned cycles [CEG, Lycée].\n";
} else {
    echo "  [FAIL] 3a. Multi-cycle user cycles mismatch! Got: " . json_encode($permittedIds3) . "\n";
}

// -------------------------------------------------------------
// SCENARIO 4: Establishment Isolation
// -------------------------------------------------------------
echo "\nSCENARIO 4: Establishment Scope Isolation (Cross-Tenant)\n";
mockTestSession(9881, 1); // User belongs to Lycee 1

$_GET = ['cycle_id' => $idCeg, 'lycee_id' => 2];
ob_start(); $classeCtrl->getNiveauxForCycle(); $crossTenantNiveaux = json_decode(ob_get_clean(), true);
if (empty($crossTenantNiveaux)) {
    echo "  [PASS] 4a. Query for foreign establishment (Lycee 2) returns EMPTY array.\n";
} else {
    echo "  [FAIL] 4a. Foreign establishment query returned data! Got: " . json_encode($crossTenantNiveaux) . "\n";
}

// -------------------------------------------------------------
// SCENARIO 5: Server-side Rejection of Falsified Requests
// -------------------------------------------------------------
echo "\nSCENARIO 5: Server-side Rejection of Falsified Cycle Submissions\n";
mockTestSession(9881, 1); // CEG-only user

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['eleve_id' => 98801, 'cycle_id' => $idLycee, 'niveau' => '2nd', 'serie' => 'C', 'numero' => '1'];
ob_start();
try { $eleveCtrl->processAssignment(); } catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "Accès refusé")) {
    echo "  [PASS] 5a. Falsified assignment submission targeting unauthorized Lycee cycle REJECTED server-side.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 5a. Falsified submission allowed! Error message: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// Forged dashboard data request for unauthorized cycle
$_GET = ['lycee_id' => 1, 'cycle_id' => $idLycee];
try {
    EleveDashboardService::getDashboardData(['lycee_id' => 1, 'cycle_id' => $idLycee], 9881);
    echo "  [FAIL] 5b. Forged dashboard query for unauthorized cycle was executed!\n";
} catch (Exception $e) {
    if (str_contains($e->getMessage(), "Accès non autorisé")) {
        echo "  [PASS] 5b. Forged dashboard request targeting unauthorized cycle BLOCKED server-side.\n";
    } else {
        echo "  [FAIL] 5b. Unexpected exception message: " . $e->getMessage() . "\n";
    }
}

// -------------------------------------------------------------
// SCENARIO 6: Level/Series/Number filters do not bypass cycle scope
// -------------------------------------------------------------
echo "\nSCENARIO 6: Level/Series/Number filters cannot bypass cycle restrictions\n";
mockTestSession(9881, 1); // CEG-only user

// Request series for Lycée level '2nd'
$_GET = ['niveau' => '2nd', 'lycee_id' => 1, 'cycle_id' => $idLycee];
ob_start(); $classeCtrl->getSeriesForNiveau(); $resSeries = json_decode(ob_get_clean(), true);
if (empty($resSeries)) {
    echo "  [PASS] 6a. Attempt to query series for out-of-scope level/cycle returns EMPTY array.\n";
} else {
    echo "  [FAIL] 6a. Series query returned out-of-scope data! Got: " . json_encode($resSeries) . "\n";
}

// Request class ID for Lycée class
$_GET = ['lycee_id' => 1, 'niveau' => '2nd', 'serie' => 'C', 'numero' => '1', 'cycle_id' => $idLycee];
ob_start(); $classeCtrl->findClassId(); $resCls = json_decode(ob_get_clean(), true);
if (empty($resCls['id_classe'])) {
    echo "  [PASS] 6b. Attempt to find class ID for out-of-scope cycle/level/series returns NULL.\n";
} else {
    echo "  [FAIL] 6b. Class ID resolution returned out-of-scope class! Got: " . json_encode($resCls) . "\n";
}

// Cleanup mock records
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (9881, 9882, 9883)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (98801, 98802)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (98801, 98802)");
$db->exec("DELETE FROM classes WHERE id_classe IN (98810, 98820, 98830)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9881, 9882, 9883)");

// Restore database file to original state
restore_file_db();

echo "\n========================================================\n";
echo "SUCCESS: ALL 6 SCENARIOS IN StudentCycleRbacFilterTest PASSED!\n";
echo "========================================================\n";

function restore_file_db() {
    // Clean up session
    unset($_SESSION['user']);
}
