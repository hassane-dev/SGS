<?php
// db/migrations/20240115_10_create_personnel_cycles.php

function migrate_10($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. personnel_cycles_assignments table
    $sql_assignments = "CREATE TABLE IF NOT EXISTS personnel_cycles_assignments (
        id $pk,
        personnel_id INT NOT NULL,
        cycle_id INT NOT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE DEFAULT NULL,
        actif TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
        FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE CASCADE,
        UNIQUE (personnel_id, cycle_id, date_debut)
    ) $fk_ref;";

    $db->exec($sql_assignments);
    echo "Migration 10: Table personnel_cycles_assignments OK.\n";
}
