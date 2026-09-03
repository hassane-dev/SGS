<?php
// db/migrations/20240115_18_add_nombre_evaluation_param.php

function migrate_18($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // 1. Add nombre_evaluation column if missing
    if ($isSqlite) {
        $stmt = $db->query("PRAGMA table_info(param_type_evaluation)");
        $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $colNames = array_column($cols, 'name');
        if (!in_array('nombre_evaluation', $colNames, true)) {
            $db->exec("ALTER TABLE param_type_evaluation ADD COLUMN nombre_evaluation INT NOT NULL DEFAULT 1");
        }
    } else {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'param_type_evaluation'
              AND COLUMN_NAME = 'nombre_evaluation'
        ");
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec("ALTER TABLE `param_type_evaluation` ADD COLUMN `nombre_evaluation` INT NOT NULL DEFAULT 1");
        }
    }

    // 2. Perform explicit backfill mapping for existing evaluation types
    try {
        // 'devoir' -> 2
        $db->exec("UPDATE param_type_evaluation SET nombre_evaluation = 2 WHERE code = 'devoir'");
        // 'interrogation' -> 3
        $db->exec("UPDATE param_type_evaluation SET nombre_evaluation = 3 WHERE code = 'interrogation'");
        // 'composition' -> 1
        $db->exec("UPDATE param_type_evaluation SET nombre_evaluation = 1 WHERE code = 'composition'");
        // fallback safety for any other or null value
        $db->exec("UPDATE param_type_evaluation SET nombre_evaluation = 1 WHERE nombre_evaluation IS NULL OR nombre_evaluation NOT IN (1, 2, 3)");
    } catch (Exception $e) {
        error_log("Error backfilling nombre_evaluation: " . $e->getMessage());
    }

    echo "Migration 18: Add nombre_evaluation to param_type_evaluation & explicit backfill OK.\n";
}
