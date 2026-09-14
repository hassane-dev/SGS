<?php
// db/migrations/20240115_24_create_discipline_incidents.php

/**
 * Migration 24:
 * 1. Creates `discipline_incidents` table for student discipline incidents.
 * 2. Creates `discipline_incident_eleves` table (multi-student involvement pivot).
 * 3. Seeds Phase 2 RBAC permissions:
 *    - `discipline:view_incidents`
 *    - `discipline:report_incident`
 *    - `discipline:manage_incident`
 * 4. Maps Phase 2 RBAC permissions to roles.
 */
function migrate_24($db) {
    echo "Running Migration 24: Creating discipline incident tables and Phase 2 permissions...\n";

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // 1. Table discipline_incidents
    try {
        if ($isSqlite) {
            $sql_incidents = "
            CREATE TABLE IF NOT EXISTS discipline_incidents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_incidents (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_incidents);
        echo "Migration 24: Table `discipline_incidents` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 24 Error (discipline_incidents): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 2. Table discipline_incident_eleves
    try {
        if ($isSqlite) {
            $sql_incident_eleves = "
            CREATE TABLE IF NOT EXISTS discipline_incident_eleves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_incident_eleves (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_incident_eleves);
        echo "Migration 24: Table `discipline_incident_eleves` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 24 Error (discipline_incident_eleves): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 3. Seed Phase 2 RBAC Permissions
    try {
        if ($isSqlite) {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON CONFLICT(resource, action) DO UPDATE SET description=excluded.description");
        } else {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
        }

        $phase2_perms = [
            ['discipline', 'view_incidents', 'Consulter la liste et les détails des incidents disciplinaires'],
            ['discipline', 'report_incident', 'Signaler un nouvel incident disciplinaire'],
            ['discipline', 'manage_incident', 'Instruire, modifier, qualifier ou classer sans suite un incident']
        ];

        foreach ($phase2_perms as $perm) {
            $stmt_ins_perm->execute([
                'resource' => $perm[0],
                'action' => $perm[1],
                'description' => $perm[2]
            ]);
        }
        echo "Migration 24: Seeded discipline Phase 2 permissions.\n";

        // Map permissions to roles
        $insert_ignore_keyword = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";

        // Super Admin (1, 2), Admin Local (3), Censeur (4), Surveillant Général (5) -> view, report, manage
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action IN ('view_incidents', 'report_incident', 'manage_incident')
              AND (r.id_role IN (1, 2, 3, 4, 5) OR r.nom_role LIKE '%admin%' OR r.nom_role LIKE '%censeur%' OR r.nom_role LIKE '%surveillant%')
        ");

        // Enseignant (6) -> view_incidents, report_incident
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action IN ('view_incidents', 'report_incident')
              AND (r.id_role = 6 OR r.nom_role LIKE '%enseignant%' OR r.nom_role LIKE '%professeur%')
        ");

        echo "Migration 24: Mapped Phase 2 discipline permissions to roles.\n";
    } catch (PDOException $e) {
        echo "Migration 24 Error (permissions): " . $e->getMessage() . "\n";
    }

    echo "Migration 24 completed successfully.\n";
}
?>