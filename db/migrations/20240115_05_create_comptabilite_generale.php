<?php
// db/migrations/20240115_05_create_comptabilite_generale.php

function migrate_05($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 0. devises
    $sql_devises = "CREATE TABLE IF NOT EXISTS devises (
        id $pk,
        code VARCHAR(3) NOT NULL UNIQUE,
        nom VARCHAR(100) NOT NULL,
        taux_reference DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
        date_taux TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $fk_ref;";
    $db->exec($sql_devises);
    echo "Migration 05: Table devises OK.\n";

    // 1. comptes_comptables
    $sql_comptes = "CREATE TABLE IF NOT EXISTS comptes_comptables (
        id $pk,
        numero VARCHAR(20) NOT NULL UNIQUE,
        libelle VARCHAR(150) NOT NULL,
        classe INT NOT NULL,
        nature VARCHAR(50) NOT NULL, -- 'actif', 'passif', 'charge', 'produit'
        compte_parent_id INT DEFAULT NULL,
        autoriser_ecriture TINYINT(1) DEFAULT 1,
        est_systeme TINYINT(1) DEFAULT 0,
        actif TINYINT(1) DEFAULT 1,
        FOREIGN KEY (compte_parent_id) REFERENCES comptes_comptables(id) ON DELETE RESTRICT
    ) $fk_ref;";
    $db->exec($sql_comptes);
    echo "Migration 05: Table comptes_comptables OK.\n";

    // 2. journaux_comptables
    $sql_journaux = "CREATE TABLE IF NOT EXISTS journaux_comptables (
        id $pk,
        lycee_id INT NOT NULL,
        code VARCHAR(10) NOT NULL,
        libelle VARCHAR(100) NOT NULL,
        type_journal VARCHAR(50) NOT NULL, -- 'tresorerie', 'achats', 'ventes', 'generaux'
        ordre_affichage INT DEFAULT 0,
        actif TINYINT(1) DEFAULT 1,
        exercice_comptable_id INT DEFAULT NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (exercice_comptable_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
        UNIQUE (lycee_id, code)
    ) $fk_ref;";
    $db->exec($sql_journaux);
    echo "Migration 05: Table journaux_comptables OK.\n";

    // 3. pieces_comptables_sequences
    $sql_sequences = "CREATE TABLE IF NOT EXISTS pieces_comptables_sequences (
        journal_id INT NOT NULL,
        annee INT NOT NULL,
        dernier_chrono INT NOT NULL DEFAULT 0,
        PRIMARY KEY (journal_id, annee)
    ) $fk_ref;";
    $db->exec($sql_sequences);
    echo "Migration 05: Table pieces_comptables_sequences OK.\n";

    // 4. pieces_comptables
    $sql_pieces = "CREATE TABLE IF NOT EXISTS pieces_comptables (
        id $pk,
        lycee_id INT NOT NULL,
        exercice_financier_id INT NOT NULL,
        journal_id INT NOT NULL,
        numero_piece VARCHAR(50) NOT NULL UNIQUE,
        libelle_piece VARCHAR(255) NOT NULL,
        date_piece DATE NOT NULL,
        source_table VARCHAR(100) DEFAULT NULL,
        source_id INT DEFAULT NULL,
        devise VARCHAR(3) NOT NULL DEFAULT 'XOF',
        taux_change DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
        cree_par INT NOT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        statut VARCHAR(50) NOT NULL DEFAULT 'valide', -- 'brouillon', 'valide', 'contrepasse'
        piece_originale_id INT DEFAULT NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
        FOREIGN KEY (journal_id) REFERENCES journaux_comptables(id) ON DELETE RESTRICT,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        FOREIGN KEY (piece_originale_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
        UNIQUE (source_table, source_id, statut)
    ) $fk_ref;";
    $db->exec($sql_pieces);
    echo "Migration 05: Table pieces_comptables OK.\n";

    // 5. ecritures_comptables
    $sql_ecritures = "CREATE TABLE IF NOT EXISTS ecritures_comptables (
        id $pk,
        piece_comptable_id INT NOT NULL,
        exercice_comptable_id INT NOT NULL,
        compte_comptable_id INT NOT NULL,
        debit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        credit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        libelle_ligne VARCHAR(255) NOT NULL,
        budget_ligne_id INT DEFAULT NULL,
        centre_cout_id INT DEFAULT NULL,
        activite_id INT DEFAULT NULL,
        verrouille TINYINT(1) DEFAULT 0,
        devise_id INT DEFAULT NULL,
        montant_devise DECIMAL(15,2) DEFAULT NULL,
        taux_conversion DECIMAL(15,6) DEFAULT NULL,
        montant_fcfa DECIMAL(15,2) DEFAULT NULL,
        FOREIGN KEY (piece_comptable_id) REFERENCES pieces_comptables(id) ON DELETE CASCADE,
        FOREIGN KEY (exercice_comptable_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
        FOREIGN KEY (compte_comptable_id) REFERENCES comptes_comptables(id) ON DELETE RESTRICT,
        FOREIGN KEY (budget_ligne_id) REFERENCES budget_lignes(id) ON DELETE SET NULL,
        FOREIGN KEY (centre_cout_id) REFERENCES depense_centres_couts(id) ON DELETE SET NULL,
        FOREIGN KEY (devise_id) REFERENCES devises(id) ON DELETE RESTRICT
    ) $fk_ref;";
    $db->exec($sql_ecritures);
    echo "Migration 05: Table ecritures_comptables OK.\n";

    // 6. comptabilite_periodes
    $sql_periodes = "CREATE TABLE IF NOT EXISTS comptabilite_periodes (
        id $pk,
        lycee_id INT NOT NULL,
        exercice_financier_id INT NOT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE NOT NULL,
        est_cloturee TINYINT(1) DEFAULT 0,
        cloturee_le TIMESTAMP NULL DEFAULT NULL,
        cloturee_par INT DEFAULT NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
        FOREIGN KEY (cloturee_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
    ) $fk_ref;";
    $db->exec($sql_periodes);
    echo "Migration 05: Table comptabilite_periodes OK.\n";

    // 7. schemas_comptables (Table des schémas comptables automatisés)
    $sql_schemas = "CREATE TABLE IF NOT EXISTS schemas_comptables (
        id $pk,
        evenement VARCHAR(100) NOT NULL UNIQUE, -- 'inscription', 'mensualite', 'depense', 'salaire', 'ecart_positif', 'ecart_negatif'
        compte_debit_numero VARCHAR(20) NOT NULL,
        compte_credit_numero VARCHAR(20) NOT NULL,
        libelle_modele VARCHAR(255) NOT NULL,
        journal_code VARCHAR(10) NOT NULL
    ) $fk_ref;";
    $db->exec($sql_schemas);
    echo "Migration 05: Table schemas_comptables OK.\n";

    // Add Performance Indexes Idempotently
    if (!$isSqlite) {
        $addIndexIfNeeded = function($db, $table, $indexName, $columnsSql) {
            try {
                $stmt = $db->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
                if (!$stmt->fetch()) {
                    $db->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($columnsSql);");
                    echo "Added index $indexName to table $table\n";
                }
            } catch (Exception $e) {
                echo "Error checking/creating index $indexName on $table: " . $e->getMessage() . "\n";
            }
        };

        $addIndexIfNeeded($db, 'pieces_comptables', 'idx_pieces_date', 'date_piece');
        $addIndexIfNeeded($db, 'pieces_comptables', 'idx_pieces_journal', 'journal_id');
        $addIndexIfNeeded($db, 'pieces_comptables', 'idx_pieces_lycee', 'lycee_id');
        $addIndexIfNeeded($db, 'pieces_comptables', 'idx_pieces_source', 'source_table, source_id');
        $addIndexIfNeeded($db, 'ecritures_comptables', 'idx_ecritures_compte', 'compte_comptable_id');
        $addIndexIfNeeded($db, 'ecritures_comptables', 'idx_ecritures_exercice', 'exercice_comptable_id');
        echo "Migration 05: Performance indexes checked/added successfully.\n";
    }
}
