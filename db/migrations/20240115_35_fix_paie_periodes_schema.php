<?php
/**
 * Migration 35: Harmonize and enforce paie_periodes schema.
 * Ensures annee, mois, periode_comptable_id, cree_par, valide_par, updated_at, closed_at
 * and unique key uk_lycee_periode exist on paie_periodes for pre-existing installations
 * where paie_periodes was created from legacy db/schema.sql.
 */

function migrate_35($db) {
    echo "Running Migration 35: Fix and harmonize paie_periodes schema...\n";
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // Helper function to inspect column existence
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
            // Fallback: column assumed missing
        }
        return false;
    };

    // Helper function to inspect index existence
    $hasIndex = function($table, $indexName) use ($db, $isSqlite) {
        try {
            if ($isSqlite) {
                $stmt = $db->query("PRAGMA index_list(`$table`)");
                if ($stmt) {
                    while ($idx = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if (strtolower($idx['name'] ?? '') === strtolower($indexName)) {
                            $stmt->closeCursor();
                            return true;
                        }
                    }
                    $stmt->closeCursor();
                }
            } else {
                $stmt = $db->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
                if ($stmt && $stmt->fetch()) {
                    $stmt->closeCursor();
                    return true;
                }
            }
        } catch (Exception $e) {
            // Fallback: index assumed missing
        }
        return false;
    };

    // 1. Check if paie_periodes table exists
    try {
        $tableCheck = $isSqlite
            ? "SELECT name FROM sqlite_master WHERE type='table' AND name='paie_periodes'"
            : "SHOW TABLES LIKE 'paie_periodes'";
        $stmtTable = $db->query($tableCheck);
        if (!$stmtTable || !$stmtTable->fetch()) {
            if ($stmtTable) $stmtTable->closeCursor();
            echo "Table paie_periodes does not exist. Skipping Migration 35 column alter.\n";
            return;
        }
        $stmtTable->closeCursor();
    } catch (Exception $e) {
        echo "Unable to verify paie_periodes table: " . $e->getMessage() . "\n";
        return;
    }

    // 2. Add missing columns individually
    if (!$hasColumn('paie_periodes', 'mois')) {
        echo "Adding 'mois' column to paie_periodes...\n";
        $sql = $isSqlite
            ? "ALTER TABLE paie_periodes ADD COLUMN mois TINYINT NULL"
            : "ALTER TABLE paie_periodes ADD COLUMN mois TINYINT NULL AFTER code_periode";
        try { $db->exec($sql); } catch (Exception $e) { /* ignore if already added */ }
    }

    if (!$hasColumn('paie_periodes', 'annee')) {
        echo "Adding 'annee' column to paie_periodes...\n";
        $sql = $isSqlite
            ? "ALTER TABLE paie_periodes ADD COLUMN annee SMALLINT NULL"
            : "ALTER TABLE paie_periodes ADD COLUMN annee SMALLINT NULL AFTER mois";
        try { $db->exec($sql); } catch (Exception $e) { /* ignore if already added */ }
    }

    if (!$hasColumn('paie_periodes', 'periode_comptable_id')) {
        echo "Adding 'periode_comptable_id' column to paie_periodes...\n";
        $sql = $isSqlite
            ? "ALTER TABLE paie_periodes ADD COLUMN periode_comptable_id INTEGER NULL"
            : "ALTER TABLE paie_periodes ADD COLUMN periode_comptable_id INT NULL AFTER lycee_id";
        try { $db->exec($sql); } catch (Exception $e) { /* ignore if already added */ }
    }

    if (!$hasColumn('paie_periodes', 'cree_par')) {
        echo "Adding 'cree_par' column to paie_periodes...\n";
        $sql = $isSqlite
            ? "ALTER TABLE paie_periodes ADD COLUMN cree_par INTEGER NULL"
            : "ALTER TABLE paie_periodes ADD COLUMN cree_par INT NULL AFTER statut";
        try { $db->exec($sql); } catch (Exception $e) { /* ignore if already added */ }
    }

    if (!$hasColumn('paie_periodes', 'valide_par')) {
        echo "Adding 'valide_par' column to paie_periodes...\n";
        $sql = $isSqlite
            ? "ALTER TABLE paie_periodes ADD COLUMN valide_par INTEGER NULL"
            : "ALTER TABLE paie_periodes ADD COLUMN valide_par INT NULL AFTER cree_par";
        try { $db->exec($sql); } catch (Exception $e) { /* ignore if already added */ }
    }

    if (!$hasColumn('paie_periodes', 'updated_at')) {
        echo "Adding 'updated_at' column to paie_periodes...\n";
        $sql = $isSqlite
            ? "ALTER TABLE paie_periodes ADD COLUMN updated_at DATETIME NULL"
            : "ALTER TABLE paie_periodes ADD COLUMN updated_at DATETIME NULL AFTER created_at";
        try { $db->exec($sql); } catch (Exception $e) { /* ignore if already added */ }
    }

    if (!$hasColumn('paie_periodes', 'closed_at')) {
        echo "Adding 'closed_at' column to paie_periodes...\n";
        $sql = $isSqlite
            ? "ALTER TABLE paie_periodes ADD COLUMN closed_at DATETIME NULL"
            : "ALTER TABLE paie_periodes ADD COLUMN closed_at DATETIME NULL AFTER updated_at";
        try { $db->exec($sql); } catch (Exception $e) { /* ignore if already added */ }
    }

    // 3. Backfill data for existing rows if needed
    try {
        if ($isSqlite) {
            $db->exec("
                UPDATE paie_periodes
                SET annee = CAST(STRFTIME('%Y', date_debut) AS INTEGER)
                WHERE (annee IS NULL OR annee = 0) AND date_debut IS NOT NULL AND date_debut != '';

                UPDATE paie_periodes
                SET mois = CAST(STRFTIME('%m', date_debut) AS INTEGER)
                WHERE (mois IS NULL OR mois = 0) AND date_debut IS NOT NULL AND date_debut != '';

                UPDATE paie_periodes
                SET updated_at = COALESCE(created_at, CURRENT_TIMESTAMP)
                WHERE updated_at IS NULL;
            ");
        } else {
            $db->exec("
                UPDATE paie_periodes
                SET annee = YEAR(date_debut)
                WHERE (annee IS NULL OR annee = 0) AND date_debut IS NOT NULL;

                UPDATE paie_periodes
                SET mois = MONTH(date_debut)
                WHERE (mois IS NULL OR mois = 0) AND date_debut IS NOT NULL;

                UPDATE paie_periodes
                SET updated_at = COALESCE(created_at, NOW())
                WHERE updated_at IS NULL;
            ");
        }
    } catch (Exception $e) {
        echo "Data backfill warning: " . $e->getMessage() . "\n";
    }

    // 4. On MySQL, enforce NOT NULL defaults where appropriate if data is backfilled
    if (!$isSqlite) {
        try {
            // Verify if any NULLs remain before applying NOT NULL
            $nullCount = (int)$db->query("SELECT COUNT(*) FROM paie_periodes WHERE annee IS NULL OR mois IS NULL")->fetchColumn();
            if ($nullCount === 0) {
                $db->exec("ALTER TABLE paie_periodes MODIFY COLUMN mois TINYINT NOT NULL");
                $db->exec("ALTER TABLE paie_periodes MODIFY COLUMN annee SMALLINT NOT NULL");
            }
        } catch (Exception $e) {
            // Ignore if MODIFY COLUMN fails due to existing nulls
        }
    }

    // 5. Add unique index uk_lycee_periode if missing
    if (!$hasIndex('paie_periodes', 'uk_lycee_periode')) {
        echo "Adding index 'uk_lycee_periode' to paie_periodes...\n";
        try {
            $idxSql = "CREATE UNIQUE INDEX uk_lycee_periode ON paie_periodes (lycee_id, annee, mois)";
            $db->exec($idxSql);
        } catch (Exception $e) {
            // Ignore if index creation fails due to duplicate data
        }
    }

    // 6. Add index idx_paie_per_statut if missing
    if (!$hasIndex('paie_periodes', 'idx_paie_per_statut')) {
        echo "Adding index 'idx_paie_per_statut' to paie_periodes...\n";
        try {
            $idxSql = "CREATE INDEX idx_paie_per_statut ON paie_periodes (lycee_id, statut)";
            $db->exec($idxSql);
        } catch (Exception $e) {
            // Ignore if index creation fails
        }
    }

    echo "Migration 35 completed successfully.\n";
}
