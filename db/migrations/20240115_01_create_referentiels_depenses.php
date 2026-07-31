<?php
// db/migrations/20240115_01_create_referentiels_depenses.php

function migrate_01($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. depense_categories
    $sql_categories = "CREATE TABLE IF NOT EXISTS depense_categories (
        id $pk,
        lycee_id INT NOT NULL,
        nom_categorie VARCHAR(150) NOT NULL,
        modifiable TINYINT(1) DEFAULT 1,
        statut VARCHAR(50) DEFAULT 'actif',
        description TEXT DEFAULT NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
    ) $fk_ref;";

    $db->exec($sql_categories);
    echo "Migration 01: Table depense_categories OK.\n";

    // 2. depense_centres_couts
    $sql_centres = "CREATE TABLE IF NOT EXISTS depense_centres_couts (
        id $pk,
        lycee_id INT NOT NULL,
        nom_centre VARCHAR(150) NOT NULL,
        statut VARCHAR(50) DEFAULT 'actif',
        description TEXT DEFAULT NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
    ) $fk_ref;";

    $db->exec($sql_centres);
    echo "Migration 01: Table depense_centres_couts OK.\n";

    // 3. depense_beneficiaires
    $sql_beneficiaires = "CREATE TABLE IF NOT EXISTS depense_beneficiaires (
        id $pk,
        lycee_id INT NOT NULL,
        nom_beneficiaire VARCHAR(150) NOT NULL,
        type VARCHAR(50) NOT NULL, -- 'interne' or 'externe'
        beneficiaire_utilisateur_id INT DEFAULT NULL,
        statut VARCHAR(50) DEFAULT 'actif',
        telephone VARCHAR(30) DEFAULT NULL,
        email VARCHAR(150) DEFAULT NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (beneficiaire_utilisateur_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
    ) $fk_ref;";

    $db->exec($sql_beneficiaires);
    echo "Migration 01: Table depense_beneficiaires OK.\n";
}

// If executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../../src/config/database.php';
    try {
        migrate_01(Database::getInstance());
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>