<?php
// db/migrations/20240115_31_migrate_frais_cycle_id.php

function migrate_31($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    if ($isSqlite) {
        // SQLite DDL Add Column & Index
        $stmt = $db->query("PRAGMA table_info(frais)");
        $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('cycle_id', $cols, true)) {
            $db->exec("ALTER TABLE frais ADD COLUMN cycle_id INTEGER NULL REFERENCES cycles(id_cycle) ON DELETE SET NULL;");
        }
        $db->exec("CREATE INDEX IF NOT EXISTS idx_frais_lycee_cycle ON frais(lycee_id, cycle_id, annee_academique_id);");
    } else {
        // MySQL/MariaDB DDL
        $stmt = $db->prepare("SHOW COLUMNS FROM `frais` LIKE 'cycle_id'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE `frais` ADD COLUMN `cycle_id` INT NULL;");
            try {
                $db->exec("ALTER TABLE `frais` ADD CONSTRAINT `fk_frais_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `cycles`(`id_cycle`) ON DELETE SET NULL ON UPDATE CASCADE;");
            } catch (Exception $e) {
                // FK constraint might already exist
            }
            try {
                $db->exec("CREATE INDEX `idx_frais_lycee_cycle` ON `frais`(`lycee_id`, `cycle_id`, `annee_academique_id`);");
            } catch (Exception $e) {
                // Index might already exist
            }
        }
    }

    echo "Migration 31: Column frais.cycle_id and index idx_frais_lycee_cycle verified successfully.\n";
}

// Standalone execution support
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/../../src/config/database.php';
    $db = Database::getInstance();
    migrate_31($db);
}
?>
