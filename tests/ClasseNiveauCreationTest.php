<?php

/**
 * Test Suite: Classe Niveau Creation & Unlocked Cascade
 */

define('TEST_MODE', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/Validator.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';

$db = Database::getInstance();

require_once __DIR__ . '/../db/migrations/20240115_32_add_utilise_series_to_cycles.php';
migrate_32($db);

// Mock session as super_admin_createur (role_id = 1)
$_SESSION['user'] = [
    'id' => 1,
    'id_user' => 1,
    'role_id' => 1,
    'role' => 'super_admin_createur',
    'role_name' => 'super_admin_createur',
    'lycee_id' => 1,
    'permissions' => [['resource' => 'class', 'action' => 'manage_all'], ['resource' => 'lycee', 'action' => 'view_all_lycees']],
    'auth_version' => 1,
    'authorized_cycles' => [1, 99891, 99892]
];
$_SESSION['lycee_id'] = 1;

echo "=== RUNNING TEST SUITE: ClasseNiveauCreationTest ===\n\n";

$db->exec("DELETE FROM classes WHERE cycle_id IN (99891, 99892) OR lycee_id = 9989");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (99891, 99892) OR lycee_id = 9989");

// Insert test cycles
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (99891, 'Cycle CEG Sans Classes', 1, 0, '6e', '3e')");
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (99892, 'Cycle Lycée Avec Classe', 1, 1, '2nde', 'Terminale')");

$controller = new ClasseController();

// TEST 1: Unselected cycle (GET cycle_id empty)
echo "--- TEST 1: GET /classes/get-niveaux with no cycle_id ---\n";
$_GET = ['lycee_id' => 1];
ob_start();
$controller->getNiveauxForCycle();
$output1 = ob_get_clean();
$data1 = json_decode($output1, true);

if (empty($data1)) {
    echo "  [PASS] Unselected cycle returns empty array.\n";
} else {
    echo "  [FAIL] Expected empty array for unselected cycle, got: " . $output1 . "\n";
    exit(1);
}

// TEST 2: Selected authorized cycle with NO classes in DB (should return canonical levels 6e..3e)
echo "\n--- TEST 2: GET /classes/get-niveaux for new cycle with NO classes in DB ---\n";
$_GET = ['cycle_id' => 99891, 'lycee_id' => 1];
ob_start();
$controller->getNiveauxForCycle();
$output2 = ob_get_clean();
$data2 = json_decode($output2, true);

$expected2 = ['6e', '5e', '4e', '3e'];
if ($data2 === $expected2) {
    echo "  [PASS] Correctly generated canonical level bounds for new cycle: " . json_encode($data2) . "\n";
} else {
    echo "  [FAIL] Expected " . json_encode($expected2) . " but got: " . $output2 . "\n";
    exit(1);
}

// TEST 3: Selected authorized cycle with existing class in DB
echo "\n--- TEST 3: GET /classes/get-niveaux for cycle with existing class ---\n";
$db->exec("INSERT INTO classes (niveau, serie, numero, cycle_id, lycee_id) VALUES ('2nde', 'A', '1', 99892, 1)");

$_GET = ['cycle_id' => 99892, 'lycee_id' => 1];
ob_start();
$controller->getNiveauxForCycle();
$output3 = ob_get_clean();
$data3 = json_decode($output3, true);

$expected3 = ['2nde', '1ère', 'Terminale'];
if ($data3 === $expected3) {
    echo "  [PASS] Correctly generated levels for cycle with existing classes: " . json_encode($data3) . "\n";
} else {
    echo "  [FAIL] Expected " . json_encode($expected3) . " but got: " . $output3 . "\n";
    exit(1);
}

// TEST 4: Unauthorized cross-tenant cycle access
echo "\n--- TEST 4: GET /classes/get-niveaux for unauthorized cycle ---\n";
$_GET = ['cycle_id' => 99891, 'lycee_id' => 9989];

// Set restricted session without view_all_lycees permission
$_SESSION['user']['role'] = 'enseignant';
$_SESSION['user']['role_name'] = 'enseignant';
$_SESSION['user']['permissions'] = [['resource' => 'class', 'action' => 'create']];

ob_start();
$controller->getNiveauxForCycle();
$output4 = ob_get_clean();
$data4 = json_decode($output4, true);

if (empty($data4)) {
    echo "  [PASS] Unauthorized cycle/tenant correctly rejected with empty array.\n";
} else {
    echo "  [FAIL] Expected empty array for unauthorized access, got: " . $output4 . "\n";
    exit(1);
}

// Cleanup isolated test data
$db->exec("DELETE FROM classes WHERE cycle_id IN (99891, 99892)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (99891, 99892)");

echo "\nALL TESTS PASSED SUCCESSFULLY IN ClasseNiveauCreationTest!\n";
