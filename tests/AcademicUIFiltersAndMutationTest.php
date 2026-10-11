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
require_once __DIR__ . '/../src/controllers/ClasseController.php';
require_once __DIR__ . '/../src/controllers/AffectationPedagogiqueController.php';
require_once __DIR__ . '/../src/controllers/FraisController.php';
require_once __DIR__ . '/../src/controllers/EleveController.php';

echo "========================================================\n";
echo "RUNNING PHASE 5A TEST SUITE: UI Cascade & Scope Mutations\n";
echo "========================================================\n";

$db = Database::getInstance();

// Clean test fixtures
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

// Link permissions
$stmtP1 = $db->query("SELECT id_permission FROM permissions WHERE resource = 'lycee' AND action = 'view_all_lycees' LIMIT 1");
$permLycee = $stmtP1 ? $stmtP1->fetchColumn() : null;

$stmtP2 = $db->query("SELECT id_permission FROM permissions WHERE resource = 'cycle' AND action = 'view_all_cycles' LIMIT 1");
$permCycle = $stmtP2 ? $stmtP2->fetchColumn() : null;

$stmtP3 = $db->query("SELECT id_permission FROM permissions WHERE resource = 'class' AND action = 'create' LIMIT 1");
$permClassCreate = $stmtP3 ? $stmtP3->fetchColumn() : null;

$stmtP4 = $db->query("SELECT id_permission FROM permissions WHERE resource = 'pedagogy' AND action = 'manage_affectations' LIMIT 1");
$permPedagogyManage = $stmtP4 ? $stmtP4->fetchColumn() : null;

$stmtP5 = $db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'inscrire' LIMIT 1");
$permInscrire = $stmtP5 ? $stmtP5->fetchColumn() : null;

if ($permLycee) {
    try { $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, $permLycee)"); } catch (Exception $e) {}
}
if ($permCycle) {
    try { $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, $permCycle)"); } catch (Exception $e) {}
    try { $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (3, $permCycle)"); } catch (Exception $e) {}
}
if ($permClassCreate) {
    try { $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (4, $permClassCreate)"); } catch (Exception $e) {}
}
if ($permPedagogyManage) {
    try { $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (4, $permPedagogyManage)"); } catch (Exception $e) {}
}
if ($permInscrire) {
    try { $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (4, $permInscrire)"); } catch (Exception $e) {}
}

// 1. Create Lycees
$db->exec("INSERT INTO param_lycee (id, nom_lycee, sigle) VALUES (8001, 'Lycée Test A', 'LTA')");
$db->exec("INSERT INTO param_lycee (id, nom_lycee, sigle) VALUES (8002, 'Lycée Test B', 'LTB')");

// 2. Create Cycles
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8010, 8001, 'CEG Test A')");
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8020, 8001, 'Lycée Test A')");
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (8030, 8002, 'CEG Test B')");

// 3. Create Classes
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8101, 8001, 8010, '6ème', NULL, 1)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8102, 8001, 8010, '6ème', NULL, 2)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8201, 8001, 8020, '2nde', 'C', 1)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (8301, 8002, 8030, '6ème', NULL, 1)");

// 4. Create Users
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8001, 'Mono', 'Admin', 'mono5a@test.com', 'hash', 3, 8001, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, mot_de_passe, role_id, lycee_id, actif, auth_version) VALUES (8003, 'CegOnly', 'User', 'ceg5a@test.com', 'hash', 4, 8001, 1, 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (8003, 8010, '2020-01-01', 1)");

echo "[OK] Fixtures loaded.\n\n";

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
// 1. AJAX ENDPOINTS CONTRACT & WRAPPER TESTS
// ========================================================

echo "TEST 1 — AJAX Endpoints JSON Contracts\n";
setTestUser([
    'id' => 8001,
    'id_user' => 8001,
    'role_id' => 3,
    'role_name' => 'admin_local',
    'lycee_id' => 8001,
    'permissions' => ['lycee' => ['view'], 'cycle' => ['view_all_cycles'], 'class' => ['view', 'create'], 'pedagogy' => ['view_affectations', 'manage_affectations'], 'eleve' => ['view_all', 'inscrire']]
]);

// Test ClasseController::getNiveauxForCycle
$_GET = ['lycee_id' => 8001, 'cycle_id' => 8010];
ob_start();
$clsCtrl = new ClasseController();
$clsCtrl->getNiveauxForCycle();
$outNiv = ob_get_clean();
$arrNiv = json_decode($outNiv, true);
assertResult(is_array($arrNiv) && in_array('6ème', $arrNiv), "ClasseController::getNiveauxForCycle returns array containing '6ème'.");

// Test ClasseController::getSeriesForNiveau
$_GET = ['lycee_id' => 8001, 'cycle_id' => 8020, 'niveau' => '2nde'];
ob_start();
$clsCtrl->getSeriesForNiveau();
$outSer = ob_get_clean();
$arrSer = json_decode($outSer, true);
assertResult(is_array($arrSer) && in_array('C', $arrSer), "ClasseController::getSeriesForNiveau returns array containing 'C'.");

// Test ClasseController::getNumerosForClasse
$_GET = ['lycee_id' => 8001, 'cycle_id' => 8010, 'niveau' => '6ème'];
ob_start();
$clsCtrl->getNumerosForClasse();
$outNum = ob_get_clean();
$arrNum = json_decode($outNum, true);
assertResult(is_array($arrNum) && in_array(1, $arrNum) && in_array(2, $arrNum), "ClasseController::getNumerosForClasse returns array [1, 2].");

// Test ClasseController::findClassId
$_GET = ['lycee_id' => 8001, 'cycle_id' => 8010, 'niveau' => '6ème', 'numero' => 2];
ob_start();
$clsCtrl->findClassId();
$outId = ob_get_clean();
$arrId = json_decode($outId, true);
assertResult(isset($arrId['id_classe']) && $arrId['id_classe'] === 8102, "ClasseController::findClassId returns {'id_classe': 8102}.");

// Test AffectationPedagogiqueController wrappers
$_GET = ['lycee_id' => 8001, 'cycle_id' => 8010];
ob_start();
$affCtrl = new AffectationPedagogiqueController();
$affCtrl->getNiveaux();
$outAffNiv = ob_get_clean();
$arrAffNiv = json_decode($outAffNiv, true);
assertResult(is_array($arrAffNiv) && in_array('6ème', $arrAffNiv), "AffectationPedagogiqueController wrapper getNiveaux matches contract.");

// Test FraisController wrappers
$_GET = ['lycee_id' => 8001, 'cycle_id' => 8010];
ob_start();
$fraisCtrl = new FraisController();
$fraisCtrl->getNiveaux();
$outFraisNiv = ob_get_clean();
$arrFraisNiv = json_decode($outFraisNiv, true);
assertResult(is_array($arrFraisNiv) && in_array('6ème', $arrFraisNiv), "FraisController wrapper getNiveaux matches contract.");


// ========================================================
// 2. SECURITY & SCOPE REJECTION ON AJAX
// ========================================================

echo "\nTEST 2 — AJAX Scope Rejection & Tampering Interception\n";
setTestUser([
    'id' => 8003,
    'id_user' => 8003,
    'role_id' => 4,
    'role_name' => 'censeur',
    'lycee_id' => 8001,
    'permissions' => ['lycee' => ['view'], 'class' => ['view', 'create'], 'pedagogy' => ['manage_affectations', 'view_affectations'], 'eleve' => ['inscrire', 'view_all']]
]);

// Attempt to get series for forbidden Lycée cycle 8020
$_GET = ['lycee_id' => 8001, 'cycle_id' => 8020, 'niveau' => '2nde'];
ob_start();
$clsCtrl->getSeriesForNiveau();
$outForbSer = ob_get_clean();
$arrForbSer = json_decode($outForbSer, true);
assertResult(empty($arrForbSer), "AJAX query for forbidden cycle 8020 returns empty array.");

// Attempt to find class ID for foreign school class 8301
$_GET = ['classe_id' => 8301];
ob_start();
$clsCtrl->findClassId();
$outForbId = ob_get_clean();
$arrForbId = json_decode($outForbId, true);
assertResult($arrForbId['id_classe'] === null, "AJAX query for foreign class 8301 returns null id_classe.");


// ========================================================
// 3. MUTATION SECURITY TESTS (CLASSES, ÉLÈVES, AFFECTATIONS)
// ========================================================

echo "\nTEST 3 — Domain Mutation Scope Enforcement\n";

// 3a. ClasseController::store() with forbidden cycle
$_POST = ['nom_classe' => 'Hack Class', 'lycee_id' => 8001, 'cycle_id' => 8020, 'niveau' => '2nde', 'numero' => 9];
$_SERVER['REQUEST_METHOD'] = 'POST';

$caughtStore403 = false;
try {
    $clsCtrl->store();
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Accès Refusé') !== false || strpos($e->getMessage(), 'hors périmètre') !== false) {
        $caughtStore403 = true;
    }
}
assertResult($caughtStore403 === true, "ClasseController::store() with out-of-scope cycle 8020 BLOCKED.");

// 3b. AffectationPedagogiqueController::store() with forbidden class 8201
$_POST = ['enseignant_id' => 8001, 'classe_id' => 8201, 'matiere_id' => 8501, 'date_debut' => '2025-09-01'];
$caughtAff403 = false;
try {
    $affCtrl->store();
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Accès Refusé') !== false || strpos($e->getMessage(), 'hors périmètre') !== false) {
        $caughtAff403 = true;
    }
}
assertResult($caughtAff403 === true, "AffectationPedagogiqueController::store() with out-of-scope class 8201 BLOCKED.");

// 3c. EleveController::processAssignment() with forbidden cycle/class
$db->exec("INSERT INTO eleves (id_eleve, nom, prenom, lycee_id, statut) VALUES (8888, 'Eleve', 'Test', 8001, 'actif')");
$_POST = ['eleve_id' => 8888, 'cycle_id' => 8020, 'niveau' => '2nde', 'serie' => 'C', 'numero' => 1];
$eleveCtrl = new EleveController();
$eleveCtrl->processAssignment();
assertResult(isset($_SESSION['error_message']) && stripos($_SESSION['error_message'], 'périmètre') !== false, "EleveController::processAssignment() with forbidden cycle sets error message and blocks.");

// ========================================================
// 4. INDEX CONTROLLER VIEW RENDER TESTS (SCOPED DATA)
// ========================================================

echo "\nTEST 4 — Scoped Index View Renders\n";

// 4a. ClasseController::index()
$_GET = [];
ob_start();
$clsCtrl->index();
$htmlCls = ob_get_clean();
assertResult(strpos($htmlCls, 'Gestion des Classes') !== false, "ClasseController::index() renders successfully with AcademicFilterService.");

// 4b. EleveController::index()
$_GET = [];
ob_start();
$eleveCtrl->index();
$htmlEleve = ob_get_clean();
assertResult(strpos($htmlEleve, 'Élèves') !== false || strpos($htmlEleve, 'Filtres') !== false, "EleveController::index() renders successfully with AcademicFilterService.");

// 4c. AffectationPedagogiqueController::index()
$_GET = [];
ob_start();
$affCtrl->index();
$htmlAff = ob_get_clean();
assertResult(strpos($htmlAff, 'Affectations') !== false || strpos($htmlAff, 'Filtres') !== false, "AffectationPedagogiqueController::index() renders successfully with AcademicFilterService.");

echo "\n========================================================\n";
echo "SUCCESS: ALL PHASE 5A UI & MUTATION TESTS PASSED!\n";
echo "========================================================\n";
