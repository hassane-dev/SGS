<?php
// db/migrations/20240115_19_add_classe_id_to_uk_eval_occ.php

/**
 * Migration 19: Add classe_id to uk_eval_occ unique index on evaluations table.
 * Ensures an evaluation note is uniquely identified by:
 * (eleve_id, classe_id, matiere_id, sequence_id, annee_academique_id, type_evaluation_id, numero_evaluation)
 */
function migrate_19($db) {
    echo "Running Migration 19: Add classe_id to uk_eval_occ on evaluations table...\n";

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    try {
        if ($isSqlite) {
            // Drop old index if exists and recreate new index on SQLite
            $db->exec("DROP INDEX IF EXISTS `uk_eval_occ`");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS `uk_eval_occ` ON `evaluations` (`eleve_id`, `classe_id`, `matiere_id`, `sequence_id`, `annee_academique_id`, `type_evaluation_id`, `numero_evaluation`)");
        } else {
            // MySQL / MariaDB
            // Check if index exists
            $chkOld = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'evaluations' AND INDEX_NAME = 'uk_eval_occ'");
            $chkOld->execute();
            if ($chkOld->fetchColumn() > 0) {
                $db->exec("ALTER TABLE `evaluations` DROP INDEX `uk_eval_occ`");
            }

            // Add new composite unique key including classe_id
            $db->exec("ALTER TABLE `evaluations` ADD UNIQUE KEY `uk_eval_occ` (`eleve_id`, `classe_id`, `matiere_id`, `sequence_id`, `annee_academique_id`, `type_evaluation_id`, `numero_evaluation`)");
        }
        echo "Migration 19: Unique constraint uk_eval_occ updated successfully with classe_id.\n";
    } catch (PDOException $e) {
        error_log("Note on Migration 19 (uk_eval_occ): " . $e->getMessage());
        echo "Migration 19 notice: " . $e->getMessage() . "\n";
    }
}
?>