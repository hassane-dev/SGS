<?php
// db/migrations/20240115_13_create_lot2_paie_engine.php

function migrate_13($db) {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    $pk_int = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $pk_bigint = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "BIGINT AUTO_INCREMENT PRIMARY KEY";
    $fk_ref = $isSqlite ? "" : "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    // 1. paie_periodes
    $sql_periodes = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_periodes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER NOT NULL,
        periode_comptable_id INTEGER NOT NULL,
        code_periode VARCHAR(30) NOT NULL,
        mois TINYINT NOT NULL,
        annee SMALLINT NOT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'brouillon',
        cree_par INTEGER NOT NULL,
        valide_par INTEGER NULL,
        cloture_par INTEGER NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        closed_at DATETIME NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE RESTRICT,
        FOREIGN KEY (periode_comptable_id) REFERENCES comptabilite_periodes(id) ON DELETE RESTRICT,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        UNIQUE (lycee_id, annee, mois)
    );" : "
    CREATE TABLE IF NOT EXISTS paie_periodes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lycee_id INT NOT NULL,
        periode_comptable_id INT NOT NULL,
        code_periode VARCHAR(30) NOT NULL,
        mois TINYINT NOT NULL,
        annee SMALLINT NOT NULL,
        date_debut DATE NOT NULL,
        date_fin DATE NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'brouillon',
        cree_par INT NOT NULL,
        valide_par INT NULL,
        cloture_par INT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        closed_at DATETIME NULL,
        FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE RESTRICT,
        FOREIGN KEY (periode_comptable_id) REFERENCES comptabilite_periodes(id) ON DELETE RESTRICT,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        UNIQUE KEY uk_lycee_periode (lycee_id, annee, mois),
        INDEX idx_paie_per_statut (lycee_id, statut)
    ) $fk_ref;";
    $db->exec($sql_periodes);

    // 2. paie_bulletins
    $sql_bulletins = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_bulletins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        periode_id INTEGER NOT NULL,
        personnel_id INTEGER NOT NULL,
        contrat_id INTEGER NOT NULL,
        version_num INTEGER NOT NULL DEFAULT 1,
        est_version_active TINYINT NOT NULL DEFAULT 1,
        entite_juridique_id INTEGER NOT NULL,
        cycle_id INTEGER NULL,
        salaire_base DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_brut DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_cotisations_salariales DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_impots DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_retenues DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        net_imposable DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        net_a_payer DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_cotisations_patronales DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        cout_total_employeur DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        devise VARCHAR(10) NOT NULL,
        taux_change DECIMAL(12,6) NOT NULL DEFAULT 1.000000,
        statut_bulletin VARCHAR(20) NOT NULL DEFAULT 'brouillon',
        statut_comptabilisation VARCHAR(20) NOT NULL DEFAULT 'non_comptabilise',
        statut_reglement VARCHAR(20) NOT NULL DEFAULT 'non_paye',
        statut_cloture VARCHAR(20) NOT NULL DEFAULT 'ouvert',
        est_reprise_legacy TINYINT NOT NULL DEFAULT 0,
        idempotency_key VARCHAR(100) NULL,
        content_hash VARCHAR(64) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        active_scope_key VARCHAR(255) GENERATED ALWAYS AS (
            CASE WHEN est_version_active = 1 THEN personnel_id || ':' || entite_juridique_id || ':' || contrat_id || ':' || periode_id ELSE NULL END
        ) STORED,
        FOREIGN KEY (periode_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT,
        FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        FOREIGN KEY (contrat_id) REFERENCES personnel_contrats_historique(id) ON DELETE RESTRICT,
        FOREIGN KEY (entite_juridique_id) REFERENCES paie_entites_juridiques(id) ON DELETE RESTRICT,
        FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE SET NULL,
        UNIQUE (personnel_id, entite_juridique_id, contrat_id, periode_id, version_num),
        UNIQUE (active_scope_key),
        UNIQUE (periode_id, idempotency_key)
    );" : "
    CREATE TABLE IF NOT EXISTS paie_bulletins (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        periode_id INT NOT NULL,
        personnel_id INT NOT NULL,
        contrat_id INT NOT NULL,
        version_num INT NOT NULL DEFAULT 1,
        est_version_active TINYINT(1) NOT NULL DEFAULT 1,
        entite_juridique_id INT NOT NULL,
        cycle_id INT NULL,
        salaire_base DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_brut DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_cotisations_salariales DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_impots DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_retenues DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        net_imposable DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        net_a_payer DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        total_cotisations_patronales DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        cout_total_employeur DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        devise VARCHAR(10) NOT NULL,
        taux_change DECIMAL(12,6) NOT NULL DEFAULT 1.000000,
        statut_bulletin VARCHAR(20) NOT NULL DEFAULT 'brouillon',
        statut_comptabilisation VARCHAR(20) NOT NULL DEFAULT 'non_comptabilise',
        statut_reglement VARCHAR(20) NOT NULL DEFAULT 'non_paye',
        statut_cloture VARCHAR(20) NOT NULL DEFAULT 'ouvert',
        est_reprise_legacy TINYINT(1) NOT NULL DEFAULT 0,
        idempotency_key VARCHAR(100) NULL,
        content_hash VARCHAR(64) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        active_scope_key VARCHAR(255) GENERATED ALWAYS AS (
            CASE WHEN est_version_active = 1 THEN CONCAT(personnel_id, ':', entite_juridique_id, ':', contrat_id, ':', periode_id) ELSE NULL END
        ) STORED,
        FOREIGN KEY (periode_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT,
        FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        FOREIGN KEY (contrat_id) REFERENCES personnel_contrats_historique(id) ON DELETE RESTRICT,
        FOREIGN KEY (entite_juridique_id) REFERENCES paie_entites_juridiques(id) ON DELETE RESTRICT,
        FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE SET NULL,
        UNIQUE KEY uk_bulletin_version (personnel_id, entite_juridique_id, contrat_id, periode_id, version_num),
        UNIQUE KEY uk_bulletin_active_scope (active_scope_key),
        UNIQUE KEY uk_bulletin_tenant_idempotency (periode_id, idempotency_key),
        INDEX idx_paie_bul_search (personnel_id, periode_id, statut_bulletin)
    ) $fk_ref;";
    $db->exec($sql_bulletins);

    // 3. paie_bulletin_lignes
    $sql_lignes = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_bulletin_lignes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bulletin_id INTEGER NOT NULL,
        code_rubrique VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        categorie VARCHAR(50) NOT NULL,
        base_calcul DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        taux DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        montant_salarial DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_patronal DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        ordre_affichage INT NOT NULL DEFAULT 0,
        est_imposable TINYINT NOT NULL DEFAULT 1,
        est_cotisable TINYINT NOT NULL DEFAULT 1,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE
    );" : "
    CREATE TABLE IF NOT EXISTS paie_bulletin_lignes (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        bulletin_id BIGINT NOT NULL,
        code_rubrique VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        categorie VARCHAR(50) NOT NULL,
        base_calcul DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        taux DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        montant_salarial DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_patronal DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        ordre_affichage INT NOT NULL DEFAULT 0,
        est_imposable TINYINT(1) NOT NULL DEFAULT 1,
        est_cotisable TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
        INDEX idx_paie_lig_bul (bulletin_id)
    ) $fk_ref;";
    $db->exec($sql_lignes);

    // 4. paie_cahier_texte_validations
    $sql_cahier_val = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_cahier_texte_validations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cahier_id INTEGER NOT NULL,
        enseignant_id INTEGER NOT NULL,
        cycle_id INTEGER NULL,
        classe_id INTEGER NULL,
        matiere_id INTEGER NULL,
        duree_heures DECIMAL(5,2) NOT NULL,
        taux_horaire DECIMAL(15,4) NOT NULL,
        statut_validation VARCHAR(20) NOT NULL DEFAULT 'en_attente',
        valide_par INTEGER NULL,
        valide_le DATETIME NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (cahier_id) REFERENCES cahier_texte(cahier_id) ON DELETE RESTRICT,
        FOREIGN KEY (enseignant_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE SET NULL,
        FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE SET NULL,
        FOREIGN KEY (matiere_id) REFERENCES matieres(id_matiere) ON DELETE SET NULL,
        FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
        UNIQUE (cahier_id)
    );" : "
    CREATE TABLE IF NOT EXISTS paie_cahier_texte_validations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cahier_id INT NOT NULL,
        enseignant_id INT NOT NULL,
        cycle_id INT NULL,
        classe_id INT NULL,
        matiere_id INT NULL,
        duree_heures DECIMAL(5,2) NOT NULL,
        taux_horaire DECIMAL(15,4) NOT NULL,
        statut_validation VARCHAR(20) NOT NULL DEFAULT 'en_attente',
        valide_par INT NULL,
        valide_le DATETIME NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (cahier_id) REFERENCES cahier_texte(cahier_id) ON DELETE RESTRICT,
        FOREIGN KEY (enseignant_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE SET NULL,
        FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE SET NULL,
        FOREIGN KEY (matiere_id) REFERENCES matieres(id_matiere) ON DELETE SET NULL,
        FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
        UNIQUE KEY uk_paie_cahier (cahier_id),
        INDEX idx_paie_cah_ens (enseignant_id, statut_validation)
    ) $fk_ref;";
    $db->exec($sql_cahier_val);

    // 5. paie_bulletin_heures
    $sql_bulletin_heures = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_bulletin_heures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bulletin_id INTEGER NOT NULL,
        cahier_validation_id INTEGER NOT NULL,
        heures_effectuees DECIMAL(5,2) NOT NULL,
        taux_horaire DECIMAL(15,4) NOT NULL,
        montant_total DECIMAL(15,4) NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
        FOREIGN KEY (cahier_validation_id) REFERENCES paie_cahier_texte_validations(id) ON DELETE RESTRICT,
        UNIQUE (cahier_validation_id)
    );" : "
    CREATE TABLE IF NOT EXISTS paie_bulletin_heures (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        bulletin_id BIGINT NOT NULL,
        cahier_validation_id INT NOT NULL,
        heures_effectuees DECIMAL(5,2) NOT NULL,
        taux_horaire DECIMAL(15,4) NOT NULL,
        montant_total DECIMAL(15,4) NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
        FOREIGN KEY (cahier_validation_id) REFERENCES paie_cahier_texte_validations(id) ON DELETE RESTRICT,
        UNIQUE KEY uk_paie_bul_cahier (cahier_validation_id),
        INDEX idx_paie_hrs_bul (bulletin_id)
    ) $fk_ref;";
    $db->exec($sql_bulletin_heures);

    // 6. paie_bulletin_contrat_snapshot
    $sql_contrat_snap = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_bulletin_contrat_snapshot (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bulletin_id INTEGER NOT NULL,
        contrat_id INTEGER NOT NULL,
        version_num INT NOT NULL,
        type_contrat VARCHAR(50) NOT NULL,
        mode_calcul_principal VARCHAR(50) NOT NULL,
        devise VARCHAR(10) NOT NULL,
        raw_json_snapshot TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
        UNIQUE (bulletin_id)
    );" : "
    CREATE TABLE IF NOT EXISTS paie_bulletin_contrat_snapshot (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        bulletin_id BIGINT NOT NULL,
        contrat_id INT NOT NULL,
        version_num INT NOT NULL,
        type_contrat VARCHAR(50) NOT NULL,
        mode_calcul_principal VARCHAR(50) NOT NULL,
        devise VARCHAR(10) NOT NULL,
        raw_json_snapshot JSON NOT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
        UNIQUE KEY uk_bulletin_contrat_snap (bulletin_id)
    ) $fk_ref;";
    $db->exec($sql_contrat_snap);

    // 7. paie_bulletin_financements
    $sql_financement_snap = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_bulletin_financements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bulletin_id INTEGER NOT NULL,
        financeur_nom VARCHAR(150) NOT NULL,
        type_financeur VARCHAR(50) NOT NULL,
        pourcentage_prise_en_charge DECIMAL(5,2) NOT NULL,
        montant_prise_en_charge DECIMAL(15,4) NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE
    );" : "
    CREATE TABLE IF NOT EXISTS paie_bulletin_financements (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        bulletin_id BIGINT NOT NULL,
        financeur_nom VARCHAR(150) NOT NULL,
        type_financeur VARCHAR(50) NOT NULL,
        pourcentage_prise_en_charge DECIMAL(5,2) NOT NULL,
        montant_prise_en_charge DECIMAL(15,4) NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
        INDEX idx_paie_fin_bul (bulletin_id)
    ) $fk_ref;";
    $db->exec($sql_financement_snap);

    // 8. paie_regles_calcul
    $sql_regles = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_regles_calcul (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        juridiction_code VARCHAR(20) NOT NULL DEFAULT 'DEFAULT',
        code_regle VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        categorie VARCHAR(50) NOT NULL,
        mode_calcul VARCHAR(50) NOT NULL,
        taux_par_defaut DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        formule VARCHAR(255) NULL,
        est_systeme TINYINT NOT NULL DEFAULT 1,
        actif TINYINT NOT NULL DEFAULT 1,
        date_debut_validite DATE NOT NULL,
        date_fin_validite DATE NULL,
        created_at DATETIME NOT NULL,
        UNIQUE (juridiction_code, code_regle, date_debut_validite)
    );" : "
    CREATE TABLE IF NOT EXISTS paie_regles_calcul (
        id INT AUTO_INCREMENT PRIMARY KEY,
        juridiction_code VARCHAR(20) NOT NULL DEFAULT 'DEFAULT',
        code_regle VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        categorie VARCHAR(50) NOT NULL,
        mode_calcul VARCHAR(50) NOT NULL,
        taux_par_defaut DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        formule VARCHAR(255) NULL,
        est_systeme TINYINT(1) NOT NULL DEFAULT 1,
        actif TINYINT(1) NOT NULL DEFAULT 1,
        date_debut_validite DATE NOT NULL,
        date_fin_validite DATE NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uk_paie_regle_jur (juridiction_code, code_regle, date_debut_validite)
    ) $fk_ref;";
    $db->exec($sql_regles);

    // 9. paie_baremes_tranches
    $sql_baremes = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_baremes_tranches (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        regle_id INTEGER NOT NULL,
        tranche_numero INT NOT NULL,
        limite_inferieure DECIMAL(15,4) NOT NULL,
        limite_superieure DECIMAL(15,4) NULL,
        taux DECIMAL(10,4) NOT NULL,
        montant_fixe DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        FOREIGN KEY (regle_id) REFERENCES paie_regles_calcul(id) ON DELETE CASCADE,
        UNIQUE (regle_id, tranche_numero)
    );" : "
    CREATE TABLE IF NOT EXISTS paie_baremes_tranches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        regle_id INT NOT NULL,
        tranche_numero INT NOT NULL,
        limite_inferieure DECIMAL(15,4) NOT NULL,
        limite_superieure DECIMAL(15,4) NULL,
        taux DECIMAL(10,4) NOT NULL,
        montant_fixe DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        FOREIGN KEY (regle_id) REFERENCES paie_regles_calcul(id) ON DELETE CASCADE,
        UNIQUE KEY uk_paie_bar_tranche (regle_id, tranche_numero)
    ) $fk_ref;";
    $db->exec($sql_baremes);

    // 10. paie_bulletin_regles_snapshot
    $sql_regles_snap = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_bulletin_regles_snapshot (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bulletin_id INTEGER NOT NULL,
        regle_id INTEGER NOT NULL,
        code_regle VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        categorie VARCHAR(50) NOT NULL,
        mode_calcul VARCHAR(50) NOT NULL,
        taux_applique DECIMAL(10,4) NOT NULL,
        raw_json_snapshot TEXT NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE
    );" : "
    CREATE TABLE IF NOT EXISTS paie_bulletin_regles_snapshot (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        bulletin_id BIGINT NOT NULL,
        regle_id INT NOT NULL,
        code_regle VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        categorie VARCHAR(50) NOT NULL,
        mode_calcul VARCHAR(50) NOT NULL,
        taux_applique DECIMAL(10,4) NOT NULL,
        raw_json_snapshot JSON NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
        INDEX idx_paie_reg_snap_bul (bulletin_id)
    ) $fk_ref;";
    $db->exec($sql_regles_snap);

    // 11. paie_bulletin_regle_tranches_snapshot
    $sql_tranches_snap = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_bulletin_regle_tranches_snapshot (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        regle_snapshot_id INTEGER NOT NULL,
        tranche_numero INT NOT NULL,
        limite_inferieure DECIMAL(15,4) NOT NULL,
        limite_superieure DECIMAL(15,4) NULL,
        taux DECIMAL(10,4) NOT NULL,
        base_imposable_tranche DECIMAL(15,4) NOT NULL,
        montant_calcule DECIMAL(15,4) NOT NULL,
        FOREIGN KEY (regle_snapshot_id) REFERENCES paie_bulletin_regles_snapshot(id) ON DELETE CASCADE
    );" : "
    CREATE TABLE IF NOT EXISTS paie_bulletin_regle_tranches_snapshot (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        regle_snapshot_id BIGINT NOT NULL,
        tranche_numero INT NOT NULL,
        limite_inferieure DECIMAL(15,4) NOT NULL,
        limite_superieure DECIMAL(15,4) NULL,
        taux DECIMAL(10,4) NOT NULL,
        base_imposable_tranche DECIMAL(15,4) NOT NULL,
        montant_calcule DECIMAL(15,4) NOT NULL,
        FOREIGN KEY (regle_snapshot_id) REFERENCES paie_bulletin_regles_snapshot(id) ON DELETE CASCADE,
        INDEX idx_paie_tra_snap (regle_snapshot_id)
    ) $fk_ref;";
    $db->exec($sql_tranches_snap);

    // 12. paie_reglements
    $sql_reglements = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_reglements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bulletin_id INTEGER NOT NULL,
        compte_financier_id INTEGER NOT NULL,
        mouvement_tresorerie_id INTEGER NULL,
        mode_reglement VARCHAR(50) NOT NULL,
        montant DECIMAL(15,4) NOT NULL,
        reference_transaction VARCHAR(100) NULL,
        date_reglement DATE NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'valide',
        cree_par INTEGER NOT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE RESTRICT,
        FOREIGN KEY (compte_financier_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
        FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE RESTRICT,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
    );" : "
    CREATE TABLE IF NOT EXISTS paie_reglements (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        bulletin_id BIGINT NOT NULL,
        compte_financier_id INT NOT NULL,
        mouvement_tresorerie_id INT NULL,
        mode_reglement VARCHAR(50) NOT NULL,
        montant DECIMAL(15,4) NOT NULL,
        reference_transaction VARCHAR(100) NULL,
        date_reglement DATE NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'valide',
        cree_par INT NOT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE RESTRICT,
        FOREIGN KEY (compte_financier_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
        FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE RESTRICT,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        INDEX idx_paie_reg_bul (bulletin_id)
    ) $fk_ref;";
    $db->exec($sql_reglements);

    // 13. paie_regularisations
    $sql_regularisations = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_regularisations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bulletin_source_id INTEGER NOT NULL,
        periode_destination_id INTEGER NOT NULL,
        type_regularisation VARCHAR(50) NOT NULL,
        motif TEXT NOT NULL,
        montant_brut_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_net_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        statut VARCHAR(20) NOT NULL DEFAULT 'brouillon',
        cree_par INTEGER NOT NULL,
        valide_par INTEGER NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (bulletin_source_id) REFERENCES paie_bulletins(id) ON DELETE RESTRICT,
        FOREIGN KEY (periode_destination_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
    );" : "
    CREATE TABLE IF NOT EXISTS paie_regularisations (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        bulletin_source_id BIGINT NOT NULL,
        periode_destination_id INT NOT NULL,
        type_regularisation VARCHAR(50) NOT NULL,
        motif TEXT NOT NULL,
        montant_brut_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_net_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        statut VARCHAR(20) NOT NULL DEFAULT 'brouillon',
        cree_par INT NOT NULL,
        valide_par INT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (bulletin_source_id) REFERENCES paie_bulletins(id) ON DELETE RESTRICT,
        FOREIGN KEY (periode_destination_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT,
        FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
        INDEX idx_paie_regu_src (bulletin_source_id)
    ) $fk_ref;";
    $db->exec($sql_regularisations);

    // 14. paie_regularisation_lignes
    $sql_regu_lignes = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_regularisation_lignes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        regularisation_id INTEGER NOT NULL,
        code_rubrique VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        base_calcul_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_salarial_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_patronal_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        FOREIGN KEY (regularisation_id) REFERENCES paie_regularisations(id) ON DELETE CASCADE
    );" : "
    CREATE TABLE IF NOT EXISTS paie_regularisation_lignes (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        regularisation_id BIGINT NOT NULL,
        code_rubrique VARCHAR(50) NOT NULL,
        libelle VARCHAR(150) NOT NULL,
        base_calcul_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_salarial_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        montant_patronal_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        FOREIGN KEY (regularisation_id) REFERENCES paie_regularisations(id) ON DELETE CASCADE,
        INDEX idx_paie_regu_lig (regularisation_id)
    ) $fk_ref;";
    $db->exec($sql_regu_lignes);

    // 15. paie_audit_logs
    $sql_audit_logs = $isSqlite ? "
    CREATE TABLE IF NOT EXISTS paie_audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        entity_type VARCHAR(50) NOT NULL,
        entity_id BIGINT NOT NULL,
        action VARCHAR(50) NOT NULL,
        user_id INTEGER NOT NULL,
        payload_before TEXT NULL,
        payload_after TEXT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
    );" : "
    CREATE TABLE IF NOT EXISTS paie_audit_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        entity_type VARCHAR(50) NOT NULL,
        entity_id BIGINT NOT NULL,
        action VARCHAR(50) NOT NULL,
        user_id INT NOT NULL,
        payload_before JSON NULL,
        payload_after JSON NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
        INDEX idx_paie_aud_entity (entity_type, entity_id)
    ) $fk_ref;";
    $db->exec($sql_audit_logs);

    echo "Migration 13: Lot 2.1 Paie Module core tables OK.\n";
}
