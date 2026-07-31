<?php
// db/migrations/20240115_02_create_depenses_principale.php

function migrate_02($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. depenses
    $sql_depenses = "CREATE TABLE IF NOT EXISTS depenses (
        id $pk,
        lycee_id INT NOT NULL,
        numero_piece VARCHAR(100) NOT NULL,
        categorie_id INT NOT NULL,
        centre_cout_id INT DEFAULT NULL,
        beneficiaire_id INT NOT NULL,
        montant DECIMAL(15, 2) NOT NULL,
        motif TEXT NOT NULL,
        statut VARCHAR(50) NOT NULL DEFAULT 'brouillon', -- 'brouillon', 'en_attente_approbation', 'approuve', 'rejete', 'paye', 'paye_partiellement', 'annule'
        compte_id INT DEFAULT NULL,
        mouvement_tresorerie_id INT DEFAULT NULL,
        cree_par INT NOT NULL,
        exercice_financier_id INT NOT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (categorie_id) REFERENCES depense_categories(id) ON DELETE RESTRICT,
        FOREIGN KEY (centre_cout_id) REFERENCES depense_centres_couts(id) ON DELETE SET NULL,
        FOREIGN KEY (beneficiaire_id) REFERENCES depense_beneficiaires(id) ON DELETE RESTRICT,
        FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
        FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE SET NULL,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
        UNIQUE (lycee_id, numero_piece),
        CONSTRAINT chk_depense_montant CHECK (montant > 0)
    ) $fk_ref;";

    $db->exec($sql_depenses);
    echo "Migration 02: Table depenses OK.\n";

    // 2. depenses_pieces
    $sql_pieces = "CREATE TABLE IF NOT EXISTS depenses_pieces (
        id $pk,
        depense_id INT NOT NULL,
        nom_fichier VARCHAR(255) NOT NULL,
        chemin_fichier VARCHAR(255) NOT NULL,
        type_mime VARCHAR(100) NOT NULL,
        taille_octets INT NOT NULL,
        sha256_hash VARCHAR(64) NOT NULL,
        date_televersement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (depense_id) REFERENCES depenses(id) ON DELETE CASCADE
    ) $fk_ref;";

    $db->exec($sql_pieces);
    echo "Migration 02: Table depenses_pieces OK.\n";
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../../src/config/database.php';
    try {
        migrate_02(Database::getInstance());
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>