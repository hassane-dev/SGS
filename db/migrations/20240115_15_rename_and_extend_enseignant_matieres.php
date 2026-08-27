<?php
// db/migrations/20240115_15_rename_and_extend_enseignant_matieres.php

function migrate_15($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $tableExists = function($tableName) use ($db, $isSqlite) {
        if ($isSqlite) {
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:name");
            $stmt->execute(['name' => $tableName]);
            return (bool)$stmt->fetch();
        } else {
            $stmt = $db->query("SHOW TABLES LIKE '$tableName'");
            return (bool)$stmt->fetch();
        }
    };

    // 1. Rename table if old name exists and new name does not
    if ($tableExists('enseignant_matieres') && !$tableExists('affectations_pedagogiques')) {
        if ($isSqlite) {
            $db->exec("ALTER TABLE enseignant_matieres RENAME TO affectations_pedagogiques;");
        } else {
            $db->exec("RENAME TABLE enseignant_matieres TO affectations_pedagogiques;");
        }
        echo "Migration 15: Renamed table enseignant_matieres to affectations_pedagogiques.\n";
    }

    if (!$tableExists('affectations_pedagogiques')) {
        // Fallback: Create table if neither existed
        $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
        $sql = "CREATE TABLE IF NOT EXISTS affectations_pedagogiques (
            id $pk,
            enseignant_id INT NOT NULL,
            classe_id INT NOT NULL,
            matiere_id INT NOT NULL,
            annee_academique_id INT NOT NULL,
            disponibilite_horaire TEXT DEFAULT NULL,
            actif TINYINT(1) DEFAULT 1,
            volume_horaire_hebdo DECIMAL(5,2) DEFAULT 0.00,
            date_debut DATE NOT NULL,
            date_fin DATE DEFAULT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT 'actif',
            motif_changement VARCHAR(255) DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (enseignant_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
            FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE CASCADE,
            FOREIGN KEY (matiere_id) REFERENCES matieres(id_matiere) ON DELETE CASCADE,
            FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        )";
        $db->exec($sql);
        echo "Migration 15: Created table affectations_pedagogiques.\n";
    }

    // Helper to add missing columns
    $addColumnIfNeeded = function($table, $column, $definition) use ($db, $isSqlite) {
        $columnExists = false;
        if ($isSqlite) {
            $stmt = $db->prepare("PRAGMA table_info(`$table`)");
            $stmt->execute();
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                if ($col['name'] === $column) {
                    $columnExists = true;
                    break;
                }
            }
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($stmt->fetch()) {
                $columnExists = true;
            }
        }

        if (!$columnExists) {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Migration 15: Added column $column to table $table.\n";
        }
    };

    // 2. Add columns if not present
    $addColumnIfNeeded('affectations_pedagogiques', 'volume_horaire_hebdo', 'DECIMAL(5,2) DEFAULT 0.00');
    $addColumnIfNeeded('affectations_pedagogiques', 'date_debut', 'DATE');
    $addColumnIfNeeded('affectations_pedagogiques', 'date_fin', 'DATE DEFAULT NULL');
    $addColumnIfNeeded('affectations_pedagogiques', 'statut', "VARCHAR(20) NOT NULL DEFAULT 'actif'");
    $addColumnIfNeeded('affectations_pedagogiques', 'motif_changement', 'VARCHAR(255) DEFAULT NULL');
    $addColumnIfNeeded('affectations_pedagogiques', 'created_by', 'INT DEFAULT NULL');
    $addColumnIfNeeded('affectations_pedagogiques', 'created_at', 'DATETIME');
    $addColumnIfNeeded('affectations_pedagogiques', 'updated_at', 'DATETIME');

    // Backfill date_debut and timestamps for existing legacy rows from annees_academiques
    $db->exec("
        UPDATE affectations_pedagogiques
        SET date_debut = (
            SELECT aa.date_debut FROM annees_academiques aa WHERE aa.id = affectations_pedagogiques.annee_academique_id
        )
        WHERE date_debut IS NULL AND annee_academique_id IS NOT NULL
    ");
    $db->exec("UPDATE affectations_pedagogiques SET date_debut = CURRENT_DATE WHERE date_debut IS NULL");
    $db->exec("UPDATE affectations_pedagogiques SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL");
    $db->exec("UPDATE affectations_pedagogiques SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL");

    // 3. Drop legacy unique constraint if present (MySQL)
    if (!$isSqlite) {
        try {
            $idx_stmt = $db->query("SHOW INDEX FROM affectations_pedagogiques WHERE Key_name = 'unique_enseignant_matiere_annee'");
            if ($idx_stmt->fetch()) {
                $db->exec("ALTER TABLE affectations_pedagogiques DROP INDEX unique_enseignant_matiere_annee;");
                echo "Migration 15: Dropped index unique_enseignant_matiere_annee.\n";
            }
        } catch (Exception $e) {
            // Index already dropped or not present
        }

        try {
            $idx_stmt = $db->query("SHOW INDEX FROM affectations_pedagogiques WHERE Key_name = 'idx_aff_pedag_unique_active'");
            if (!$idx_stmt->fetch()) {
                $db->exec("ALTER TABLE affectations_pedagogiques ADD INDEX idx_aff_pedag_unique_active (classe_id, matiere_id, annee_academique_id, statut);");
                echo "Migration 15: Added index idx_aff_pedag_unique_active.\n";
            }
        } catch (Exception $e) {
            // Index exists
        }

        try {
            $idx_stmt = $db->query("SHOW INDEX FROM affectations_pedagogiques WHERE Key_name = 'idx_aff_pedag_enseignant'");
            if (!$idx_stmt->fetch()) {
                $db->exec("ALTER TABLE affectations_pedagogiques ADD INDEX idx_aff_pedag_enseignant (enseignant_id, annee_academique_id, statut);");
                echo "Migration 15: Added index idx_aff_pedag_enseignant.\n";
            }
        } catch (Exception $e) {
            // Index exists
        }
    } else {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_aff_pedag_unique_active ON affectations_pedagogiques (classe_id, matiere_id, annee_academique_id, statut);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_aff_pedag_enseignant ON affectations_pedagogiques (enseignant_id, annee_academique_id, statut);");
    }

    echo "Migration 15: Table affectations_pedagogiques extension complete.\n";
}
