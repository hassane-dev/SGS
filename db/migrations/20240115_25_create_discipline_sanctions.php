<?php
// db/migrations/20240115_25_create_discipline_sanctions.php

/**
 * Migration 25:
 * 1. Creates `discipline_sanctions` table for individual student sanctions.
 * 2. Seeds Phase 3 RBAC permissions:
 *    - `discipline:view_sanctions`
 *    - `discipline:manage_sanctions`
 * 3. Maps Phase 3 RBAC permissions to authorized roles.
 */
function migrate_25($db) {
    echo "Running Migration 25: Creating discipline sanctions table and Phase 3 permissions...\n";

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // 1. Table discipline_sanctions
    try {
        if ($isSqlite) {
            $sql_sanctions = "
            CREATE TABLE IF NOT EXISTS discipline_sanctions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_sanctions (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_sanctions);
        echo "Migration 25: Table `discipline_sanctions` OK.\n";

        // Ensure columns exist on already created tables
        addColumnIfNeeded($db, 'discipline_sanctions', 'date_levee_annulation', 'DATETIME NULL');
        addColumnIfNeeded($db, 'discipline_sanctions', 'motif_levee_annulation', 'TEXT NULL');
        addColumnIfNeeded($db, 'discipline_sanctions', 'par_user_id_levee_annulation', 'INT NULL');
    } catch (PDOException $e) {
        echo "Migration 25 Error (discipline_sanctions): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 2. Seed Phase 3 RBAC Permissions
    try {
        if ($isSqlite) {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON CONFLICT(resource, action) DO UPDATE SET description=excluded.description");
        } else {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
        }

        $phase3_perms = [
            ['discipline', 'view_sanctions', 'Consulter la liste et les détails des sanctions disciplinaires'],
            ['discipline', 'manage_sanctions', 'Prononcer, exécuter, lever ou annuler des sanctions disciplinaires']
        ];

        foreach ($phase3_perms as $perm) {
            $stmt_ins_perm->execute([
                'resource' => $perm[0],
                'action' => $perm[1],
                'description' => $perm[2]
            ]);
        }
        echo "Migration 25: Seeded discipline Phase 3 permissions.\n";

        // Map permissions to roles
        $insert_ignore_keyword = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";

        // Super Admin (1, 2), Admin Local (3), Censeur (4), Surveillant Général (5) -> view_sanctions, manage_sanctions
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action IN ('view_sanctions', 'manage_sanctions')
              AND (r.id_role IN (1, 2, 3, 4, 5) OR r.nom_role LIKE '%admin%' OR r.nom_role LIKE '%censeur%' OR r.nom_role LIKE '%surveillant%')
        ");

        // Enseignant (6) -> view_sanctions ONLY
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action = 'view_sanctions'
              AND (r.id_role = 6 OR r.nom_role LIKE '%enseignant%' OR r.nom_role LIKE '%professeur%')
        ");

        echo "Migration 25: Mapped Phase 3 discipline permissions to roles.\n";
    } catch (PDOException $e) {
        echo "Migration 25 Error (permissions): " . $e->getMessage() . "\n";
        throw $e;
    }

    echo "Migration 25 completed successfully.\n";
}
?>