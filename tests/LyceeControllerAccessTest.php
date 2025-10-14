<?php

// Test for LyceeController access control
define('DB_USER', 'jules');
define('DB_PASS', '');
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/controllers/LyceeController.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';

function run_test() {
    echo "Running test: LyceeController Access Control...\n";

    // --- Test Case: User with 'system_view_all_lycees' permission can access the lycee list ---
    echo "  Case: User with 'system_view_all_lycees' can access /lycees.\n";
    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // 1. Setup: Create a role with the 'system_view_all_lycees' permission
        Role::save(['nom_role' => 'Test Lycee Manager']);
        $role_id = $db->lastInsertId();

        $perm_id_stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'system' AND action = 'view_all_lycees'");
        $perm_id_stmt->execute();
        $perm_id = $perm_id_stmt->fetchColumn();
        if (!$perm_id) throw new Exception("Permission 'system_view_all_lycees' not found. Seeds might be outdated.");

        Role::setPermissions($role_id, [$perm_id]);

        // 2. Create a user with this role
        User::save([
            'nom' => 'Lycee', 'prenom' => 'Manager', 'email' => 'lyceemanager@test.com',
            'mot_de_passe' => 'password', 'role_id' => $role_id, 'actif' => 1
        ]);

        // 3. Log in as the user
        if (!Auth::login('lyceemanager@test.com', 'password')) {
            throw new Exception("Login failed unexpectedly.");
        }

        // 4. Attempt to access the lycee index page
        $controller = new LyceeController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        // 5. Assert that the user was not denied access
        if (strpos($output, 'Accès Interdit.') === false) {
            echo "    [PASS] Access to lycee management page was granted.\n";
        } else {
            echo "    [FAIL] Access was denied for a user with 'system_view_all_lycees' permission.\n";
            exit(1);
        }

        echo "All assertions passed!\n";

    } catch (Exception $e) {
        echo "    [FAIL] Test failed with exception: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        $db->rollBack();
        Auth::logout();
    }
}

run_test();
?>