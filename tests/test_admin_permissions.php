<?php

// A simple test to verify that the admin created during setup has the correct permissions.

// 1. Set up the environment
define('DB_USER', 'jules'); // Override for testing
define('DB_PASS', '');
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/ParametresGeneraux.php';

function run_test() {
    echo "Running test: test_admin_permissions...\n";

    // --- Test Case: Admin created during setup should have 'annee_academique manage' permission ---
    echo "  Case: Admin has correct permissions.\n";

    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        // Clean up previous test data
        $db->exec("DELETE FROM role_permissions WHERE role_id > 8");
        $db->exec("DELETE FROM utilisateurs");
        $db->exec("DELETE FROM roles WHERE lycee_id IS NOT NULL");
        $db->exec("DELETE FROM lycees");
        $db->exec("DELETE FROM annees_academiques");
        $db->exec("DELETE FROM parametres_generaux");


        // 1. Create the Lycee
        $lycee_id = Lycee::save(['nom_lycee' => 'Test Lycee', 'type_lycee' => 'prive']);

        // 2. Seed the database (we assume this is already done, but we need the roles)
        $seed_sql = file_get_contents(__DIR__ . '/../db/seeds.sql');
        if ($seed_sql) $db->exec($seed_sql);

        // 3. Create a specific admin role for this Lycee
        Role::save(['nom_role' => 'Admin - Test Lycee', 'lycee_id' => $lycee_id]);
        $role_id = $db->lastInsertId();

        // 4. Assign permissions to this new role from the template
        $template_permissions = Role::getPermissions(3); // admin_local template
        $perm_ids = [];
        if (is_array($template_permissions)) {
            foreach ($template_permissions as $resource => $actions) {
                foreach ($actions as $action) {
                    $stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = :r AND action = :a");
                    $stmt->execute(['r' => $resource, 'a' => $action]);
                    if ($p_id = $stmt->fetchColumn()) $perm_ids[] = $p_id;
                }
            }
        }
        Role::setPermissions($role_id, $perm_ids);

        // 5. Create the admin user
        User::save([
            'nom' => 'Admin',
            'prenom' => 'Test',
            'email' => 'admin@test.com',
            'mot_de_passe' => 'password',
            'role_id' => $role_id,
            'lycee_id' => $lycee_id,
            'actif' => 1
        ]);

        // Retrieve the newly created admin user
        $user = User::findByEmail('admin@test.com');
        if (!$user) {
            throw new Exception("Could not find the created admin user.");
        }

        // Check the user's permissions
        $permissions = Role::getPermissions($user->role_id);
        $has_permission = isset($permissions['annee_academique']) && in_array('manage', $permissions['annee_academique']);

        if ($has_permission) {
            echo "    [PASS] Admin has the 'annee_academique manage' permission.\n";
        } else {
            echo "    [FAIL] Admin is missing the 'annee_academique manage' permission.\n";
            $db->rollBack();
            exit(1);
        }

        echo "All tests passed!\n";
        $db->rollBack(); // Rollback to leave the database clean

    } catch (Exception $e) {
        echo "    [FAIL] Test failed with exception: " . $e->getMessage() . "\n";
        $db->rollBack();
        exit(1);
    }
}

// Run the test
run_test();

?>