<?php
// db/migrations/20240115_04_create_budget_tables.php

function migrate_04($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. budgets
    $sql_budgets = "CREATE TABLE IF NOT EXISTS budgets (
        id $pk,
        lycee_id INT NOT NULL,
        exercice_financier_id INT NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        statut VARCHAR(50) NOT NULL DEFAULT 'brouillon', -- 'brouillon', 'soumis', 'valide', 'actif', 'clos'
        cree_par INT NOT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        UNIQUE (lycee_id, exercice_financier_id)
    ) $fk_ref;";
    $db->exec($sql_budgets);
    echo "Migration 04: Table budgets OK.\n";

    // 2. budget_lignes
    $sql_lignes = "CREATE TABLE IF NOT EXISTS budget_lignes (
        id $pk,
        budget_id INT NOT NULL,
        categorie_id INT NOT NULL,
        centre_cout_id INT DEFAULT NULL,
        allocation_initiale DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        montant_ajustements DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        montant_engage DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        montant_consomme DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
        FOREIGN KEY (categorie_id) REFERENCES depense_categories(id) ON DELETE RESTRICT,
        FOREIGN KEY (centre_cout_id) REFERENCES depense_centres_couts(id) ON DELETE SET NULL,
        UNIQUE (budget_id, categorie_id, centre_cout_id)
    ) $fk_ref;";
    $db->exec($sql_lignes);
    echo "Migration 04: Table budget_lignes OK.\n";

    // 3. budget_ajustements
    $sql_ajustements = "CREATE TABLE IF NOT EXISTS budget_ajustements (
        id $pk,
        lycee_id INT NOT NULL,
        type_ajustement VARCHAR(50) NOT NULL, -- 'dotation_supplementaire', 'transfert'
        ligne_source_id INT DEFAULT NULL,
        ligne_destination_id INT NOT NULL,
        montant DECIMAL(15, 2) NOT NULL,
        motif TEXT NOT NULL,
        execute_par INT NOT NULL,
        date_ajustement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (ligne_source_id) REFERENCES budget_lignes(id) ON DELETE SET NULL,
        FOREIGN KEY (ligne_destination_id) REFERENCES budget_lignes(id) ON DELETE RESTRICT,
        FOREIGN KEY (execute_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
    ) $fk_ref;";
    $db->exec($sql_ajustements);
    echo "Migration 04: Table budget_ajustements OK.\n";

    // 4. budget_engagements
    $sql_engagements = "CREATE TABLE IF NOT EXISTS budget_engagements (
        id $pk,
        depense_id INT NOT NULL,
        budget_ligne_id INT NOT NULL,
        montant DECIMAL(15, 2) NOT NULL,
        statut VARCHAR(50) NOT NULL DEFAULT 'reserve', -- 'reserve', 'engage', 'consomme', 'libere', 'annule'
        date_engagement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (depense_id) REFERENCES depenses(id) ON DELETE CASCADE,
        FOREIGN KEY (budget_ligne_id) REFERENCES budget_lignes(id) ON DELETE RESTRICT
    ) $fk_ref;";
    $db->exec($sql_engagements);
    echo "Migration 04: Table budget_engagements OK.\n";

    // 5. budget_historique
    $sql_historique = "CREATE TABLE IF NOT EXISTS budget_historique (
        id $pk,
        lycee_id INT NOT NULL,
        evenement VARCHAR(100) NOT NULL, -- 'BudgetCreated', 'BudgetActivated', 'BudgetAdjusted', 'BudgetClosed', 'BudgetExceeded', 'BudgetReleased', 'BudgetConsumed'
        details TEXT NOT NULL,
        execute_par INT NOT NULL,
        date_evenement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (execute_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
    ) $fk_ref;";
    $db->exec($sql_historique);
    echo "Migration 04: Table budget_historique OK.\n";
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../../src/config/database.php';
    try {
        migrate_04(Database::getInstance());
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>