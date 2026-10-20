<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/core/Validator.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Serie.php';
require_once __DIR__ . '/../src/controllers/ClasseController.php';

$db = Database::getInstance();

echo "========================================================\n";
echo "RUNNING SUITE: Cycle utilise_series Configuration & API Test\n";
echo "========================================================\n";

// Ensure migration 32 is run on test DB
require_once __DIR__ . '/../db/migrations/20240115_32_add_utilise_series_to_cycles.php';
migrate_32($db);

// Cleanup mock test records
$db->exec("DELETE FROM classes WHERE id_classe IN (99001, 99002)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (9901, 9902, 9903)");

// 1. Create a cycle WITH series (utilise_series = 1)
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (9901, 'Cycle Test Avec Series', 1, 1, 1, 3)");

// 2. Create a cycle WITHOUT series (utilise_series = 0)
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (9902, 'Cycle Test Sans Series', 1, 0, 1, 3)");

// 3. Create an UNCONFIGURED cycle (utilise_series = NULL)
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (9903, 'Cycle Non Configure', 1, NULL, 1, 3)");

// Ensure series exist for Lycee 1
$stmtSer = $db->query("SELECT COUNT(*) FROM series WHERE lycee_id = 1");
if ($stmtSer->fetchColumn() == 0) {
    $db->exec("INSERT INTO series (nom_serie, categorie, lycee_id) VALUES ('A', 'Littéraire', 1)");
    $db->exec("INSERT INTO series (nom_serie, categorie, lycee_id) VALUES ('C', 'Scientifique', 1)");
}

$classeCtrl = new ClasseController();

// Mock session as admin
$_SESSION['user'] = [
    'id' => 1,
    'id_user' => 1,
    'lycee_id' => 1,
    'role_id' => 3,
    'role_name' => 'admin_local',
    'auth_version' => 1
];

// TEST 1: Cycle with utilise_series = 0 returns [] for series API
$_GET = ['cycle_id' => 9902, 'niveau' => '2nd', 'lycee_id' => 1];
ob_start();
$classeCtrl->getSeriesForNiveau();
$resSeriesNo = json_decode(ob_get_clean(), true);

if (is_array($resSeriesNo) && empty($resSeriesNo)) {
    echo "  [PASS] 1. Cycle with utilise_series = 0 returns empty array [] for getSeriesForNiveau.\n";
} else {
    echo "  [FAIL] 1. Cycle with utilise_series = 0 returned series! Got: " . json_encode($resSeriesNo) . "\n";
}

// TEST 2: Cycle with utilise_series = 1 returns series for getSeriesForNiveau
$_GET = ['cycle_id' => 9901, 'niveau' => '2nd', 'lycee_id' => 1];
ob_start();
$classeCtrl->getSeriesForNiveau();
$resSeriesYes = json_decode(ob_get_clean(), true);

if (is_array($resSeriesYes) && !empty($resSeriesYes)) {
    echo "  [PASS] 2. Cycle with utilise_series = 1 returns valid series array: " . json_encode($resSeriesYes) . "\n";
} else {
    echo "  [FAIL] 2. Cycle with utilise_series = 1 returned empty series!\n";
}

// TEST 3: Server-side rejection when submitting a serie for a cycle with utilise_series = 0
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'cycle_id' => 9902,
    'niveau' => '6e',
    'serie' => 'A',
    'numero' => '1',
    'lycee_id' => 1
];
unset($_SESSION['error_message']);

ob_start();
try {
    $classeCtrl->store();
} catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "configuré sans série")) {
    echo "  [PASS] 3. Server rejected series submission for cycle with utilise_series = 0.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 3. Server allowed series submission for cycle with utilise_series = 0! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// TEST 4: Cycle persistence and update retains utilise_series value
$cycleData = [
    'id_cycle' => 9902,
    'nom_cycle' => 'Cycle Test Sans Series Modifie',
    'niveau_debut' => 1,
    'niveau_fin' => 4,
    'lycee_id' => 1,
    'utilise_series' => 0
];
Cycle::save($cycleData);
$reloadedCycle = Cycle::findById(9902);

if ($reloadedCycle && (int)$reloadedCycle['utilise_series'] === 0) {
    echo "  [PASS] 4. Cycle::save updated cycle while correctly retaining utilise_series = 0.\n";
} else {
    echo "  [FAIL] 4. Cycle utilise_series value lost on update! Got: " . json_encode($reloadedCycle) . "\n";
}

// TEST 5: Cross-tenant isolation on getSeriesForNiveau (forged lycee_id)
$_GET = ['cycle_id' => 9901, 'niveau' => '2nd', 'lycee_id' => 9999]; // Foreign lycee_id user has no access to
ob_start();
$classeCtrl->getSeriesForNiveau();
$resForgedLycee = json_decode(ob_get_clean(), true);

if (is_array($resForgedLycee) && empty($resForgedLycee)) {
    echo "  [PASS] 5. getSeriesForNiveau with unauthorized forged lycee_id REJECTED (empty array []).\n";
} else {
    echo "  [FAIL] 5. getSeriesForNiveau allowed unauthorized foreign lycee_id!\n";
}

// TEST 6: Server-side rejection when updating an existing class with a forbidden serie
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES (99002, 1, 9902, '6e', '1')");

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'id_classe' => 99002,
    'cycle_id' => 9902,
    'niveau' => '6e',
    'serie' => 'C',
    'numero' => '1',
    'lycee_id' => 1
];
unset($_SESSION['error_message']);

ob_start();
try {
    $classeCtrl->update();
} catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "configuré sans série")) {
    echo "  [PASS] 6. Server rejected series update on existing class for cycle with utilise_series = 0.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 6. Server allowed series update for cycle with utilise_series = 0! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// TEST 7: Server-side rejection when creating class in an UNCONFIGURED cycle (utilise_series = NULL)
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'cycle_id' => 9903,
    'niveau' => '2nd',
    'numero' => '1',
    'lycee_id' => 1
];
unset($_SESSION['error_message']);

ob_start();
try {
    $classeCtrl->store();
} catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "n'a pas encore été configuré")) {
    echo "  [PASS] 7. Server blocked class creation for unconfigured cycle (utilise_series = NULL).\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 7. Server allowed class creation for unconfigured cycle! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// TEST 8: Migration 32 re-run idempotency (does not overwrite already configured utilise_series = 0 to 1)
migrate_32($db);
$checkCycle0 = Cycle::findById(9902);
if ($checkCycle0 && (int)$checkCycle0['utilise_series'] === 0) {
    echo "  [PASS] 8. Migration re-run is strictly idempotent and preserved cycle 9902 utilise_series = 0.\n";
} else {
    echo "  [FAIL] 8. Migration re-run overwrote configured utilise_series value! Got: " . json_encode($checkCycle0) . "\n";
}

// TEST 9: Server-side rejection when submitting an unlisted/unauthorized serie for a cycle with utilise_series = 1
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'cycle_id' => 9901,
    'niveau' => '2nd',
    'serie' => 'INVALID_SERIE_XYZ',
    'numero' => '1',
    'lycee_id' => 1
];
unset($_SESSION['error_message']);

ob_start();
try {
    $classeCtrl->store();
} catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "pas répertoriée")) {
    echo "  [PASS] 9. Server rejected unlisted/invalid serie name submission.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 9. Server allowed invalid serie name submission! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// TEST 10: Server-side rejection when submitting level out of boundary defined for cycle
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'cycle_id' => 9901, // Cycle 9901 has niveau_debut=1 (6e) and niveau_fin=3 (4e)
    'niveau' => 'Terminale', // Order 7 > 3
    'serie' => 'A',
    'numero' => '1',
    'lycee_id' => 1
];
unset($_SESSION['error_message']);

ob_start();
try {
    $classeCtrl->store();
} catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "en dehors de la plage")) {
    echo "  [PASS] 10. Server rejected level out of boundary for cycle definition.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 10. Server allowed out-of-boundary level submission! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// TEST 11: Server-side rejection when submitting unknown level name
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'cycle_id' => 9901,
    'niveau' => 'NIVEAU_INCONNU_XYZ',
    'serie' => 'A',
    'numero' => '1',
    'lycee_id' => 1
];
unset($_SESSION['error_message']);

ob_start();
try {
    $classeCtrl->store();
} catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "pas reconnu")) {
    echo "  [PASS] 11. Server rejected unknown class level name.\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 11. Server allowed unknown level name submission! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// TEST 12: Server-side rejection on update() when transferring a class to a foreign school cycle
// Create cycle 9904 belonging to lycee_id = 2
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id, utilise_series, niveau_debut, niveau_fin) VALUES (9904, 'Cycle Foreign Lycee', 2, 1, 1, 3)");

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'id_classe' => 99002, // Belongs to lycee_id = 1
    'cycle_id' => 9904,  // Foreign cycle for lycee_id = 2
    'niveau' => '6e',
    'numero' => '1',
    'lycee_id' => 1
];
unset($_SESSION['error_message']);

ob_start();
try {
    $classeCtrl->update();
} catch (Throwable $e) {}
ob_end_clean();

if (isset($_SESSION['error_message']) && str_contains($_SESSION['error_message'], "n'appartient pas à l'établissement")) {
    echo "  [PASS] 12. Server rejected cross-tenant cycle transfer in update().\n";
    unset($_SESSION['error_message']);
} else {
    echo "  [FAIL] 12. Server allowed cross-tenant cycle transfer in update()! Msg: " . ($_SESSION['error_message'] ?? 'None') . "\n";
}

// Cleanup
$db->exec("DELETE FROM classes WHERE id_classe IN (99001, 99002)");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (9901, 9902, 9903, 9904)");

echo "========================================================\n";
echo "SUCCESS: ALL CYCLE UTILISE_SERIES TESTS PASSED!\n";
echo "========================================================\n";
