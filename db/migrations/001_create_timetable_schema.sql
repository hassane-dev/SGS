-- Migration to create tables for the timetable feature

-- 1. Create the 'salles' (rooms) table
CREATE TABLE `salles` (
    `id_salle` INT AUTO_INCREMENT PRIMARY KEY,
    `nom_salle` VARCHAR(100) NOT NULL,
    `capacite` INT,
    `lycee_id` INT NOT NULL,
    FOREIGN KEY (`lycee_id`) REFERENCES `lycees`(`id_lycee`) ON DELETE CASCADE
);

-- 2. Add a column to the 'classes' table to assign a fixed room
ALTER TABLE `classes`
ADD COLUMN `salle_id` INT,
ADD FOREIGN KEY (`salle_id`) REFERENCES `salles`(`id_salle`) ON DELETE SET NULL;

-- 3. Create the 'emploi_du_temps' (timetable) table
CREATE TABLE `emploi_du_temps` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `classe_id` INT NOT NULL,
  `matiere_id` INT NOT NULL,
  `professeur_id` INT NOT NULL,
  `jour` ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche') NOT NULL,
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
