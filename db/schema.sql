-- =================================================================
-- Schema for the High School Management Application
-- =================================================================

-- Drop tables if they exist to ensure a clean slate on execution
DROP TABLE IF EXISTS `salaires`, `cahier_texte`, `type_contrat`, `emploi_du_temps`, `role_permissions`, `permissions`, `tests_entree`, `traductions`, `licences`, `cartes_scolaires`, `boutique_achats`, `boutique_articles`, `paiements`, `notes_compositions`, `notes_devoirs`, `etudes`, `enseignant_matieres`, `classe_matieres`, `eleves`, `matieres`, `classes`, `salles`, `cycles`, `utilisateurs`, `roles`, `lycees`, `parametres_generaux`;

-- =================================================================
-- General and Core Tables
-- =================================================================

-- Table for general application settings (scoped per Lycee)
CREATE TABLE `parametres_generaux` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lycee_id` INT,
    `nom_lycee` VARCHAR(255) NOT NULL,
    `type_lycee` ENUM('public', 'prive', 'parapublic') NOT NULL,
    `annee_academique` VARCHAR(20) NOT NULL,
    `nombre_devoirs_par_trimestre` INT DEFAULT 2,
    `modalite_paiement` ENUM('avant_inscription', 'apres_test', 'fractionne') NOT NULL,
    `multilingue_actif` BOOLEAN DEFAULT TRUE,
    `biometrie_actif` BOOLEAN DEFAULT FALSE,
    `confidentialite_nationale` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
);

-- Table for high schools (Lycees)
CREATE TABLE `lycees` (
    `id_lycee` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_lycee` VARCHAR(255) NOT NULL,
    `type_lycee` ENUM('public', 'prive', 'parapublic') NOT NULL,
    `adresse` TEXT,
    `ville` VARCHAR(100),
    `quartier` VARCHAR(100),
    `telephone` VARCHAR(50),
    `email` VARCHAR(255) UNIQUE,
    `logo` VARCHAR(255)
);

-- =================================================================
-- Roles and Permissions Tables
-- =================================================================

CREATE TABLE `roles` (
    `id_role` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_role` VARCHAR(100) NOT NULL,
    `lycee_id` INT, -- NULL for global roles
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
);

CREATE TABLE `permissions` (
    `id_permission` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_permission` VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'manage_users', 'edit_settings'
    `description` TEXT
);

CREATE TABLE `role_permissions` (
    `role_id` INT NOT NULL,
    `permission_id` INT NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id_role`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id_permission`) ON DELETE CASCADE
);

-- Table for users
CREATE TABLE `utilisateurs` (
    `id_user` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `mot_de_passe` VARCHAR(255) NOT NULL, -- Should be hashed
    `role_id` INT,
    `lycee_id` INT, -- NULL for global roles
    `contrat_id` INT,
    `actif` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE SET NULL,
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
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
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
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE,
    FOREIGN KEY (`salle_id`) REFERENCES `salles`(`id_salle`) ON DELETE SET NULL
);

-- Table for subjects
CREATE TABLE `matieres` (
    `id_matiere` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_matiere` VARCHAR(100) NOT NULL,
    `coef` DECIMAL(4, 2)
);

-- Junction table for classes and subjects
CREATE TABLE `classe_matieres` (
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    PRIMARY KEY (`classe_id`, `matiere_id`),
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE
);

-- Junction table for teachers, classes, and subjects
CREATE TABLE `enseignant_matieres` (
    `enseignant_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `actif` BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (`enseignant_id`, `classe_id`, `matiere_id`),
    FOREIGN KEY (`enseignant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE
);


-- =================================================================
-- Student-related Tables
-- =================================================================

-- Table for students' personal data
CREATE TABLE `eleves` (
    `id_eleve` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `date_naissance` DATE,
    `photo` VARCHAR(255),
    `email` VARCHAR(255) UNIQUE,
    `telephone` VARCHAR(50)
);

-- Table linking students to classes for a specific academic year
CREATE TABLE `etudes` (
    `id_etude` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `annee_academique` VARCHAR(20) NOT NULL,
    `actif` BOOLEAN DEFAULT FALSE, -- Activated upon full payment/validation
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE
);

-- Table for homework grades
CREATE TABLE `notes_devoirs` (
    `id_note` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `note` DECIMAL(5, 2),
    `date_devoir` DATE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    UNIQUE KEY `unique_grade` (`eleve_id`, `classe_id`, `matiere_id`)
);

-- Table for exam grades
CREATE TABLE `notes_compositions` (
    `id_note` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `note` DECIMAL(5, 2),
    `date_composition` DATE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    UNIQUE KEY `unique_grade` (`eleve_id`, `classe_id`, `matiere_id`)
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

-- Table for payments
CREATE TABLE `paiements` (
    `id_paiement` INT AUTO_INCREMENT PRIMARY KEY,
    `eleve_id` INT NOT NULL,
    `type_paiement` ENUM('inscription', 'mensualite', 'assurance', 'boutique') NOT NULL,
    `montant` DECIMAL(10, 2) NOT NULL,
    `statut` ENUM('paye', 'partiel', 'non_paye') NOT NULL,
    `date_paiement` DATETIME NOT NULL,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE
);

-- Table for shop articles
CREATE TABLE `boutique_articles` (
    `id_article` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_article` VARCHAR(255) NOT NULL,
    `prix` DECIMAL(10, 2) NOT NULL,
    `stock` INT,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
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
    `annee_academique` VARCHAR(20) NOT NULL,
    `layout` JSON, -- To store positions of text, images, etc.
    `qr_code_data` TEXT,
    `date_emission` DATE,
    FOREIGN KEY (`eleve_id`) REFERENCES `eleves`(`id_eleve`) ON DELETE CASCADE
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
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
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
    `lycee_id` INT, -- NULL for global contract types
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
);

ALTER TABLE `utilisateurs` ADD FOREIGN KEY (`contrat_id`) REFERENCES `type_contrat`(`id_contrat`) ON DELETE SET NULL;

CREATE TABLE `cahier_texte` (
    `id_cahier` INT AUTO_INCREMENT PRIMARY KEY,
    `professeur_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `matiere_id` INT NOT NULL,
    `date_cours` DATE NOT NULL,
    `heure_debut` TIME,
    `heure_fin` TIME,
    `contenu_cours` TEXT,
    `exercices` TEXT,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`professeur_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
);

CREATE TABLE `salaires` (
    `id_salaire` INT AUTO_INCREMENT PRIMARY KEY,
    `personnel_id` INT NOT NULL,
    `mois` INT NOT NULL,
    `annee` INT NOT NULL,
    `montant_brut` DECIMAL(10, 2),
    `montant_net` DECIMAL(10, 2),
    `date_paiement` DATE,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`personnel_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
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
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
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
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
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
  `annee_academique` VARCHAR(20) NOT NULL,
  `modifiable` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
  FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE,
  FOREIGN KEY (`professeur_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`salle_id`) REFERENCES `salles`(`id_salle`) ON DELETE CASCADE
);
