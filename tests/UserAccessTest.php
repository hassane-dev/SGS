<?php

// Test for UserController access control
define('DB_USER', 'jules');
define('DB_PASS', '');
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/controllers/UserController.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';

function run_test() {
    echo "Running test: UserController Access Control...\n";

    // --- Test Case: User with 'user_manage' permission can access the user list ---
    echo "  Case: User with 'user_manage' can access /users.\n";
    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // 1. Setup: Create a role with the 'user_manage' permission
        Role::save(['nom_role' => 'Test User Manager']);
        $role_id = $db->lastInsertId();

        $perm_id_stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'user' AND action = 'manage'");
        $perm_id_stmt->execute();
        $perm_id = $perm_id_stmt->fetchColumn();
        if (!$perm_id) throw new Exception("Permission 'user_manage' not found. Seeds might be outdated.");

        Role::setPermissions($role_id, [$perm_id]);

        // 2. Create a user with this role
        User::save([
            'nom' => 'Access', 'prenom' => 'Test', 'email' => 'accesstest@example.com',
            'mot_de_passe' => 'password', 'role_id' => $role_id, 'actif' => 1
        ]);

        // 3. Log in as the user
        if (!Auth::login('accesstest@example.com', 'password')) {
            throw new Exception("Login failed unexpectedly.");
        }

        // 4. Attempt to access the user index page
        $controller = new UserController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        // 5. Assert that the user was not denied access
        if (strpos($output, 'Accès Interdit.') === false) {
            echo "    [PASS] Access to user management page was granted.\n";
        } else {
            echo "    [FAIL] Access was denied for a user with 'user_manage' permission.\n";
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