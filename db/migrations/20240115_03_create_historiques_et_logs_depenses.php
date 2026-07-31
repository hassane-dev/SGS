<?php
// db/migrations/20240115_03_create_historiques_et_logs_depenses.php

function migrate_03($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. depenses_historique
    $sql_historique = "CREATE TABLE IF NOT EXISTS depenses_historique (
        id $pk,
        depense_id INT NOT NULL,
        statut_precedent VARCHAR(50) DEFAULT NULL,
        statut_nouveau VARCHAR(50) NOT NULL,
        modifie_par INT NOT NULL,
        motif_transition TEXT,
        date_historique TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (depense_id) REFERENCES depenses(id) ON DELETE CASCADE,
        FOREIGN KEY (modifie_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
    ) $fk_ref;";

    $db->exec($sql_historique);
    echo "Migration 03: Table depenses_historique OK.\n";

    // 2. depenses_evenements_log
    $sql_logs = "CREATE TABLE IF NOT EXISTS depenses_evenements_log (
        id $pk,
        depense_id INT DEFAULT NULL,
        evenement_type VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        permission_utilisee VARCHAR(100) DEFAULT NULL,
        adresse_ip VARCHAR(45) NOT NULL,
        correlation_id VARCHAR(100) NOT NULL,
        session_id VARCHAR(100) NOT NULL,
        details TEXT,
        date_evenement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (depense_id) REFERENCES depenses(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
    ) $fk_ref;";

    $db->exec($sql_logs);
    echo "Migration 03: Table depenses_evenements_log OK.\n";
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../../src/config/database.php';
    try {
        migrate_03(Database::getInstance());
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>