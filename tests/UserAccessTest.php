<?php

// Test for UserController access control
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/controllers/UserController.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';
require_once __DIR__ . '/../src/core/View.php';

// Mock i18n globals for header rendering
$GLOBALS['lang'] = 'fr_FR';
$GLOBALS['supported_languages'] = ['fr_FR' => ['dir' => 'ltr', 'name' => 'Français']];

function run_test() {
    $output_buffer = "Running test: UserController Access Control...\n";

    // --- Test Case: User with 'user_manage' permission can access the user list ---
    $output_buffer .= "  Case: User with 'user_manage' can access /users.\n";
    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // 1. Setup: Create a role with the 'user_manage' permission
        Role::save(['nom_role' => 'Test User Manager']);
        $role_id = $db->lastInsertId();

        $perm_id_stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'user' AND action = 'view_all'");
        $perm_id_stmt->execute();
        $perm_id = $perm_id_stmt->fetchColumn();
        if (!$perm_id) throw new Exception("Permission 'user_view_all' not found. Seeds might be outdated.");

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
            $output_buffer .= "    [PASS] Access to user management page was granted.\n";
        } else {
            $output_buffer .= "    [FAIL] Access was denied for a user with 'user_manage' permission.\n";
            echo $output_buffer;
            exit(1);
        }

        $output_buffer .= "All assertions passed!\n";

    } catch (Exception $e) {
        $output_buffer .= "    [FAIL] Test failed with exception: " . $e->getMessage() . "\n";
        echo $output_buffer;
        exit(1);
    } finally {
        $db->rollBack();
        Auth::logout();
        echo $output_buffer;
    }
}

run_test();
?>