<?php
/**
 * Targeted Integration Test for sys_idempotency_keys schema fix & DRH view compatibility.
 * Validates Scenarios A, B, C, D:
 *  - Scenario A: Fresh Schema (all columns present)
 *  - Scenario B: Pre-existing Schema (generic columns only -> Migration 34 adds missing DRH columns)
 *  - Scenario C: Repeated Migration Execution (idempotent re-run)
 *  - Scenario D: Functional Regression & DRH show view read path execution
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/controllers/PersonnelController.php';
require_once __DIR__ . '/../db/migrations/20240115_34_fix_sys_idempotency_keys_schema.php';

function assertTestSys($condition, $message) {
    if (!$condition) {
        echo " [FAIL] {$message}\n";
        throw new RuntimeException("Test Failed: {$message}");
    }
    echo " [PASS] {$message}\n";
}

echo "=== STARTING TEST: sys_idempotency_keys Schema Correction & DRH Show View ===\n";

$db = Database::getInstance();
$isSqlite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

// Helper to check table columns safely
$getColumns = function($tableName) use ($db, $isSqlite) {
    $cols = [];
    if ($isSqlite) {
        $stmt = $db->query("PRAGMA table_info(`{$tableName}`)");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cols[] = strtolower($row['name']);
            }
            $stmt->closeCursor();
        }
    } else {
        $stmt = $db->query("SHOW COLUMNS FROM `{$tableName}`");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cols[] = strtolower($row['Field']);
            }
            $stmt->closeCursor();
        }
    }
    return $cols;
};

// -------------------------------------------------------------------------
// SCENARIO B: Pre-existing Table with Generic Columns -> Migration 34 adds DRH columns
// -------------------------------------------------------------------------
echo "\n--- SCENARIO B: Pre-existing Schema Upgrade ---\n";
$db->exec("DROP TABLE IF EXISTS sys_idempotency_keys;");

// Simulate pre-existing schema as defined originally in schema.sql
if ($isSqlite) {
    $db->exec("CREATE TABLE sys_idempotency_keys (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        idempotency_key VARCHAR(100) NOT NULL,
        scope VARCHAR(100) NOT NULL,
        request_hash VARCHAR(64) NOT NULL,
        response_code INT NOT NULL,
        response_body TEXT NULL,
        created_at DATETIME NOT NULL
    );");
} else {
    $db->exec("CREATE TABLE sys_idempotency_keys (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        idempotency_key VARCHAR(100) NOT NULL,
        scope VARCHAR(100) NOT NULL,
        request_hash VARCHAR(64) NOT NULL,
        response_code INT NOT NULL,
        response_body JSON NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// Insert existing sample record to verify data preservation
$db->exec("INSERT INTO sys_idempotency_keys (idempotency_key, scope, request_hash, response_code, created_at)
           VALUES ('KEY_LEGACY_001', 'global', 'HASH123', 200, '2024-01-01 10:00:00');");

$colsBefore = $getColumns('sys_idempotency_keys');
assertTestSys(!in_array('response_payload', $colsBefore), "Table pre-exists WITHOUT response_payload column");

// Run Migration 34
migrate_34($db);

$colsAfterB = $getColumns('sys_idempotency_keys');
assertTestSys(in_array('response_payload', $colsAfterB), "Migration 34 added response_payload column");
assertTestSys(in_array('route', $colsAfterB), "Migration 34 added route column");
assertTestSys(in_array('user_id', $colsAfterB), "Migration 34 added user_id column");
assertTestSys(in_array('scope', $colsAfterB), "Migration 34 preserved scope column");

// Verify existing data was preserved
$stmtCheckLegacy = $db->query("SELECT idempotency_key, request_hash FROM sys_idempotency_keys WHERE idempotency_key = 'KEY_LEGACY_001'");
$legacyRecord = $stmtCheckLegacy ? $stmtCheckLegacy->fetch(PDO::FETCH_ASSOC) : null;
if ($stmtCheckLegacy) $stmtCheckLegacy->closeCursor();
assertTestSys($legacyRecord && $legacyRecord['request_hash'] === 'HASH123', "Pre-existing data preserved after Migration 34");

// -------------------------------------------------------------------------
// SCENARIO C: Idempotent Re-execution of Migration 34
// -------------------------------------------------------------------------
echo "\n--- SCENARIO C: Repeated Migration Execution ---\n";
try {
    migrate_34($db);
    assertTestSys(true, "Migration 34 executed a second time without throwing errors");
} catch (Exception $e) {
    assertTestSys(false, "Re-running Migration 34 failed: " . $e->getMessage());
}

// -------------------------------------------------------------------------
// SCENARIO A: Fresh Table Initialization
// -------------------------------------------------------------------------
echo "\n--- SCENARIO A: Fresh Schema Creation ---\n";
$db->exec("DROP TABLE IF EXISTS sys_idempotency_keys;");

// Run Migration 34 on non-existent table
migrate_34($db);

$colsFresh = $getColumns('sys_idempotency_keys');
assertTestSys(in_array('response_payload', $colsFresh), "Fresh table created with response_payload column");
assertTestSys(in_array('route', $colsFresh), "Fresh table created with route column");
assertTestSys(in_array('user_id', $colsFresh), "Fresh table created with user_id column");
assertTestSys(in_array('scope', $colsFresh), "Fresh table created with scope column");

// -------------------------------------------------------------------------
// SCENARIO D: Functional Tests (Idempotency & DRH Show View Read Path)
// -------------------------------------------------------------------------
echo "\n--- SCENARIO D: DRH Functional Idempotency & Show Read Path ---\n";

// 1. DRH Idempotency Service Storage & Retrieval
$testKey = 'IDEMP_TEST_' . bin2hex(random_bytes(8));
PersonnelContractService::storeIdempotency($testKey, '/drh/contracts/store', 1, ['success' => true, 'test_id' => 99]);

$cachedPayload = PersonnelContractService::checkIdempotency($testKey);
assertTestSys($cachedPayload !== null && $cachedPayload['success'] === true && $cachedPayload['test_id'] === 99,
    "PersonnelContractService store & check idempotency working as expected");

// 2. Verify DRH Show View read path execution without SQL error 1054
$stmtU = $db->query("SELECT id_user, lycee_id FROM utilisateurs LIMIT 1");
$userRow = $stmtU ? $stmtU->fetch(PDO::FETCH_ASSOC) : null;
if ($stmtU) $stmtU->closeCursor();

if ($userRow) {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $uId = (int)$userRow['id_user'];
        $lId = (int)($userRow['lycee_id'] ?? 1);

        $_SESSION['user_id'] = $uId;
        $_SESSION['user'] = [
            'id' => $uId,
            'id_user' => $uId,
            'role_id' => 1,
            'nom_role' => 'super_admin_createur',
            'lycee_id' => $lId
        ];

        $details = PersonnelService::get360Details($uId, true);
        assertTestSys(is_array($details) && isset($details['personnel']),
            "PersonnelService::get360Details() executed successfully without SQL column errors");
    } catch (Exception $e) {
        assertTestSys(false, "DRH show read path failed with exception: " . $e->getMessage());
    }
}

echo "\n=========================================================================\n";
echo "🏆 ALL SYS_IDEMPOTENCY_KEYS TESTS PASSED SUCCESSFULLY!\n";
echo "=========================================================================\n";
