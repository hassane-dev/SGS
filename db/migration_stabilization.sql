-- Migration to stabilize core architecture

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Transformer "etudes" en cœur du système
ALTER TABLE `etudes`
    ADD COLUMN `status` ENUM('pending_payment', 'active', 'inactive', 'suspended') DEFAULT 'pending_payment',
    ADD COLUMN `is_active` BOOLEAN DEFAULT FALSE,
    ADD COLUMN `date_activation` DATETIME DEFAULT NULL,
    ADD COLUMN `active_par` INT DEFAULT NULL,
    ADD COLUMN `motif_inactif` TEXT DEFAULT NULL,
    ADD CONSTRAINT `fk_etudes_active_par` FOREIGN KEY (`active_par`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL;

-- 2. Refactoriser la comptabilité (link to etude_id)
ALTER TABLE `inscriptions` ADD COLUMN `etude_id` INT AFTER `id_inscription`;
ALTER TABLE `inscriptions` ADD CONSTRAINT `fk_inscriptions_etude` FOREIGN KEY (`etude_id`) REFERENCES `etudes`(`id_etude`) ON DELETE CASCADE;

ALTER TABLE `mensualites` ADD COLUMN `etude_id` INT AFTER `id_mensualite`;
ALTER TABLE `mensualites` ADD CONSTRAINT `fk_mensualites_etude` FOREIGN KEY (`etude_id`) REFERENCES `etudes`(`id_etude`) ON DELETE CASCADE;

-- 3. Gestion détaillée des mensualités
CREATE TABLE `mensualite_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `mensualite_id` INT NOT NULL,
    `montant` DECIMAL(10, 2) NOT NULL,
    `mode_paiement` VARCHAR(50),
    `reference_transaction` VARCHAR(100),
    `date_paiement` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `recu_numero` VARCHAR(50),
    CONSTRAINT `fk_mensualite_details_mensualite` FOREIGN KEY (`mensualite_id`) REFERENCES `mensualites`(`id_mensualite`) ON DELETE CASCADE
);

-- 4. Corriger la gestion des présences
ALTER TABLE `presences` DROP INDEX `unique_presence_day`;
ALTER TABLE `presences` ADD COLUMN `matiere_id` INT AFTER `classe_id`;
ALTER TABLE `presences` ADD CONSTRAINT `fk_presences_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres`(`id_matiere`) ON DELETE CASCADE;
ALTER TABLE `presences` ADD UNIQUE KEY `unique_presence_eleve_matiere_date` (`eleve_id`, `matiere_id`, `date_presence`);

-- 5. Refactoriser les surveillants
CREATE TABLE `surveillant_classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `surveillant_id` INT NOT NULL,
    `classe_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    CONSTRAINT `fk_surveillant_classes_surveillant` FOREIGN KEY (`surveillant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    CONSTRAINT `fk_surveillant_classes_classe` FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE,
    CONSTRAINT `fk_surveillant_classes_lycee` FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

CREATE TABLE `surveillant_niveaux` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `surveillant_id` INT NOT NULL,
    `niveau` VARCHAR(50) NOT NULL,
    `lycee_id` INT NOT NULL,
    CONSTRAINT `fk_surveillant_niveaux_surveillant` FOREIGN KEY (`surveillant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    CONSTRAINT `fk_surveillant_niveaux_lycee` FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

CREATE TABLE `surveillant_general` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `surveillant_id` INT NOT NULL,
    `lycee_id` INT NOT NULL,
    CONSTRAINT `fk_surveillant_general_surveillant` FOREIGN KEY (`surveillant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
    CONSTRAINT `fk_surveillant_general_lycee` FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE
);

-- 6. Stabiliser les cycles
ALTER TABLE `cycles` ADD COLUMN `lycee_id` INT AFTER `id_cycle`;
ALTER TABLE `cycles` ADD CONSTRAINT `fk_cycles_lycee` FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
