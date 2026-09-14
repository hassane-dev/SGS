<?php
// db/migrations/20240115_26_create_discipline_phase4_tables.php

/**
 * Migration 26:
 * 1. Creates `discipline_historique` table for append-only audit trail.
 * 2. Creates `discipline_documents` table for attachments & documents.
 * 3. Creates `discipline_notifications` table for tracking parent notifications.
 * 4. Seeds Phase 4 RBAC permissions:
 *    - `discipline:view_history`
 *    - `discipline:manage_documents`
 *    - `discipline:manage_notifications`
 * 5. Maps Phase 4 RBAC permissions to authorized roles.
 */
function migrate_26($db) {
    echo "Running Migration 26: Creating discipline audit, document, notification tables and Phase 4 permissions...\n";

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // 1. Table discipline_historique (Append-Only Audit Log)
    try {
        if ($isSqlite) {
            $sql_historique = "
            CREATE TABLE IF NOT EXISTS discipline_historique (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_historique (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_historique);
        echo "Migration 26: Table `discipline_historique` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 26 Error (discipline_historique): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 2. Table discipline_documents
    try {
        if ($isSqlite) {
            $sql_documents = "
            CREATE TABLE IF NOT EXISTS discipline_documents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_documents (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_documents);
        echo "Migration 26: Table `discipline_documents` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 26 Error (discipline_documents): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 3. Table discipline_notifications
    try {
        if ($isSqlite) {
            $sql_notifications = "
            CREATE TABLE IF NOT EXISTS discipline_notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_notifications (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_notifications);
        echo "Migration 26: Table `discipline_notifications` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 26 Error (discipline_notifications): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 4. Seed Phase 4 RBAC Permissions
    try {
        if ($isSqlite) {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON CONFLICT(resource, action) DO UPDATE SET description=excluded.description");
        } else {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
        }

        $phase4_perms = [
            ['discipline', 'view_history', 'Consulter le journal d\'historique et d\'audit disciplinaire'],
            ['discipline', 'manage_documents', 'Téléverser et consulter les pièces jointes disciplinaires'],
            ['discipline', 'manage_notifications', 'Consigner et suivre les notifications disciplinaires aux responsables']
        ];

        foreach ($phase4_perms as $perm) {
            $stmt_ins_perm->execute([
                'resource' => $perm[0],
                'action' => $perm[1],
                'description' => $perm[2]
            ]);
        }
        echo "Migration 26: Seeded discipline Phase 4 permissions.\n";

        // Map permissions to roles
        $insert_ignore_keyword = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";

        // Super Admin (1, 2), Admin Local (3), Censeur (4), Surveillant Général (5) -> view_history, manage_documents, manage_notifications
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action IN ('view_history', 'manage_documents', 'manage_notifications')
              AND (r.id_role IN (1, 2, 3, 4, 5) OR r.nom_role LIKE '%admin%' OR r.nom_role LIKE '%censeur%' OR r.nom_role LIKE '%surveillant%')
        ");

        // Enseignant (6) -> view_history & manage_documents (for assigned students)
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action IN ('view_history', 'manage_documents')
              AND (r.id_role = 6 OR r.nom_role LIKE '%enseignant%' OR r.nom_role LIKE '%professeur%')
        ");

        echo "Migration 26: Mapped Phase 4 discipline permissions to roles.\n";
    } catch (PDOException $e) {
        echo "Migration 26 Error (permissions): " . $e->getMessage() . "\n";
        throw $e;
    }

    echo "Migration 26 completed successfully.\n";
}
?>