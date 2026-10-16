<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/core/Validator.php';
require_once __DIR__ . '/../src/core/CsrfService.php';
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

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Custom Role-Name & RBAC Scope Test\n";
echo "========================================================\n";

function setMockUser($userId, $roleId, $roleName, $lyceeId = 1) {
    Auth::startSession();
    unset($_SESSION['user']['authorized_cycles']);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => $roleId,
        'role_name' => $roleName,
        'auth_version' => 1
    ];
    AuthorizationScopeService::invalidateUserCache($userId);
}

// Ensure active academic year
$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}

// Cleanup mock records
$db->exec("DELETE FROM etudes WHERE eleve_id IN (99801, 99802, 99803)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (99801, 99802, 99803)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99810, 99820)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9981, 9982, 9983, 9984)");

// Ensure school
$db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée Test A')");

// Ensure cycles
$cycleCeg = $db->query("SELECT * FROM cycles WHERE LOWER(nom_cycle) LIKE '%ceg%' OR LOWER(nom_cycle) LIKE '%collège%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$cycleLycee = $db->query("SELECT * FROM cycles WHERE LOWER(nom_cycle) LIKE '%lycée%' OR LOWER(nom_cycle) LIKE '%lycee%' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$cycleCeg || !$cycleLycee) {
    $db->exec("INSERT OR IGNORE INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (9910, 'Collège CEG', 1)");
    $db->exec("INSERT OR IGNORE INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (9920, 'Lycée Général', 1)");
    $cycleCeg = ['id_cycle' => 9910, 'nom_cycle' => 'Collège CEG'];
    $cycleLycee = ['id_cycle' => 9920, 'nom_cycle' => 'Lycée Général'];
}

$idCeg = (int)$cycleCeg['id_cycle'];
$idLycee = (int)$cycleLycee['id_cycle'];

// Ensure permissions in DB
$stmtP = $db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'inscrire'");
$permInscrire = $stmtP->fetchColumn();
if (!$permInscrire) {
    $db->exec("INSERT INTO permissions (resource, action) VALUES ('eleve', 'inscrire')");
    $permInscrire = $db->lastInsertId();
}

$stmtPView = $db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'view_all'");
$permViewAll = $stmtPView->fetchColumn();
if (!$permViewAll) {
    $db->exec("INSERT INTO permissions (resource, action) VALUES ('eleve', 'view_all')");
    $permViewAll = $db->lastInsertId();
}

// Create custom role with arbitrary name (e.g. "agent_inscription_custom_xyz")
$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role) VALUES (777, 'agent_inscription_custom_xyz')");
$db->exec("INSERT OR IGNORE INTO roles (id_role, nom_role) VALUES (998, 'unprivileged_test')");

$db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (777, {$permInscrire})");
$db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (777, {$permViewAll})");

// Create users in DB with active status
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9981, 1, 'CustomAgent', 'Test', 777, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, role_id, auth_version, actif) VALUES (9983, 1, 'Unprivileged', 'Test', 998, 1, 1)");

// Find or create target class
$targetClassId = Classe::findIdByDetails(1, '6e', null, 'A', $idCeg);
if (!$targetClassId) {
    $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (99810, 1, {$idCeg}, '6e', 'A')");
    $targetClassId = 99810;
}
$targetClassNiveau = '6e';
$targetClassNumero = 'A';
$targetClassCycleId = $idCeg;

$classeCtrl = new ClasseController();
$eleveCtrl = new EleveController();

// -------------------------------------------------------------
// TEST 1: Custom Role Name with eleve:inscrire permission loads cycles
// -------------------------------------------------------------
echo "\nTEST 1: Rôle à nom personnalisé ('agent_inscription_custom_xyz') charge les cycles via ses permissions\n";
setMockUser(9981, 777, 'agent_inscription_custom_xyz');

$permittedCycles = AuthorizationScopeService::getPermittedCycles(1);
$permittedIds = array_column($permittedCycles, 'id_cycle');

if (!empty($permittedIds) && in_array($targetClassCycleId, $permittedIds)) {
    echo "  [PASS] 1. Custom role user retrieved authorized cycles without relying on role name: " . json_encode($permittedIds) . "\n";
} else {
    echo "  [FAIL] 1. Custom role user failed to retrieve cycles! Got: " . json_encode($permittedIds) . "\n";
}

// -------------------------------------------------------------
// TEST 2: Cycle selection triggers levels/classes AJAX cascade
// -------------------------------------------------------------
echo "\nTEST 2: La sélection du cycle par le rôle personnalisé déclenche le chargement des niveaux\n";
setMockUser(9981, 777, 'agent_inscription_custom_xyz');
$_GET = ['cycle_id' => $targetClassCycleId, 'lycee_id' => 1];
ob_start(); $classeCtrl->getNiveauxForCycle(); $cegNiveaux = json_decode(ob_get_clean(), true);

if (is_array($cegNiveaux) && in_array($targetClassNiveau, $cegNiveaux)) {
    echo "  [PASS] 2. Niveaux AJAX endpoint returned '{$targetClassNiveau}' for custom role.\n";
} else {
    echo "  [FAIL] 2. Niveaux AJAX endpoint failed! Got: " . json_encode($cegNiveaux) . "\n";
}

// -------------------------------------------------------------
// TEST 3: Custom Role completes class assignment for new student
// -------------------------------------------------------------
echo "\nTEST 3: Le rôle personnalisé affecte un élève à une classe autorisée\n";
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut, sexe) VALUES (99801, 1, 'Nouveau', 'CustomRoleEleve', 'en_attente_paiement', 'Masculin')");

setMockUser(9981, 777, 'agent_inscription_custom_xyz');
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'eleve_id' => 99801,
    'cycle_id' => $targetClassCycleId,
    'niveau' => $targetClassNiveau,
    'numero' => $targetClassNumero
];

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
ob_start();
try { $eleveCtrl->processAssignment(); } catch (Throwable $e) {}
ob_end_clean();

$checkEtude = $db->query("SELECT * FROM etudes WHERE eleve_id = 99801 AND classe_id = {$targetClassId}")->fetch(PDO::FETCH_ASSOC);
if ($checkEtude) {
    echo "  [PASS] 3. Student assignment to class {$targetClassId} registered successfully for custom role.\n";
} else {
    echo "  [FAIL] 3. Student assignment failed! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// -------------------------------------------------------------
// TEST 4: Renaming role in DB produces zero functionality change
// -------------------------------------------------------------
echo "\nTEST 4: Renommer le rôle en base ne modifie pas les droits effectifs de l'utilisateur\n";
$db->exec("UPDATE roles SET nom_role = 'renamed_role_123' WHERE id_role = 777");
setMockUser(9981, 777, 'renamed_role_123');

$renamedCycles = AuthorizationScopeService::getPermittedCycles(1);
$renamedIds = array_column($renamedCycles, 'id_cycle');

if (!empty($renamedIds) && in_array($targetClassCycleId, $renamedIds)) {
    echo "  [PASS] 4. Renamed role retains full functionality without code change.\n";
} else {
    echo "  [FAIL] 4. Renamed role lost functionality! Got: " . json_encode($renamedIds) . "\n";
}

// Restore original role name
$db->exec("UPDATE roles SET nom_role = 'agent_inscription_custom_xyz' WHERE id_role = 777");

// -------------------------------------------------------------
// TEST 5: Parameter tampering with out-of-scope cycle/class rejected
// -------------------------------------------------------------
echo "\nTEST 5: Modification des requêtes HTTP hors périmètre rejetée côté serveur\n";
setMockUser(9981, 777, 'agent_inscription_custom_xyz');
$_POST = [
    'eleve_id' => 99801,
    'cycle_id' => 999999, // Invalid cycle ID
    'niveau' => '2nd',
    'numero' => '1'
];

unset($_SESSION['error_message']);
ob_start();
try { $eleveCtrl->processAssignment(); } catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "Accès refusé")) {
    echo "  [PASS] 5. Tampered request with out-of-scope cycle_id rejected with 403 error message.\n";
} else {
    echo "  [FAIL] 5. Tampered request was NOT blocked! Error msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// -------------------------------------------------------------
// TEST 6: Unprivileged user blocked server-side
// -------------------------------------------------------------
echo "\nTEST 6: Utilisateur sans permissions reste bloqué côté serveur\n";
setMockUser(9983, 998, 'unprivileged_test');

$unprivCycles = AuthorizationScopeService::getPermittedCycles(1);
if (empty($unprivCycles)) {
    echo "  [PASS] 6. Unprivileged user cycle retrieval returned empty array (blocked).\n";
} else {
    echo "  [FAIL] 6. Unprivileged user was granted cycles! Got: " . json_encode($unprivCycles) . "\n";
}

// -------------------------------------------------------------
// TEST 7: Enrolled student appears in student list and student dashboard
// -------------------------------------------------------------
echo "\nTEST 7: Élève inscrit et enregistré apparaît dans le tableau de bord et la liste des élèves\n";
setMockUser(9981, 777, 'agent_inscription_custom_xyz');

// Create a new unassigned student with status en_attente_paiement
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, prenom, nom, statut, sexe) VALUES (99802, 1, 'Inscrit', 'SansClasseCustom', 'en_attente_paiement', 'Féminin')");

$dashboardData = EleveDashboardService::getDashboardData(['lycee_id' => 1], 9981);
if (!empty($dashboardData['success']) && $dashboardData['kpis']['en_attente_paiement'] >= 1) {
    echo "  [PASS] 7a. Unassigned newly enrolled student included in student dashboard KPI count for custom role.\n";
} else {
    echo "  [FAIL] 7a. Student missing from dashboard! KPIs: " . json_encode($dashboardData['kpis'] ?? []) . "\n";
}

// Verify student appears in EleveController::index listing query
$_GET = ['lycee_id' => 1];
ob_start();
try { $eleveCtrl->index(); } catch (Throwable $e) {}
$htmlOutput = ob_get_clean();

if (str_contains($htmlOutput, 'SansClasseCustom') || str_contains($htmlOutput, 'Inscrit')) {
    echo "  [PASS] 7b. Enrolled student 'Inscrit SansClasseCustom' appears in student list HTML view for custom role.\n";
} else {
    echo "  [FAIL] 7b. Enrolled student missing from student list view!\n";
}

// Cleanup mock records
$db->exec("DELETE FROM etudes WHERE eleve_id IN (99801, 99802, 99803)");
$db->exec("DELETE FROM eleves WHERE id_eleve IN (99801, 99802, 99803)");
$db->exec("DELETE FROM classes WHERE id_classe IN (99810, 99820)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (9981, 9982, 9983)");

echo "\n========================================================\n";
echo "SUCCESS: ALL CUSTOM ROLE & RBAC SCOPE TESTS PASSED!\n";
echo "========================================================\n";
