<?php

// Test for HomeController authorization bug
define('DB_USER', 'jules');
define('DB_PASS', '');
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/controllers/HomeController.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';

function run_test() {
    echo "Running test: HomeController Authorization...\n";

    // --- Test Case: super_admin_national should see the 'Gérer les Lycées' link ---
    echo "  Case: super_admin_national sees correct navigation links.\n";

    try {
        // 1. Create a dummy user with the 'super_admin_national' role
        $db = Database::getInstance();
        $db->exec("DELETE FROM utilisateurs WHERE email = 'authtest@example.com'");
        // Find the role ID for 'super_admin_national'
        $role_stmt = $db->prepare("SELECT id_role FROM roles WHERE nom_role = 'super_admin_national' LIMIT 1");
        $role_stmt->execute();
        $role_id = $role_stmt->fetchColumn();
        if (!$role_id) {
            // Seed the roles if they don't exist
            $seed_sql = file_get_contents(__DIR__ . '/../db/seeds.sql');
            if ($seed_sql) $db->exec($seed_sql);
            $role_stmt->execute();
            $role_id = $role_stmt->fetchColumn();
            if (!$role_id) throw new Exception("Role 'super_admin_national' not found.");
        }

        $user_data = [
            'nom' => 'Auth',
            'prenom' => 'Test',
            'email' => 'authtest@example.com',
            'mot_de_passe' => password_hash('password', PASSWORD_DEFAULT),
            'role_id' => $role_id,
            'actif' => 1
        ];
        $stmt = $db->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, actif) VALUES (:nom, :prenom, :email, :mot_de_passe, :role_id, :actif)");
        $stmt->execute($user_data);

        // 2. Log in as the new user
        if (!Auth::login('authtest@example.com', 'password')) {
            throw new Exception("Login failed unexpectedly.");
        }

        // 3. Call the HomeController's index method and capture output
        $controller = new HomeController();
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        // 4. Assert that the specific link is present in the output
        $expected_link = '<a href="/lycees" class="text-blue-500 hover:underline">Gérer les Lycées</a>';
        if (strpos($output, $expected_link) !== false) {
            echo "    [PASS] 'Gérer les Lycées' link is visible.\n";
        } else {
            echo "    [FAIL] 'Gérer les Lycées' link is NOT visible for super_admin_national.\n";
            exit(1);
        }

        echo "All assertions passed for this test!\n";

    } catch (Exception $e) {
        echo "    [FAIL] Test failed with exception: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        // Clean up
        $db = Database::getInstance();
        $db->exec("DELETE FROM utilisateurs WHERE email = 'authtest@example.com'");
        Auth::logout();
    }
}

run_test();

?>