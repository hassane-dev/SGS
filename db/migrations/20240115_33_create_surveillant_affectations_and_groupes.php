<?php
/**
 * Migration 33: Create surveillant_groupes, surveillant_groupe_classes, and surveillant_affectations tables,
 * extend param_lycee with surveillant_config_version and surveillant_migration_status, and seed permissions.
 */

function migrate_33($db) {
    echo "Running Migration 33: Create surveillant affectations and groups architecture...\n";
    $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

    // 1. Extend param_lycee with surveillant_config_version and surveillant_migration_status
    $hasConfigVersion = false;
    $hasMigrationStatus = false;

    if ($isSqlite) {
        $stmt = $db->prepare("PRAGMA table_info(`param_lycee`)");
        $stmt->execute();
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if ($col['name'] === 'surveillant_config_version') $hasConfigVersion = true;
            if ($col['name'] === 'surveillant_migration_status') $hasMigrationStatus = true;
        }
    } else {
        $stmt = $db->query("SHOW COLUMNS FROM `param_lycee` LIKE 'surveillant_config_version'");
        if ($stmt && $stmt->fetch()) $hasConfigVersion = true;

        $stmt2 = $db->query("SHOW COLUMNS FROM `param_lycee` LIKE 'surveillant_migration_status'");
        if ($stmt2 && $stmt2->fetch()) $hasMigrationStatus = true;
    }

    if (!$hasConfigVersion) {
        if ($isSqlite) {
            $db->exec("ALTER TABLE `param_lycee` ADD COLUMN `surveillant_config_version` INTEGER NOT NULL DEFAULT 1;");
        } else {
            $db->exec("ALTER TABLE `param_lycee` ADD COLUMN `surveillant_config_version` INT NOT NULL DEFAULT 1;");
        }
        echo "Migration 33: Added surveillant_config_version column to param_lycee.\n";
    }

    if (!$hasMigrationStatus) {
        if ($isSqlite) {
            $db->exec("ALTER TABLE `param_lycee` ADD COLUMN `surveillant_migration_status` TEXT NOT NULL DEFAULT 'non_migre';");
        } else {
            $db->exec("ALTER TABLE `param_lycee` ADD COLUMN `surveillant_migration_status` ENUM('non_migre', 'migre_legacy', 'migre_valide') NOT NULL DEFAULT 'non_migre';");
        }
        echo "Migration 33: Added surveillant_migration_status column to param_lycee.\n";
    }

    // 2. Create surveillant_groupes
    if ($isSqlite) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `surveillant_groupes` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `lycee_id` INTEGER NOT NULL,
                `annee_academique_id` INTEGER NOT NULL,
                `nom_groupe` TEXT NOT NULL,
                `description` TEXT NULL,
                `type_groupe` TEXT NOT NULL DEFAULT 'personnalise',
                `niveau` TEXT NULL,
                `cycle_id` INTEGER NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE RESTRICT,
                FOREIGN KEY (`cycle_id`) REFERENCES `cycles`(`id_cycle`) ON DELETE SET NULL
            );
            CREATE INDEX IF NOT EXISTS `idx_sg_lycee_annee` ON `surveillant_groupes` (`lycee_id`, `annee_academique_id`);
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `surveillant_groupes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `lycee_id` INT NOT NULL,
                `annee_academique_id` INT NOT NULL,
                `nom_groupe` VARCHAR(150) NOT NULL,
                `description` TEXT NULL,
                `type_groupe` ENUM('personnalise', 'plage', 'niveau', 'cycle', 'etablissement') NOT NULL DEFAULT 'personnalise',
                `niveau` VARCHAR(50) NULL,
                `cycle_id` INT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE RESTRICT,
                FOREIGN KEY (`cycle_id`) REFERENCES `cycles`(`id_cycle`) ON DELETE SET NULL,
                INDEX `idx_sg_lycee_annee` (`lycee_id`, `annee_academique_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
    echo "Migration 33: Created table surveillant_groupes.\n";

    // 3. Create surveillant_groupe_classes
    if ($isSqlite) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `surveillant_groupe_classes` (
                `groupe_id` INTEGER NOT NULL,
                `classe_id` INTEGER NOT NULL,
                PRIMARY KEY (`groupe_id`, `classe_id`),
                FOREIGN KEY (`groupe_id`) REFERENCES `surveillant_groupes`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE
            );
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `surveillant_groupe_classes` (
                `groupe_id` INT NOT NULL,
                `classe_id` INT NOT NULL,
                PRIMARY KEY (`groupe_id`, `classe_id`),
                FOREIGN KEY (`groupe_id`) REFERENCES `surveillant_groupes`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
    echo "Migration 33: Created table surveillant_groupe_classes.\n";

    // 4. Create surveillant_affectations
    if ($isSqlite) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `surveillant_affectations` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `lycee_id` INTEGER NOT NULL,
                `annee_academique_id` INTEGER NOT NULL,
                `surveillant_id` INTEGER NULL,
                `type_responsabilite` TEXT NOT NULL DEFAULT 'surveillant_classe',
                `portee_type` TEXT NOT NULL,
                `classe_id` INTEGER NULL,
                `groupe_id` INTEGER NULL,
                `cycle_id` INTEGER NULL,
                `niveau_code` TEXT NULL,
                `date_debut` DATE NOT NULL,
                `date_fin` DATE NULL,
                `statut` TEXT NOT NULL DEFAULT 'actif',
                `est_reprise_legacy` INTEGER NOT NULL DEFAULT 0,
                `created_by` INTEGER NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `classe_nom_snapshot` TEXT NULL,
                `groupe_nom_snapshot` TEXT NULL,
                `cycle_nom_snapshot` TEXT NULL,
                `surveillant_nom_snapshot` TEXT NULL,
                FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE RESTRICT,
                FOREIGN KEY (`surveillant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL,
                FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE SET NULL,
                FOREIGN KEY (`groupe_id`) REFERENCES `surveillant_groupes`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`cycle_id`) REFERENCES `cycles`(`id_cycle`) ON DELETE SET NULL,
                FOREIGN KEY (`created_by`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
            );
            CREATE INDEX IF NOT EXISTS `idx_sa_surv_statut` ON `surveillant_affectations` (`surveillant_id`, `statut`, `annee_academique_id`);
            CREATE INDEX IF NOT EXISTS `idx_sa_lycee_annee` ON `surveillant_affectations` (`lycee_id`, `annee_academique_id`);
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `surveillant_affectations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `lycee_id` INT NOT NULL,
                `annee_academique_id` INT NOT NULL,
                `surveillant_id` INT NULL,
                `type_responsabilite` ENUM('surveillant_classe', 'surveillant_general') NOT NULL DEFAULT 'surveillant_classe',
                `portee_type` ENUM('classe', 'groupe_classes', 'niveau', 'cycle', 'etablissement') NOT NULL,
                `classe_id` INT NULL,
                `groupe_id` INT NULL,
                `cycle_id` INT NULL,
                `niveau_code` VARCHAR(50) NULL,
                `date_debut` DATE NOT NULL,
                `date_fin` DATE NULL,
                `statut` ENUM('actif', 'suspendu', 'termine') NOT NULL DEFAULT 'actif',
                `est_reprise_legacy` TINYINT(1) NOT NULL DEFAULT 0,
                `created_by` INT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

                `classe_nom_snapshot` VARCHAR(100) NULL,
                `groupe_nom_snapshot` VARCHAR(150) NULL,
                `cycle_nom_snapshot` VARCHAR(100) NULL,
                `surveillant_nom_snapshot` VARCHAR(200) NULL,

                `target_classe_id` INT GENERATED ALWAYS AS (COALESCE(`classe_id`, 0)) STORED,
                `target_groupe_id` INT GENERATED ALWAYS AS (COALESCE(`groupe_id`, 0)) STORED,
                `target_cycle_id` INT GENERATED ALWAYS AS (COALESCE(`cycle_id`, 0)) STORED,
                `target_niveau_code` VARCHAR(50) GENERATED ALWAYS AS (COALESCE(`niveau_code`, '')) STORED,
                `active_status_sentinel` TINYINT GENERATED ALWAYS AS (IF(`statut` = 'actif', 1, NULL)) STORED,

                FOREIGN KEY (`lycee_id`) REFERENCES `param_lycee`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques`(`id`) ON DELETE RESTRICT,
                FOREIGN KEY (`surveillant_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL,
                FOREIGN KEY (`classe_id`) REFERENCES `classes`(`id_classe`) ON DELETE SET NULL,
                FOREIGN KEY (`groupe_id`) REFERENCES `surveillant_groupes`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`cycle_id`) REFERENCES `cycles`(`id_cycle`) ON DELETE SET NULL,
                FOREIGN KEY (`created_by`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL,

                CONSTRAINT `chk_surv_aff_exclusive_target` CHECK (
                    (portee_type = 'classe'        AND classe_id IS NOT NULL AND groupe_id IS NULL     AND cycle_id IS NULL     AND niveau_code IS NULL) OR
                    (portee_type = 'groupe_classes' AND groupe_id IS NOT NULL AND classe_id IS NULL     AND cycle_id IS NULL     AND niveau_code IS NULL) OR
                    (portee_type = 'niveau'        AND niveau_code IS NOT NULL AND classe_id IS NULL    AND groupe_id IS NULL    AND cycle_id IS NULL) OR
                    (portee_type = 'cycle'         AND cycle_id IS NOT NULL  AND classe_id IS NULL     AND groupe_id IS NULL    AND niveau_code IS NULL) OR
                    (portee_type = 'etablissement' AND classe_id IS NULL     AND groupe_id IS NULL     AND cycle_id IS NULL     AND niveau_code IS NULL)
                ),
                CONSTRAINT `chk_surv_aff_active_user` CHECK (
                    statut != 'actif' OR surveillant_id IS NOT NULL
                ),

                UNIQUE KEY `uk_surv_aff_active_only` (
                    `surveillant_id`,
                    `annee_academique_id`,
                    `type_responsabilite`,
                    `portee_type`,
                    `target_classe_id`,
                    `target_groupe_id`,
                    `target_cycle_id`,
                    `target_niveau_code`,
                    `active_status_sentinel`
                ),
                INDEX `idx_sa_surv_statut` (`surveillant_id`, `statut`, `annee_academique_id`),
                INDEX `idx_sa_lycee_annee` (`lycee_id`, `annee_academique_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
    echo "Migration 33: Created table surveillant_affectations.\n";

    // 5. Seed Permissions for Surveillant Assignment Management
    $insert_ignore = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";

    $perms = [
        ['discipline', 'manage_surveillants', 'Créer, répartir, modifier et clôturer les affectations de surveillants'],
        ['discipline', 'view_surveillants', 'Consulter le registre des affectations de surveillants']
    ];

    $stmtPerm = $db->prepare("{$insert_ignore} INTO permissions (resource, action, description) VALUES (:resource, :action, :description)");
    foreach ($perms as $p) {
        $stmtPerm->execute([
            'resource' => $p[0],
            'action' => $p[1],
            'description' => $p[2]
        ]);
    }

    // Grant permissions to Super Admin (1, 2), Admin Local (3), Censeur (4), Surveillant Général (5)
    $db->exec("
        {$insert_ignore} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE (r.id_role IN (1, 2, 3, 4, 5) OR r.nom_role LIKE '%admin%' OR r.nom_role LIKE '%censeur%' OR r.nom_role LIKE '%surveillant%')
        AND p.resource = 'discipline' AND p.action IN ('manage_surveillants', 'view_surveillants')
    ");

    echo "Migration 33 completed successfully.\n";
}
