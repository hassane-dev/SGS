<?php
// db/migrations/20240115_20_create_bulletin_details_and_extend_bulletins.php

/**
 * Migration 20:
 * 1. Creates `bulletin_details` table to store itemized subject snapshots per official report card.
 * 2. Extends `bulletins` table with historical snapshot context fields:
 *    - classe_id
 *    - nom_classe_snapshot
 *    - effectif_classe
 *    - total_points
 *    - total_coefficients
 *    - moyenne_classe
 *    - rang_int
 *    - date_cloture
 *    - cloture_par_user_id
 */
function migrate_20($db) {
    echo "Running Migration 20: Creating bulletin_details and extending bulletins...\n";

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. Create bulletin_details
    try {
        if ($isSqlite) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS bulletin_details (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    bulletin_id INTEGER NOT NULL,
                    matiere_id INTEGER NOT NULL,
                    nom_matiere_snapshot VARCHAR(255) NOT NULL,
                    moyenne_matiere DECIMAL(5,2) NOT NULL,
                    coefficient_snapshot DECIMAL(4,2) NOT NULL,
                    points_ponderes DECIMAL(6,2) NOT NULL,
                    rang_matiere VARCHAR(20) NULL,
                    moyenne_classe_matiere DECIMAL(5,2) NULL,
                    appreciation_matiere TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (bulletin_id) REFERENCES bulletins(id) ON DELETE CASCADE,
                    FOREIGN KEY (matiere_id) REFERENCES matieres(id_matiere) ON DELETE RESTRICT,
                    UNIQUE (bulletin_id, matiere_id)
                );
            ");
        } else {
            $db->exec("
                CREATE TABLE IF NOT EXISTS bulletin_details (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    bulletin_id INT NOT NULL,
                    matiere_id INT NOT NULL,
                    nom_matiere_snapshot VARCHAR(255) NOT NULL,
                    moyenne_matiere DECIMAL(5,2) NOT NULL,
                    coefficient_snapshot DECIMAL(4,2) NOT NULL,
                    points_ponderes DECIMAL(6,2) NOT NULL,
                    rang_matiere VARCHAR(20) NULL,
                    moyenne_classe_matiere DECIMAL(5,2) NULL,
                    appreciation_matiere TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (bulletin_id) REFERENCES bulletins(id) ON DELETE CASCADE,
                    FOREIGN KEY (matiere_id) REFERENCES matieres(id_matiere) ON DELETE RESTRICT,
                    UNIQUE KEY uk_bulletin_matiere (bulletin_id, matiere_id)
                ) {$fk_ref};
            ");
        }
        echo "Migration 20: Table bulletin_details created or verified.\n";
    } catch (PDOException $e) {
        echo "Migration 20 notice (bulletin_details): " . $e->getMessage() . "\n";
    }

    // Helper to add column if not exists
    $addColumnIfNeeded = function($table, $column, $definition) use ($db, $isSqlite) {
        try {
            $exists = false;
            if ($isSqlite) {
                $stmt = $db->prepare("PRAGMA table_info(`$table`)");
                $stmt->execute();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                    if ($col['name'] === $column) {
                        $exists = true;
                        break;
                    }
                }
            } else {
                $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                if ($stmt && $stmt->fetch()) {
                    $exists = true;
                }
            }

            if (!$exists) {
                $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                echo "Migration 20: Added column `$column` to `$table`.\n";
            }
        } catch (PDOException $e) {
            echo "Migration 20 notice (column $column on $table): " . $e->getMessage() . "\n";
        }
    };

    // 2. Extend bulletins table
    $addColumnIfNeeded('bulletins', 'classe_id', 'INT NULL');
    $addColumnIfNeeded('bulletins', 'nom_classe_snapshot', 'VARCHAR(255) NULL');
    $addColumnIfNeeded('bulletins', 'effectif_classe', 'INT NULL');
    $addColumnIfNeeded('bulletins', 'total_points', 'DECIMAL(8,2) NULL');
    $addColumnIfNeeded('bulletins', 'total_coefficients', 'DECIMAL(6,2) NULL');
    $addColumnIfNeeded('bulletins', 'moyenne_classe', 'DECIMAL(5,2) NULL');
    $addColumnIfNeeded('bulletins', 'rang_int', 'INT NULL');
    $addColumnIfNeeded('bulletins', 'date_cloture', 'DATETIME NULL');
    $addColumnIfNeeded('bulletins', 'cloture_par_user_id', 'INT NULL');

    echo "Migration 20: bulletins table extension completed successfully.\n";
}
?>