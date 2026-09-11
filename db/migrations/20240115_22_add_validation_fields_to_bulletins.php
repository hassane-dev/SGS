<?php
// db/migrations/20240115_22_add_validation_fields_to_bulletins.php

/**
 * Migration 22:
 * Extends `bulletins` table with institutional validation tracking fields:
 * - `valide_le DATETIME NULL`
 * - `valide_par INT NULL` (FOREIGN KEY to `utilisateurs(id_user) ON DELETE SET NULL`)
 */
function migrate_22($db) {
    echo "Running Migration 22: Adding valide_le and valide_par to bulletins...\n";

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

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
                echo "Migration 22: Added column `$column` to `$table`.\n";
            }
        } catch (PDOException $e) {
            echo "Migration 22 notice (column $column on $table): " . $e->getMessage() . "\n";
        }
    };

    $addColumnIfNeeded('bulletins', 'valide_le', 'DATETIME NULL');
    $addColumnIfNeeded('bulletins', 'valide_par', 'INT NULL');

    // Add Foreign Key constraint for valide_par if MySQL
    if (!$isSqlite) {
        try {
            $stmtFk = $db->query("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'bulletins'
                  AND COLUMN_NAME = 'valide_par'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            if (!$stmtFk || !$stmtFk->fetch()) {
                $db->exec("ALTER TABLE `bulletins` ADD CONSTRAINT `fk_bulletins_valide_par` FOREIGN KEY (`valide_par`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL");
                echo "Migration 22: Added foreign key fk_bulletins_valide_par.\n";
            }
        } catch (PDOException $e) {
            echo "Migration 22 notice (fk_bulletins_valide_par): " . $e->getMessage() . "\n";
        }
    }

    echo "Migration 22 completed successfully.\n";
}
?>