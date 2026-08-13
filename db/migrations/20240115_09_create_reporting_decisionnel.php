<?php
// db/migrations/20240115_09_create_reporting_decisionnel.php

function migrate_09($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. reporting_snapshots
    $sql_snapshots = "CREATE TABLE IF NOT EXISTS reporting_snapshots (
        id $pk,
        lycee_id INT NOT NULL,
        exercice_financier_id INT NOT NULL,
        periode_id INT DEFAULT NULL,
        date_snapshot DATE NOT NULL,
        frequence VARCHAR(50) NOT NULL, -- 'quotidienne', 'mensuelle', 'annuelle'
        kpi_code VARCHAR(100) NOT NULL,
        valeur DECIMAL(15, 4) NOT NULL,
        devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
        source_version VARCHAR(50) NOT NULL DEFAULT '1.0',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE CASCADE,
        FOREIGN KEY (periode_id) REFERENCES comptabilite_periodes(id) ON DELETE SET NULL,
        UNIQUE (lycee_id, exercice_financier_id, date_snapshot, frequence, kpi_code)
    ) $fk_ref;";
    $db->exec($sql_snapshots);
    echo "Migration 09: Table reporting_snapshots OK.\n";

    // 2. reporting_kpi_seuils
    $sql_seuils = "CREATE TABLE IF NOT EXISTS reporting_kpi_seuils (
        id $pk,
        lycee_id INT NOT NULL,
        kpi_code VARCHAR(100) NOT NULL,
        seuil_min DECIMAL(15, 2) DEFAULT 0.00,
        seuil_warning DECIMAL(15, 2) DEFAULT 0.00,
        objectif DECIMAL(15, 2) DEFAULT 0.00,
        seuil_danger DECIMAL(15, 2) DEFAULT 0.00,
        sens_variation VARCHAR(20) NOT NULL DEFAULT 'croissant', -- 'croissant', 'decroissant'
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        UNIQUE (lycee_id, kpi_code)
    ) $fk_ref;";
    $db->exec($sql_seuils);
    echo "Migration 09: Table reporting_kpi_seuils OK.\n";

    // 3. reporting_previsions_config
    $sql_previsions = "CREATE TABLE IF NOT EXISTS reporting_previsions_config (
        id $pk,
        lycee_id INT NOT NULL,
        methode VARCHAR(100) NOT NULL, -- 'baseline', 'moving_average', 'weighted_moving_average', 'linear_trend', 'exponential_smoothing'
        horizon INT NOT NULL DEFAULT 3, -- in months
        scenario VARCHAR(50) NOT NULL DEFAULT 'central', -- 'prudent', 'central', 'optimiste'
        hypotheses TEXT DEFAULT NULL,
        date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        UNIQUE (lycee_id, scenario)
    ) $fk_ref;";
    $db->exec($sql_previsions);
    echo "Migration 09: Table reporting_previsions_config OK.\n";

    // 4. reporting_audit_logs
    $sql_audit_logs = "CREATE TABLE IF NOT EXISTS reporting_audit_logs (
        id $pk,
        user_id INT NOT NULL,
        lycee_id INT NOT NULL,
        operation VARCHAR(100) NOT NULL,
        details TEXT NOT NULL,
        date_operation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_audit_logs);
    echo "Migration 09: Table reporting_audit_logs OK.\n";
}
