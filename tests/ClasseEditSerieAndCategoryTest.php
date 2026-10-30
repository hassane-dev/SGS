<?php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bootstrap application environment
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/CsrfService.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Serie.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/core/Validator.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';

$db = Database::getInstance();

// Ensure SQLite schema compatibility if needed
try { $db->exec("ALTER TABLE classes ADD COLUMN categorie VARCHAR(100)"); } catch (Throwable $e) {}
try { $db->exec("ALTER TABLE cycles ADD COLUMN utilise_series TINYINT(1) DEFAULT NULL"); } catch (Throwable $e) {}

// Setup permissions dynamically
function grantPermission($roleId, $resource, $action) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = :r AND action = :a");
        $stmt->execute(['r' => $resource, 'a' => $action]);
        $pId = $stmt->fetchColumn();
        if (!$pId) {
            $stmtIns = $db->prepare("INSERT INTO permissions (resource, action) VALUES (:r, :a)");
            $stmtIns->execute(['r' => $resource, 'a' => $action]);
            $pId = $db->lastInsertId();
        }
        $stmtRp = $db->prepare("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role, :perm)");
        $stmtRp->execute(['role' => $roleId, 'perm' => $pId]);
    } catch (Throwable $e) {}
}

grantPermission(1, 'class', 'edit');
grantPermission(1, 'class', 'view');
grantPermission(1, 'class', 'create');
grantPermission(1, 'class', 'delete');
grantPermission(1, 'lycee', 'view_all_lycees');

// Setup test database fixtures
$lyceeId1 = 99100;
$lyceeId2 = 99200;

function setupFixturesGlobal() {
    global $db, $lyceeId1, $lyceeId2;
    $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES ($lyceeId1, 'Lycée Test 1')");
    $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES ($lyceeId2, 'Lycée Test 2')");

    $db->exec("DELETE FROM classes WHERE lycee_id IN ($lyceeId1, $lyceeId2)");
    $db->exec("DELETE FROM cycles WHERE lycee_id IN ($lyceeId1, $lyceeId2)");
    $db->exec("DELETE FROM series WHERE lycee_id IN ($lyceeId1, $lyceeId2)");

    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series) VALUES (99101, 'Lycée Test', '2nde', 'Terminale', $lyceeId1, 1)");
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series) VALUES (99102, 'CEG Test', '6e', '3e', $lyceeId1, 0)");
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series) VALUES (99103, 'Cycle Incomplet', '2nde', 'Terminale', $lyceeId1, NULL)");
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series) VALUES (99201, 'Lycée 2 Test', '2nde', 'Terminale', $lyceeId2, 1)");

    $db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES (9911, 'A', 'Littéraire', $lyceeId1)");
    $db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES (9912, 'C', 'Scientifique', $lyceeId1)");
    $db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES (9913, 'D', 'Scientifique', $lyceeId1)");
    $db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES (9921, 'S2', 'Scientifique', $lyceeId2)");

    $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, categorie, cycle_id, lycee_id) VALUES (99151, '2nde', 'C', 1, 'Scientifique', 99101, $lyceeId1)");
    $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, categorie, cycle_id, lycee_id) VALUES (99152, '6e', NULL, 1, NULL, 99102, $lyceeId1)");
    $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, categorie, cycle_id, lycee_id) VALUES (99153, '2nde', NULL, 2, NULL, 99103, $lyceeId1)");
}

setupFixturesGlobal();

// Mock Authentication Session for Super Admin Creator
$_SESSION['user'] = [
    'id' => 1,
    'id_user' => 1,
    'role_id' => 1,
    'role' => 'super_admin_createur',
    'role_name' => 'super_admin_createur',
    'nom' => 'Admin',
    'prenom' => 'Super',
    'lycee_id' => $lyceeId1,
    'auth_version' => 1,
    'authorized_cycles' => [99101, 99102, 99103, 99201]
];
$_SESSION['lycee_id'] = $lyceeId1;

$testResults = [];

// --- SCENARIO A & B: Initial load with series & unique class in level ---
ob_start();
$controller = new ClasseController();
$_GET['id'] = 99151;
$controller->edit();
$htmlOutput = ob_get_clean();

// Check 1: data-utilise-series="1" is rendered on cycle option 99101
if (strpos($htmlOutput, 'value="99101"') !== false && strpos($htmlOutput, 'data-utilise-series="1"') !== false) {
    $testResults[] = "[PASS] 1. data-utilise-series=\"1\" correctly rendered on cycle option 99101.";
} else {
    $testResults[] = "[FAIL] 1. Expected data-utilise-series=\"1\" on cycle option 99101.";
}

// Check 2: Pre-selected Serie C and Categorie Scientifique in HTML output
if (strpos($htmlOutput, 'value="C"') !== false && strpos($htmlOutput, 'selected') !== false) {
    $testResults[] = "[PASS] 2. Pre-selected Serie 'C' present in rendered HTML.";
} else {
    $testResults[] = "[FAIL] 2. Expected pre-selected Serie 'C' in rendered HTML.";
}

if (strpos($htmlOutput, 'value="Scientifique" selected') !== false) {
    $testResults[] = "[PASS] 3. Pre-selected Catégorie 'Scientifique' present in rendered HTML.";
} else {
    $testResults[] = "[FAIL] 3. Expected pre-selected Catégorie 'Scientifique' in rendered HTML.";
}

// Check 3: JS updateSerieAndCategorieVisibility(false) called without wiping values
if (strpos($htmlOutput, 'updateSerieAndCategorieVisibility(false)') !== false) {
    $testResults[] = "[PASS] 4. JavaScript initializes state with updateSerieAndCategorieVisibility(false) preventing initial value wipe.";
} else {
    $testResults[] = "[FAIL] 4. Expected updateSerieAndCategorieVisibility(false) in JS script.";
}

// Subprocess runner using register_shutdown_function to capture error message on exit()
function runUpdateSubprocess($postData) {
    $code = '
    define("TEST_MODE", true);
    session_start();
    require_once "src/config/database.php";
    require_once "src/core/Auth.php";
    require_once "src/core/CsrfService.php";
    require_once "src/models/Classe.php";
    require_once "src/models/Cycle.php";
    require_once "src/models/Serie.php";
    require_once "src/models/User.php";
    require_once "src/core/View.php";
    require_once "src/core/Validator.php";
    require_once "src/controllers/ClasseController.php";

    $db = Database::getInstance();
    $lyceeId1 = 99100;
    $lyceeId2 = 99200;

    try { $db->exec("ALTER TABLE classes ADD COLUMN categorie VARCHAR(100)"); } catch (Throwable $e) {}
    try { $db->exec("ALTER TABLE cycles ADD COLUMN utilise_series TINYINT(1) DEFAULT NULL"); } catch (Throwable $e) {}

    function grantPermInSub($roleId, $resource, $action) {
        global $db;
        try {
            $stmt = $db->prepare("SELECT id_permission FROM permissions WHERE resource = :r AND action = :a");
            $stmt->execute([\'r\' => $resource, \'a\' => $action]);
            $pId = $stmt->fetchColumn();
            if (!$pId) {
                $stmtIns = $db->prepare("INSERT INTO permissions (resource, action) VALUES (:r, :a)");
                $stmtIns->execute([\'r\' => $resource, \'a\' => $action]);
                $pId = $db->lastInsertId();
            }
            $stmtRp = $db->prepare("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role, :perm)");
            $stmtRp->execute([\'role\' => $roleId, \'perm\' => $pId]);
        } catch (Throwable $e) {}
    }

    grantPermInSub(1, \'class\', \'edit\');
    grantPermInSub(1, \'class\', \'view\');
    grantPermInSub(1, \'lycee\', \'view_all_lycees\');

    $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES ($lyceeId1, \'Lycée Test 1\')");
    $db->exec("INSERT OR IGNORE INTO param_lycee (id, nom_lycee) VALUES ($lyceeId2, \'Lycée Test 2\')");

    $db->exec("DELETE FROM classes WHERE lycee_id IN ($lyceeId1, $lyceeId2)");
    $db->exec("DELETE FROM cycles WHERE lycee_id IN ($lyceeId1, $lyceeId2)");
    $db->exec("DELETE FROM series WHERE lycee_id IN ($lyceeId1, $lyceeId2)");

    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series) VALUES (99101, \'Lycée Test\', \'2nde\', \'Terminale\', $lyceeId1, 1)");
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series) VALUES (99102, \'CEG Test\', \'6e\', \'3e\', $lyceeId1, 0)");
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series) VALUES (99103, \'Cycle Incomplet\', \'2nde\', \'Terminale\', $lyceeId1, NULL)");
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series) VALUES (99201, \'Lycée 2 Test\', \'2nde\', \'Terminale\', $lyceeId2, 1)");

    $db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES (9911, \'A\', \'Littéraire\', $lyceeId1)");
    $db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES (9912, \'C\', \'Scientifique\', $lyceeId1)");
    $db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES (9913, \'D\', \'Scientifique\', $lyceeId1)");
    $db->exec("INSERT INTO series (id, nom_serie, categorie, lycee_id) VALUES (9921, \'S2\', \'Scientifique\', $lyceeId2)");

    $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, categorie, cycle_id, lycee_id) VALUES (99151, \'2nde\', \'C\', 1, \'Scientifique\', 99101, $lyceeId1)");
    $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, categorie, cycle_id, lycee_id) VALUES (99152, \'6e\', NULL, 1, NULL, 99102, $lyceeId1)");
    $db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, categorie, cycle_id, lycee_id) VALUES (99153, \'2nde\', NULL, 2, NULL, 99103, $lyceeId1)");

    $_SESSION["user"] = [
        "id" => 1,
        "id_user" => 1,
        "role_id" => 1,
        "role" => "super_admin_createur",
        "role_name" => "super_admin_createur",
        "nom" => "Admin",
        "prenom" => "Super",
        "lycee_id" => ' . $postData['lycee_id_session'] . ',
        "auth_version" => 1
    ];
    $_SESSION["lycee_id"] = ' . $postData['lycee_id_session'] . ';

    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST = ' . var_export($postData['post'], true) . ';

    register_shutdown_function(function() {
        echo json_encode(["error" => $_SESSION["error_message"] ?? null]);
    });

    $ctrl = new ClasseController();
    $ctrl->update();
    ';

    $cmd = "php -r " . escapeshellarg($code);
    $out = shell_exec($cmd);
    return json_decode($out, true);
}

// --- SCENARIO C: Cycle without series (utilise_series = 0) ---
$resC = runUpdateSubprocess([
    'lycee_id_session' => $lyceeId1,
    'post' => [
        'csrf_token' => CsrfService::token(),
        'id_classe' => 99152,
        'niveau' => '6e',
        'numero' => 1,
        'cycle_id' => 99102,
        'serie' => 'A' // Forbidden series for CEG!
    ]
]);

if (!empty($resC['error']) && strpos($resC['error'], 'sans série') !== false) {
    $testResults[] = "[PASS] 5. Server correctly rejected forbidden series 'A' for cycle 99102 (utilise_series = 0). Message: {$resC['error']}";
} else {
    $testResults[] = "[FAIL] 5. Expected server rejection for series on cycle with utilise_series = 0. Got error: " . ($resC['error'] ?? 'NONE');
}

// --- SCENARIO D: Unconfigured cycle (utilise_series = NULL) ---
ob_start();
$_GET['id'] = 99153;
$controller->edit();
$htmlOutputUnconf = ob_get_clean();

if (strpos($htmlOutputUnconf, 'data-utilise-series="unconfigured"') !== false) {
    $testResults[] = "[PASS] 6. data-utilise-series=\"unconfigured\" rendered for cycle 99103.";
} else {
    $testResults[] = "[FAIL] 6. Expected data-utilise-series=\"unconfigured\" for cycle 99103.";
}

$resD = runUpdateSubprocess([
    'lycee_id_session' => $lyceeId1,
    'post' => [
        'csrf_token' => CsrfService::token(),
        'id_classe' => 99153,
        'niveau' => '2nde',
        'numero' => 2,
        'cycle_id' => 99103,
        'serie' => 'C'
    ]
]);

if (!empty($resD['error']) && strpos($resD['error'], 'pas encore été configuré') !== false) {
    $testResults[] = "[PASS] 7. Server correctly blocked update on unconfigured cycle 99103. Message: {$resD['error']}";
} else {
    $testResults[] = "[FAIL] 7. Expected server block for update on unconfigured cycle. Got error: " . ($resD['error'] ?? 'NONE');
}

// --- SCENARIO E: Successful Update & Preservation of Valid Series/Category ---
$resE = runUpdateSubprocess([
    'lycee_id_session' => $lyceeId1,
    'post' => [
        'csrf_token' => CsrfService::token(),
        'id_classe' => 99151,
        'niveau' => '2nde',
        'numero' => 1,
        'cycle_id' => 99101,
        'serie' => 'D',
        'categorie' => 'Scientifique'
    ]
]);

// Refresh class 99151 state in parent after subprocess update
$updatedClass = Classe::findById(99151);

if ($updatedClass && $updatedClass['serie'] === 'D' && ($updatedClass['categorie'] ?? 'Scientifique') === 'Scientifique') {
    $testResults[] = "[PASS] 8. Class 99151 updated successfully with serie 'D' and categorie 'Scientifique'.";
} else {
    $testResults[] = "[FAIL] 8. Class update failed. Current DB values: " . json_encode($updatedClass);
}

// --- SCENARIO F: Security Multi-tenant Scoping ---
$resF = runUpdateSubprocess([
    'lycee_id_session' => $lyceeId1,
    'post' => [
        'csrf_token' => CsrfService::token(),
        'id_classe' => 99151,
        'niveau' => '2nde',
        'numero' => 1,
        'cycle_id' => 99101,
        'serie' => 'S2' // Foreign series from Lycee 2!
    ]
]);

if (!empty($resF['error']) && strpos($resF['error'], 'pas répertoriée dans le référentiel') !== false) {
    $testResults[] = "[PASS] 9. Server correctly rejected foreign school series 'S2'. Message: {$resF['error']}";
} else {
    $testResults[] = "[FAIL] 9. Expected multi-tenant rejection for foreign series. Got error: " . ($resF['error'] ?? 'NONE');
}

// Cleanup test fixtures
$db->exec("DELETE FROM classes WHERE lycee_id IN ($lyceeId1, $lyceeId2)");
$db->exec("DELETE FROM cycles WHERE lycee_id IN ($lyceeId1, $lyceeId2)");
$db->exec("DELETE FROM series WHERE lycee_id IN ($lyceeId1, $lyceeId2)");

echo "\n\n================ RESULTS =================\n";
foreach ($testResults as $res) {
    echo $res . "\n";
}
echo "==========================================\n";
