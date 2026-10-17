<?php
/**
 * Migration 31: Add cycle_id to frais table and backfill non-ambiguous historical rules.
 */

function migrate_31($db) {
    echo "Running Migration 31: Add cycle_id to frais table...\n";
    $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

    // 1. Add cycle_id column if not exists
    $columnExists = false;
    if ($isSqlite) {
        $stmt = $db->prepare("PRAGMA table_info(`frais`)");
        $stmt->execute();
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if ($col['name'] === 'cycle_id') {
                $columnExists = true;
                break;
            }
        }
    } else {
        $stmt = $db->query("SHOW COLUMNS FROM `frais` LIKE 'cycle_id'");
        if ($stmt && $stmt->fetch()) {
            $columnExists = true;
        }
    }

    if (!$columnExists) {
        if ($isSqlite) {
            $db->exec("ALTER TABLE `frais` ADD COLUMN `cycle_id` INTEGER DEFAULT NULL REFERENCES `cycles`(`id_cycle`) ON DELETE SET NULL;");
        } else {
            $db->exec("ALTER TABLE `frais` ADD COLUMN `cycle_id` INT DEFAULT NULL, ADD CONSTRAINT `fk_frais_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `cycles`(`id_cycle`) ON DELETE SET NULL;");
        }
        echo "Migration 31: Added column cycle_id to table frais.\n";
    }

    // 2. Add index idx_frais_lycee_cycle
    if ($isSqlite) {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_frais_lycee_cycle ON frais (lycee_id, cycle_id, annee_academique_id);");
    } else {
        $idxStmt = $db->query("SHOW INDEX FROM `frais` WHERE Key_name = 'idx_frais_lycee_cycle'");
        if (!$idxStmt->fetch()) {
            $db->exec("ALTER TABLE `frais` ADD INDEX `idx_frais_lycee_cycle` (`lycee_id`, `cycle_id`, `annee_academique_id`);");
        }
    }
    echo "Migration 31: Index idx_frais_lycee_cycle verified/created.\n";

    // 3. Backfill non-ambiguous historical tariff records
    $stmtUnlinked = $db->query("SELECT id_frais, lycee_id, cycle FROM frais WHERE cycle IS NOT NULL AND TRIM(cycle) != '' AND cycle_id IS NULL");
    if ($stmtUnlinked) {
        $unlinkedRows = $stmtUnlinked->fetchAll(PDO::FETCH_ASSOC);
        $stmtUnlinked->closeCursor();

        $updatedCount = 0;
        $ambiguousCount = 0;

        foreach ($unlinkedRows as $row) {
            $idFrais = (int)$row['id_frais'];
            $lyceeId = (int)$row['lycee_id'];
            $cycleName = trim((string)$row['cycle']);

            // Find matching cycles in the same school tenant
            $stmtMatch = $db->prepare("SELECT id_cycle FROM cycles WHERE (lycee_id = :lycee_id OR lycee_id IS NULL) AND LOWER(TRIM(nom_cycle)) = LOWER(:cycle_name)");
            $stmtMatch->execute(['lycee_id' => $lyceeId, 'cycle_name' => $cycleName]);
            $matchingCycleIds = array_map('intval', $stmtMatch->fetchAll(PDO::FETCH_COLUMN));

            if (count($matchingCycleIds) === 1) {
                // Non-ambiguous match
                $targetCycleId = $matchingCycleIds[0];
                $stmtUp = $db->prepare("UPDATE frais SET cycle_id = :cycle_id WHERE id_frais = :id_frais");
                $stmtUp->execute(['cycle_id' => $targetCycleId, 'id_frais' => $idFrais]);
                $updatedCount++;
            } else if (count($matchingCycleIds) > 1) {
                $ambiguousCount++;
                error_log("MIGRATION_31_WARNING: Tariff #{$idFrais} for school #{$lyceeId} with cycle string '{$cycleName}' has multiple matching cycle records (" . implode(',', $matchingCycleIds) . "). Preserved as unlinked for manual review.");
            }
        }
        echo "Migration 31: Backfilled {$updatedCount} historical tariff rules. ({$ambiguousCount} ambiguous records retained as NULL for safety).\n";
    }

    echo "Migration 31 completed successfully.\n";
}
