-- =================================================================
-- Schema for the High School Management Application
-- =================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist to ensure a clean slate on execution
DROP TABLE IF EXISTS `salaires`, `cahier_texte`, `type_contrat`, `emploi_du_temps`, `role_permissions`, `permissions`, `tests_entree`, `traductions`, `licences`, `cartes_scolaires`, `boutique_ventes`, `boutique_achats`, `boutique_articles`, `paiements`, `notes_compositions`, `notes_devoirs`, `etudes`, `affectations_pedagogiques`, `classe_matieres`, `eleves`, `matieres`, `classes`, `salles`, `cycles`, `utilisateurs`, `roles`, `parametres_generaux`, `annees_academiques`, `personnel_assignments`, `param_lycee`, `param_general`, `param_devoir`, `param_composition`, `bulletins`, `parametres_evaluations`, `deblocages_notes`, `classe_parametres`, `inscriptions`, `mensualites`, `mensualite_details`, `frais`, `modele_carte`, `modele_bulletin`, `notifications`, `evaluations`, `presences`, `horaire_enseignant`, `sequences`, `surveillant_classes`, `surveillant_niveaux`, `surveillant_general`, `series`, `carte_templates`, `carte_objects`, `politiques_financieres`, `parametres_financiers_eleves`, `parametres_financiers_historique`, `etats_financiers_eleves`, `journal_comptable`;

-- =================================================================
-- General and Core Tables
-- =================================================================

-- Table for Academic Years
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
    `statut` ENUM('ouverte', 'fermee') NOT NULL DEFAULT 'ouverte',
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                lycee_id INT NOT NULL,
                code VARCHAR(50) NOT NULL,
                libelle VARCHAR(150) NOT NULL,
                niveau_gravite VARCHAR(20) NOT NULL DEFAULT 'moyen',
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                UNIQUE(lycee_id, code)
            );";
        } else {
            $sql_incidents = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                lycee_id INT NOT NULL,
                code VARCHAR(50) NOT NULL,
                libelle VARCHAR(150) NOT NULL,
                demande_duree_jours TINYINT(1) NOT NULL DEFAULT 0,
                demande_heures TINYINT(1) NOT NULL DEFAULT 0,
                affiche_sur_bulletin TINYINT(1) NOT NULL DEFAULT 0,
                autorite_min_requise VARCHAR(50) NOT NULL DEFAULT 'surveillant_general',
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                UNIQUE(lycee_id, code)
            );";
        } else {
            $sql_sanctions = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
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
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
                FOREIGN KEY (type_incident_id) REFERENCES discipline_types_incidents(id) ON DELETE RESTRICT,
                FOREIGN KEY (signale_par_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
            );";
        } else {
            $sql_incidents = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                incident_id INT NOT NULL,
                eleve_id INT NOT NULL,
                classe_id INT NOT NULL,
                role_implication VARCHAR(30) NOT NULL DEFAULT 'auteur_principal',
                observation_individuelle TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE CASCADE,
                FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
                FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE RESTRICT
            );";
        } else {
            $sql_incident_eleves = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
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
                date_levee_annulation DATETIME NULL,
                motif_levee_annulation TEXT NULL,
                par_user_id_levee_annulation INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
                FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE SET NULL,
                FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
                FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE RESTRICT,
                FOREIGN KEY (type_sanction_id) REFERENCES discipline_types_sanctions(id) ON DELETE RESTRICT,
                FOREIGN KEY (prononcee_par_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
            );";
        } else {
            $sql_sanctions = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
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
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
            );";
        } else {
            $sql_historique = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
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
                FOREIGN KEY (uploaded_by_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
            );";
        } else {
            $sql_documents = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
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
                FOREIGN KEY (created_by_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT
            );";
        } else {
            $sql_notifications = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
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
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                FOREIGN KEY (annee_academique_id) REFERENCES annees_academiques(id) ON DELETE RESTRICT,
                FOREIGN KEY (president_user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
                FOREIGN KEY (secretaire_user_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
                FOREIGN KEY (cloture_par_user_id) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
                UNIQUE (lycee_id, code)
            );";
        } else {
            $sql_conseils = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                conseil_id INT NOT NULL,
                user_id INT NOT NULL,
                qualite_membre VARCHAR(50) NOT NULL,
                a_droit_vote TINYINT(1) NOT NULL DEFAULT 1,
                est_present TINYINT(1) NOT NULL DEFAULT 0,
                nom_snapshot VARCHAR(150) NOT NULL,
                fonction_snapshot VARCHAR(100) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conseil_id) REFERENCES discipline_conseils(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES utilisateurs(id_user) ON DELETE RESTRICT,
                UNIQUE (conseil_id, user_id)
            );";
        } else {
            $sql_membres = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
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
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conseil_id) REFERENCES discipline_conseils(id) ON DELETE CASCADE,
                FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
                FOREIGN KEY (classe_id) REFERENCES classes(id_classe) ON DELETE RESTRICT,
                FOREIGN KEY (sanction_id) REFERENCES discipline_sanctions(id) ON DELETE SET NULL,
                UNIQUE (conseil_id, eleve_id)
            );";
        } else {
            $sql_eleves = "
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
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                conseil_id INT NOT NULL,
                incident_id INT NOT NULL,
                eleve_id INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conseil_id) REFERENCES discipline_conseils(id) ON DELETE CASCADE,
                FOREIGN KEY (incident_id) REFERENCES discipline_incidents(id) ON DELETE CASCADE,
                FOREIGN KEY (eleve_id) REFERENCES eleves(id_eleve) ON DELETE CASCADE,
                UNIQUE (conseil_id, incident_id, eleve_id)
            );";
        } else {
            $sql_incidents = "
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

SET FOREIGN_KEY_CHECKS = 1;
