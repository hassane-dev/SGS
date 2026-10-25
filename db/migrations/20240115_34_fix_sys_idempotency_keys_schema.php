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

    // If scope was defined as NOT NULL in pre-existing MySQL tables, relax it to DEFAULT NULL
    if (!$isSqlite) {
        try {
            $db->exec("ALTER TABLE sys_idempotency_keys MODIFY COLUMN scope VARCHAR(100) DEFAULT NULL;");
            $db->exec("ALTER TABLE sys_idempotency_keys MODIFY COLUMN request_hash VARCHAR(64) DEFAULT NULL;");
            $db->exec("ALTER TABLE sys_idempotency_keys MODIFY COLUMN response_code INT DEFAULT NULL;");
        } catch (Exception $e) {
            // Ignore if column modification is unsupported or already nullable
        }
    }

    echo "Migration 34 completed successfully.\n";
}
