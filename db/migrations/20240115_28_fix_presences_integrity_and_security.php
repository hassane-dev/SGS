<?php

function migrate_28($db) {
    echo "Running Migration 28: Fixing presences table uniqueness and integrity...\n";

    $isSqlite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

    // Helper to check column presence
    $hasColumn = function($tableName, $columnName) use ($db, $isSqlite) {
        if ($isSqlite) {
            $stmt = $db->query("PRAGMA table_info({$tableName})");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (strtolower($row['name']) === strtolower($columnName)) {
                    return true;
                }
            }
            return false;
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = :col
            ");
            $stmt->execute(['tbl' => $tableName, 'col' => $columnName]);
            return ((int)$stmt->fetchColumn() > 0);
        }
    };

    // Helper to check index presence
    $hasIndex = function($tableName, $indexName) use ($db, $isSqlite) {
        if ($isSqlite) {
            $stmt = $db->query("PRAGMA index_list({$tableName})");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (strtolower($row['name']) === strtolower($indexName)) {
                    return true;
                }
            }
            return false;
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND INDEX_NAME = :idx
            ");
            $stmt->execute(['tbl' => $tableName, 'idx' => $indexName]);
            return ((int)$stmt->fetchColumn() > 0);
        }
    };

    // 1. Add column matiere_key if not exists
    if (!$hasColumn('presences', 'matiere_key')) {
        if ($isSqlite) {
            $db->exec("ALTER TABLE presences ADD COLUMN matiere_key INT NOT NULL DEFAULT 0");
            $db->exec("UPDATE presences SET matiere_key = COALESCE(matiere_id, 0)");
        } else {
            $db->exec("ALTER TABLE presences ADD COLUMN matiere_key INT GENERATED ALWAYS AS (IFNULL(matiere_id, 0)) STORED");
        }
        echo "Migration 28: Added column `matiere_key` to `presences`.\n";
    }

    // 2. Clean up duplicate rows before creating unique index
    try {
        if ($isSqlite) {
            $db->exec("
                DELETE FROM presences
                WHERE id NOT IN (
                    SELECT MAX(id)
                    FROM presences
                    GROUP BY eleve_id, classe_id, date_presence, matiere_key, annee_academique_id
                )
            ");
        } else {
            $db->exec("
                DELETE p1 FROM presences p1
                INNER JOIN presences p2
                ON p1.eleve_id = p2.eleve_id
               AND p1.classe_id = p2.classe_id
               AND p1.date_presence = p2.date_presence
               AND p1.matiere_key = p2.matiere_key
               AND p1.annee_academique_id = p2.annee_academique_id
               AND p1.id < p2.id
            ");
        }
        echo "Migration 28: Cleaned up duplicate attendance records.\n";
    } catch (Exception $e) {
        echo "Migration 28 Warning (duplicate cleanup): " . $e->getMessage() . "\n";
    }

    // 3. Drop legacy index unique_presence_eleve_matiere_date if exists
    if ($hasIndex('presences', 'unique_presence_eleve_matiere_date')) {
        try {
            if ($isSqlite) {
                $db->exec("DROP INDEX IF EXISTS unique_presence_eleve_matiere_date");
            } else {
                $db->exec("ALTER TABLE presences DROP INDEX unique_presence_eleve_matiere_date");
            }
            echo "Migration 28: Dropped old index `unique_presence_eleve_matiere_date`.\n";
        } catch (Exception $e) {
            echo "Migration 28 Warning (drop old index): " . $e->getMessage() . "\n";
        }
    }

    // 4. Add new unique index uk_presence_eleve_context if not exists
    if (!$hasIndex('presences', 'uk_presence_eleve_context')) {
        try {
            if ($isSqlite) {
                $db->exec("CREATE UNIQUE INDEX uk_presence_eleve_context ON presences(eleve_id, classe_id, date_presence, matiere_key, annee_academique_id)");
            } else {
                $db->exec("ALTER TABLE presences ADD UNIQUE KEY uk_presence_eleve_context (eleve_id, classe_id, date_presence, matiere_key, annee_academique_id)");
            }
            echo "Migration 28: Added unique key `uk_presence_eleve_context`.\n";
        } catch (Exception $e) {
            echo "Migration 28 Warning (add new index): " . $e->getMessage() . "\n";
        }
    }

    echo "Migration 28 completed successfully.\n";
}

// Execute migration directly when called
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/../../src/config/database.php';
    migrate_28(Database::getInstance());
}
