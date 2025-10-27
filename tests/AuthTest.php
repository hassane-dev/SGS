<?php

// Test for Auth class, specifically the can() method.
define('DB_USER', 'jules');
define('DB_PASS', '');

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';

function run_test() {
    echo "Running test: Auth::can() Authorization Logic...\n";

    // --- Test Case: User with 'user' -> 'edit' permission ---
    echo "  Case: Auth::can('user_edit') returns true for authorized user.\n";

    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // 1. Setup: Create a role with the 'user' -> 'edit' permission
        Role::save(['nom_role' => 'Test Auth Role']);
        $role_id = $db->lastInsertId();

        // Find the permission ID for 'user' -> 'edit'
        $perm_id_stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = 'user' AND action = 'edit'");
        $perm_id_stmt->execute();
        $perm_id = $perm_id_stmt->fetchColumn();
        if (!$perm_id) {
            $seed_sql = file_get_contents(__DIR__ . '/../db/seeds.sql');
            if ($seed_sql) $db->exec($seed_sql);
            $perm_id_stmt->execute();
            $perm_id = $perm_id_stmt->fetchColumn();
            if (!$perm_id) throw new Exception("Permission 'user' -> 'edit' not found in seeds.");
        }

        Role::setPermissions($role_id, [$perm_id]);

        // 2. Create a user with this role
        User::save([
            'nom' => 'AuthCan', 'prenom' => 'Test', 'email' => 'authcantest@example.com',
            'mot_de_passe' => 'password', 'role_id' => $role_id, 'actif' => 1
        ]);

        // 3. Log in as the user
        if (!Auth::login('authcantest@example.com', 'password')) {
            throw new Exception("Login failed unexpectedly.");
        }

        // 4. Check the permission
        $has_permission = Auth::can('edit', 'user');

        // 5. Assert the result
        if ($has_permission) {
            echo "    [PASS] Auth::can('edit', 'user') correctly returned true.\n";
        } else {
            echo "    [FAIL] Auth::can('edit', 'user') returned false for an authorized user.\n";
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