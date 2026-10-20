<?php

/**
 * Test Suite: Cycle Textual Niveau Boundaries & Form Inputs
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
require_once __DIR__ . '/../src/controllers/CycleController.php';

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
    'permissions' => [['resource' => 'cycle', 'action' => 'manage']],
    'auth_version' => 1,
    'authorized_cycles' => [1, 2, 99881, 99882]
];
$_SESSION['lycee_id'] = 1;

echo "RUNNING SUITE: Cycle Textual Niveau Boundaries & Form Inputs Test\n";

// ----------------------------------------------------
// TEST 1: Inspect Create & Edit View Templates
// ----------------------------------------------------
echo "\n--- TEST 1: Form Views HTML Input Attributes ---\n";

$createViewContent = file_get_contents(__DIR__ . '/../src/views/cycles/create.php');
$editViewContent = file_get_contents(__DIR__ . '/../src/views/cycles/edit.php');

if (strpos($createViewContent, 'type="text" name="niveau_debut"') !== false &&
    strpos($createViewContent, 'type="text" name="niveau_fin"') !== false) {
    echo "  [PASS] create.php uses type=\"text\" for niveau_debut and niveau_fin.\n";
} else {
    echo "  [FAIL] create.php does NOT use type=\"text\" for niveau_debut and niveau_fin!\n";
    exit(1);
}

if (strpos($createViewContent, 'type="number" name="niveau_debut"') === false &&
    strpos($createViewContent, 'type="number" name="niveau_fin"') === false) {
    echo "  [PASS] create.php does NOT contain type=\"number\" for niveau_debut or niveau_fin.\n";
} else {
    echo "  [FAIL] create.php still contains type=\"number\"!\n";
    exit(1);
}

if (strpos($editViewContent, 'type="text" name="niveau_debut"') !== false &&
    strpos($editViewContent, 'type="text" name="niveau_fin"') !== false) {
    echo "  [PASS] edit.php uses type=\"text\" for niveau_debut and niveau_fin.\n";
} else {
    echo "  [FAIL] edit.php does NOT use type=\"text\" for niveau_debut and niveau_fin!\n";
    exit(1);
}

if (strpos($editViewContent, 'type="number" name="niveau_debut"') === false &&
    strpos($editViewContent, 'type="number" name="niveau_fin"') === false) {
    echo "  [PASS] edit.php does NOT contain type=\"number\" for niveau_debut or niveau_fin.\n";
} else {
    echo "  [FAIL] edit.php still contains type=\"number\"!\n";
    exit(1);
}

// ----------------------------------------------------
// TEST 2: Single Source of Truth, Accent Stripping & Strict Numeric Rejection
// ----------------------------------------------------
echo "\n--- TEST 2: SSoT Normalization & Strict Level Resolution ---\n";

$levelVariations = [
    ' 6e ' => 1,
    '6ème' => 1,
    '5EME' => 2,
    ' 4e ' => 3,
    '3ème' => 4,
    'Seconde' => 5,
    ' 2nd ' => 5,
    '2nde' => 5,
    '1ere' => 6,
    '1Ère' => 6,
    'Tle' => 7,
    'TERMINALE' => 7
];

foreach ($levelVariations as $input => $expectedOrder) {
    $resolved = Classe::resolveLevelOrder($input);
    if ($resolved === $expectedOrder) {
        echo "  [PASS] '$input' resolved to order $expectedOrder.\n";
    } else {
        echo "  [FAIL] '$input' resolved to " . var_export($resolved, true) . ", expected $expectedOrder!\n";
        exit(1);
    }
}

// Test rejection of arbitrary numbers and unknown strings
$invalidLevels = ['99', '999', 'InconnuLevel', '10', 'ABC'];
foreach ($invalidLevels as $inv) {
    if (Classe::resolveLevelOrder($inv) === null) {
        echo "  [PASS] Invalid/unrecognized level '$inv' strictly resolved to null.\n";
    } else {
        echo "  [FAIL] Invalid level '$inv' did NOT resolve to null!\n";
        exit(1);
    }
}

// Test inverted range check
$startInverted = Classe::resolveLevelOrder('Terminale');
$endInverted = Classe::resolveLevelOrder('Seconde');
if ($startInverted > $endInverted) {
    echo "  [PASS] Inverted range 'Terminale' -> 'Seconde' correctly identified ($startInverted > $endInverted).\n";
} else {
    echo "  [FAIL] Inverted range check failed!\n";
    exit(1);
}

// ----------------------------------------------------
// TEST 3: CycleController Partial & Invalid Boundary Validation
// ----------------------------------------------------
echo "\n--- TEST 3: CycleController Partial & Invalid Boundary Validation ---\n";

$cycleController = new CycleController();

// 3a. Partial configuration (only debut provided)
unset($_SESSION['error_message']);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'nom_cycle' => 'Cycle Partial Test',
    'niveau_debut' => '6e',
    'niveau_fin' => ''
];

ob_start();
$cycleController->store();
ob_end_clean();

if (isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'laisser les deux champs vides') !== false) {
    echo "  [PASS] Partial configuration (only debut) correctly rejected with error message.\n";
} else {
    echo "  [FAIL] Partial configuration was NOT rejected! Session error: " . ($_SESSION['error_message'] ?? 'NONE') . "\n";
    exit(1);
}

// 3b. Invalid level string (arbitrary number 99)
unset($_SESSION['error_message']);
$_POST = [
    'nom_cycle' => 'Cycle Arbitrary Number Test',
    'niveau_debut' => '6e',
    'niveau_fin' => '99'
];

ob_start();
$cycleController->store();
ob_end_clean();

if (isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'pas valides ou reconnues') !== false) {
    echo "  [PASS] Invalid level '99' correctly rejected by CycleController.\n";
} else {
    echo "  [FAIL] Invalid level '99' was NOT rejected! Session error: " . ($_SESSION['error_message'] ?? 'NONE') . "\n";
    exit(1);
}

// 3c. Inverted range (Terminale -> Seconde)
unset($_SESSION['error_message']);
$_POST = [
    'nom_cycle' => 'Cycle Inverted Range Test',
    'niveau_debut' => 'Terminale',
    'niveau_fin' => 'Seconde'
];

ob_start();
$cycleController->store();
ob_end_clean();

if (isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'pas valides ou reconnues') !== false) {
    echo "  [PASS] Inverted range 'Terminale' -> 'Seconde' correctly rejected by CycleController.\n";
} else {
    echo "  [FAIL] Inverted range was NOT rejected! Session error: " . ($_SESSION['error_message'] ?? 'NONE') . "\n";
    exit(1);
}

// ----------------------------------------------------
// TEST 4: Cycle Model Persistence & Range Generation
// ----------------------------------------------------
echo "\n--- TEST 4: Cycle Model Persistence & Range Generation ---\n";

$db->exec("DELETE FROM cycles WHERE nom_cycle LIKE '%Test Textual%' OR id_cycle IN (99881, 99882)");

// Insert college cycle with ' 6e ' -> ' 3ème ' (with spaces and variants)
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (99881, 'Cycle College Dynamic', 1, 0, ' 6e ', ' 3ème ')");

$_GET['cycle_id'] = 99881;
$_GET['lycee_id'] = 1;

$controller = new ClasseController();
ob_start();
$controller->getNiveauxForCycle();
$outputJson = ob_get_clean();
$niveauxRes = json_decode($outputJson, true);

$expectedCollege = ['6e', '5e', '4e', '3e'];
if ($niveauxRes === $expectedCollege) {
    echo "  [PASS] getNiveauxForCycle generated canonical range for ' 6e ' -> ' 3ème ': " . json_encode($niveauxRes) . "\n";
} else {
    echo "  [FAIL] getNiveauxForCycle failed! Expected " . json_encode($expectedCollege) . " but got " . json_encode($outputJson) . "\n";
    exit(1);
}

// Insert lycee cycle with ' 2nd ' -> ' Tle '
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (99882, 'Cycle Lycee Dynamic', 1, 1, ' 2nd ', ' Tle ')");

$_GET['cycle_id'] = 99882;
$_GET['lycee_id'] = 1;

ob_start();
$controller->getNiveauxForCycle();
$outputJsonLycee = ob_get_clean();
$niveauxLyceeRes = json_decode($outputJsonLycee, true);

$expectedLycee = ['2nde', '1ère', 'Terminale'];
if ($niveauxLyceeRes === $expectedLycee) {
    echo "  [PASS] getNiveauxForCycle generated canonical range for ' 2nd ' -> ' Tle ': " . json_encode($niveauxLyceeRes) . "\n";
} else {
    echo "  [FAIL] getNiveauxForCycle failed! Expected " . json_encode($expectedLycee) . " but got " . json_encode($outputJsonLycee) . "\n";
    exit(1);
}

// Clean up
$db->exec("DELETE FROM cycles WHERE id_cycle IN (99881, 99882)");

echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
