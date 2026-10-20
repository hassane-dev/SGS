<?php
/**
 * Migration 32: Add utilise_series column to cycles table (3-state support: NULL = unconfigured, 0 = no series, 1 = uses series).
 */

function migrate_32($db) {
    echo "Running Migration 32: Add utilise_series to cycles table...\n";
    $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

    // 1. Add utilise_series column if not exists (nullable DEFAULT NULL)
    $columnExists = false;
    if ($isSqlite) {
        $stmt = $db->prepare("PRAGMA table_info(`cycles`)");
        $stmt->execute();
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if ($col['name'] === 'utilise_series') {
                $columnExists = true;
                break;
            }
        }
    } else {
        $stmt = $db->query("SHOW COLUMNS FROM `cycles` LIKE 'utilise_series'");
        if ($stmt && $stmt->fetch()) {
            $columnExists = true;
        }
    }

    if (!$columnExists) {
        if ($isSqlite) {
            $db->exec("ALTER TABLE `cycles` ADD COLUMN `utilise_series` INTEGER DEFAULT NULL;");
        } else {
            $db->exec("ALTER TABLE `cycles` ADD COLUMN `utilise_series` TINYINT(1) DEFAULT NULL;");
        }
        echo "Migration 32: Added nullable column utilise_series to table cycles.\n";
    }

    // 2. Initialize unconfigured existing cycles ONLY when objective historical evidence exists.
    // Idempotent: Never overwrite cycles that already have an explicit value (utilise_series IS NOT NULL).
    $stmtUnconfigured = $db->query("SELECT id_cycle FROM cycles WHERE utilise_series IS NULL");
    if ($stmtUnconfigured) {
        $unconfiguredCycles = $stmtUnconfigured->fetchAll(PDO::FETCH_ASSOC);
        $stmtUnconfigured->closeCursor();

        foreach ($unconfiguredCycles as $cy) {
            $cyId = (int)$cy['id_cycle'];

            // Objective data check: Check if any existing classes in this cycle explicitly have a series assigned
            $stmtSeries = $db->prepare("SELECT COUNT(*) FROM classes WHERE cycle_id = :id AND serie IS NOT NULL AND TRIM(serie) <> ''");
            $stmtSeries->execute(['id' => $cyId]);
            $hasSeriesClasses = ((int)$stmtSeries->fetchColumn()) > 0;

            if ($hasSeriesClasses) {
                // Objective evidence: classes with series exist, so cycle uses series (1)
                $stmtUp = $db->prepare("UPDATE cycles SET utilise_series = 1 WHERE id_cycle = :id AND utilise_series IS NULL");
                $stmtUp->execute(['id' => $cyId]);
            }
            // Otherwise leave as NULL (unconfigured) for explicit admin configuration without guessing from names
        }
    }

    echo "Migration 32 completed successfully.\n";
}
