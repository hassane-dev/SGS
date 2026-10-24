-- =================================================================
-- High School Management Application (SGS) - Full Database Schema
-- =================================================================
-- ATTENTION / CAUTION:
-- THIS SCRIPT DROPS AND RECREATES ALL DATABASE TABLES.
-- IT IS STRICTLY INTENDED FOR INITIALIZING A FRESH/EMPTY DATABASE.
-- DO NOT EXECUTE THIS SCRIPT ON AN EXISTING PRODUCTION DATABASE WITH REAL DATA.
-- FOR INCREMENTAL UPDATES ON EXISTING DATABASES, USE "php migrate.php".
-- =================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables if they exist to ensure a clean slate on fresh initialization
DROP TABLE IF EXISTS `paiement_ventilations`, `modes_paiement`, `affectations_caisses`, `bulletin_details`, `paie_audit_logs`, `paie_reglements`, `paie_regularisation_integrations`, `paie_regularisation_lignes`, `paie_regularisations`, `paie_bulletin_regle_tranches_snapshot`, `paie_bulletin_regles_snapshot`, `paie_bulletin_financements`, `paie_bulletin_contrat_snapshot`, `paie_bulletin_heures`, `paie_cahier_texte_validations`, `paie_bulletin_lignes`, `paie_bulletins`, `paie_regles`, `paie_periodes`, `paie_grilles_salariales`, `paie_baremes_tranches`, `paie_baremes`, `sys_idempotency_keys`, `personnel_contrat_financements`, `personnel_contrat_composants`, `personnel_affectations_historique`, `personnel_postes`, `personnel_unites_organisationnelles`, `paie_entites_juridiques`, `personnel_historique_mouvements`, `personnel_documents`, `personnel_contrats_historique`, `personnel_fonctions`, `personnel_details`, `personnel_cycles_assignments`, `reporting_audit_logs`, `reporting_previsions_config`, `reporting_kpi_seuils`, `reporting_snapshots`, `schemas_comptables`, `comptabilite_periodes`, `ecritures_comptables`, `pieces_comptables`, `pieces_comptables_sequences`, `journaux_comptables`, `comptes_comptables`, `devises`, `budget_historique`, `budget_engagements`, `budget_ajustements`, `budget_lignes`, `budgets`, `depenses_evenements_log`, `depenses_historique`, `depenses_pieces`, `depenses`, `depense_beneficiaires`, `depense_centres_couts`, `depense_categories`, `surveillant_affectations`, `surveillant_groupe_classes`, `surveillant_groupes`, `discipline_conseil_incidents`, `discipline_conseil_eleves`, `discipline_conseil_membres`, `discipline_conseils`, `discipline_notifications`, `discipline_documents`, `discipline_historique`, `discipline_sanctions`, `discipline_incident_eleves`, `discipline_incidents`, `discipline_types_sanctions`, `discipline_types_incidents`, `achat_avoir_fournisseur_lignes`, `achat_avoirs_fournisseurs`, `achat_facture_reglements`, `achat_facture_lignes`, `achat_factures`, `achat_reception_lignes`, `achat_receptions`, `achat_commande_lignes`, `achat_commandes`, `achat_demande_lignes`, `achat_demandes`, `achat_articles`, `achat_categories`, `fournisseurs`, `regularisations_ecarts`, `mouvements_tresorerie`, `transferts_financiers`, `sessions_caisse`, `comptes_financiers`, `exercices_financiers`, `parametres_utilisateurs`, `etats_financiers_eleves`, `parametres_financiers_historique`, `parametres_financiers_eleves`, `politiques_financieres`, `evaluations`, `deblocages_notes`, `parametres_evaluations`, `param_type_evaluation`, `presences`, `emploi_du_temps`, `bulletins`, `notifications`, `modele_bulletin`, `carte_objects`, `carte_templates`, `salaires`, `surveillant_general`, `surveillant_niveaux`, `surveillant_classes`, `personnel_assignments`, `cahier_texte`, `horaire_enseignant`, `type_contrat`, `traductions`, `licences`, `cartes_scolaires`, `boutique_achats`, `boutique_ventes`, `boutique_articles`, `frais`, `journal_comptable`, `mensualite_details`, `mensualites`, `inscriptions`, `tests_entree`, `etudes`, `eleves`, `param_composition`, `param_devoir`, `sequences`, `affectations_pedagogiques`, `classe_matieres`, `matieres`, `classe_parametres`, `classes`, `series`, `cycles`, `salles`, `utilisateurs`, `role_permissions`, `permissions`, `roles`, `param_general`, `param_lycee`, `annees_academiques`;

CREATE TABLE `annees_academiques` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `libelle` VARCHAR(100) NOT NULL UNIQUE, -- e.g., "2024-2025"
    `date_debut` DATE NOT NULL,
    `date_fin` DATE NOT NULL,
    `est_active` BOOLEAN NOT NULL DEFAULT FALSE,
    `cloturee` BOOLEAN NOT NULL DEFAULT FALSE
);

-- Table for high schools (Lycees)
CREATE TABLE `param_lycee` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_lycee` VARCHAR(255) NOT NULL,
    `sigle` VARCHAR(50),
    `tel` VARCHAR(50),
    `email` VARCHAR(255) UNIQUE,
    `ville` VARCHAR(100),
    `quartier` VARCHAR(100),
    `ruelle` VARCHAR(100),
    `boite_postale` VARCHAR(50),
    `arrete` VARCHAR(255),
    `arrondissement` VARCHAR(100),
    `devise` VARCHAR(255),
    `logo` TEXT,
    `type_lycee` ENUM('public', 'prive', 'parapublic') NOT NULL,
    `boutique` BOOLEAN NOT NULL DEFAULT FALSE,
    `header_primary` TEXT,
    `header_secondary` TEXT,
    `signature_directeur` TEXT,
    `tampon_ecole` TEXT
);

-- Table for general system settings (scoped per Lycee)
CREATE TABLE `param_general` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `devise_pays` VARCHAR(100),
    `monnaie` VARCHAR(10),
    `modalite_paiement` VARCHAR(255), -- e.g., 'Especes, Versement, Mobile Money'
    `nb_langue` INT DEFAULT 1,
    `langue_1` VARCHAR(50) DEFAULT 'Francais',
    `langue_2` VARCHAR(50),
    `sequence_annuelle` ENUM('Semestrielle', 'Trimestrielle') NOT NULL DEFAULT 'Trimestrielle',
    `mode_cycle` ENUM('lycee_unique', 'separe_ceg_lycee') NOT NULL DEFAULT 'separe_ceg_lycee',
    `multilingue_actif` BOOLEAN DEFAULT FALSE,
    `biometrie_actif` BOOLEAN DEFAULT FALSE,
    `confidentialite_nationale` BOOLEAN DEFAULT FALSE,
    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- =================================================================
-- Roles and Permissions Tables
-- =================================================================

CREATE TABLE `roles` (
    `id_role` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_role` VARCHAR(100) NOT NULL,
    `lycee_id` INT, -- NULL for global roles
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

CREATE TABLE `permissions` (
    `id_permission` INT AUTO_INCREMENT PRIMARY KEY,
    `resource` VARCHAR(100) NOT NULL, -- e.g., 'user', 'class', 'cahier_texte'
    `action` VARCHAR(100) NOT NULL, -- e.g., 'create', 'view', 'edit', 'delete'
    `description` TEXT,
    UNIQUE KEY `unique_permission` (`resource`, `action`)
);

CREATE TABLE `role_permissions` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id_permission`) ON DELETE CASCADE
);

-- Table for users (Personnel)
CREATE TABLE `utilisateurs` (
    `id_user` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `sexe` ENUM('Homme', 'Femme'),
    `date_naissance` DATE,
    `lieu_naissance` VARCHAR(255),
    `adresse` TEXT,
    `telephone` VARCHAR(50),
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `mot_de_passe` VARCHAR(255) NOT NULL,
    `fonction` VARCHAR(100),
    `role_id` INT,
    `lycee_id` INT,
    `contrat_id` INT,
    `date_embauche` DATE,
    `actif` BOOLEAN DEFAULT TRUE,
    `photo` TEXT,
    `identifiant_public` VARCHAR(50) UNIQUE DEFAULT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`) ON DELETE SET NULL
);

-- =================================================================
-- Academic Structure Tables
-- =================================================================

-- Table for rooms
CREATE TABLE `salles` (
    `id_salle` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_salle` VARCHAR(100) NOT NULL,
    `capacite` INT,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table for academic cycles (e.g., Middle School, High School)
CREATE TABLE `cycles` (
    `id_cycle` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT,
    `nom_cycle` VARCHAR(100) NOT NULL,
    `niveau_debut` VARCHAR(40),
    `niveau_fin` VARCHAR(40),
    `utilise_series` TINYINT(1) DEFAULT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table for series (e.g., A, C, D)
CREATE TABLE `series` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_serie` VARCHAR(50) NOT NULL,
    `categorie` ENUM('Scientifique', 'Littéraire', 'Technique', 'Autre') NOT NULL,
    `lycee_id` INT,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table for classes
CREATE TABLE `classes` (
    `id_classe` INT AUTO_INCREMENT PRIMARY KEY,
    `niveau` VARCHAR(50) NOT NULL,
    `serie` VARCHAR(50),
    `numero` INT,
    `categorie` VARCHAR(100),
    `cycle_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `created_on` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cycle_id`) REFERENCES `cycles`(`id_cycle`),
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table for class parameters (annual settings)
CREATE TABLE `classe_parametres` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `classe_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `nombre_places` INT,
    `effectif_actuel` INT DEFAULT 0,
    `professeur_principal_id` INT,
    `commentaire` TEXT,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`professeur_principal_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL,
    UNIQUE KEY `unique_classe_annee` (`classe_id`, `annee_academique_id`)
);

-- Table for subjects
CREATE TABLE `matieres` (
    `id_matiere` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_matiere` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `type` VARCHAR(100),
    `cycle_concerne` VARCHAR(100),
    `statut` ENUM('principale', 'optionnelle') NOT NULL DEFAULT 'principale',
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Junction table for classes and subjects
CREATE TABLE `classe_matieres` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `coefficient` DECIMAL(4, 2) NOT NULL,
    `statut` ENUM('obligatoire', 'optionnelle') NOT NULL DEFAULT 'obligatoire',
    `categorie` VARCHAR(100),
    `cycle` VARCHAR(100),
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    UNIQUE KEY `unique_classe_matiere` (`classe_id`, `matiere_id`)
);

-- Junction table for pedagogical teacher assignments
CREATE TABLE `affectations_pedagogiques` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `enseignant_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `disponibilite_horaire` TEXT,
    `actif` BOOLEAN DEFAULT TRUE,
    `volume_horaire_hebdo` DECIMAL(5,2) DEFAULT 0.00,
    `date_debut` DATE NOT NULL,
    `date_fin` DATE DEFAULT NULL,
    `statut` VARCHAR(20) NOT NULL DEFAULT 'actif',
    `motif_changement` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL,
    KEY `idx_aff_pedag_unique_active` (`classe_id`, `matiere_id`, `annee_academique_id`, `statut`),
    KEY `idx_aff_pedag_enseignant` (`enseignant_id`, `annee_academique_id`, `statut`)
);

-- =================================================================
-- Academic Evaluation Tables
-- =================================================================

-- Table for academic sequences (e.g., Trimester, Semester)
CREATE TABLE `sequences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `nom` VARCHAR(255) NOT NULL,
    `type` ENUM('trimestrielle', 'semestrielle') NOT NULL,
    `date_debut` DATE NOT NULL,
    `date_fin` DATE NOT NULL,
    `statut` ENUM('planifiee', 'ouverte', 'fermee') NOT NULL DEFAULT 'ouverte',
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE
);

-- Table for homework parameters
CREATE TABLE `param_devoir` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `annee_id` INT NOT NULL,
    `nombre_devoir_par_sequence` INT,
    `note_maximale` DECIMAL(5, 2) DEFAULT 20.00,
    `date_debut_insertion` DATETIME,
    `date_fin_insertion` DATETIME,
    `deblocage_urgence` BOOLEAN DEFAULT FALSE,
    `classe_id` INT,
    `matiere_id` INT,
    `cree_par` INT,
    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`cree_par`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- Table for exam parameters
CREATE TABLE `param_composition` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `annee_id` INT NOT NULL,
    `nombre_composition_par_sequence` INT,
    `note_maximale` DECIMAL(5, 2) DEFAULT 20.00,
    `date_debut_insertion` DATETIME,
    `date_fin_insertion` DATETIME,
    `deblocage_urgence` BOOLEAN DEFAULT FALSE,
    `classe_id` INT,
    `matiere_id` INT,
    `cree_par` INT,
    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`cree_par`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- =================================================================
-- Student-related Tables
-- =================================================================

-- Table for students' personal data
CREATE TABLE `eleves` (
    `id_eleve` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `date_naissance` DATE,
    `lieu_naissance` VARCHAR(255),
    `nationalite` VARCHAR(100),
    `sexe` ENUM('Masculin', 'Féminin'),
    `quartier` VARCHAR(255),
    `tel_parent` VARCHAR(50),
    `nom_pere` VARCHAR(255),
    `nom_mere` VARCHAR(255),
    `profession_pere` VARCHAR(255),
    `profession_mere` VARCHAR(255),
    `photo` TEXT,
    `email` VARCHAR(255) UNIQUE,
    `telephone` VARCHAR(50),
    `statut` ENUM('en_attente', 'en_attente_paiement', 'actif', 'transféré', 'radié', 'diplômé', 'abandonné') NOT NULL DEFAULT 'en_attente',
    `identifiant_public` VARCHAR(50) UNIQUE DEFAULT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table representing an annual academic enrollment (The Heart of the System)
CREATE TABLE `etudes` (
    `id_etude` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT,
    `status` ENUM('en_attente_paiement', 'active', 'inactive', 'suspended') DEFAULT 'en_attente_paiement',
    `is_active` BOOLEAN DEFAULT FALSE,
    `date_activation` DATETIME DEFAULT NULL,
    `active_par` INT DEFAULT NULL,
    `motif_inactif` TEXT DEFAULT NULL,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`active_par`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- Table for entrance exams
CREATE TABLE `tests_entree` (
    `id_test` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `classe_visee_id` INT NOT NULL,
    `score` DECIMAL(5, 2),
    `date_test` DATE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_visee_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE
);

-- =================================================================
-- Financial and Administrative Tables
-- =================================================================

-- Table for initial enrollment fees
CREATE TABLE `inscriptions` (
    `id_inscription` INT AUTO_INCREMENT PRIMARY KEY,
    `etude_id` INT,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT,
    `montant_total` DECIMAL(10, 2) NOT NULL,
    `montant_verse` DECIMAL(10, 2) NOT NULL,
    `reste_a_payer` DECIMAL(10, 2) NOT NULL,
    `details_frais` JSON,
    `user_id` INT,
    `recu_numero` VARCHAR(50),
    `statut` ENUM('en_attente', 'valide', 'annule', 'rembourse') NOT NULL DEFAULT 'valide',
    `date_inscription` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`etude_id`) REFERENCES `etudes`(`id_etude`) ON DELETE CASCADE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- Table for monthly/sequential payments
CREATE TABLE `mensualites` (
    `id_mensualite` INT AUTO_INCREMENT PRIMARY KEY,
    `etude_id` INT,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT,
    `mois_ou_sequence` VARCHAR(50) NOT NULL,
    `montant_verse` DECIMAL(10, 2) NOT NULL,
    `reste_a_payer` DECIMAL(10, 2) DEFAULT 0.00,
    `date_paiement` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_id` INT,
    FOREIGN KEY (`etude_id`) REFERENCES `etudes`(`id_etude`) ON DELETE CASCADE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- Detailed records for monthly payments (allows partial payments, history)
CREATE TABLE `mensualite_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `mensualite_id` INT NOT NULL,
    `montant` DECIMAL(10, 2) NOT NULL,
    `mode_paiement` VARCHAR(50),
    `reference_transaction` VARCHAR(100),
    `date_paiement` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `recu_numero` VARCHAR(50),
    `statut` ENUM('en_attente', 'valide', 'annule', 'rembourse') NOT NULL DEFAULT 'valide',
    FOREIGN KEY (`mensualite_id`) REFERENCES `mensualites`(`id_mensualite`) ON DELETE CASCADE
);

-- Table for Journal Comptable (Unified Financial Operations Journal)
CREATE TABLE `journal_comptable` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `eleve_id` INT DEFAULT NULL,
    `user_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `operation` VARCHAR(100) NOT NULL, -- 'inscription', 'mensualite', 'annulation', 'remboursement'
    `montant` DECIMAL(10, 2) NOT NULL,
    `mode_paiement` VARCHAR(50) DEFAULT NULL,
    `recu_numero` VARCHAR(50) DEFAULT NULL,
    `reference_origine` VARCHAR(100) DEFAULT NULL, -- references to other entities or tables
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for defining school fees (tuition, etc.)
CREATE TABLE `frais` (
    `id_frais` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `cycle` VARCHAR(100),
    `niveau_debut` VARCHAR(50),
    `niveau_fin` VARCHAR(50),
    `serie` VARCHAR(50),
    `frais_inscription` DECIMAL(10, 2) NOT NULL,
    `frais_mensuel` DECIMAL(10, 2) NOT NULL,
    `frais_logo` DECIMAL(10, 2) DEFAULT NULL,
    `frais_carte` DECIMAL(10, 2) DEFAULT NULL,
    `autres_frais` JSON,
    `annee_academique_id` INT,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL
);

-- Table for shop articles
CREATE TABLE `boutique_articles` (
    `id_article` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_article` VARCHAR(255) NOT NULL,
    `categorie` VARCHAR(255) DEFAULT NULL,
    `prix` DECIMAL(10, 2) NOT NULL,
    `ancien_prix` DECIMAL(10, 2) DEFAULT NULL,
    `stock` INT,
    `image` TEXT,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table for shop sales (to group items in one receipt)
CREATE TABLE `boutique_ventes` (
    `id_vente` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `montant_total` DECIMAL(10, 2) NOT NULL,
    `recu_numero` VARCHAR(50),
    `date_vente` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for shop purchases
CREATE TABLE `boutique_achats` (
    `id_achat` INT AUTO_INCREMENT PRIMARY KEY,
    `vente_id` INT,
    `eleve_id` INT NOT NULL,
    `article_id` INT NOT NULL,
    `quantite` INT NOT NULL,
    `prix_unitaire` DECIMAL(10, 2),
    `date_achat` DATETIME NOT NULL,
    FOREIGN KEY (`vente_id`) REFERENCES `boutique_ventes`(`id_vente`) ON DELETE CASCADE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`article_id`) REFERENCES `boutique_articles`(`id_article`) ON DELETE CASCADE
);

-- Table for student ID cards
CREATE TABLE `cartes_scolaires` (
    `id_carte` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `annee_academique_id` INT,
    `layout` JSON,
    `qr_code_data` TEXT,
    `date_emission` DATE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL
);

-- =================================================================
-- System-level Tables
-- =================================================================

-- Table for software licenses
CREATE TABLE `licences` (
    `id_licence` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `duree_mois` INT NOT NULL,
    `date_debut` DATE NOT NULL,
    `date_fin` DATE NOT NULL,
    `actif` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table for translations
CREATE TABLE `traductions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `langue_code` VARCHAR(5) NOT NULL,
    `cle_traduction` VARCHAR(255) NOT NULL,
    `valeur` TEXT NOT NULL,
    UNIQUE KEY `unique_translation` (`langue_code`, `cle_traduction`)
);

-- =================================================================
-- Staff Management Tables
-- =================================================================

CREATE TABLE `type_contrat` (
    `id_contrat` INT AUTO_INCREMENT PRIMARY KEY,
    `libelle` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `type_paiement` ENUM('fixe', 'a_l_heure', 'aucun') NOT NULL DEFAULT 'fixe',
    `prise_en_charge` ENUM('Etat', 'Ecole', 'Mixte') NOT NULL DEFAULT 'Ecole',
    `lycee_id` INT,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

ALTER TABLE `utilisateurs` ADD FOREIGN KEY (`contrat_id`) REFERENCES `type_contrat`(`id_contrat`) ON DELETE SET NULL;

CREATE TABLE `horaire_enseignant` (
    `horaire_id` INT AUTO_INCREMENT PRIMARY KEY,
    `personnel_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `jour` VARCHAR(20) NOT NULL,
    `heure_debut` TIME NOT NULL,
    `heure_fin` TIME NOT NULL,
    `annee_id` INT,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`personnel_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL
);

CREATE TABLE `cahier_texte` (
    `cahier_id` INT AUTO_INCREMENT PRIMARY KEY,
    `personnel_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `date_cours` DATE NOT NULL,
    `heure_debut` TIME,
    `heure_fin` TIME,
    `contenu_cours` TEXT,
    `travail_donne` TEXT,
    `observation` TEXT,
    `annee_id` INT,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`personnel_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL
);

CREATE TABLE `personnel_assignments` (
    `id_assignment` INT AUTO_INCREMENT PRIMARY KEY,
    `personnel_id` INT NOT NULL,
    `assignment_type` VARCHAR(100) NOT NULL,
    `target_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`personnel_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_assignment` (`personnel_id`, `assignment_type`, `target_id`)
);

-- Explicit supervisor assignments
CREATE TABLE `surveillant_classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `surveillant_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`surveillant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

CREATE TABLE `surveillant_niveaux` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `surveillant_id` INT NOT NULL,
    `niveau` VARCHAR(50) NOT NULL,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`surveillant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

CREATE TABLE `surveillant_general` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `surveillant_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`surveillant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

CREATE TABLE `salaires` (
    `id_salaire` INT AUTO_INCREMENT PRIMARY KEY,
    `personnel_id` INT NOT NULL,
    `montant` DECIMAL(10, 2) NOT NULL,
    `mode_paiement` ENUM('mensuel', 'horaire') NOT NULL,
    `nb_heures_travaillees` DECIMAL(5, 2) DEFAULT NULL,
    `periode_mois` INT NOT NULL,
    `periode_annee` INT NOT NULL,
    `date_paiement` DATE,
    `etat_paiement` ENUM('paye', 'non_paye') NOT NULL DEFAULT 'non_paye',
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT,
    FOREIGN KEY (`personnel_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL
);

-- =================================================================
-- Template Tables
-- =================================================================

CREATE TABLE `carte_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `nom_modele` VARCHAR(255) NOT NULL,
    `orientation` ENUM('landscape', 'portrait') DEFAULT 'landscape',
    `width_mm` DECIMAL(5,2) DEFAULT 85.60,
    `height_mm` DECIMAL(5,2) DEFAULT 53.98,
    `background` TEXT,
    `styles` JSON,
    `layout_data` JSON, -- Keep for backward compatibility or complex layouts
    `config_visuelle` JSON,
    `version` VARCHAR(10) DEFAULT '2.1',
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

CREATE TABLE `carte_objects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_id` INT NOT NULL,
    `type_objet` VARCHAR(50) NOT NULL,
    `pos_x` INT,
    `pos_y` INT,
    `width` INT,
    `height` INT,
    `z_index` INT DEFAULT 0,
    `styles` JSON,
    `placeholder` VARCHAR(100),
    FOREIGN KEY (`template_id`) REFERENCES `carte_templates`(`id`) ON DELETE CASCADE
);

CREATE TABLE `modele_bulletin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `nom_modele` VARCHAR(255) NOT NULL,
    `format` ENUM('A4_portrait', 'A4_landscape') DEFAULT 'A4_portrait',
    `background` TEXT,
    `font_settings` JSON,
    `header_content` TEXT,
    `footer_content` TEXT,
    `qr_code_settings` JSON,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- =================================================================
-- Application Logic Tables
-- =================================================================

CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255),
    `is_read` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- =================================================================
-- Report Card (Bulletin) Tables
-- =================================================================

CREATE TABLE `bulletins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `sequence_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `moyenne_generale` DECIMAL(5, 2) NOT NULL,
    `rang` VARCHAR(100),
    `appreciation` TEXT,
    `statut` ENUM('provisoire', 'valide', 'publie') NOT NULL DEFAULT 'provisoire',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`sequence_id`) REFERENCES `sequences`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_bulletin` (`eleve_id`, `sequence_id`)
);

-- =================================================================
-- Timetable Tables
-- =================================================================

CREATE TABLE `emploi_du_temps` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `classe_id` INT NOT NULL,
  `matiere_id` INT NOT NULL,
  `professeur_id` INT NOT NULL,
  `jour` VARCHAR(20) NOT NULL,
  `heure_debut` TIME NOT NULL,
  `heure_fin` TIME NOT NULL,
  `salle_id` INT NULL,
  `annee_academique_id` INT,
  `modifiable` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
  FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
  FOREIGN KEY (`professeur_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`salle_id`) REFERENCES `salles`(`id_salle`) ON DELETE CASCADE,
  FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL
);

ALTER TABLE `emploi_du_temps` ADD COLUMN `lycee_id` INT NOT NULL AFTER `professeur_id`;

ALTER TABLE `emploi_du_temps` ADD FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE;

-- Table for student attendance
CREATE TABLE `presences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT,
    `enseignant_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `date_presence` DATE NOT NULL,
    `statut` ENUM('present', 'absent', 'retard', 'justifie') NOT NULL DEFAULT 'present',
    `commentaire` TEXT,
    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_presence_eleve_matiere_date` (`eleve_id`, `matiere_id`, `date_presence`)
);

-- Table for configurable evaluation types per school
CREATE TABLE IF NOT EXISTS `param_type_evaluation` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `libelle` VARCHAR(100) NOT NULL,
    `bareme_defaut` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `nombre_evaluation` INT NOT NULL DEFAULT 1,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `ordre_affichage` INT DEFAULT 0,
    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_lycee_code` (`lycee_id`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for evaluation parameters (when can teachers enter grades)
CREATE TABLE `parametres_evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `classe_id` INT DEFAULT NULL,
    `matiere_id` INT DEFAULT NULL,
    `sequence_id` INT DEFAULT NULL,
    `enseignant_id` INT DEFAULT NULL,
    `annee_academique_id` INT NOT NULL,
    `type` ENUM('global', 'classe', 'matiere', 'classe_matiere', 'enseignant') NOT NULL DEFAULT 'enseignant',
    `type_evaluation` ENUM('devoir', 'composition', 'tous') NOT NULL DEFAULT 'tous',
    `type_evaluation_id` INT DEFAULT NULL,
    `date_ouverture_saisie` DATETIME NOT NULL,
    `date_fermeture_saisie` DATETIME NOT NULL,
    `commentaire` TEXT,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`sequence_id`) REFERENCES `sequences`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`type_evaluation_id`) REFERENCES `param_type_evaluation`(`id`) ON DELETE SET NULL
);

CREATE TABLE `deblocages_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `type` ENUM('global', 'classe', 'matiere', 'classe_matiere', 'enseignant') NOT NULL,
    `classe_id` INT DEFAULT NULL,
    `matiere_id` INT DEFAULT NULL,
    `enseignant_id` INT DEFAULT NULL,
    `sequence_id` INT DEFAULT NULL,
    `type_evaluation` ENUM('devoir', 'composition', 'tous') NOT NULL DEFAULT 'tous',
    `date_debut` DATETIME NOT NULL,
    `date_fin` DATETIME NOT NULL,
    `motif` TEXT,
    `cree_par` INT,
    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`sequence_id`) REFERENCES `sequences`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`cree_par`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for grades (unified for homework and exams)
CREATE TABLE `evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `enseignant_id` INT NOT NULL,
    `eleve_id` INT NOT NULL,
    `sequence_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'devoir',
    `type_evaluation_id` INT NULL,
    `numero_evaluation` INT NOT NULL DEFAULT 1,
    `libelle_evaluation` VARCHAR(100) NULL,
    `note` DECIMAL(5, 2) NOT NULL,
    `bareme_snapshot` DECIMAL(5, 2) NOT NULL DEFAULT 20.00,
    `coefficient` DECIMAL(4, 2) NOT NULL,
    `appreciation` TEXT,
    `date_saisie` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`sequence_id`) REFERENCES `sequences`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`type_evaluation_id`) REFERENCES `param_type_evaluation`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `uk_eval_occ` (`eleve_id`, `classe_id`, `matiere_id`, `sequence_id`, `annee_academique_id`, `type_evaluation_id`, `numero_evaluation`)
);

CREATE TABLE `politiques_financieres` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL UNIQUE,
    `activation_seuil_type` ENUM('100', '75', '50', 'montant_minimum') NOT NULL DEFAULT '100',
    `activation_seuil_valeur` DECIMAL(10,2) NULL,
    `notes_seuil_mensualites` INT NOT NULL DEFAULT 0,
    `bulletin_seuil_complet` TINYINT(1) NOT NULL DEFAULT 1,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `parametres_financiers_eleves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL UNIQUE,
    `type_avantage` ENUM('Aucun', 'Réduction', 'Exonération', 'Bourse', 'Prise en charge', 'Autre') NOT NULL DEFAULT 'Aucun',
    `valeur_type` ENUM('Pourcentage', 'Montant fixe') NOT NULL DEFAULT 'Pourcentage',
    `valeur` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `date_debut` DATE NULL,
    `date_fin` DATE NULL,
    `motif` TEXT NULL,
    `organisme_financeur` VARCHAR(255) NULL,
    `frais_concernes` JSON NULL,
    `tous_frais` TINYINT(1) NOT NULL DEFAULT 0,
    `cree_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `parametres_financiers_historique` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `ancienne_valeur` JSON NULL,
    `nouvelle_valeur` JSON NULL,
    `date_modification` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `motif` TEXT NULL,
    `ip_modification` VARCHAR(45) NULL,
    `role_utilisateur` VARCHAR(100) NULL,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `etats_financiers_eleves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL UNIQUE,
    `inscription_statut` ENUM('Non payée', 'Partiellement payée', 'Payée') NOT NULL DEFAULT 'Non payée',
    `mensualite_statut` ENUM('À jour', 'Partiellement payée', 'En retard') NOT NULL DEFAULT 'À jour',
    `notes_consultation` ENUM('Autorisée', 'Interdite') NOT NULL DEFAULT 'Interdite',
    `bulletin_impression` ENUM('Autorisée', 'Interdite') NOT NULL DEFAULT 'Interdite',
    `mis_a_jour_le` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `parametres_utilisateurs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `lycee_id` INT DEFAULT NULL,
    `signature` TEXT DEFAULT NULL,
    `cachet` TEXT DEFAULT NULL,
    `langue_preferee` VARCHAR(10) DEFAULT 'fr_FR',
    `theme_prefere` VARCHAR(50) DEFAULT 'light',
    `notifications_actives` TINYINT(1) DEFAULT 1,
    `date_modification` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =================================================================

-- =================================================================
-- Supplemental MySQL Tables (Synchronized from Migrations 01-27)
-- =================================================================

-- Table `exercices_financiers`
CREATE TABLE exercices_financiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            libelle VARCHAR(100) NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            est_actif BOOLEAN NOT NULL DEFAULT FALSE,
            cloture BOOLEAN NOT NULL DEFAULT FALSE,
            type_exercice ENUM('normal', 'historique_transition') NOT NULL DEFAULT 'normal',
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            CONSTRAINT chk_dates_exercice CHECK (date_fin >= date_debut)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `comptes_financiers`
CREATE TABLE comptes_financiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            nom_compte VARCHAR(150) NOT NULL,
            type_compte ENUM('caisse', 'banque', 'mobile_money', 'autre') NOT NULL,
            solde_courant DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
            responsable_id INT DEFAULT NULL,
            statut ENUM('actif', 'suspendu') NOT NULL DEFAULT 'actif',
            cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `sessions_caisse`
CREATE TABLE sessions_caisse (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            user_id INT NOT NULL,
            compte_id INT NOT NULL,
            date_ouverture DATETIME NOT NULL,
            date_fermeture DATETIME DEFAULT NULL,
            solde_ouverture DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            solde_theorique DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            solde_reel DECIMAL(15, 2) DEFAULT NULL,
            ecart DECIMAL(15, 2) DEFAULT 0.00,
            justificatif_ecart TEXT DEFAULT NULL,
            statut ENUM('ouverte', 'fermee_a_valider', 'fermee_validee') NOT NULL DEFAULT 'ouverte',
            valide_par INT DEFAULT NULL,
            valide_le DATETIME DEFAULT NULL,
            is_active TINYINT GENERATED ALWAYS AS (
                CASE WHEN statut IN ('ouverte', 'fermee_a_valider') THEN 1 ELSE NULL END
            ) STORED,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
            UNIQUE KEY unique_active_session_per_account (compte_id, is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `transferts_financiers`
CREATE TABLE transferts_financiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            compte_source_id INT NOT NULL,
            compte_destination_id INT NOT NULL,
            montant DECIMAL(15, 2) NOT NULL,
            motif VARCHAR(255) NOT NULL,
            statut ENUM('demande', 'autorise', 'complete', 'rejete') NOT NULL DEFAULT 'demande',
            demande_par INT NOT NULL,
            autorise_par INT DEFAULT NULL,
            date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            date_execution DATETIME DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (compte_source_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (compte_destination_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (demande_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (autorise_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
            CONSTRAINT chk_montant_transfert CHECK (montant > 0.00),
            CONSTRAINT chk_different_accounts CHECK (compte_source_id <> compte_destination_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `mouvements_tresorerie`
CREATE TABLE mouvements_tresorerie (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            compte_id INT NOT NULL,
            session_caisse_id INT DEFAULT NULL,
            exercice_financier_id INT NOT NULL,
            transfert_id INT DEFAULT NULL,
            type_mouvement ENUM('entree', 'sortie') NOT NULL,
            montant DECIMAL(15, 2) NOT NULL,
            mode_paiement VARCHAR(50) NOT NULL,
            reference_transaction VARCHAR(150) DEFAULT NULL,
            source_type VARCHAR(100) NOT NULL,
            source_id INT NOT NULL,
            evenement_type ENUM('encaissement', 'annulation', 'remboursement', 'correction', 'remise_coffre_sortie', 'remise_coffre_entree', 'reglement_fournisseur') NOT NULL,
            motif VARCHAR(255) NOT NULL,
            date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_id INT NOT NULL,
            is_aggregate_data BOOLEAN NOT NULL DEFAULT FALSE,
            date_reconstruite BOOLEAN NOT NULL DEFAULT FALSE,
            is_historical_migration BOOLEAN NOT NULL DEFAULT FALSE,
            mode_paiement_reconstruit BOOLEAN NOT NULL DEFAULT FALSE,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (session_caisse_id) REFERENCES sessions_caisse(id) ON DELETE SET NULL,
            FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
            FOREIGN KEY (transfert_id) REFERENCES transferts_financiers(id) ON DELETE SET NULL,
            FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            UNIQUE KEY unique_idempotence_flux (compte_id, source_type, source_id, evenement_type),
            CONSTRAINT chk_montant_positif CHECK (montant > 0.00)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `regularisations_ecarts`
CREATE TABLE regularisations_ecarts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            session_caisse_id INT NOT NULL,
            montant DECIMAL(15, 2) NOT NULL,
            type_ecart ENUM('negatif', 'positif') NOT NULL,
            motif TEXT NOT NULL,
            constate_par INT NOT NULL,
            approuve_par INT NOT NULL,
            date_constat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reference_audit VARCHAR(100) NOT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (session_caisse_id) REFERENCES sessions_caisse(id) ON DELETE RESTRICT,
            FOREIGN KEY (constate_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (approuve_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            UNIQUE KEY unique_audit_ref (reference_audit),
            CONSTRAINT chk_montant_ecart CHECK (montant > 0.00)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `depense_categories`
CREATE TABLE depense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    nom_categorie VARCHAR(150) NOT NULL,
    modifiable TINYINT(1) DEFAULT 1,
    statut VARCHAR(50) DEFAULT 'actif',
    description TEXT DEFAULT NULL,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `depense_centres_couts`
CREATE TABLE depense_centres_couts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    nom_centre VARCHAR(150) NOT NULL,
    statut VARCHAR(50) DEFAULT 'actif',
    description TEXT DEFAULT NULL,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `depense_beneficiaires`
CREATE TABLE depense_beneficiaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    nom_beneficiaire VARCHAR(150) NOT NULL,
    type VARCHAR(50) NOT NULL,
    beneficiaire_utilisateur_id INT DEFAULT NULL,
    statut VARCHAR(50) DEFAULT 'actif',
    telephone VARCHAR(30) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (beneficiaire_utilisateur_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `depenses`
CREATE TABLE depenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    numero_piece VARCHAR(100) NOT NULL,
    categorie_id INT NOT NULL,
    centre_cout_id INT DEFAULT NULL,
    beneficiaire_id INT NOT NULL,
    montant DECIMAL(15, 2) NOT NULL,
    motif TEXT NOT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'brouillon',
    compte_id INT DEFAULT NULL,
    mouvement_tresorerie_id INT DEFAULT NULL,
    cree_par INT NOT NULL,
    exercice_financier_id INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `depenses_pieces`
CREATE TABLE depenses_pieces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    depense_id INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    type_mime VARCHAR(100) NOT NULL,
    taille_octets INT NOT NULL,
    sha256_hash VARCHAR(64) NOT NULL,
    date_televersement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (depense_id) REFERENCES depenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `depenses_historique`
CREATE TABLE depenses_historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    depense_id INT NOT NULL,
    statut_precedent VARCHAR(50) DEFAULT NULL,
    statut_nouveau VARCHAR(50) NOT NULL,
    modifie_par INT NOT NULL,
    motif_transition TEXT,
    date_historique TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (depense_id) REFERENCES depenses(id) ON DELETE CASCADE,
    FOREIGN KEY (modifie_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `depenses_evenements_log`
CREATE TABLE depenses_evenements_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `budgets`
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    exercice_financier_id INT NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'brouillon',
    cree_par INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
    FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    UNIQUE (lycee_id, exercice_financier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `budget_lignes`
CREATE TABLE budget_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `budget_ajustements`
CREATE TABLE budget_ajustements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    type_ajustement VARCHAR(50) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `budget_engagements`
CREATE TABLE budget_engagements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    depense_id INT NOT NULL,
    budget_ligne_id INT NOT NULL,
    montant DECIMAL(15, 2) NOT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'reserve',
    date_engagement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (depense_id) REFERENCES depenses(id) ON DELETE CASCADE,
    FOREIGN KEY (budget_ligne_id) REFERENCES budget_lignes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `budget_historique`
CREATE TABLE budget_historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    evenement VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    execute_par INT NOT NULL,
    date_evenement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (execute_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `devises`
CREATE TABLE devises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    taux_reference DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
    date_taux TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `comptes_comptables`
CREATE TABLE comptes_comptables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    libelle VARCHAR(150) NOT NULL,
    classe INT NOT NULL,
    nature VARCHAR(50) NOT NULL,
    compte_parent_id INT DEFAULT NULL,
    autoriser_ecriture TINYINT(1) DEFAULT 1,
    est_systeme TINYINT(1) DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (compte_parent_id) REFERENCES comptes_comptables(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `journaux_comptables`
CREATE TABLE journaux_comptables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    code VARCHAR(10) NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    type_journal VARCHAR(50) NOT NULL,
    ordre_affichage INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    exercice_comptable_id INT DEFAULT NULL,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (exercice_comptable_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
    UNIQUE (lycee_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `pieces_comptables_sequences`
CREATE TABLE pieces_comptables_sequences (
    journal_id INT NOT NULL,
    annee INT NOT NULL,
    dernier_chrono INT NOT NULL DEFAULT 0,
    PRIMARY KEY (journal_id, annee)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `pieces_comptables`
CREATE TABLE pieces_comptables (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    statut VARCHAR(50) NOT NULL DEFAULT 'valide',
    piece_originale_id INT DEFAULT NULL,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
    FOREIGN KEY (journal_id) REFERENCES journaux_comptables(id) ON DELETE RESTRICT,
    FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    FOREIGN KEY (piece_originale_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
    UNIQUE (source_table, source_id, statut),
    INDEX idx_pieces_date (date_piece),
    INDEX idx_pieces_journal (journal_id),
    INDEX idx_pieces_lycee (lycee_id),
    INDEX idx_pieces_source (source_table, source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `ecritures_comptables`
CREATE TABLE ecritures_comptables (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    FOREIGN KEY (devise_id) REFERENCES devises(id) ON DELETE RESTRICT,
    INDEX idx_ecritures_compte (compte_comptable_id),
    INDEX idx_ecritures_exercice (exercice_comptable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `comptabilite_periodes`
CREATE TABLE comptabilite_periodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `schemas_comptables`
CREATE TABLE schemas_comptables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement VARCHAR(100) NOT NULL UNIQUE,
    compte_debit_numero VARCHAR(20) NOT NULL,
    compte_credit_numero VARCHAR(20) NOT NULL,
    libelle_modele VARCHAR(255) NOT NULL,
    journal_code VARCHAR(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `fournisseurs`
CREATE TABLE fournisseurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NULL,
            raison_sociale VARCHAR(255) NOT NULL,
            code_fournisseur VARCHAR(50) NOT NULL UNIQUE,
            nif VARCHAR(100) NULL,
            rccm VARCHAR(100) NULL,
            adresse TEXT NULL,
            telephone VARCHAR(50) NULL,
            email VARCHAR(255) NULL,
            contact_nom VARCHAR(150) NULL,
            compte_comptable_tiers VARCHAR(20) DEFAULT NULL,
            actif TINYINT(1) DEFAULT 1,
            cree_par INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_categories`
CREATE TABLE achat_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            libelle VARCHAR(150) NOT NULL,
            compte_comptable_charge VARCHAR(20) NOT NULL,
            actif TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_articles`
CREATE TABLE achat_articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categorie_id INT NOT NULL,
            libelle VARCHAR(255) NOT NULL,
            reference VARCHAR(100) NOT NULL UNIQUE,
            unite_mesure VARCHAR(50) NOT NULL,
            prix_unitaire_estime DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            is_service TINYINT(1) DEFAULT 0,
            actif TINYINT(1) DEFAULT 1,
            FOREIGN KEY (categorie_id) REFERENCES achat_categories(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_demandes`
CREATE TABLE achat_demandes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            demandeur_id INT NOT NULL,
            justification TEXT NOT NULL,
            date_demande DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon', -- 'brouillon', 'en_attente_approbation', 'approuvee', 'rejete', 'annule'
            approuve_par INT DEFAULT NULL,
            date_approbation DATETIME DEFAULT NULL,
            motif_statut TEXT DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (demandeur_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (approuve_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_demande_lignes`
CREATE TABLE achat_demande_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            demande_id INT NOT NULL,
            article_id INT NOT NULL,
            quantite_demandee DECIMAL(12,4) NOT NULL,
            prix_unitaire_estime DECIMAL(15,4) NOT NULL,
            budget_ligne_id INT DEFAULT NULL,
            FOREIGN KEY (demande_id) REFERENCES achat_demandes(id) ON DELETE CASCADE,
            FOREIGN KEY (article_id) REFERENCES achat_articles(id) ON DELETE RESTRICT,
            FOREIGN KEY (budget_ligne_id) REFERENCES budget_lignes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_commandes`
CREATE TABLE achat_commandes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            demande_id INT DEFAULT NULL,
            fournisseur_id INT NOT NULL,
            numero_commande VARCHAR(100) NOT NULL,
            date_commande DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon', -- 'brouillon', 'emis', 'reception_partielle', 'executee', 'annulee'
            cree_par INT NOT NULL,
            valide_par INT DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (demande_id) REFERENCES achat_demandes(id) ON DELETE SET NULL,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
            UNIQUE (lycee_id, numero_commande)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_commande_lignes`
CREATE TABLE achat_commande_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            commande_id INT NOT NULL,
            demande_ligne_id INT DEFAULT NULL,
            article_id INT NOT NULL,
            quantite_commandee DECIMAL(12,4) NOT NULL,
            prix_unitaire_negocie DECIMAL(15,4) NOT NULL,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE CASCADE,
            FOREIGN KEY (demande_ligne_id) REFERENCES achat_demande_lignes(id) ON DELETE SET NULL,
            FOREIGN KEY (article_id) REFERENCES achat_articles(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_receptions`
CREATE TABLE achat_receptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            commande_id INT NOT NULL,
            numero_reception VARCHAR(100) NOT NULL,
            date_reception DATE NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'brouillon', -- 'brouillon', 'valide'
            receptionne_par INT NOT NULL,
            details TEXT DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE RESTRICT,
            FOREIGN KEY (receptionne_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
            UNIQUE (lycee_id, numero_reception)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_reception_lignes`
CREATE TABLE achat_reception_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reception_id INT NOT NULL,
            commande_ligne_id INT NOT NULL,
            quantite_receptionnee DECIMAL(12,4) NOT NULL,
            quantite_refusee DECIMAL(12,4) DEFAULT 0.0000,
            motif_refus TEXT DEFAULT NULL,
            FOREIGN KEY (reception_id) REFERENCES achat_receptions(id) ON DELETE CASCADE,
            FOREIGN KEY (commande_ligne_id) REFERENCES achat_commande_lignes(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_factures`
CREATE TABLE achat_factures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            fournisseur_id INT NOT NULL,
            commande_id INT DEFAULT NULL,
            reception_id INT DEFAULT NULL,
            piece_comptable_id INT DEFAULT NULL,
            reference_facture VARCHAR(150) NOT NULL,
            date_facture DATE NOT NULL,
            date_echeance DATE NOT NULL,
            montant_ht DECIMAL(15,2) NOT NULL,
            montant_ttc DECIMAL(15,2) NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'enregistree', -- 'enregistree', 'payee_partiellement', 'payee', 'annulee'
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (commande_id) REFERENCES achat_commandes(id) ON DELETE SET NULL,
            FOREIGN KEY (reception_id) REFERENCES achat_receptions(id) ON DELETE SET NULL,
            FOREIGN KEY (piece_comptable_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
            UNIQUE (lycee_id, fournisseur_id, reference_facture)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_facture_lignes`
CREATE TABLE achat_facture_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            facture_id INT NOT NULL,
            reception_ligne_id INT NOT NULL,
            quantite_facturee DECIMAL(12,4) NOT NULL,
            prix_unitaire_facture DECIMAL(15,4) NOT NULL,
            taux_tva_facture DECIMAL(5,4) DEFAULT 0.0000,
            montant_ht_ligne DECIMAL(15,2) NOT NULL,
            montant_ttc_ligne DECIMAL(15,2) NOT NULL,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE CASCADE,
            FOREIGN KEY (reception_ligne_id) REFERENCES achat_reception_lignes(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_facture_reglements`
CREATE TABLE achat_facture_reglements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            facture_id INT NOT NULL,
            mouvement_tresorerie_id INT NOT NULL,
            montant_alloue DECIMAL(15,2) NOT NULL,
            idempotency_key VARCHAR(100) NOT NULL UNIQUE,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE RESTRICT,
            FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_avoirs_fournisseurs`
CREATE TABLE achat_avoirs_fournisseurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lycee_id INT NOT NULL,
            fournisseur_id INT NOT NULL,
            facture_id INT NOT NULL,
            reference_avoir VARCHAR(150) NOT NULL,
            date_avoir DATE NOT NULL,
            montant_ht DECIMAL(15,2) NOT NULL,
            montant_ttc DECIMAL(15,2) NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT 'enregistre', -- 'enregistre', 'valide'
            piece_comptable_id INT DEFAULT NULL,
            FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
            FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
            FOREIGN KEY (facture_id) REFERENCES achat_factures(id) ON DELETE RESTRICT,
            FOREIGN KEY (piece_comptable_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
            UNIQUE (lycee_id, fournisseur_id, reference_avoir)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `achat_avoir_fournisseur_lignes`
CREATE TABLE achat_avoir_fournisseur_lignes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            avoir_id INT NOT NULL,
            facture_ligne_id INT NOT NULL,
            quantite_avoir DECIMAL(12,4) NOT NULL,
            prix_unitaire_avoir DECIMAL(15,4) NOT NULL,
            montant_ht_ligne DECIMAL(15,2) NOT NULL,
            montant_ttc_ligne DECIMAL(15,2) NOT NULL,
            FOREIGN KEY (avoir_id) REFERENCES achat_avoirs_fournisseurs(id) ON DELETE CASCADE,
            FOREIGN KEY (facture_ligne_id) REFERENCES achat_facture_lignes(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `discipline_types_incidents`
CREATE TABLE discipline_types_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    niveau_gravite VARCHAR(20) NOT NULL DEFAULT 'moyen',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    UNIQUE KEY uk_disc_inc_code (lycee_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_types_sanctions`
CREATE TABLE discipline_types_sanctions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    demande_duree_jours TINYINT(1) NOT NULL DEFAULT 0,
    demande_heures TINYINT(1) NOT NULL DEFAULT 0,
    affiche_sur_bulletin TINYINT(1) NOT NULL DEFAULT 0,
    autorite_min_requise VARCHAR(50) NOT NULL DEFAULT 'surveillant_general',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    UNIQUE KEY uk_disc_sanc_code (lycee_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_incidents`
CREATE TABLE discipline_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    annee_academique_id INT NOT NULL,
    type_incident_id INT NOT NULL,
    date_incident DATE NOT NULL,
    heure_incident TIME NULL,
    lieu VARCHAR(150) NULL,
    description_faits TEXT NOT NULL,
    signale_par_user_id INT NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'signale',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
    FOREIGN KEY (type_incident_id) REFERENCES discipline_types_incidents(id) ON DELETE RESTRICT,
    FOREIGN KEY (signale_par_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    INDEX idx_disc_inc_dates (lycee_id, annee_academique_id, date_incident),
    INDEX idx_disc_inc_statut (lycee_id, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_incident_eleves`
CREATE TABLE discipline_incident_eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    eleve_id INT NOT NULL,
    classe_id INT NOT NULL,
    role_implication VARCHAR(30) NOT NULL DEFAULT 'auteur_principal',
    observation_individuelle TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE RESTRICT,
    INDEX idx_disc_eleve_inc (eleve_id, classe_id),
    INDEX idx_disc_inc_eleve (incident_id, eleve_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_sanctions`
CREATE TABLE discipline_sanctions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    annee_academique_id INT NOT NULL,
    incident_id INT NULL,
    eleve_id INT NOT NULL,
    classe_id INT NOT NULL,
    type_sanction_id INT NOT NULL,
    motif VARCHAR(255) NOT NULL,
    details TEXT NULL,
    prononcee_par_user_id INT NOT NULL,
    date_decision DATE NOT NULL,
    date_debut_execution DATE NULL,
    date_fin_execution DATE NULL,
    duree_jours INT NULL,
    duree_heures INT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'prononcee',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
    FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE SET NULL,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE RESTRICT,
    FOREIGN KEY (type_sanction_id) REFERENCES discipline_types_sanctions(id) ON DELETE RESTRICT,
    FOREIGN KEY (prononcee_par_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    INDEX idx_disc_sanc_eleve (eleve_id, lycee_id),
    INDEX idx_disc_sanc_decision (lycee_id, date_decision),
    INDEX idx_disc_sanc_statut (lycee_id, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_historique`
CREATE TABLE discipline_historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    annee_academique_id INT NULL,
    incident_id INT NULL,
    sanction_id INT NULL,
    eleve_id INT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    statut_avant VARCHAR(50) NULL,
    statut_apres VARCHAR(50) NULL,
    description TEXT NOT NULL,
    metadata TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE SET NULL,
    FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE SET NULL,
    FOREIGN KEY (sanction_id) REFERENCES discipline_sanctions(id) ON DELETE SET NULL,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    INDEX idx_disc_hist_target (lycee_id, incident_id, sanction_id),
    INDEX idx_disc_hist_eleve (lycee_id, eleve_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_documents`
CREATE TABLE discipline_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    incident_id INT NULL,
    sanction_id INT NULL,
    eleve_id INT NULL,
    uploaded_by_user_id INT NOT NULL,
    nom_original VARCHAR(255) NOT NULL,
    nom_stockage VARCHAR(255) NOT NULL,
    chemin_interne VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    taille INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (sanction_id) REFERENCES discipline_sanctions(id) ON DELETE CASCADE,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    INDEX idx_disc_doc_target (lycee_id, incident_id, sanction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_notifications`
CREATE TABLE discipline_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    eleve_id INT NOT NULL,
    incident_id INT NULL,
    sanction_id INT NULL,
    destinataire_nom VARCHAR(150) NOT NULL,
    destinataire_contact VARCHAR(150) NULL,
    mode_notification VARCHAR(50) NOT NULL DEFAULT 'main_propre',
    objet VARCHAR(255) NOT NULL,
    message TEXT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'transmise',
    date_envoi DATETIME NOT NULL,
    created_by_user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE SET NULL,
    FOREIGN KEY (sanction_id) REFERENCES discipline_sanctions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    INDEX idx_disc_notif_target (lycee_id, eleve_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_conseils`
CREATE TABLE discipline_conseils (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    annee_academique_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    titre VARCHAR(255) NOT NULL,
    date_conseil DATE NOT NULL,
    heure_debut TIME NULL,
    heure_fin TIME NULL,
    lieu VARCHAR(150) NULL,
    president_user_id INT NOT NULL,
    secretaire_user_id INT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'planifie',
    observations_generales TEXT NULL,
    cloture_par_user_id INT NULL,
    date_cloture DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
    FOREIGN KEY (president_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    FOREIGN KEY (secretaire_user_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
    FOREIGN KEY (cloture_par_user_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
    UNIQUE KEY uk_disc_cons_code (lycee_id, code),
    INDEX idx_disc_cons_tenant (lycee_id, annee_academique_id, date_conseil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_conseil_membres`
CREATE TABLE discipline_conseil_membres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conseil_id INT NOT NULL,
    user_id INT NOT NULL,
    qualite_membre VARCHAR(50) NOT NULL,
    a_droit_vote TINYINT(1) NOT NULL DEFAULT 1,
    est_present TINYINT(1) NOT NULL DEFAULT 0,
    nom_snapshot VARCHAR(150) NOT NULL,
    fonction_snapshot VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (conseil_id) REFERENCES discipline_conseils(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    UNIQUE KEY uk_disc_cons_user (conseil_id, user_id),
    INDEX idx_disc_cons_membre (conseil_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_conseil_eleves`
CREATE TABLE discipline_conseil_eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conseil_id INT NOT NULL,
    eleve_id INT NOT NULL,
    classe_id INT NOT NULL,
    motif_convocation TEXT NOT NULL,
    presence_eleve TINYINT(1) NOT NULL DEFAULT 0,
    presence_representant_legal TINYINT(1) NOT NULL DEFAULT 0,
    nom_representant_legal VARCHAR(150) NULL,
    decision_statut VARCHAR(30) NOT NULL DEFAULT 'en_attente',
    sanction_id INT NULL,
    motivation_decision TEXT NULL,
    votes_pour INT NOT NULL DEFAULT 0,
    votes_contre INT NOT NULL DEFAULT 0,
    abstentions INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (conseil_id) REFERENCES discipline_conseils(id) ON DELETE CASCADE,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE RESTRICT,
    FOREIGN KEY (sanction_id) REFERENCES discipline_sanctions(id) ON DELETE SET NULL,
    UNIQUE KEY uk_disc_cons_eleve (conseil_id, eleve_id),
    INDEX idx_disc_cons_eleve_target (conseil_id, eleve_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `discipline_conseil_incidents`
CREATE TABLE discipline_conseil_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conseil_id INT NOT NULL,
    incident_id INT NOT NULL,
    eleve_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conseil_id) REFERENCES discipline_conseils(id) ON DELETE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
    UNIQUE KEY uk_disc_cons_inc_eleve (conseil_id, incident_id, eleve_id),
    INDEX idx_disc_cons_inc (conseil_id, incident_id, eleve_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `surveillant_groupes`
CREATE TABLE surveillant_groupes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    annee_academique_id INT NOT NULL,
    nom_groupe VARCHAR(150) NOT NULL,
    description TEXT NULL,
    portee_type ENUM('personnalise', 'niveau', 'cycle', 'etablissement') NOT NULL DEFAULT 'personnalise',
    niveau VARCHAR(50) NULL,
    cycle_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
    FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE SET NULL,
    INDEX idx_sg_lycee_annee (lycee_id, annee_academique_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `surveillant_groupe_classes`
CREATE TABLE surveillant_groupe_classes (
    groupe_id INT NOT NULL,
    classe_id INT NOT NULL,
    PRIMARY KEY (groupe_id, classe_id),
    FOREIGN KEY (groupe_id) REFERENCES surveillant_groupes(id) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `surveillant_affectations`
CREATE TABLE surveillant_affectations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    annee_academique_id INT NOT NULL,
    surveillant_id INT NULL,
    type_responsabilite ENUM('surveillant_classe', 'surveillant_general') NOT NULL DEFAULT 'surveillant_classe',
    portee_type ENUM('classe', 'groupe_classes', 'niveau', 'cycle', 'etablissement') NOT NULL,
    classe_id INT NULL,
    groupe_id INT NULL,
    cycle_id INT NULL,
    niveau_code VARCHAR(50) NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    statut ENUM('actif', 'suspendu', 'termine') NOT NULL DEFAULT 'actif',
    est_reprise_legacy TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    classe_nom_snapshot VARCHAR(100) NULL,
    groupe_nom_snapshot VARCHAR(150) NULL,
    cycle_nom_snapshot VARCHAR(100) NULL,
    surveillant_nom_snapshot VARCHAR(200) NULL,
    target_classe_id INT GENERATED ALWAYS AS (COALESCE(classe_id, 0)) STORED,
    target_groupe_id INT GENERATED ALWAYS AS (COALESCE(groupe_id, 0)) STORED,
    target_cycle_id INT GENERATED ALWAYS AS (COALESCE(cycle_id, 0)) STORED,
    target_niveau_code VARCHAR(50) GENERATED ALWAYS AS (COALESCE(niveau_code, '')) STORED,
    active_status_sentinel TINYINT GENERATED ALWAYS AS (IF(statut = 'actif', 1, NULL)) STORED,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
    FOREIGN KEY (surveillant_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
    FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE SET NULL,
    FOREIGN KEY (groupe_id) REFERENCES surveillant_groupes(id) ON DELETE SET NULL,
    FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
    CONSTRAINT chk_surv_aff_exclusive_target CHECK (
        (portee_type = 'classe'        AND classe_id IS NOT NULL AND groupe_id IS NULL     AND cycle_id IS NULL     AND niveau_code IS NULL) OR
        (portee_type = 'groupe_classes' AND groupe_id IS NOT NULL AND classe_id IS NULL     AND cycle_id IS NULL     AND niveau_code IS NULL) OR
        (portee_type = 'niveau'        AND niveau_code IS NOT NULL AND classe_id IS NULL    AND groupe_id IS NULL    AND cycle_id IS NULL) OR
        (portee_type = 'cycle'         AND cycle_id IS NOT NULL  AND classe_id IS NULL     AND groupe_id IS NULL    AND niveau_code IS NULL) OR
        (portee_type = 'etablissement' AND classe_id IS NULL     AND groupe_id IS NULL     AND cycle_id IS NULL     AND niveau_code IS NULL)
    ),
    CONSTRAINT chk_surv_aff_active_user CHECK (
        statut != 'actif' OR surveillant_id IS NOT NULL
    ),
    UNIQUE KEY uk_surv_aff_active_only (
        surveillant_id,
        annee_academique_id,
        type_responsabilite,
        portee_type,
        target_classe_id,
        target_groupe_id,
        target_cycle_id,
        target_niveau_code,
        active_status_sentinel
    ),
    INDEX idx_sa_surv_statut (surveillant_id, statut, annee_academique_id),
    INDEX idx_sa_lycee_annee (lycee_id, annee_academique_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- Supplemental Module Tables (Synchronized from Migrations 01-33)
-- =================================================================

-- Table `reporting_snapshots`
CREATE TABLE reporting_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    exercice_financier_id INT NOT NULL,
    periode_id INT DEFAULT NULL,
    date_snapshot DATE NOT NULL,
    frequence VARCHAR(20) NOT NULL DEFAULT 'mensuel',
    kpi_code VARCHAR(100) NOT NULL,
    valeur_numerique DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE CASCADE,
    FOREIGN KEY (periode_id) REFERENCES comptabilite_periodes(id) ON DELETE SET NULL,
    UNIQUE (lycee_id, exercice_financier_id, date_snapshot, frequence, kpi_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `reporting_kpi_seuils`
CREATE TABLE reporting_kpi_seuils (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    kpi_code VARCHAR(100) NOT NULL,
    seuil_min DECIMAL(15, 2) DEFAULT 0.00,
    seuil_warning DECIMAL(15, 2) DEFAULT 0.00,
    objectif DECIMAL(15, 2) DEFAULT 0.00,
    seuil_danger DECIMAL(15, 2) DEFAULT 0.00,
    sens_variation VARCHAR(20) NOT NULL DEFAULT 'croissant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    UNIQUE (lycee_id, kpi_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `reporting_previsions_config`
CREATE TABLE reporting_previsions_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    methode VARCHAR(100) NOT NULL,
    horizon INT NOT NULL DEFAULT 3,
    scenario VARCHAR(50) NOT NULL DEFAULT 'central',
    hypotheses TEXT DEFAULT NULL,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    UNIQUE (lycee_id, scenario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `reporting_audit_logs`
CREATE TABLE reporting_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lycee_id INT NOT NULL,
    operation VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    date_operation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_cycles_assignments`
CREATE TABLE personnel_cycles_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    cycle_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
    FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE CASCADE,
    UNIQUE (personnel_id, cycle_id, date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_details`
CREATE TABLE personnel_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL UNIQUE,
    num_cnss VARCHAR(100) DEFAULT NULL,
    situation_matrimoniale VARCHAR(50) DEFAULT 'celibataire',
    nombre_enfants INT DEFAULT 0,
    statut_rh VARCHAR(50) DEFAULT 'en_activite',
    date_sortie DATE DEFAULT NULL,
    motif_sortie TEXT DEFAULT NULL,
    remarques TEXT DEFAULT NULL,
    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
    INDEX idx_pers_det_statut (statut_rh)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_fonctions`
CREATE TABLE personnel_fonctions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT DEFAULT NULL,
    libelle VARCHAR(150) NOT NULL,
    departement VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_contrats_historique`
CREATE TABLE personnel_contrats_historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    type_contrat_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE DEFAULT NULL,
    salaire_base DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    devise VARCHAR(10) DEFAULT NULL,
    volume_horaire_mensuel DECIMAL(6,2) DEFAULT NULL,
    statut_contrat VARCHAR(50) DEFAULT 'actif',
    commentaire TEXT DEFAULT NULL,
    cree_par INT DEFAULT NULL,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
    FOREIGN KEY (type_contrat_id) REFERENCES type_contrat(id_contrat) ON DELETE CASCADE,
    INDEX idx_pers_con_dates (personnel_id, statut_contrat, date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_documents`
CREATE TABLE personnel_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    type_document VARCHAR(100) NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier TEXT NOT NULL,
    confidentiel TINYINT(1) DEFAULT 1,
    version INT DEFAULT 1,
    date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_par INT DEFAULT NULL,
    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_historique_mouvements`
CREATE TABLE personnel_historique_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    type_mouvement VARCHAR(100) NOT NULL,
    date_mouvement DATETIME NOT NULL,
    motif TEXT DEFAULT NULL,
    auteur_id INT NOT NULL,
    ancien_etat TEXT DEFAULT NULL,
    nouvel_etat TEXT DEFAULT NULL,
    lycee_id INT DEFAULT NULL,
    cycle_id INT DEFAULT NULL,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
    INDEX idx_pers_mov_pers (personnel_id, date_mouvement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_entites_juridiques`
CREATE TABLE paie_entites_juridiques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT DEFAULT NULL,
    organisation_id INT DEFAULT NULL,
    raison_sociale VARCHAR(255) NOT NULL,
    sigle VARCHAR(50) DEFAULT NULL,
    immatriculation_fiscale VARCHAR(100) DEFAULT NULL,
    numero_nif VARCHAR(100) DEFAULT NULL,
    numero_cnss_employeur VARCHAR(100) DEFAULT NULL,
    numero_cnss VARCHAR(100) DEFAULT NULL,
    adresse_siege TEXT DEFAULT NULL,
    adresse TEXT DEFAULT NULL,
    juridiction_id INT DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    UNIQUE KEY uk_paie_entite_lycee (lycee_id, raison_sociale)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_unites_organisationnelles`
CREATE TABLE personnel_unites_organisationnelles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    parent_id INT NULL,
    type_unite VARCHAR(50) NOT NULL DEFAULT 'departement',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES personnel_unites_organisationnelles(id) ON DELETE SET NULL,
    UNIQUE KEY uk_pers_unite_code (lycee_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_postes`
CREATE TABLE personnel_postes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    unite_id INT NULL,
    fonction_id INT NULL,
    code VARCHAR(50) NOT NULL,
    titre VARCHAR(150) NOT NULL,
    description TEXT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (unite_id) REFERENCES personnel_unites_organisationnelles(id) ON DELETE SET NULL,
    FOREIGN KEY (fonction_id) REFERENCES personnel_fonctions(id) ON DELETE SET NULL,
    UNIQUE KEY uk_pers_poste_code (lycee_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_affectations_historique`
CREATE TABLE personnel_affectations_historique (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    unite_id INT NULL,
    poste_id INT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    motif_changement VARCHAR(255) NULL,
    est_principale TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
    FOREIGN KEY (unite_id) REFERENCES personnel_unites_organisationnelles(id) ON DELETE SET NULL,
    FOREIGN KEY (poste_id) REFERENCES personnel_postes(id) ON DELETE SET NULL,
    INDEX idx_pers_aff_target (personnel_id, date_debut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_contrat_composants`
CREATE TABLE personnel_contrat_composants (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    contrat_id INT NOT NULL,
    code_composant VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    type_composant VARCHAR(30) NOT NULL,
    mode_calcul VARCHAR(30) NOT NULL DEFAULT 'montant_fixe',
    valeur DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    soumis_cotisation TINYINT(1) NOT NULL DEFAULT 1,
    soumis_impot TINYINT(1) NOT NULL DEFAULT 1,
    periodicite VARCHAR(20) NOT NULL DEFAULT 'mensuel',
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (contrat_id) REFERENCES personnel_contrats_historique(id) ON DELETE CASCADE,
    INDEX idx_pers_comp_contrat (contrat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `personnel_contrat_financements`
CREATE TABLE personnel_contrat_financements (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    contrat_id INT NOT NULL,
    source_financement VARCHAR(50) NOT NULL DEFAULT 'fond_propre',
    centre_cout_id INT NULL,
    pourcentage_imputation DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (contrat_id) REFERENCES personnel_contrats_historique(id) ON DELETE CASCADE,
    FOREIGN KEY (centre_cout_id) REFERENCES depense_centres_couts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `sys_idempotency_keys`
CREATE TABLE sys_idempotency_keys (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    idempotency_key VARCHAR(100) NOT NULL,
    scope VARCHAR(100) NOT NULL,
    request_hash VARCHAR(64) NOT NULL,
    response_code INT NOT NULL,
    response_body JSON NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uk_sys_idemp_key (idempotency_key, scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_baremes`
CREATE TABLE paie_baremes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NULL,
    code VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    type_bareme VARCHAR(30) NOT NULL,
    tranche_min DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tranche_max DECIMAL(15,2) NULL,
    taux DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    montant_forfaitaire DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    INDEX idx_paie_bar_type (type_bareme, lycee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_baremes_tranches`
CREATE TABLE paie_baremes_tranches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bareme_id INT NOT NULL,
    tranche_min DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tranche_max DECIMAL(15,2) NULL,
    taux DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    montant_forfaitaire DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (bareme_id) REFERENCES paie_baremes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_grilles_salariales`
CREATE TABLE paie_grilles_salariales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NULL,
    code_grille VARCHAR(50) NOT NULL,
    categorie VARCHAR(50) NOT NULL,
    echelon VARCHAR(50) NOT NULL,
    salaire_base DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    taux_horaire DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    UNIQUE KEY uk_paie_grille_cat_ech (lycee_id, code_grille, categorie, echelon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_periodes`
CREATE TABLE paie_periodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NOT NULL,
    exercice_financier_id INT NOT NULL,
    code_periode VARCHAR(30) NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'ouverte',
    date_cloture DATETIME NULL,
    cloture_par INT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    FOREIGN KEY (exercice_financier_id) REFERENCES exercices_financiers(id) ON DELETE RESTRICT,
    FOREIGN KEY (cloture_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
    UNIQUE KEY uk_paie_per_code (lycee_id, code_periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_regles`
CREATE TABLE paie_regles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lycee_id INT NULL,
    code_rubrique VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    type_rubrique VARCHAR(30) NOT NULL,
    nature VARCHAR(30) NOT NULL DEFAULT 'gain',
    phase_calcul INT NOT NULL DEFAULT 1,
    rang_execution INT NOT NULL DEFAULT 100,
    formule_type VARCHAR(30) NOT NULL DEFAULT 'montant_fixe',
    formule_custom TEXT NULL,
    type_valeur VARCHAR(30) NOT NULL DEFAULT 'montant',
    valeur_defaut DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    soumis_cotisation TINYINT(1) NOT NULL DEFAULT 1,
    soumis_impot TINYINT(1) NOT NULL DEFAULT 1,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
    UNIQUE KEY uk_paie_regle_code (lycee_id, code_rubrique)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_bulletins`
CREATE TABLE paie_bulletins (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    periode_id INT NOT NULL,
    personnel_id INT NOT NULL,
    contrat_id INT NOT NULL,
    version INT NOT NULL DEFAULT 1,
    bulletin_parent_id BIGINT NULL,
    brut_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    charges_salariales DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    impots_retenus DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    charges_patronales DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    net_a_payer DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    statut VARCHAR(30) NOT NULL DEFAULT 'calcule',
    date_calcul DATETIME NOT NULL,
    cree_par INT NOT NULL,
    valide_par INT NULL,
    date_validation DATETIME NULL,
    comptabilise_le DATETIME NULL,
    piece_comptable_id INT NULL,
    FOREIGN KEY (periode_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT,
    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    FOREIGN KEY (contrat_id) REFERENCES personnel_contrats_historique(id) ON DELETE RESTRICT,
    FOREIGN KEY (bulletin_parent_id) REFERENCES paie_bulletins(id) ON DELETE SET NULL,
    FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
    FOREIGN KEY (piece_comptable_id) REFERENCES pieces_comptables(id) ON DELETE SET NULL,
    UNIQUE KEY uk_paie_bull_per_ver (periode_id, personnel_id, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_bulletin_lignes`
CREATE TABLE paie_bulletin_lignes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id BIGINT NOT NULL,
    regle_id INT NULL,
    code_rubrique VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    base_calcul DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    taux_salarial DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
    montant_salarial DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    taux_patronal DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
    montant_patronal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    ordre_affichage INT NOT NULL DEFAULT 100,
    FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
    FOREIGN KEY (regle_id) REFERENCES paie_regles(id) ON DELETE SET NULL,
    INDEX idx_paie_ligne_bull (bulletin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_cahier_texte_validations`
CREATE TABLE paie_cahier_texte_validations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_bulletin_heures`
CREATE TABLE paie_bulletin_heures (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id BIGINT NOT NULL,
    cahier_validation_id INT NOT NULL,
    heures_effectuees DECIMAL(5,2) NOT NULL,
    taux_horaire DECIMAL(15,4) NOT NULL,
    montant_total DECIMAL(15,4) NOT NULL,
    FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE,
    FOREIGN KEY (cahier_validation_id) REFERENCES paie_cahier_texte_validations(id) ON DELETE RESTRICT,
    UNIQUE KEY uk_paie_bul_cahier (bulletin_id, cahier_validation_id),
    INDEX idx_paie_hrs_bul (bulletin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_bulletin_contrat_snapshot`
CREATE TABLE paie_bulletin_contrat_snapshot (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id BIGINT NOT NULL UNIQUE,
    contrat_id INT NOT NULL,
    salaire_base DECIMAL(15,2) NOT NULL,
    statut_contrat VARCHAR(50) NOT NULL,
    FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_bulletin_financements`
CREATE TABLE paie_bulletin_financements (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id BIGINT NOT NULL,
    centre_cout_id INT NULL,
    source_financement VARCHAR(50) NOT NULL,
    pourcentage_imputation DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_bulletin_regles_snapshot`
CREATE TABLE paie_bulletin_regles_snapshot (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id BIGINT NOT NULL,
    code_rubrique VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    type_rubrique VARCHAR(30) NOT NULL,
    FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_bulletin_regle_tranches_snapshot`
CREATE TABLE paie_bulletin_regle_tranches_snapshot (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    bulletin_regle_snapshot_id BIGINT NOT NULL,
    tranche_min DECIMAL(15,2) NOT NULL,
    tranche_max DECIMAL(15,2) NULL,
    taux DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (bulletin_regle_snapshot_id) REFERENCES paie_bulletin_regles_snapshot(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_regularisations`
CREATE TABLE paie_regularisations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NULL,
    source_type VARCHAR(30) NULL,
    periode_source_id INT NULL,
    bulletin_source_id BIGINT NULL,
    periode_destination_id INT NOT NULL,
    type_regularisation VARCHAR(50) NOT NULL,
    motif TEXT NOT NULL,
    montant_brut_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    montant_net_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    statut VARCHAR(20) NOT NULL DEFAULT 'brouillon',
    cree_par INT NOT NULL,
    valide_par INT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (personnel_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    FOREIGN KEY (periode_source_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT,
    FOREIGN KEY (bulletin_source_id) REFERENCES paie_bulletins(id) ON DELETE RESTRICT,
    FOREIGN KEY (periode_destination_id) REFERENCES paie_periodes(id) ON DELETE RESTRICT,
    FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
    INDEX idx_paie_regu_src (bulletin_source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_regularisation_lignes`
CREATE TABLE paie_regularisation_lignes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    regularisation_id BIGINT NOT NULL,
    code_rubrique VARCHAR(50) NOT NULL,
    libelle VARCHAR(150) NOT NULL,
    base_calcul_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    montant_salarial_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    montant_patronal_delta DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    FOREIGN KEY (regularisation_id) REFERENCES paie_regularisations(id) ON DELETE CASCADE,
    INDEX idx_paie_regu_lig (regularisation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_regularisation_integrations`
CREATE TABLE paie_regularisation_integrations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    regularisation_id BIGINT NOT NULL,
    bulletin_destination_id BIGINT NOT NULL,
    montant_brut_integre DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    montant_net_integre DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    statut VARCHAR(20) NOT NULL DEFAULT 'integre',
    integre_par INT NOT NULL,
    integre_le DATETIME NOT NULL,
    FOREIGN KEY (regularisation_id) REFERENCES paie_regularisations(id) ON DELETE RESTRICT,
    FOREIGN KEY (bulletin_destination_id) REFERENCES paie_bulletins(id) ON DELETE RESTRICT,
    FOREIGN KEY (integre_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    UNIQUE KEY uk_paie_regu_integ (regularisation_id, bulletin_destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_reglements`
CREATE TABLE paie_reglements (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id BIGINT NOT NULL,
    compte_id INT NOT NULL,
    session_caisse_id INT NULL,
    mouvement_tresorerie_id INT NULL,
    montant DECIMAL(15,2) NOT NULL,
    mode_paiement VARCHAR(30) NOT NULL,
    reference_paiement VARCHAR(100) NULL,
    date_reglement DATETIME NOT NULL,
    execute_par INT NOT NULL,
    statut VARCHAR(30) NOT NULL DEFAULT 'valide',
    FOREIGN KEY (bulletin_id) REFERENCES paie_bulletins(id) ON DELETE RESTRICT,
    FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
    FOREIGN KEY (session_caisse_id) REFERENCES sessions_caisse(id) ON DELETE SET NULL,
    FOREIGN KEY (mouvement_tresorerie_id) REFERENCES mouvements_tresorerie(id) ON DELETE SET NULL,
    FOREIGN KEY (execute_par) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
    INDEX idx_paie_regl_bull (bulletin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paie_audit_logs`
CREATE TABLE paie_audit_logs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `bulletin_details`
CREATE TABLE bulletin_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulletin_id INT NOT NULL,
    matiere_id INT NOT NULL,
    nom_matiere_snapshot VARCHAR(255) NOT NULL,
    moyenne_matiere DECIMAL(5,2) NOT NULL,
    coefficient_snapshot DECIMAL(4,2) NOT NULL,
    points_ponderes DECIMAL(6,2) NOT NULL,
    rang_matiere VARCHAR(20) NULL,
    moyenne_classe_matiere DECIMAL(5,2) NULL,
    appreciation_matiere TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bulletin_id) REFERENCES bulletins(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id_matiere) ON DELETE RESTRICT,
    UNIQUE KEY uk_bulletin_matiere (bulletin_id, matiere_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `affectations_caisses`
CREATE TABLE affectations_caisses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compte_id INT NOT NULL,
    cashier_id INT NOT NULL,
    statut ENUM('actif', 'suspendu') NOT NULL DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE CASCADE,
    FOREIGN KEY (cashier_id) REFERENCES utilisateurs(id_user) ON DELETE CASCADE,
    UNIQUE KEY uk_compte_cashier (compte_id, cashier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `modes_paiement`
CREATE TABLE modes_paiement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    type_compte_defaut ENUM('caisse', 'banque', 'mobile_money', 'autre') NOT NULL,
    actif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table `paiement_ventilations`
CREATE TABLE paiement_ventilations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paiement_type ENUM('inscription', 'mensualite') NOT NULL,
    paiement_id INT NOT NULL,
    mode_paiement_id INT NOT NULL,
    compte_id INT NOT NULL,
    montant DECIMAL(15,2) NOT NULL,
    reference_externe VARCHAR(100) NULL,
    mouvement_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mode_paiement_id) REFERENCES modes_paiement(id) ON DELETE RESTRICT,
    FOREIGN KEY (compte_id) REFERENCES comptes_financiers(id) ON DELETE RESTRICT,
    INDEX idx_ventilation_target (paiement_type, paiement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


SET FOREIGN_KEY_CHECKS = 1;
