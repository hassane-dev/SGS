<?php
// db/migrations/20240115_23_create_discipline_referentiels.php

/**
 * Migration 23:
 * 1. Creates `discipline_types_incidents` table for establishment-level incident types.
 * 2. Creates `discipline_types_sanctions` table for establishment-level sanction types.
 * 3. Seeds Phase 1 RBAC permissions:
 *    - `discipline:view_config`
 *    - `discipline:manage_config`
 * 4. Maps Phase 1 RBAC permissions to roles (Super Admin, Admin Local, Censeur, Surveillant).
 */
function migrate_23($db) {
    echo "Running Migration 23: Creating discipline referential tables and RBAC permissions...\n";

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // 1. Table discipline_types_incidents
    try {
        if ($isSqlite) {
            $sql_incidents = "
            CREATE TABLE IF NOT EXISTS discipline_types_incidents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INT NOT NULL,
                code VARCHAR(50) NOT NULL,
                libelle VARCHAR(150) NOT NULL,
                niveau_gravite VARCHAR(20) NOT NULL DEFAULT 'moyen',
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES lycees(id_lycee) ON DELETE CASCADE,
                UNIQUE(lycee_id, code)
            );";
        } else {
            $sql_incidents = "
            CREATE TABLE IF NOT EXISTS discipline_types_incidents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lycee_id INT NOT NULL,
                code VARCHAR(50) NOT NULL,
                libelle VARCHAR(150) NOT NULL,
                niveau_gravite VARCHAR(20) NOT NULL DEFAULT 'moyen',
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES lycees(id_lycee) ON DELETE CASCADE,
                UNIQUE KEY uk_disc_inc_code (lycee_id, code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_incidents);
        echo "Migration 23: Table `discipline_types_incidents` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 23 Error (discipline_types_incidents): " . $e->getMessage() . "\n";
    }

    // 2. Table discipline_types_sanctions
    try {
        if ($isSqlite) {
            $sql_sanctions = "
            CREATE TABLE IF NOT EXISTS discipline_types_sanctions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
                FOREIGN KEY (lycee_id) REFERENCES lycees(id_lycee) ON DELETE CASCADE,
                UNIQUE(lycee_id, code)
            );";
        } else {
            $sql_sanctions = "
            CREATE TABLE IF NOT EXISTS discipline_types_sanctions (
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
                FOREIGN KEY (lycee_id) REFERENCES lycees(id_lycee) ON DELETE CASCADE,
                UNIQUE KEY uk_disc_sanc_code (lycee_id, code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_sanctions);
        echo "Migration 23: Table `discipline_types_sanctions` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 23 Error (discipline_types_sanctions): " . $e->getMessage() . "\n";
    }

    // 3. Seed Phase 1 RBAC Permissions
    try {
        if ($isSqlite) {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON CONFLICT(resource, action) DO UPDATE SET description=excluded.description");
        } else {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
        }

        $phase1_perms = [
            ['discipline', 'view_config', 'Consulter le référentiel des types d\'incidents et de sanctions disciplinaires'],
            ['discipline', 'manage_config', 'Gérer et configurer le référentiel disciplinaire de l\'établissement']
        ];

        foreach ($phase1_perms as $perm) {
            $stmt_ins_perm->execute([
                'resource' => $perm[0],
                'action' => $perm[1],
                'description' => $perm[2]
            ]);
        }
        echo "Migration 23: Seeded discipline Phase 1 permissions.\n";

        // Map permissions to roles
        $insert_ignore_keyword = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";

        // Super Admins & Admin Local -> view_config & manage_config
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action IN ('view_config', 'manage_config')
              AND (r.id_role IN (1, 2, 3) OR r.nom_role LIKE '%admin%')
        ");

        // Censeur & Surveillant -> view_config
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action = 'view_config'
              AND (r.id_role IN (4, 5) OR r.nom_role LIKE '%censeur%' OR r.nom_role LIKE '%surveillant%')
        ");

        echo "Migration 23: Mapped Phase 1 discipline permissions to roles.\n";
    } catch (PDOException $e) {
        echo "Migration 23 Error (permissions): " . $e->getMessage() . "\n";
    }

    echo "Migration 23 completed successfully.\n";
}
?>