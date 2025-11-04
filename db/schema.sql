-- =================================================================
-- Schema for the High School Management Application
-- =================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist to ensure a clean slate on execution
DROP TABLE IF EXISTS `salaires`, `cahier_texte`, `type_contrat`, `emploi_du_temps`, `role_permissions`, `permissions`, `tests_entree`, `traductions`, `licences`, `cartes_scolaires`, `boutique_achats`, `boutique_articles`, `paiements`, `notes_compositions`, `notes_devoirs`, `etudes`, `enseignant_matieres`, `classe_matieres`, `eleves`, `matieres`, `classes`, `salles`, `cycles`, `utilisateurs`, `roles`, `parametres_generaux`, `annees_academiques`, `personnel_assignments`, `param_lycee`, `param_general`, `param_devoir`, `param_composition`, `bulletins`, `parametres_evaluations`;

-- =================================================================
-- General and Core Tables
-- =================================================================

-- Table for Academic Years
CREATE TABLE `annees_academiques` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `libelle` VARCHAR(100) NOT NULL UNIQUE, -- e.g., "2024-2025"
    `date_debut` DATE NOT NULL,
    `date_fin` DATE NOT NULL,
    `est_active` BOOLEAN NOT NULL DEFAULT FALSE
);

-- Table for high schools (Lycees)
-- Table for School-specific administrative settings
CREATE TABLE `param_lycee` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_lycee` VARCHAR(255) NOT NULL,
    `sigle` VARCHAR(50),
    `tel` VARCHAR(50),
    `email` VARCHAR(255) UNIQUE,
    `ville` VARCHAR(100),
    `quartier` VARCHAR(100),
    `ruelle` VARCHAR(100),
    `boitePostale` VARCHAR(50),
    `arrete` VARCHAR(255),
    `arrondissement` VARCHAR(100),
    `devise` VARCHAR(255),
    `logo` VARCHAR(255),
    `typeLycee` ENUM('public', 'prive', 'semi-public') NOT NULL,
    `boutique` BOOLEAN NOT NULL DEFAULT FALSE
);

-- Table for general system settings (scoped per Lycee)
CREATE TABLE `param_general` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `devisePays` VARCHAR(100),
    `monnaie` VARCHAR(10),
    `modalitePaiement` VARCHAR(255), -- e.g., 'Especes, Versement, Mobile Money'
    `nbLangue` INT DEFAULT 1,
    `langue_1` VARCHAR(50) DEFAULT 'Francais',
    `langue_2` VARCHAR(50),
    `sequenceAnnuelle` ENUM('Semestrielle', 'Trimestrielle') NOT NULL DEFAULT 'Trimestrielle',
    `creeLe` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

-- Table for users
-- Table for users (now serving as personnel table)
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
    `mot_de_passe` VARCHAR(255) NOT NULL, -- Should be hashed
    `fonction` VARCHAR(100), -- e.g., enseignant, proviseur, surveillant
    `role_id` INT,
    `lycee_id` INT, -- NULL for global roles
    `contrat_id` INT,
    `date_embauche` DATE,
    `actif` BOOLEAN DEFAULT TRUE,
    `photo` VARCHAR(255),
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`) ON DELETE SET NULL
);

-- Note: A foreign key for contrat_id will be added after the type_contrat table is defined.


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
    `nom_cycle` VARCHAR(100) NOT NULL,
    `niveau_debut` INT,
    `niveau_fin` INT
);

-- Table for classes
CREATE TABLE `classes` (
    `id_classe` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_classe` VARCHAR(100) NOT NULL,
    `niveau` VARCHAR(50),
    `serie` VARCHAR(50),
    `numero_classe` INT,
    `cycle_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `salle_id` INT, -- Default room for the class
    FOREIGN KEY (`cycle_id`) REFERENCES `cycles`(`id_cycle`),
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`salle_id`) REFERENCES `salles`(`id_salle`) ON DELETE SET NULL
);

-- Table for subjects
CREATE TABLE `matieres` (
    `id_matiere` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_matiere` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `type` VARCHAR(100), -- e.g., scientifique, littéraire, technique
    `cycle_concerne` VARCHAR(100), -- e.g., CEG, Lycée
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
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    UNIQUE KEY `unique_classe_matiere` (`classe_id`, `matiere_id`)
);

-- Junction table for teachers, classes, and subjects
CREATE TABLE `enseignant_matieres` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `enseignant_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `actif` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_enseignant_matiere_annee` (`enseignant_id`, `classe_id`, `matiere_id`, `annee_academique_id`)
);


-- =================================================================
-- Academic Evaluation Tables
-- =================================================================

-- Table for academic sequences (e.g., Trimester, Semester)
CREATE TABLE `sequences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT NOT NULL,
    `nom` VARCHAR(255) NOT NULL, -- e.g., Séquence 1, Trimestre 2
    `type` ENUM('trimestrielle', 'semestrielle') NOT NULL,
    `date_debut` DATE NOT NULL,
    `date_fin` DATE NOT NULL,
    `statut` ENUM('ouverte', 'fermee') NOT NULL DEFAULT 'ouverte',
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE
);

-- Table for evaluation settings (homework and exams)
-- Table for homework parameters
CREATE TABLE `param_devoir` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `anneeId` INT NOT NULL,
    `nombreDevoirParSequence` INT,
    `noteMaximale` DECIMAL(5, 2) DEFAULT 20.00,
    `dateDebutInsertion` DATETIME,
    `dateFinInsertion` DATETIME,
    `deblocageUrgence` BOOLEAN DEFAULT FALSE,
    `classeId` INT,
    `matiereId` INT,
    `creePar` INT,
    `creeLe` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`anneeId`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`classeId`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiereId`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`creePar`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- Table for exam parameters
CREATE TABLE `param_composition` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `anneeId` INT NOT NULL,
    `nombreCompositionParSequence` INT,
    `noteMaximale` DECIMAL(5, 2) DEFAULT 20.00,
    `dateDebutInsertion` DATETIME,
    `dateFinInsertion` DATETIME,
    `deblocageUrgence` BOOLEAN DEFAULT FALSE,
    `classeId` INT,
    `matiereId` INT,
    `creePar` INT,
    `creeLe` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`anneeId`) REFERENCES `annees_academiques`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`classeId`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiereId`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`creePar`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- Table for grades (unified for homework and exams)


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
    `photo` VARCHAR(255),
    `email` VARCHAR(255) UNIQUE,
    `telephone` VARCHAR(50),
    `statut` VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table linking students to classes for a specific academic year
CREATE TABLE `etudes` (
    `id_etude` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT,
    `actif` BOOLEAN DEFAULT FALSE, -- Activated upon full payment/validation
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL
);

-- Table for homework grades
-- Note: The tables `notes_devoirs` and `notes_compositions` are now replaced by the `evaluations` table.

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

-- Table for payments
-- Table for initial enrollment fees
CREATE TABLE `inscriptions` (
    `id_inscription` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT,
    `montant_total` DECIMAL(10, 2) NOT NULL,
    `montant_verse` DECIMAL(10, 2) NOT NULL,
    `reste_a_payer` DECIMAL(10, 2) NOT NULL,
    `details_frais` JSON,
    `user_id` INT,
    `date_inscription` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- Table for monthly/sequential payments
CREATE TABLE `mensualites` (
    `id_mensualite` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    `annee_academique_id` INT,
    `mois_ou_sequence` VARCHAR(50) NOT NULL, -- e.g., 'Octobre', 'Trimestre 1'
    `montant_verse` DECIMAL(10, 2) NOT NULL,
    `date_paiement` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `user_id` INT,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
);

-- Table for defining school fees (tuition, etc.)
CREATE TABLE `frais` (
    `id_frais` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `niveau` VARCHAR(50) NOT NULL,
    `serie` VARCHAR(50),
    `frais_inscription` DECIMAL(10, 2) NOT NULL,
    `frais_mensuel` DECIMAL(10, 2) NOT NULL,
    `autres_frais` JSON, -- For additional fixed fees like uniform, insurance, etc.
    `annee_academique_id` INT,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_frais` (`lycee_id`, `niveau`, `serie`, `annee_academique_id`)
);

-- Table for shop articles
CREATE TABLE `boutique_articles` (
    `id_article` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_article` VARCHAR(255) NOT NULL,
    `prix` DECIMAL(10, 2) NOT NULL,
    `stock` INT,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table for shop purchases
CREATE TABLE `boutique_achats` (
    `id_achat` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `article_id` INT NOT NULL,
    `quantite` INT NOT NULL,
    `date_achat` DATETIME NOT NULL,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`article_id`) REFERENCES `boutique_articles`(`id_article`) ON DELETE CASCADE
);

-- Table for student ID cards
CREATE TABLE `cartes_scolaires` (
    `id_carte` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `annee_academique_id` INT,
    `layout` JSON, -- To store positions of text, images, etc.
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
    `duree_mois` INT NOT NULL, -- 3, 6, 12
    `date_debut` DATE NOT NULL,
    `date_fin` DATE NOT NULL,
    `actif` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- Table for translations
CREATE TABLE `traductions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `langue_code` VARCHAR(5) NOT NULL, -- e.g., 'fr', 'en', 'ar'
    `cle_traduction` VARCHAR(255) NOT NULL,
    `valeur` TEXT NOT NULL,
    UNIQUE KEY `unique_translation` (`langue_code`, `cle_traduction`)
);

-- =================================================================
-- Staff Management Tables
-- =================================================================

CREATE TABLE `type_contrat` (
    `id_contrat` INT AUTO_INCREMENT PRIMARY KEY,
    `libelle` VARCHAR(255) NOT NULL, -- e.g., 'Fonctionnaire', 'Contractuel'
    `description` TEXT,
    `type_paiement` ENUM('fixe', 'a_l_heure', 'aucun') NOT NULL DEFAULT 'fixe',
    `prise_en_charge` ENUM('Etat', 'Ecole', 'Mixte') NOT NULL DEFAULT 'Ecole',
    `lycee_id` INT, -- NULL for global contract types
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
    `assignment_type` VARCHAR(100) NOT NULL, -- e.g., 'supervises_class', 'teaches_class_in_matiere'
    `target_id` INT NOT NULL, -- The ID of the resource being assigned (e.g., class_id, matiere_id)
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`personnel_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_assignment` (`personnel_id`, `assignment_type`, `target_id`)
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

CREATE TABLE `modele_carte` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `nom_modele` VARCHAR(255) NOT NULL,
    `background` VARCHAR(255), -- Path to image or color code
    `font_settings` JSON,
    `layout_data` JSON, -- Stores positions of all elements
    `qr_code_settings` JSON,
    FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

CREATE TABLE `modele_bulletin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT NOT NULL,
    `nom_modele` VARCHAR(255) NOT NULL,
    `format` ENUM('A4_portrait', 'A4_landscape') DEFAULT 'A4_portrait',
    `background` VARCHAR(255), -- Path to watermark image or color
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
  `jour` VARCHAR(20) NOT NULL,          -- ex: 'Lundi'
  `heure_debut` TIME NOT NULL,
  `heure_fin` TIME NOT NULL,
  `salle_id` INT NOT NULL,
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
    `note` DECIMAL(5, 2) NOT NULL,
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
    UNIQUE KEY `unique_evaluation_note` (`eleve_id`, `matiere_id`, `sequence_id`, `annee_academique_id`)
);

SET FOREIGN_KEY_CHECKS = 1;
