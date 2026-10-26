<?php
/**
 * Migration 34: Fix and unify sys_idempotency_keys table schema.
 * Ensures route, user_id, and response_payload columns exist on sys_idempotency_keys
 * for pre-existing installations where sys_idempotency_keys was created from db/schema.sql.
 */

function migrate_34($db) {
    echo "Running Migration 34: Fix sys_idempotency_keys schema...\n";
    $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

    // Helper function to check if column exists
    $hasColumn = function($table, $column) use ($db, $isSqlite) {
        try {
            if ($isSqlite) {
                $stmt = $db->query("PRAGMA table_info(`$table`)");
                if ($stmt) {
                    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (strtolower($col['name'] ?? '') === strtolower($column)) {
                            $stmt->closeCursor();
                            return true;
                        }
                    }
                    $stmt->closeCursor();
                }
            } else {
                $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                if ($stmt && $stmt->fetch()) {
                    $stmt->closeCursor();
                    return true;
                }
            }
        } catch (Exception $e) {
            // Ignore error
        }
        return false;
    };

    // Ensure table exists
    $tableExists = false;
    try {
        if ($isSqlite) {
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sys_idempotency_keys'");
            $tableExists = (bool)($stmt && $stmt->fetch());
        } else {
            $stmt = $db->query("SHOW TABLES LIKE 'sys_idempotency_keys'");
            $tableExists = (bool)($stmt && $stmt->fetch());
        }
    } catch (Exception $e) {
        $tableExists = false;
    }

    if (!$tableExists) {
        if ($isSqlite) {
            $db->exec("CREATE TABLE IF NOT EXISTS sys_idempotency_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                idempotency_key VARCHAR(128) NOT NULL,
                scope VARCHAR(100) DEFAULT NULL,
                request_hash VARCHAR(64) DEFAULT NULL,
                response_code INT DEFAULT NULL,
                response_body TEXT DEFAULT NULL,
                route VARCHAR(255) DEFAULT NULL,
                user_id INT DEFAULT NULL,
                response_payload TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );");
        } else {
            $db->exec("CREATE TABLE IF NOT EXISTS sys_idempotency_keys (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                idempotency_key VARCHAR(128) NOT NULL,
                scope VARCHAR(100) DEFAULT NULL,
                request_hash VARCHAR(64) DEFAULT NULL,
                response_code INT DEFAULT NULL,
                response_body JSON NULL,
                route VARCHAR(255) DEFAULT NULL,
                user_id INT DEFAULT NULL,
                response_payload TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_sys_idemp_key (idempotency_key, scope)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
        echo "Migration 34: Created sys_idempotency_keys table.\n";
        return;
    }

    // Add missing columns idempotently
    $columnsToAdd = [
        'route' => 'VARCHAR(255) DEFAULT NULL',
        'user_id' => 'INT DEFAULT NULL',
        'response_payload' => 'TEXT DEFAULT NULL',
        'scope' => 'VARCHAR(100) DEFAULT NULL',
        'request_hash' => 'VARCHAR(64) DEFAULT NULL',
        'response_code' => 'INT DEFAULT NULL',
        'response_body' => $isSqlite ? 'TEXT DEFAULT NULL' : 'JSON DEFAULT NULL'
    ];

    foreach ($columnsToAdd as $col => $def) {
        if (!$hasColumn('sys_idempotency_keys', $col)) {
            $db->exec("ALTER TABLE sys_idempotency_keys ADD COLUMN $col $def;");
            echo "Migration 34: Added column '$col' to sys_idempotency_keys.\n";
        }
    }

    // Helper function to verify if a MySQL column is in a compliant state
    $isColumnCompliant = function($colName) use ($db) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM `sys_idempotency_keys` LIKE '$colName'");
            $info = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($stmt) $stmt->closeCursor();

            if (!$info) return false;

            $isNullable = strtoupper($info['Null'] ?? '') === 'YES';
            $defaultVal = strtoupper((string)($info['Default'] ?? ''));

            if ($colName === 'created_at') {
                $hasCurrentTimestamp = (strpos($defaultVal, 'CURRENT_TIMESTAMP') !== false);
                $isNotNull = strtoupper($info['Null'] ?? '') === 'NO';
                return $isNotNull && $hasCurrentTimestamp;
            }

            if (in_array($colName, ['scope', 'request_hash', 'response_code', 'route'], true)) {
                return $isNullable;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    };

    // Ensure column definitions, nullabilities, and defaults are harmonized individually on pre-existing MySQL tables
    if (!$isSqlite) {
        $modifications = [
            'scope' => 'ALTER TABLE sys_idempotency_keys MODIFY COLUMN scope VARCHAR(100) DEFAULT NULL;',
            'request_hash' => 'ALTER TABLE sys_idempotency_keys MODIFY COLUMN request_hash VARCHAR(64) DEFAULT NULL;',
            'response_code' => 'ALTER TABLE sys_idempotency_keys MODIFY COLUMN response_code INT DEFAULT NULL;',
            'route' => 'ALTER TABLE sys_idempotency_keys MODIFY COLUMN route VARCHAR(255) DEFAULT NULL;',
            'created_at' => 'ALTER TABLE sys_idempotency_keys MODIFY COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;'
        ];

        foreach ($modifications as $colName => $sql) {
            try {
                $db->exec($sql);
            } catch (Exception $e) {
                // If ALTER fails, check if the column is already compliant (e.g. already altered or matching)
                if (!$isColumnCompliant($colName)) {
                    throw new RuntimeException("Migration 34: DDL modification failed and column '$colName' is NOT in a compliant state: " . $e->getMessage(), 0, $e);
                }
            }
        }
    }

    echo "Migration 34 completed successfully.\n";
}
