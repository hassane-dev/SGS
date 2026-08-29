<?php
// db/migrations/20240115_16_extend_paie_rules_dynamic.php

function migrate_16($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // Helper to add column if missing
    $addColumnIfMissing = function($table, $column, $definition) use ($db, $isSqlite) {
        if ($isSqlite) {
            $stmt = $db->query("PRAGMA table_info({$table})");
            $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $colNames = array_column($cols, 'name');
            if (!in_array($column, $colNames, true)) {
                $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name
                  AND COLUMN_NAME = :col_name
            ");
            $stmt->execute([
                'table_name' => $table,
                'col_name' => $column
            ]);
            if ((int)$stmt->fetchColumn() === 0) {
                $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        }
    };

    // Extend paie_regles_calcul
    $addColumnIfMissing('paie_regles_calcul', 'pays_code', "VARCHAR(10) NOT NULL DEFAULT 'RCA'");
    $addColumnIfMissing('paie_regles_calcul', 'base_calcul_type', "VARCHAR(50) NOT NULL DEFAULT 'brut_total'");
    $addColumnIfMissing('paie_regles_calcul', 'montant_fixe_salarial', "DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
    $addColumnIfMissing('paie_regles_calcul', 'taux_patronal', "DECIMAL(10,4) NOT NULL DEFAULT 0.0000");
    $addColumnIfMissing('paie_regles_calcul', 'montant_fixe_patronal', "DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
    $addColumnIfMissing('paie_regles_calcul', 'seuil_minimum', "DECIMAL(15,4) NULL");
    $addColumnIfMissing('paie_regles_calcul', 'plafond_maximum', "DECIMAL(15,4) NULL");
    $addColumnIfMissing('paie_regles_calcul', 'abattement_forfaitaire', "DECIMAL(15,4) NOT NULL DEFAULT 0.0000");
    $addColumnIfMissing('paie_regles_calcul', 'abattement_pourcentage', "DECIMAL(10,4) NOT NULL DEFAULT 0.0000");
    $addColumnIfMissing('paie_regles_calcul', 'ordre_application', "INT NOT NULL DEFAULT 100");
    $addColumnIfMissing('paie_regles_calcul', 'type_contrat_id', "INT NULL");

    // Backfill default values for existing system rules if needed
    $db->exec("UPDATE paie_regles_calcul SET pays_code = 'RCA' WHERE (pays_code IS NULL OR pays_code = '') AND est_systeme = 1");
    $db->exec("UPDATE paie_regles_calcul SET juridiction_code = 'RCA' WHERE juridiction_code = 'DEFAULT' AND est_systeme = 1");
    $db->exec("UPDATE paie_regles_calcul SET ordre_application = 100 WHERE code_regle = 'CNSS_SALARIALE' AND est_systeme = 1");
    $db->exec("UPDATE paie_regles_calcul SET ordre_application = 300 WHERE code_regle = 'CNSS_PATRONALE' AND est_systeme = 1");
    $db->exec("UPDATE paie_regles_calcul SET ordre_application = 200, base_calcul_type = 'net_imposable_provisoire' WHERE code_regle = 'IUTS_IMPOT' AND est_systeme = 1");

    echo "Migration 16: Extended paie_regles_calcul table OK.\n";
}
