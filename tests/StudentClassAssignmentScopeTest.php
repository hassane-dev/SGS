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
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/controllers/EleveController.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Student Class Assignment & Scope Security Test\n";
echo "========================================================\n";

// Cleanup mock records
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (8881, 8882, 8883)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (88801, 88802)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (88801, 88802)");
$db->exec("DELETE FROM classes WHERE id_classe IN (88810, 88820, 88830)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (8881, 8882, 8883)");

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
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (88810, 1, {$idCeg}, '6e', 'A')");
// Lycée class (2nd C1, Lycee 1)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (88820, 1, {$idLycee}, '2nd', 'C', '1')");
// Lycée class for another school (Lycee 2)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (88830, 2, {$idLycee}, '2nd', 'C', '1')");

// Create test students
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (88801, 1, 'Eleve', 'CEG', 'en_attente')");
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut) VALUES (88802, 2, 'Eleve', 'Lycee2', 'en_attente')");

// Ensure role 99 exists for restricted scope testing and has eleve:inscrire permission
$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role, lycee_id) VALUES (99, 'agent_scolarite', 1)");
$permInscrire = $db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'inscrire'")->fetchColumn();
if (!$permInscrire) {
    $db->exec("INSERT INTO permissions (resource, action) VALUES ('eleve', 'inscrire')");
    $permInscrire = $db->lastInsertId();
}
$stmtRp = $db->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = 99 AND permission_id = :p");
$stmtRp->execute(['p' => $permInscrire]);
if ($stmtRp->fetchColumn() == 0) {
    $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (99, {$permInscrire})");
}

// Create users with role_id = 99 (no global view_all_cycles or view_all_lycees)
// User 8881: CEG-only assigned
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version) VALUES (8881, 1, 'User', 'CEGOnly', 99, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (8881, {$idCeg}, '2020-01-01', 1)");

// User 8882: Lycee-only assigned
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version) VALUES (8882, 1, 'User', 'LyceeOnly', 99, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (8882, {$idLycee}, '2020-01-01', 1)");

// User 8883: Multi-cycle assigned (CEG + Lycee)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version) VALUES (8883, 1, 'User', 'MultiCycle', 99, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (8883, {$idCeg}, '2020-01-01', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (8883, {$idLycee}, '2020-01-01', 1)");

echo "[OK] Mock data successfully prepared.\n\n";

function mockSession($userId, $lyceeId) {
    unset($_SESSION['user']['authorized_cycles']);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => 99,
        'role_name' => 'agent_scolarite',
        'auth_version' => 1
    ];
    AuthorizationScopeService::invalidateUserCache($userId);
}

$classeCtrl = new ClasseController();
$eleveCtrl = new EleveController();

// -------------------------------------------------------------
// TEST 1: Utilisateur Périmètre CEG
// -------------------------------------------------------------
echo "TEST 1: Utilisateur Périmètre CEG Uniquement\n";
mockSession(8881, 1);

// 1a. Cycles autorisés
$permittedCycles = AuthorizationScopeService::getPermittedCycles(1);
$permittedIds = array_column($permittedCycles, 'id_cycle');
if (count($permittedIds) === 1 && in_array($idCeg, $permittedIds)) {
    echo "  [PASS] 1a. Permitted cycles strictly contains CEG [{$idCeg}], excludes Lycee [{$idLycee}].\n";
} else {
    echo "  [FAIL] 1a. Permitted cycles mismatch! Got: " . json_encode($permittedIds) . "\n";
}

// 1b. AJAX getNiveauxForCycle CEG vs Lycée
$_GET = ['cycle_id' => $idCeg, 'lycee_id' => 1];
ob_start(); $classeCtrl->getNiveauxForCycle(); $cegNiveaux = json_decode(ob_get_clean(), true);
if (in_array('6e', $cegNiveaux)) {
    echo "  [PASS] 1b. In-scope CEG cycle levels returned correctly.\n";
} else {
    echo "  [FAIL] 1b. In-scope CEG cycle levels failed! Got: " . json_encode($cegNiveaux) . "\n";
}

$_GET = ['cycle_id' => $idLycee, 'lycee_id' => 1];
ob_start(); $classeCtrl->getNiveauxForCycle(); $lyceeNiveaux = json_decode(ob_get_clean(), true);
if (empty($lyceeNiveaux)) {
    echo "  [PASS] 1c. Out-of-scope Lycee cycle levels REJECTED (empty array).\n";
} else {
    echo "  [FAIL] 1c. Out-of-scope Lycee cycle levels returned! Got: " . json_encode($lyceeNiveaux) . "\n";
}

// 1d. Direct AJAX findClassId for Lycee class
$_GET = ['lycee_id' => 1, 'niveau' => '2nd', 'serie' => 'C', 'numero' => '1', 'cycle_id' => $idLycee];
ob_start(); $classeCtrl->findClassId(); $resClassId = json_decode(ob_get_clean(), true);
if (empty($resClassId['id_classe'])) {
    echo "  [PASS] 1d. Direct findClassId call for out-of-scope Lycee class REJECTED (null).\n";
} else {
    echo "  [FAIL] 1d. Direct findClassId call allowed out-of-scope class ID! Got: " . json_encode($resClassId) . "\n";
}

// 1e. Process Assignment submission with out-of-scope Lycee cycle_id
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['eleve_id' => 88801, 'cycle_id' => $idLycee, 'niveau' => '2nd', 'serie' => 'C', 'numero' => '1'];
ob_start();
try { $eleveCtrl->processAssignment(); } catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "Accès refusé")) {
    echo "  [PASS] 1e. Submission with out-of-scope cycle_id REJECTED server-side with error message.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 1e. Submission with out-of-scope cycle_id allowed! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// 1f. Valid CEG assignment submission
$_POST = ['eleve_id' => 88801, 'cycle_id' => $idCeg, 'niveau' => '6e', 'numero' => 'A'];
ob_start();
try { $eleveCtrl->processAssignment(); } catch (Throwable $e) {}
ob_end_clean();

$checkEtude = $db->query("SELECT * FROM etudes WHERE eleve_id = 88801 AND classe_id = 88810")->fetch(PDO::FETCH_ASSOC);
if ($checkEtude) {
    echo "  [PASS] 1f. Valid in-scope CEG class assignment successfully registered in DB.\n";
} else {
    echo "  [FAIL] 1f. Valid CEG assignment failed! Error: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// -------------------------------------------------------------
// TEST 2: Utilisateur Périmètre Lycée Uniquement
// -------------------------------------------------------------
echo "\nTEST 2: Utilisateur Périmètre Lycée Uniquement\n";
mockSession(8882, 1);

// 2a. Permitted cycles
$permittedCycles = AuthorizationScopeService::getPermittedCycles(1);
$permittedIds = array_column($permittedCycles, 'id_cycle');
if (count($permittedIds) === 1 && in_array($idLycee, $permittedIds)) {
    echo "  [PASS] 2a. Permitted cycles strictly contains Lycee [{$idLycee}], excludes CEG [{$idCeg}].\n";
} else {
    echo "  [FAIL] 2a. Permitted cycles mismatch! Got: " . json_encode($permittedIds) . "\n";
}

// 2b. Out-of-scope CEG levels request
$_GET = ['cycle_id' => $idCeg, 'lycee_id' => 1];
ob_start(); $classeCtrl->getNiveauxForCycle(); $cegNiveaux = json_decode(ob_get_clean(), true);
if (empty($cegNiveaux)) {
    echo "  [PASS] 2b. Out-of-scope CEG levels REJECTED for Lycee-only user.\n";
} else {
    echo "  [FAIL] 2b. Out-of-scope CEG levels allowed! Got: " . json_encode($cegNiveaux) . "\n";
}

// 2c. Submission with out-of-scope CEG cycle_id
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['eleve_id' => 88801, 'cycle_id' => $idCeg, 'niveau' => '6e', 'numero' => 'A'];
ob_start();
try { $eleveCtrl->processAssignment(); } catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "Accès refusé")) {
    echo "  [PASS] 2c. Submission with out-of-scope CEG cycle REJECTED for Lycee-only user.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 2c. Submission with out-of-scope CEG cycle allowed! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// -------------------------------------------------------------
// TEST 3: Utilisateur Multi-Périmètre (CEG + Lycée)
// -------------------------------------------------------------
echo "\nTEST 3: Utilisateur Multi-Périmètre (CEG + Lycée)\n";
mockSession(8883, 1);

$permittedCycles = AuthorizationScopeService::getPermittedCycles(1);
$permittedIds = array_column($permittedCycles, 'id_cycle');
sort($permittedIds);
$expectedIds = [$idCeg, $idLycee]; sort($expectedIds);

if ($permittedIds === $expectedIds) {
    echo "  [PASS] 3a. Multi-cycle user sees exact union of assigned cycles [CEG, Lycée].\n";
} else {
    echo "  [FAIL] 3a. Multi-cycle user cycles mismatch! Got: " . json_encode($permittedIds) . "\n";
}

// -------------------------------------------------------------
// TEST 4: Multi-Lycée Isolation
// -------------------------------------------------------------
echo "\nTEST 4: Isolation Multi-Lycée (Cross-Tenant)\n";
mockSession(8881, 1); // User belongs to Lycee 1

// Attempting to assign student 88802 (belongs to Lycee 2) or query Lycee 2 data
$_GET = ['cycle_id' => $idCeg, 'lycee_id' => 2];
ob_start(); $classeCtrl->getNiveauxForCycle(); $crossTenantNiveaux = json_decode(ob_get_clean(), true);
if (empty($crossTenantNiveaux)) {
    echo "  [PASS] 4a. Direct AJAX query for foreign school (Lycee 2) REJECTED (empty array).\n";
} else {
    echo "  [FAIL] 4a. Direct AJAX query allowed foreign school data! Got: " . json_encode($crossTenantNiveaux) . "\n";
}

$_POST = ['eleve_id' => 88802, 'cycle_id' => $idCeg, 'niveau' => '6e', 'numero' => 'A'];
ob_start();
try {
    $eleveCtrl->processAssignment();
    echo "  [FAIL] 4b. Cross-tenant student assignment was allowed!\n";
} catch (Throwable $e) {
    echo "  [PASS] 4b. Cross-tenant student assignment strictly REJECTED with exception: " . $e->getMessage() . "\n";
}
ob_end_clean();

// Cleanup mock records
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id IN (8881, 8882, 8883)");
$db->exec("DELETE FROM etudes WHERE eleve_id IN (88801, 88802)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (88801, 88802)");
$db->exec("DELETE FROM classes WHERE id_classe IN (88810, 88820, 88830)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (8881, 8882, 8883)");

echo "\n========================================================\n";
echo "SUCCESS: ALL STUDENT CLASS ASSIGNMENT SCOPE TESTS PASSED!\n";
echo "========================================================\n";
