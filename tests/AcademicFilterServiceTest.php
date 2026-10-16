<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/AcademicFilterService.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Lycee.php';

echo "========================================================\n";
echo "RUNNING PHASE 4 TEST SUITE: AcademicFilterService (20 SCENARIOS)\n";
echo "========================================================\n";

$db = Database::getInstance();

// Seed test database fixtures
$db->exec("DELETE FROM affectations_pedagogiques");
$db->exec("DELETE FROM surveillant_classes");
$db->exec("DELETE FROM surveillant_niveaux");
$db->exec("DELETE FROM surveillant_general");
$db->exec("DELETE FROM personnel_assignments");
$db->exec("DELETE FROM personnel_cycles_assignments");
$db->exec("DELETE FROM classes WHERE id_classe >= 8000");
$db->exec("DELETE FROM cycles WHERE id_cycle >= 8000");
$db->exec("DELETE FROM param_lycee WHERE id >= 8000");
$db->exec("DELETE FROM utilisateurs WHERE id_user >= 8000");

// Ensure permissions 34 (lycee:view_all_lycees) and 100 (cycle:view_all_cycles) exist
try { $db->exec("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES ('lycee', 'view_all_lycees', 'View all lycees')"); } catch (Exception $e) {}
try { $db->exec("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES ('cycle', 'view_all_cycles', 'View all cycles')"); } catch (Exception $e) {}

$pLycee = (int)$db->query("SELECT id_permission FROM permissions WHERE resource = 'lycee' AND action = 'view_all_lycees'")->fetchColumn();
$pCycle = (int)$db->query("SELECT id_permission FROM permissions WHERE resource = 'cycle' AND action = 'view_all_cycles'")->fetchColumn();

if ($pLycee) {
    try { $db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, {$pLycee})"); } catch (Exception $e) {}
}
if ($pCycle) {
    try { $db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, {$pCycle})"); } catch (Exception $e) {}
    try { $db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, {$pCycle})"); } catch (Exception $e) {}
}

// 1. Create 2 Lycees (Lycee A = 8001, Lycee B = 8002)
$db->exec("INSERT INTO param_lycee (id, nom_lycee, sigle) VALUES (8001, 'Lycée Test A', 'LTA')");
$db->exec("INSERT INTO param_lycee (id, nom_lycee, sigle) VALUES (8002, 'Lycée Test B', 'LTB')");

// 2. Create Cycles for Lycee A (CEG A = 8010, Lycee A = 8020) and Lycee B (CEG B = 8030)
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8010, 8001, 'CEG Test A')");
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8020, 8001, 'Lycée Test A')");
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8030, 8002, 'CEG Test B')");

// 3. Create Classes
// CEG A: 6ème 1 (8101), 6ème 2 (8102), 5ème 1 (8103) - No series
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8101, 8001, 8010, '6ème', NULL, 1)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8102, 8001, 8010, '6ème', NULL, 2)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8103, 8001, 8010, '5ème', NULL, 1)");

// Lycée A: 2nde C 1 (8201), 2nde A4 1 (8202), Tle D 1 (8203) - With series
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8201, 8001, 8020, '2nde', 'C', 1)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8202, 8001, 8020, '2nde', 'A4', 1)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8203, 8001, 8020, 'Tle', 'D', 1)");

// CEG B: 6ème 1 (8301)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8301, 8002, 8030, '6ème', NULL, 1)");

// 4. Create Subjects for Lycee A (Maths = 8501, Français = 8502)
$db->exec("DELETE FROM matieres WHERE id_matiere >= 8500");
$db->exec("INSERT INTO matieres (id_matiere, nom_matiere, lycee_id) VALUES (8501, 'Mathématiques', 8001)");
$db->exec("INSERT INTO matieres (id_matiere, nom_matiere, lycee_id) VALUES (8502, 'Français', 8001)");

// 5. Create Active Academic Year (id = 8900)
$db->exec("UPDATE annees_academiques SET est_active = 0");
$db->exec("DELETE FROM annees_academiques WHERE id = 8900");
$db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (8900, '2025-2026', '2025-09-01', '2026-06-30', 1)");

// 6. Create Users
// User 8001: Admin Local Mono-Lycée (Lycee 8001, Role 3 = Admin Local)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8001, 'Mono', 'Admin', 'mono@test.com', 'hash', 3, 8001, 1, 1)");
// User 8002: Super Admin Multi-Lycée (Role 1 = Super Admin)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8002, 'Multi', 'Admin', 'multi@test.com', 'hash', 1, NULL, 1, 1)");
// User 8003: User restricted to CEG A (8010) (Role 4 = Censeur, without view_all_cycles)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8003, 'CegOnly', 'User', 'ceg@test.com', 'hash', 4, 8001, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (8003, 8010, '2020-01-01', 1)");

// User 8004: Enseignant (Role 6 = Enseignant)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8004, 'Teacher', 'Ens', 'prof@test.com', 'hash', 6, 8001, 1, 1)");
// Teacher assignments: Class 8101 + Math (8501), Class 8201 + Math (8501)
$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, date_debut, statut) VALUES (8004, 8101, 8501, 8900, '2020-01-01', 'actif')");
$db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, date_debut, statut) VALUES (8004, 8201, 8501, 8900, '2020-01-01', 'actif')");

// User 8005: Surveillant par Classe (8102) (Role 5 = Surveillant)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8005, 'Surv', 'Classe', 'survcls@test.com', 'hash', 5, 8001, 1, 1)");
$db->exec("INSERT INTO surveillant_classes (surveillant_id, classe_id, lycee_id) VALUES (8005, 8102, 8001)");

// User 8006: Surveillant par Niveau ("2nde") (Role 5 = Surveillant)
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8006, 'Surv', 'Niveau', 'survniv@test.com', 'hash', 5, 8001, 1, 1)");
$db->exec("INSERT INTO surveillant_niveaux (surveillant_id, niveau, lycee_id) VALUES (8006, '2nde', 8001)");

// User 8007: Inactive / No Access User
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8007, 'Inactive', 'User', 'inact@test.com', 'hash', 4, 8001, 0, 1)");

echo "[OK] Fixtures successfully loaded.\n\n";

function setTestUser(array $userData) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user'] = $userData;
    $_SESSION['user']['id'] = $userData['id_user'] ?? $userData['id'];
    unset($_SESSION['user']['authorized_cycles']);
    unset($_SESSION['user']['auth_version']);
}

function assertResult($condition, $description) {
    if ($condition) {
        echo "  [PASS] $description\n";
    } else {
        echo "  [FAIL] $description\n";
        exit(1);
    }
}

// ========================================================
// SCENARIOS TESTING
// ========================================================

// --- SCENARIO 1: Mono-lycée ---
echo "TEST 1 — Mono-Lycée User Scope\n";
setTestUser([
    'id' => 8001,
    'id_user' => 8001,
    'role_id' => 3,
    'role_name' => 'admin_local',
    'lycee_id' => 8001,
    'permissions' => ['lycee' => ['view'], 'cycle' => ['view_all_cycles']]
]);

$scope1 = AcademicFilterService::getEffectiveScope(8001);
assertResult(count($scope1['lycee_ids']) === 1 && $scope1['lycee_ids'][0] === 8001, "Mono-lycée scope contains strictly [8001].");


// --- SCENARIO 2: Multi-lycée ---
echo "TEST 2 — Multi-Lycée Super Admin Scope\n";
setTestUser([
    'id' => 8002,
    'id_user' => 8002,
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'lycee_id' => null,
    'permissions' => ['lycee' => ['view_all_lycees'], 'cycle' => ['view_all_cycles']]
]);

$scope2 = AcademicFilterService::getEffectiveScope(8002);
assertResult(count($scope2['lycee_ids']) >= 2 && in_array(8001, $scope2['lycee_ids']) && in_array(8002, $scope2['lycee_ids']), "Multi-lycée scope includes [8001, 8002].");


// --- SCENARIO 3: Cycle Autorisé ---
echo "TEST 3 — Cycle Autorisé Filter Intersect\n";
setTestUser([
    'id' => 8003,
    'id_user' => 8003,
    'role_id' => 4,
    'role_name' => 'censeur',
    'lycee_id' => 8001,
    'permissions' => ['lycee' => ['view']] // Note: no view_all_cycles permission so cycle assignment applies
]);

$filter3 = AcademicFilterService::sanitizeAndIntersectFilters(['cycle_id' => 8010]);
assertResult($filter3['valid'] === true && $filter3['cycle_id'] === 8010, "In-scope CEG cycle (8010) is validated successfully.");


// --- SCENARIO 4: Cycle Interdit ---
echo "TEST 4 — Cycle Interdit Tampering Rejection\n";
$filter4 = AcademicFilterService::sanitizeAndIntersectFilters(['cycle_id' => 8020]); // Lycée A cycle
assertResult($filter4['valid'] === false && strpos($filter4['error'], 'Cycle hors périmètre') !== false, "Forbidden cycle (8020) is REJECTED for CEG-only user.");


// --- SCENARIO 5: Niveau Autorisé ---
echo "TEST 5 — Niveau Autorisé Cascade Options\n";
$opts5 = AcademicFilterService::getCascadeOptions(['cycle_id' => 8010]);
assertResult(in_array('6ème', $opts5['niveaux']) && in_array('5ème', $opts5['niveaux']), "Authorized levels ('6ème', '5ème') returned for CEG cycle.");


// --- SCENARIO 6: Niveau Interdit ---
echo "TEST 6 — Niveau Interdit Rejection\n";
$filter6 = AcademicFilterService::sanitizeAndIntersectFilters(['niveau' => 'Tle']);
assertResult($filter6['valid'] === false || ($filter6['valid'] && !empty($filter6['scope']['niveau_names']) && !in_array('Tle', $filter6['scope']['niveau_names'])), "Level 'Tle' outside CEG cycle is rejected or excluded.");


// --- SCENARIO 7: Série Applicable (Lycée) ---
echo "TEST 7 — Dynamic Serie Detection (Lycée Cycle)\n";
setTestUser([
    'id' => 8001,
    'id_user' => 8001,
    'role_id' => 3,
    'role_name' => 'admin_local',
    'lycee_id' => 8001,
    'permissions' => ['lycee' => ['view'], 'cycle' => ['view_all_cycles']]
]);

$opts7 = AcademicFilterService::getCascadeOptions(['lycee_id' => 8001, 'cycle_id' => 8020, 'niveau' => '2nde']);
assertResult($opts7['has_series'] === true && in_array('A4', $opts7['series']) && in_array('C', $opts7['series']), "Series ('A4', 'C') dynamically detected for 2nde in Lycée A cycle.");


// --- SCENARIO 8: Série Non Applicable (CEG) ---
echo "TEST 8 — Dynamic Serie Hiding (CEG Cycle)\n";
$opts8 = AcademicFilterService::getCascadeOptions(['lycee_id' => 8001, 'cycle_id' => 8010, 'niveau' => '6ème']);
assertResult($opts8['has_series'] === false && empty($opts8['series']), "Series filter correctly HIDDEN (has_series = false) for CEG 6ème.");


// --- SCENARIO 9: Numéro Existant ---
echo "TEST 9 — Numéro Existant Resolution\n";
$filter9 = AcademicFilterService::sanitizeAndIntersectFilters(['lycee_id' => 8001, 'cycle_id' => 8010, 'niveau' => '6ème', 'numero' => 2]);
assertResult($filter9['valid'] === true && $filter9['classe_id'] === 8102, "Existing (6ème, numero 2) correctly resolves to class ID 8102.");


// --- SCENARIO 10: Numéro Inexistant ---
echo "TEST 10 — Numéro Inexistant Rejection\n";
$filter10 = AcademicFilterService::sanitizeAndIntersectFilters(['lycee_id' => 8001, 'cycle_id' => 8010, 'niveau' => '6ème', 'numero' => 99]);
assertResult($filter10['valid'] === false && strpos($filter10['error'], 'Aucune classe ne correspond') !== false, "Non-existent numero 99 is REJECTED.");


// --- SCENARIO 11: Classe Autorisée ---
echo "TEST 11 — Direct Authorized Class ID Access\n";
$filter11 = AcademicFilterService::sanitizeAndIntersectFilters(['classe_id' => 8101]);
assertResult($filter11['valid'] === true && $filter11['classe_id'] === 8101 && $filter11['cycle_id'] === 8010, "Authorized class ID 8101 validated and hierarchy auto-populated.");


// --- SCENARIO 12: Classe Interdite ---
echo "TEST 12 — Direct Forbidden Class ID Access (Cross-School / Cross-Cycle)\n";
setTestUser([
    'id' => 8003,
    'id_user' => 8003,
    'role_id' => 4,
    'role_name' => 'censeur',
    'lycee_id' => 8001,
    'permissions' => ['lycee' => ['view']]
]);

$filter12 = AcademicFilterService::sanitizeAndIntersectFilters(['classe_id' => 8301]); // Class in Lycee B
assertResult($filter12['valid'] === false, "Foreign school class 8301 REJECTED for user restricted to Lycee A / CEG.");


// --- SCENARIO 13: Combinaison Parent/Enfant Incohérente ---
echo "TEST 13 — Parent/Child Incoherence Detection\n";
$filter13 = AcademicFilterService::sanitizeAndIntersectFilters([
    'lycee_id' => 8001,
    'cycle_id' => 8010, // CEG
    'classe_id' => 8201 // Class in Lycée cycle
]);
assertResult($filter13['valid'] === false && stripos($filter13['error'], 'Incoh') !== false, "Mismatched CEG cycle and Lycée class REJECTED.");


// --- SCENARIO 14: Enseignant avec Plusieurs Affectations ---
echo "TEST 14 — Teacher Pedagogical Scope\n";
setTestUser([
    'id' => 8004,
    'id_user' => 8004,
    'role_id' => 6,
    'role_name' => 'enseignant',
    'lycee_id' => 8001,
    'permissions' => ['note' => ['create_own']]
]);

$scope14 = AcademicFilterService::getEffectiveScope(8004);
assertResult($scope14['is_teacher'] === true && count($scope14['classe_ids']) === 2 && in_array(8101, $scope14['classe_ids']) && in_array(8201, $scope14['classe_ids']), "Teacher scope strictly restricted to assigned classes [8101, 8201].");


// --- SCENARIO 15: Surveillant par Classe ---
echo "TEST 15 — Supervisor Class-Level Scope\n";
setTestUser([
    'id' => 8005,
    'id_user' => 8005,
    'role_id' => 5,
    'role_name' => 'surveillant',
    'lycee_id' => 8001,
    'permissions' => ['presence' => ['view']]
]);

$scope15 = AcademicFilterService::getEffectiveScope(8005);
assertResult($scope15['is_supervisor'] === true && count($scope15['classe_ids']) === 1 && $scope15['classe_ids'][0] === 8102, "Supervisor assigned to class 8102 restricted strictly to [8102].");


// --- SCENARIO 16: Surveillant par Niveau ---
echo "TEST 16 — Supervisor Niveau-Level Scope\n";
setTestUser([
    'id' => 8006,
    'id_user' => 8006,
    'role_id' => 5,
    'role_name' => 'surveillant',
    'lycee_id' => 8001,
    'permissions' => ['presence' => ['view']]
]);

$scope16 = AcademicFilterService::getEffectiveScope(8006);
// 2nde classes in Lycee A are 8201 and 8202
assertResult($scope16['is_supervisor'] === true && count($scope16['classe_ids']) === 2 && in_array(8201, $scope16['classe_ids']) && in_array(8202, $scope16['classe_ids']), "Supervisor assigned to niveau '2nde' resolved to classes [8201, 8202].");


// --- SCENARIO 17: Accès Global ---
echo "TEST 17 — Global Academic Access\n";
setTestUser([
    'id' => 8002,
    'id_user' => 8002,
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'lycee_id' => null,
    'permissions' => ['lycee' => ['view_all_lycees'], 'cycle' => ['view_all_cycles']]
]);

$scope17 = AcademicFilterService::getEffectiveScope(8002);
assertResult($scope17['access_type'] === AcademicFilterService::SCOPE_GLOBAL && $scope17['classe_ids'] === null, "Global admin scope returns access_type = 'global' with unrestricted classe_ids (null).");


// --- SCENARIO 18: Aucun Accès (Utilisateur Inactif) ---
echo "TEST 18 — No Access (Inactive User)\n";
setTestUser([
    'id' => 8007,
    'id_user' => 8007,
    'role_id' => 4,
    'role_name' => 'censeur',
    'lycee_id' => 8001,
    'permissions' => ['lycee' => ['view']]
]);

$scope18 = AcademicFilterService::getEffectiveScope(8007);
assertResult($scope18['access_type'] === AcademicFilterService::SCOPE_NONE && empty($scope18['classe_ids']), "Inactive user scope returns access_type = 'none' and empty arrays.");


// --- SCENARIO 19: IDOR Tampering Attempt ---
echo "TEST 19 — IDOR Assertion Interception\n";
setTestUser([
    'id' => 8004,
    'id_user' => 8004,
    'role_id' => 6,
    'role_name' => 'enseignant',
    'lycee_id' => 8001,
    'permissions' => ['note' => ['create_own']]
]);

$caught403 = false;
try {
    // Attempting to assert access to unassigned class 8103
    AcademicFilterService::assertAccess(['classe_id' => 8103]);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Accès Refusé') !== false) {
        $caught403 = true;
    }
}
assertResult($caught403 === true, "IDOR attempt on unassigned class 8103 successfully BLOCKED with 403 Exception.");


// --- SCENARIO 20: SQL Query Injection and Filtering ---
echo "TEST 20 — SQL Query Parameterization & Scope Injection\n";
setTestUser([
    'id' => 8001,
    'id_user' => 8001,
    'role_id' => 3,
    'role_name' => 'admin_local',
    'lycee_id' => 8001,
    'permissions' => ['lycee' => ['view'], 'cycle' => ['view_all_cycles']]
]);
$baseSql = "SELECT e.* FROM eleves e JOIN classes c ON e.classe_id = c.id_classe";
$queryParams = ['status' => 'active'];

$resQuery = AcademicFilterService::applyToQuery($baseSql, $queryParams, ['classe_id' => 8101], 'c');
assertResult(
    strpos($resQuery['sql'], 'c.id_classe = :afs_classe_id') !== false &&
    strpos($resQuery['sql'], ':afs_lycee_id1') !== false &&
    strpos($resQuery['sql'], ':afs_lycee_id2') !== false &&
    isset($resQuery['params']['afs_classe_id']) &&
    $resQuery['params']['afs_classe_id'] === 8101 &&
    isset($resQuery['params']['afs_lycee_id1']) &&
    $resQuery['params']['afs_lycee_id1'] === 8001 &&
    isset($resQuery['params']['afs_lycee_id2']) &&
    $resQuery['params']['afs_lycee_id2'] === 8001,
    "SQL query correctly parameterized with distinct placeholders :afs_lycee_id1 and :afs_lycee_id2 and :afs_classe_id = 8101."
);

echo "\n========================================================\n";
echo "SUCCESS: ALL 20 SCENARIOS PASSED FOR AcademicFilterService!\n";
echo "========================================================\n";
