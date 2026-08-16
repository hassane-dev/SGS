<?php
// db/migrations/20240115_12_create_lot1_drh_contrats.php

function migrate_12($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // Helper function to check if column exists idempotently across SQLite and MySQL
    $hasColumn = function($table, $column) use ($db, $isSqlite) {
        try {
            if ($isSqlite) {
                $stmt = $db->query("PRAGMA table_info(`$table`)");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (strtolower($row['name']) === strtolower($column)) return true;
                }
                return false;
            } else {
                $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                return (bool)($stmt && $stmt->fetch());
            }
        } catch (Exception $e) {
            return false;
        }
    };

    // 1. paie_entites_juridiques
    $sql_entites = "CREATE TABLE IF NOT EXISTS paie_entites_juridiques (
        id $pk,
        organisation_id INT DEFAULT NULL,
        raison_sociale VARCHAR(255) NOT NULL,
        sigle VARCHAR(50) DEFAULT NULL,
        immatriculation_fiscale VARCHAR(100) DEFAULT NULL,
        numero_cnss_employeur VARCHAR(100) DEFAULT NULL,
        adresse_siege TEXT DEFAULT NULL,
        juridiction_id INT DEFAULT NULL,
        actif TINYINT(1) DEFAULT 1,
        cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $fk_ref;";
    $db->exec($sql_entites);

    // Seed default Legal Entity if empty
    $stmt_check_e = $db->query("SELECT COUNT(*) FROM paie_entites_juridiques");
    if ((int)$stmt_check_e->fetchColumn() === 0) {
        $stmt_ins_e = $db->prepare("INSERT INTO paie_entites_juridiques (raison_sociale, sigle, immatriculation_fiscale) VALUES (?, ?, ?)");
        $stmt_ins_e->execute(['Établissement Employeur Principal', 'EEP', 'NIF-DEFAULT-001']);
    }

    // 2. personnel_unites_organisationnelles
    $sql_unites = "CREATE TABLE IF NOT EXISTS personnel_unites_organisationnelles (
        id $pk,
        lycee_id INT DEFAULT NULL,
        entite_juridique_id INT DEFAULT NULL,
        code_unite VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        description TEXT DEFAULT NULL,
        unite_parente_id INT DEFAULT NULL,
        actif TINYINT(1) DEFAULT 1,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (entite_juridique_id) REFERENCES paie_entites_juridiques(id) ON DELETE SET NULL
    ) $fk_ref;";
    $db->exec($sql_unites);

    // 3. personnel_postes
    $sql_postes = "CREATE TABLE IF NOT EXISTS personnel_postes (
        id $pk,
        unite_id INT NOT NULL,
        fonction_id INT NOT NULL,
        intitule_poste VARCHAR(150) NOT NULL,
        niveau_hierarchique INT DEFAULT 1,
        statut_poste VARCHAR(50) DEFAULT 'vacant',
        actif TINYINT(1) DEFAULT 1,
        FOREIGN KEY (unite_id) REFERENCES personnel_unites_organisationnelles(id) ON DELETE CASCADE,
        FOREIGN KEY (fonction_id) REFERENCES personnel_fonctions(id) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_postes);

    // 4. personnel_affectations_historique
    $sql_affectations = "CREATE TABLE IF NOT EXISTS personnel_affectations_historique (
        id $pk,
        personnel_id INT NOT NULL,
        entite_juridique_id INT DEFAULT NULL,
        lycee_id INT NOT NULL,
        unite_id INT DEFAULT NULL,
        poste_id INT DEFAULT NULL,
        fonction_id INT DEFAULT NULL,
        cycle_id INT DEFAULT NULL,
        role_exercice VARCHAR(100) DEFAULT NULL,
        type_affectation VARCHAR(50) DEFAULT 'principale',
        motif_changement TEXT DEFAULT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE DEFAULT NULL,
        actif TINYINT(1) DEFAULT 1,
        FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
        FOREIGN KEY (entite_juridique_id) REFERENCES paie_entites_juridiques(id) ON DELETE SET NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
        FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE SET NULL
    ) $fk_ref;";
    $db->exec($sql_affectations);

    // 5. Extend personnel_contrats_historique
    $columns_to_add = [
        'contrat_souche_id' => 'INT DEFAULT NULL',
        'entite_juridique_id' => 'INT DEFAULT NULL',
        'mode_calcul_principal' => "VARCHAR(50) DEFAULT 'forfait_fixe'",
        'unite_remuneration' => "VARCHAR(50) DEFAULT 'mois'",
        'periodicite_paiement' => "VARCHAR(50) DEFAULT 'mensuel'",
        'periode_essai_jours' => 'INT DEFAULT 0',
        'statut_essai' => "VARCHAR(50) DEFAULT 'non_applicable'",
        'version_num' => 'INT DEFAULT 1',
        'avenant_numero' => 'VARCHAR(100) DEFAULT NULL',
        'type_avenant' => 'VARCHAR(100) DEFAULT NULL'
    ];

    foreach ($columns_to_add as $col => $definition) {
        if (!$hasColumn('personnel_contrats_historique', $col)) {
            $db->exec("ALTER TABLE personnel_contrats_historique ADD COLUMN $col $definition;");
        }
    }

    // 6. personnel_contrat_composants
    $sql_composants = "CREATE TABLE IF NOT EXISTS personnel_contrat_composants (
        id $pk,
        contrat_id INT NOT NULL,
        code_composant VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        nature_composant VARCHAR(50) NOT NULL,
        mode_calcul VARCHAR(50) NOT NULL,
        valeur_numerique DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        unite_remuneration VARCHAR(50) DEFAULT NULL,
        periodicite_paiement VARCHAR(50) DEFAULT NULL,
        devise_code VARCHAR(10) DEFAULT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE DEFAULT NULL,
        cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (contrat_id) REFERENCES personnel_contrats_historique(id) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_composants);

    // 7. personnel_contrat_financements
    $sql_financements = "CREATE TABLE IF NOT EXISTS personnel_contrat_financements (
        id $pk,
        contrat_id INT NOT NULL,
        financeur_nom VARCHAR(150) NOT NULL,
        type_financeur VARCHAR(50) DEFAULT 'etablissement',
        pourcentage_prise_en_charge DECIMAL(5,2) NOT NULL DEFAULT 100.00,
        montant_plafone DECIMAL(12,2) DEFAULT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE DEFAULT NULL,
        FOREIGN KEY (contrat_id) REFERENCES personnel_contrats_historique(id) ON DELETE CASCADE
    ) $fk_ref;";
    $db->exec($sql_financements);

    // 8. sys_idempotency_keys
    $sql_idempotency = "CREATE TABLE IF NOT EXISTS sys_idempotency_keys (
        id $pk,
        idempotency_key VARCHAR(128) NOT NULL UNIQUE,
        route VARCHAR(255) NOT NULL,
        user_id INT DEFAULT NULL,
        response_payload TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $fk_ref;";
    $db->exec($sql_idempotency);

    // Create Indexes
    try {
        if ($isSqlite) {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_aff_dates ON personnel_affectations_historique (personnel_id, date_debut, date_fin);");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_con_souche ON personnel_contrats_historique (contrat_souche_id, version_num);");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_comp_contrat ON personnel_contrat_composants (contrat_id, date_debut);");
        } else {
            $idx1 = $db->query("SHOW INDEX FROM personnel_affectations_historique WHERE Key_name = 'idx_aff_dates'");
            if (!$idx1->fetch()) {
                $db->exec("ALTER TABLE personnel_affectations_historique ADD INDEX idx_aff_dates (personnel_id, date_debut, date_fin);");
            }
            $idx2 = $db->query("SHOW INDEX FROM personnel_contrats_historique WHERE Key_name = 'idx_con_souche'");
            if (!$idx2->fetch()) {
                $db->exec("ALTER TABLE personnel_contrats_historique ADD INDEX idx_con_souche (contrat_souche_id, version_num);");
            }
            $idx3 = $db->query("SHOW INDEX FROM personnel_contrat_composants WHERE Key_name = 'idx_comp_contrat'");
            if (!$idx3->fetch()) {
                $db->exec("ALTER TABLE personnel_contrat_composants ADD INDEX idx_comp_contrat (contrat_id, date_debut);");
            }
        }
    } catch (Exception $e) {
        error_log("Index creation warning in migrate_12: " . $e->getMessage());
    }

    // Legacy Data Migration: migrate utilisateurs.contrat_id to personnel_contrats_historique
    try {
        require_once __DIR__ . '/../../src/services/PersonnelContractService.php';
        $migratedCount = PersonnelContractService::migrateLegacyContracts();
        if ($migratedCount > 0) {
            echo "Migration 12: $migratedCount legacy user contracts successfully migrated to personnel_contrats_historique.\n";
        }
    } catch (Exception $e) {
        error_log("Legacy contract migration notice in migrate_12: " . $e->getMessage());
    }

    echo "Migration 12: Lot 1 DRH & Contrats tables and extensions OK.\n";
}
