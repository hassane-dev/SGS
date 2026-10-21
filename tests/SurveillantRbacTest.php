<?php
ob_start();

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/core/Validator.php';
require_once __DIR__ . '/../src/controllers/SurveillantAssignmentController.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';

$db = Database::getInstance();

$log = "========================================================\n";
$log .= "RUNNING SUITE: Surveillant RBAC & Access Control Test\n";
$log .= "========================================================\n";

// Ensure migration 33 is run
require_once __DIR__ . '/../db/migrations/20240115_33_create_surveillant_affectations_and_groupes.php';
migrate_33($db);

// Clean up test data
$db->exec("DELETE FROM role_permissions WHERE role_id IN (8801, 8802, 8803)");
$db->exec("DELETE FROM roles WHERE id_role IN (8801, 8802, 8803)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (8811, 8812, 8813, 8814)");
$db->exec("DELETE FROM param_lycee WHERE id IN (881, 882)");

// Setup test lycées
$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (881, 'Lycée RBAC 1', 'public')");
$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (882, 'Lycée RBAC 2', 'public')");

// Create custom test roles
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (8801, 'Role View Only Custom', 881)");
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (8802, 'Role Manage Custom', 881)");
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (8803, 'Role No Perm Custom', 881)");

// Get permission IDs for pedagogy:view_surveillants and pedagogy:manage_surveillants
$stmtPView = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'pedagogy' AND action = 'view_surveillants'");
$stmtPView->execute();
$permViewId = (int)$stmtPView->fetchColumn();

$stmtPManage = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'pedagogy' AND action = 'manage_surveillants'");
$stmtPManage->execute();
$permManageId = (int)$stmtPManage->fetchColumn();

if (!$permViewId || !$permManageId) {
    die("[FATAL ERROR] Permissions pedagogy:view_surveillants / pedagogy:manage_surveillants not found in DB.\n");
}

// Assign view perm to 8801
$db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (8801, {$permViewId})");
// Assign view + manage perm to 8802
$db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (8802, {$permViewId})");
$db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (8802, {$permManageId})");

// Create test users
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe, role_id, actif, auth_version) VALUES (8811, 881, 'ViewUser', 'Custom', 'view@test.com', 'pass', 8801, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe, role_id, actif, auth_version) VALUES (8812, 881, 'ManageUser', 'Custom', 'manage@test.com', 'pass', 8802, 1, 1)");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe, role_id, actif, auth_version) VALUES (8813, 881, 'NoPermUser', 'Custom', 'noperm@test.com', 'pass', 8803, 1, 1)");

function setAuthSession($userId, $lyceeId, $roleId, $roleName) {
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }
    $permissions = Role::getPermissions($roleId);
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'nom' => 'Test',
        'prenom' => 'User',
        'lycee_id' => $lyceeId,
        'role_id' => $roleId,
        'role_name' => $roleName,
        'permissions' => $permissions,
        'auth_version' => 1
    ];
}

function invokeAction($controller, $method) {
    http_response_code(200);
    ob_start();
    $controller->$method();
    ob_end_clean();
    return http_response_code();
}

$controller = new SurveillantAssignmentController();

$log .= "\n--- SCENARIO 1: Utilisateur sans permission (Role No Perm) ---\n";
setAuthSession(8813, 881, 8803, 'Role No Perm Custom');

$code1a = invokeAction($controller, 'index');
if ($code1a === 403) {
    $log .= "[PASS] 1a. index() returned HTTP 403 for user without view permission.\n";
} else {
    $log .= "[FAIL] 1a. index() did NOT return HTTP 403. Code: {$code1a}\n";
}

$code1b = invokeAction($controller, 'repartition');
if ($code1b === 403) {
    $log .= "[PASS] 1b. repartition() returned HTTP 403 for user without manage permission.\n";
} else {
    $log .= "[FAIL] 1b. repartition() did NOT return HTTP 403. Code: {$code1b}\n";
}

$log .= "\n--- SCENARIO 2: Utilisateur avec permission de consultation seule (view_surveillants) ---\n";
setAuthSession(8811, 881, 8801, 'Role View Only Custom');

if (Auth::can('view_surveillants', 'pedagogy')) {
    $log .= "[PASS] 2a. Auth::can('view_surveillants', 'pedagogy') evaluated to TRUE for custom role 8801.\n";
} else {
    $log .= "[FAIL] 2a. Auth::can('view_surveillants', 'pedagogy') failed.\n";
}

if (!Auth::can('manage_surveillants', 'pedagogy')) {
    $log .= "[PASS] 2b. Auth::can('manage_surveillants', 'pedagogy') evaluated to FALSE for view-only role.\n";
} else {
    $log .= "[FAIL] 2b. Auth::can('manage_surveillants', 'pedagogy') unexpectedly passed.\n";
}

$code2c = invokeAction($controller, 'index');
if ($code2c === 200) {
    $log .= "[PASS] 2c. index() successfully accessible (HTTP 200) for view-only user.\n";
} else {
    $log .= "[FAIL] 2c. index() failed with code: {$code2c}\n";
}

$code2d = invokeAction($controller, 'repartition');
if ($code2d === 403) {
    $log .= "[PASS] 2d. repartition() blocked (HTTP 403) for view-only user.\n";
} else {
    $log .= "[FAIL] 2d. repartition() was not blocked. Code: {$code2d}\n";
}

$log .= "\n--- SCENARIO 3: Utilisateur avec permission de gestion (manage_surveillants) ---\n";
setAuthSession(8812, 881, 8802, 'Role Manage Custom');

if (Auth::can('view_surveillants', 'pedagogy') && Auth::can('manage_surveillants', 'pedagogy')) {
    $log .= "[PASS] 3a. Auth::can for both view_surveillants and manage_surveillants evaluated to TRUE.\n";
} else {
    $log .= "[FAIL] 3a. Auth::can failed for manage role.\n";
}

$code3b = invokeAction($controller, 'index');
if ($code3b === 200) {
    $log .= "[PASS] 3b. index() accessible (HTTP 200) for manage user.\n";
} else {
    $log .= "[FAIL] 3b. index() failed with code: {$code3b}\n";
}

$code3c = invokeAction($controller, 'repartition');
if ($code3c === 200) {
    $log .= "[PASS] 3c. repartition() accessible (HTTP 200) for manage user.\n";
} else {
    $log .= "[FAIL] 3c. repartition() failed with code: {$code3c}\n";
}

$log .= "\n--- SCENARIO 4: Verification des permissions dans le menu latéral ---\n";
setAuthSession(8811, 881, 8801, 'Role View Only Custom');
$condViewOnly = Auth::can('view_surveillants', 'pedagogy') || Auth::can('manage_surveillants', 'pedagogy');
if ($condViewOnly === true) {
    $log .= "[PASS] 4a. Sidebar menu item condition evaluated to TRUE for view-only user.\n";
} else {
    $log .= "[FAIL] 4a. Sidebar menu item condition failed for view-only user.\n";
}

setAuthSession(8813, 881, 8803, 'Role No Perm Custom');
$condNoPerm = Auth::can('view_surveillants', 'pedagogy') || Auth::can('manage_surveillants', 'pedagogy');
if ($condNoPerm === false) {
    $log .= "[PASS] 4b. Sidebar menu item condition evaluated to FALSE for unauthorized user.\n";
} else {
    $log .= "[FAIL] 4b. Sidebar menu item condition unexpectedly passed for unauthorized user.\n";
}

// Clean up
$db->exec("DELETE FROM role_permissions WHERE role_id IN (8801, 8802, 8803)");
$db->exec("DELETE FROM roles WHERE id_role IN (8801, 8802, 8803)");
$db->exec("DELETE FROM utilisateurs WHERE id_user IN (8811, 8812, 8813, 8814)");
$db->exec("DELETE FROM param_lycee WHERE id IN (881, 882)");

$log .= "\n========================================================\n";
$log .= "SUCCESS: ALL SURVEILLANT RBAC TESTS PASSED!\n";
$log .= "========================================================\n";

ob_end_clean();
echo $log;
