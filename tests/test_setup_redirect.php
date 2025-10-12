<?php

// A simple test to verify that the setup redirect logic works correctly.

// 1. Set up the environment
// Override the default database user for testing purposes
define('DB_USER', 'jules');
define('DB_PASS', '');

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Lycee.php';

function run_test() {
    echo "Running test: test_setup_redirect...\n";

    // --- Test Case 1: No schools exist, /setup should be accessible ---
    echo "  Case 1: No schools in DB. Expect /setup to be allowed.\n";

    // Clear the lycees table to simulate a fresh install
    $db = Database::getInstance();
    $db->exec("DELETE FROM lycees");

    // Simulate a request to /setup
    $_SERVER['REQUEST_URI'] = '/setup';

    // Capture the output of index.php
    ob_start();
    include __DIR__ . '/../public/index.php';
    $output = ob_get_clean();

    // In a real test framework, we'd check the HTTP status code.
    // Here, we check if the output contains the setup page content.
    if (strpos($output, 'Bienvenue dans l\'installation') !== false) {
        echo "    [PASS] Correctly showed setup page.\n";
    } else {
        echo "    [FAIL] Did not show setup page when it should have.\n";
        exit(1);
    }


    // --- Test Case 2: School exists, /setup should redirect ---
    echo "  Case 2: School exists in DB. Expect /setup to redirect to /login.\n";

    // Add a school to the database
    Lycee::save(['nom_lycee' => 'Test Lycee', 'type_lycee' => 'prive']);

    // Simulate a request to /setup again
    $_SERVER['REQUEST_URI'] = '/setup';

    // This is tricky without a real web server context. We can't easily test
    // the header() redirect directly. However, our script should exit after the
    // header call, so we can check if the output is empty.
    ob_start();
    include __DIR__ . '/../public/index.php';
    $output = ob_get_clean();

    // A successful redirect will have no output because of the exit() call.
    if (empty($output)) {
        echo "    [PASS] Redirected as expected (no output).\n";
    } else {
        echo "    [FAIL] Did not redirect to login page.\n";
        exit(1);
    }

    echo "All tests passed!\n";

    // Clean up
    $db->exec("DELETE FROM lycees");
}

// Run the test
run_test();

?>