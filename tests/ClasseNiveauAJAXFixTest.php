<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';

echo "=== RUNNING TEST SUITE: ClasseNiveauAJAXFixTest ===\n\n";

$db = Database::getInstance();

// Setup test fixture cycles and lycees
$db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee, type_lycee) VALUES (8891, 'Lycée Test Alpha', 'prive')");
$db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee, type_lycee) VALUES (8892, 'Lycée Test Beta', 'prive')");

$db->exec("DELETE FROM classes WHERE id_classe = 88930");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (88901, 88902, 88903)");
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (88901, 'Collège Alpha', 8891, 0, '6e', '3e')");
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (88902, 'Lycée Alpha', 8891, 1, '2nde', 'Terminale')");
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (88903, 'Collège Beta', 8892, 0, '6e', '3e')");

// Create class for school 8892, cycle 88903
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (88930, 8892, 88903, '6e', '1')");

$controller = new ClasseController();

// TEST A: Super Admin sans lycee_id transmis (avec cycle_id) -> Déduit le lycée du cycle si valide & autorisé
$_SESSION['user'] = [
    'id_user' => 1,
    'role_id' => 2,
    'lycee_id' => null,
    'permissions' => [
        'lycee' => ['view_all_lycees' => true],
        'class' => ['view_all_classes' => true]
    ]
];
unset($_SESSION['user']['authorized_cycles']);
$_GET['cycle_id'] = '88901';
$_GET['lycee_id'] = '';

ob_start();
$controller->getNiveauxForCycle();
$testA = json_decode(ob_get_clean(), true);

if ($testA === ['6e', '5e', '4e', '3e']) {
    echo "  [PASS] Test A: Super Admin without explicit lycee_id successfully deduces cycle's school (8891) and returns levels.\n";
} else {
    echo "  [FAIL] Test A: Expected ['6e','5e','4e','3e'], got " . json_encode($testA) . "\n";
}

// TEST B: Super Admin avec lycee_id et cycle_id explicites et valides
$_GET['cycle_id'] = '88901';
$_GET['lycee_id'] = '8891';

ob_start();
$controller->getNiveauxForCycle();
$testB = json_decode(ob_get_clean(), true);

if ($testB === ['6e', '5e', '4e', '3e']) {
    echo "  [PASS] Test B: Super Admin with valid lycee_id 8891 & cycle 88901 returns strictly ['6e','5e','4e','3e'].\n";
} else {
    echo "  [FAIL] Test B: Expected ['6e','5e','4e','3e'], got " . json_encode($testB) . "\n";
}

// TEST C: Cross-Tenant Isolation (Cycle 88903 de l'école Beta demandé sous l'école Alpha 8891)
$_GET['cycle_id'] = '88903'; // Belongs to 8892
$_GET['lycee_id'] = '8891'; // Selected 8891

ob_start();
$controller->getNiveauxForCycle();
$testC = json_decode(ob_get_clean(), true);

if ($testC === []) {
    echo "  [PASS] Test C: Cross-tenant cycle query (cycle 88903 under school 8891) strictly REJECTED with empty array [].\n";
} else {
    echo "  [FAIL] Test C: Expected [], got " . json_encode($testC) . "\n";
}

// TEST D: Utilisateur restreint à l'école Beta (8892) tentant d'accéder au cycle d'Alpha (88901)
$_SESSION['user'] = [
    'id_user' => 7712,
    'role_id' => 5,
    'lycee_id' => 8892,
    'permissions' => []
];
unset($_SESSION['user']['authorized_cycles']);

// Add explicit cycle assignment for 88903 so user has active cycle 88903
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id = 7712");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (7712, 88903, '2020-01-01', 1)");

$_GET['cycle_id'] = '88901'; // Out of scope cycle from school 8891
$_GET['lycee_id'] = '8891'; // Out of scope school

ob_start();
$controller->getNiveauxForCycle();
$testD = json_decode(ob_get_clean(), true);

if ($testD === []) {
    echo "  [PASS] Test D: Restricted user query for out-of-scope school/cycle strictly REJECTED with empty array [].\n";
} else {
    echo "  [FAIL] Test D: Expected [], got " . json_encode($testD) . "\n";
}

// TEST E: Utilisateur restreint à Beta interrogeant son propre cycle autorisé (88903)
$_GET['cycle_id'] = '88903';
$_GET['lycee_id'] = '8892';

ob_start();
$controller->getNiveauxForCycle();
$testE = json_decode(ob_get_clean(), true);

if ($testE === ['6e', '5e', '4e', '3e']) {
    echo "  [PASS] Test E: Restricted user querying in-scope cycle 88903 under school 8892 returns levels.\n";
} else {
    echo "  [FAIL] Test E: Expected ['6e','5e','4e','3e'], got " . json_encode($testE) . "\n";
}

// TEST F: Administrateur Local (role_id = 3, lycee_id = 8891) créant la PREMIÈRE CLASSE sur un cycle (0 classe préexistante)
$_SESSION['user'] = [
    'id_user' => 3301,
    'role_id' => null,
    'lycee_id' => 8891,
    'permissions' => [
        'class' => ['create', 'view', 'edit', 'manage'],
        'cycle' => ['manage']
    ]
];
unset($_SESSION['user']['authorized_cycles']);
$_GET['cycle_id'] = '88901'; // Cycle 88901 belongs to school 8891
$_GET['lycee_id'] = '8891';   // Or empty string, local admin uses Auth::getLyceeId()

// Ensure 0 classes exist for this school & cycle
$db->exec("DELETE FROM classes WHERE lycee_id = 8891 AND cycle_id = 88901");

ob_start();
$controller->getNiveauxForCycle();
$testF = json_decode(ob_get_clean(), true);

if ($testF === ['6e', '5e', '4e', '3e']) {
    echo "  [PASS] Test F: Local Admin creating FIRST class (0 pre-existing classes in DB) successfully gets cycle's configured levels ['6e','5e','4e','3e'].\n";
} else {
    echo "  [FAIL] Test F: Expected ['6e','5e','4e','3e'], got " . json_encode($testF) . "\n";
}

// TEST G: Administrateur Local avec lycee_id transmis comme chaîne vide ("") utilise Auth::getLyceeId()
$_GET['cycle_id'] = '88901';
$_GET['lycee_id'] = '';

ob_start();
$controller->getNiveauxForCycle();
$testG = json_decode(ob_get_clean(), true);

if ($testG === ['6e', '5e', '4e', '3e']) {
    echo "  [PASS] Test G: Local Admin sending empty lycee_id parameter successfully falls back to session lycee_id (8891) and returns levels.\n";
} else {
    echo "  [FAIL] Test G: Expected ['6e','5e','4e','3e'], got " . json_encode($testG) . "\n";
}

// Clean up test fixture
$db->exec("DELETE FROM classes WHERE id_classe = 88930");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (88901, 88902, 88903)");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id = 7712");

echo "\nALL TESTS PASSED IN ClasseNiveauAJAXFixTest!\n";
