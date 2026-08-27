<?php
// db/migrations/20240115_14_update_paie_regularisations.php

function migrate_14($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($isSqlite) {
        $stmtPragma = $db->query("PRAGMA table_info(`paie_regularisations`)");
        $cols = $stmtPragma ? $stmtPragma->fetchAll(PDO::FETCH_ASSOC) : [];
        if ($stmtPragma) {
            $stmtPragma->closeCursor();
            $stmtPragma = null;
        }

        $bulletinSourceNotNull = false;
        foreach ($cols as $col) {
            if ($col['name'] === 'bulletin_source_id' && (int)$col['notnull'] === 1) {
                $bulletinSourceNotNull = true;
                break;
            }
        }

        if ($bulletinSourceNotNull) {
            $db->exec("PRAGMA foreign_keys = OFF;");
            $db->exec("DROP TABLE IF EXISTS paie_regularisations_temp;");
            $db->exec("
                CREATE TABLE paie_regularisations_temp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    personnel_id INTEGER NULL,
                    source_type VARCHAR(30) NULL,
                    periode_source_id INTEGER NULL,
                    bulletin_source_id INTEGER NULL,
                    periode_destination_id INTEGER NOT NULL,
                    type_regularisation VARCHAR(50) NOT NULL,
                    motif TEXT NOT NULL,
                    montant_brut_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                    montant_net_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                    statut VARCHAR(20) NOT NULL DEFAULT 'brouillon',
                    cree_par INTEGER NOT NULL,
                    valide_par INTEGER NULL,
                    created_at DATETIME NOT NULL,
                    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
                    FOREIGN KEY (bulletin_source_id) REFERENCES paie_bulletins(id) ON DELETE RESTRICT,
                    FOREIGN KEY (periode_destination_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT,
                    FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
                    FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
                );
            ");

            $db->exec("
                INSERT INTO paie_regularisations_temp (id, bulletin_source_id, periode_destination_id, type_regularisation, motif, montant_brut_delta, montant_net_delta, statut, cree_par, valide_par, created_at)
                SELECT id, bulletin_source_id, periode_destination_id, type_regularisation, motif, montant_brut_delta, montant_net_delta, statut, cree_par, valide_par, created_at
                FROM paie_regularisations;
            ");

            $db->exec("DROP TABLE paie_regularisations;");
            $db->exec("ALTER TABLE paie_regularisations_temp RENAME TO paie_regularisations;");
            $db->exec("PRAGMA foreign_keys = ON;");
            echo "Migration 14: Recreated SQLite paie_regularisations table with nullable bulletin_source_id\n";
        }

        // Re-scope paie_bulletin_heures unique constraint to (bulletin_id, cahier_validation_id)
        $stmtHPragma = $db->query("PRAGMA index_list(`paie_bulletin_heures`)");
        $hIndexes = $stmtHPragma ? $stmtHPragma->fetchAll(PDO::FETCH_ASSOC) : [];
        if ($stmtHPragma) {
            $stmtHPragma->closeCursor();
            $stmtHPragma = null;
        }

        $hasOldUkCahier = false;
        foreach ($hIndexes as $hIdx) {
            $stmtInfo = $db->query("PRAGMA index_info(`" . $hIdx['name'] . "`)");
            $idxCols = $stmtInfo ? $stmtInfo->fetchAll(PDO::FETCH_ASSOC) : [];
            if ($stmtInfo) {
                $stmtInfo->closeCursor();
                $stmtInfo = null;
            }

            if (count($idxCols) === 1 && $idxCols[0]['name'] === 'cahier_validation_id') {
                $hasOldUkCahier = true;
                break;
            }
        }

        if ($hasOldUkCahier) {
            $db->exec("PRAGMA foreign_keys = OFF;");
            $db->exec("DROP TABLE IF EXISTS paie_bulletin_heures_temp;");
            $db->exec("
                CREATE TABLE paie_bulletin_heures_temp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    bulletin_id INTEGER NOT NULL,
                    cahier_validation_id INTEGER NOT NULL,
                    heures_effectuees DECIMAL(5,2) NOT NULL,
                    taux_horaire DECIMAL(15,4) NOT NULL,
                    montant_total DECIMAL(15,4) NOT NULL,
                    FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
                    FOREIGN KEY (cahier_validation_id) REFERENCES paie_cahier_texte_validations(id) ON DELETE RESTRICT,
                    UNIQUE (bulletin_id, cahier_validation_id)
                );
            ");
            $db->exec("
                INSERT INTO paie_bulletin_heures_temp (id, bulletin_id, cahier_validation_id, heures_effectuees, taux_horaire, montant_total)
                SELECT id, bulletin_id, cahier_validation_id, heures_effectuees, taux_horaire, montant_total
                FROM paie_bulletin_heures;
            ");
            $db->exec("DROP TABLE paie_bulletin_heures;");
            $db->exec("ALTER TABLE paie_bulletin_heures_temp RENAME TO paie_bulletin_heures;");
            $db->exec("PRAGMA foreign_keys = ON;");
            echo "Migration 14: Re-scoped paie_bulletin_heures UNIQUE constraint to (bulletin_id, cahier_validation_id)\n";
        }
    }

    // Helper to check if column exists
    $columnExists = function($table, $column) use ($db, $isSqlite) {
        if ($isSqlite) {
            $stmtC = $db->query("PRAGMA table_info(`$table`)");
            $cols = $stmtC ? $stmtC->fetchAll(PDO::FETCH_ASSOC) : [];
            if ($stmtC) {
                $stmtC->closeCursor();
                $stmtC = null;
            }
            foreach ($cols as $col) {
                if ($col['name'] === $column) {
                    return true;
                }
            }
            return false;
        } else {
            $stmtC = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            $res = (bool)($stmtC ? $stmtC->fetch() : false);
            if ($stmtC) {
                $stmtC->closeCursor();
                $stmtC = null;
            }
            return $res;
        }
    };

    // 1. Add NULLable columns if they don't exist
    if (!$columnExists('paie_regularisations', 'personnel_id')) {
        $db->exec("ALTER TABLE paie_regularisations ADD COLUMN personnel_id INT NULL;");
        echo "Migration 14: Added column personnel_id to paie_regularisations\n";
    }

    if (!$columnExists('paie_regularisations', 'source_type')) {
        $db->exec("ALTER TABLE paie_regularisations ADD COLUMN source_type VARCHAR(30) NULL;");
        echo "Migration 14: Added column source_type to paie_regularisations\n";
    }

    if (!$columnExists('paie_regularisations', 'periode_source_id')) {
        $db->exec("ALTER TABLE paie_regularisations ADD COLUMN periode_source_id INT NULL;");
        echo "Migration 14: Added column periode_source_id to paie_regularisations\n";
    }

    // 2. Backfill historical records
    $db->exec("
        UPDATE paie_regularisations
        SET personnel_id = (SELECT personnel_id FROM paie_bulletins WHERE paie_bulletins.id = paie_regularisations.bulletin_source_id),
            periode_source_id = (SELECT periode_id FROM paie_bulletins WHERE paie_bulletins.id = paie_regularisations.bulletin_source_id),
            source_type = 'bulletin'
        WHERE personnel_id IS NULL AND bulletin_source_id IS NOT NULL
    ");
    echo "Migration 14: Backfilled historical paie_regularisations records\n";

    // Default fallback for any remaining orphaned records
    $db->exec("UPDATE paie_regularisations SET personnel_id = 1, source_type = 'autre' WHERE personnel_id IS NULL");

    // 3. Update Column Nullability and FK constraints for MySQL
    if (!$isSqlite) {
        $db->exec("ALTER TABLE paie_regularisations MODIFY COLUMN bulletin_source_id BIGINT NULL;");
        $db->exec("ALTER TABLE paie_regularisations MODIFY COLUMN personnel_id INT NOT NULL;");
        $db->exec("ALTER TABLE paie_regularisations MODIFY COLUMN source_type VARCHAR(30) NOT NULL;");

        // Update paie_bulletin_heures unique constraint in MySQL if needed
        try {
            $db->exec("ALTER TABLE paie_bulletin_heures DROP INDEX uk_paie_bul_cahier;");
            $db->exec("ALTER TABLE paie_bulletin_heures ADD UNIQUE KEY uk_paie_bul_cahier (bulletin_id, cahier_validation_id);");
        } catch (Exception $e) {}

        try {
            $db->exec("ALTER TABLE paie_regularisations ADD CONSTRAINT fk_paie_regu_pers FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT;");
        } catch (Exception $e) {}
        try {
            $db->exec("ALTER TABLE paie_regularisations ADD CONSTRAINT fk_paie_regu_per_src FOREIGN KEY (periode_source_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT;");
        } catch (Exception $e) {}
        try {
            $db->exec("CREATE INDEX idx_paie_regu_pers ON paie_regularisations(personnel_id, statut);");
        } catch (Exception $e) {}
    }

    // 4. Create paie_regularisation_integrations table
    $sql_integrations = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_regularisation_integrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        regularisation_id INTEGER NOT NULL,
        bulletin_id INTEGER NOT NULL,
        montant_brut_integre DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_net_integre DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (regularisation_id) REFERENCES paie_regularisations(id) ON DELETE RESTRICT,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE
    );" : "
    CREATE TABLE IF NOT EXISTS paie_regularisation_integrations (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        regularisation_id BIGINT NOT NULL,
        bulletin_id BIGINT NOT NULL,
        montant_brut_integre DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_net_integre DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (regularisation_id) REFERENCES paie_regularisations(id) ON DELETE RESTRICT,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
        INDEX idx_paie_regu_int_bul (bulletin_id),
        INDEX idx_paie_regu_int_reg (regularisation_id)
    ) $fk_ref;";
    $db->exec($sql_integrations);

    echo "Migration 14: Regularisations update & integrations table OK.\n";
}
