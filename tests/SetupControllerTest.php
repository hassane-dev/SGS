<?php

// Test for SetupController academic year bug
define('DB_USER', 'jules');
define('DB_PASS', '');
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/controllers/SetupController.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/ParametresGeneraux.php';


function run_test() {
    echo "Running test: SetupController Academic Year Handling...\n";

    // --- Test Case: Setup with a future academic year ---
    $test_year_label = "2027-2028";
    $expected_start_date = "2027-09-01";
    $expected_end_date = "2028-06-30";
    echo "  Case: Creates academic year '$test_year_label' correctly.\n";

    try {
        // 1. Set up the environment to mimic a POST request
        $_POST = [
            'install_mode' => 'single',
            'nom_lycee' => 'Lycee De Test',
            'type_lycee' => 'prive',
            'annee_academique' => $test_year_label,
            'admin_prenom' => 'Test',
            'admin_nom' => 'Admin',
            'admin_email' => 'test@admin.com',
            'admin_pass' => 'password123',
        ];

        // 2. Instantiate and run the controller's private method
        $controller = new SetupController();
        $reflection = new ReflectionClass('SetupController');
        $method = $reflection->getMethod('setupSingleSchool');
        $method->setAccessible(true);
        $method->invoke($controller, $_POST);

        // 3. Verify the result in the database
        $db = Database::getInstance();
        $latest_year_stmt = $db->query("SELECT * FROM annees_academiques ORDER BY id DESC LIMIT 1");
        $ac_year = $latest_year_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ac_year) {
            throw new Exception("Academic year was not created.");
        }

        $actual_start_date = $ac_year['date_debut'];
        $actual_end_date = $ac_year['date_fin'];

        // 4. Assertions
        if ($actual_start_date == $expected_start_date) {
            echo "    [PASS] Start date is correct: $actual_start_date\n";
        } else {
            echo "    [FAIL] Start date is incorrect. Expected: $expected_start_date, Got: $actual_start_date\n";
            exit(1);
        }

        if ($actual_end_date == $expected_end_date) {
            echo "    [PASS] End date is correct: $actual_end_date\n";
        } else {
            echo "    [FAIL] End date is incorrect. Expected: $expected_end_date, Got: $actual_end_date\n";
            exit(1);
        }

        echo "All assertions passed for this test!\n";

    } catch (Exception $e) {
        echo "    [FAIL] Test failed with exception: " . $e->getMessage() . "\n";
        exit(1);
    } finally {
        cleanup_db();
    }
}

// Clean up database before and after running test
function cleanup_db() {
    try {
        $db = Database::getInstance();
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $db->exec("DELETE FROM utilisateurs");
        $db->exec("DELETE FROM role_permissions");
        $db->exec("DELETE FROM roles WHERE id_role > 8"); // Keep seed roles
        $db->exec("DELETE FROM lycees");
        $db->exec("DELETE FROM annees_academiques");
        $db->exec("DELETE FROM parametres_generaux");
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    } catch (Exception $e) {
        // Ignore errors during cleanup, as tables might not exist on first run
    }
}

cleanup_db();
run_test();

?>