<?php

function migrate_27($db) {
    echo "Running Migration 27: Creating discipline council tables, extending Phase 4 tables, and RBAC permissions...\n";

    $isSqlite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

    // Helper for SQLite/MySQL column addition
    $addColumnIfNeeded = function($tableName, $columnName, $columnDefSqlite, $columnDefMysql) use ($db, $isSqlite) {
        $hasColumn = false;
        if ($isSqlite) {
            $stmt = $db->query("PRAGMA table_info({$tableName})");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (strtolower($row['name']) === strtolower($columnName)) {
                    $hasColumn = true;
                    break;
                }
            }
        } else {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = :col
            ");
            $stmt->execute(['tbl' => $tableName, 'col' => $columnName]);
            $hasColumn = ((int)$stmt->fetchColumn() > 0);
        }

        if (!$hasColumn) {
            $colDef = $isSqlite ? $columnDefSqlite : $columnDefMysql;
            $db->exec("ALTER TABLE {$tableName} ADD COLUMN {$colDef}");
            echo "Migration 27: Added column `{$columnName}` to `{$tableName}`.\n";
        }
    };

    // 1. Table discipline_conseils
    try {
        if ($isSqlite) {
            $sql_conseils = "
            CREATE TABLE IF NOT EXISTS discipline_conseils (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_conseils (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_conseils);
        echo "Migration 27: Table `discipline_conseils` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 27 Error (discipline_conseils): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 2. Table discipline_conseil_membres
    try {
        if ($isSqlite) {
            $sql_membres = "
            CREATE TABLE IF NOT EXISTS discipline_conseil_membres (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_conseil_membres (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_membres);
        echo "Migration 27: Table `discipline_conseil_membres` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 27 Error (discipline_conseil_membres): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 3. Table discipline_conseil_eleves
    try {
        if ($isSqlite) {
            $sql_eleves = "
            CREATE TABLE IF NOT EXISTS discipline_conseil_eleves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_conseil_eleves (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_eleves);
        echo "Migration 27: Table `discipline_conseil_eleves` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 27 Error (discipline_conseil_eleves): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 4. Table discipline_conseil_incidents
    try {
        if ($isSqlite) {
            $sql_incidents = "
            CREATE TABLE IF NOT EXISTS discipline_conseil_incidents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            CREATE TABLE IF NOT EXISTS discipline_conseil_incidents (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }
        $db->exec($sql_incidents);
        echo "Migration 27: Table `discipline_conseil_incidents` OK.\n";
    } catch (PDOException $e) {
        echo "Migration 27 Error (discipline_conseil_incidents): " . $e->getMessage() . "\n";
        throw $e;
    }

    // 5. Extend Phase 4 tables (discipline_documents & discipline_notifications) with conseil_id
    try {
        $addColumnIfNeeded(
            'discipline_documents',
            'conseil_id',
            'conseil_id INT NULL REFERENCES discipline_conseils(id) ON DELETE CASCADE',
            'conseil_id INT NULL, ADD CONSTRAINT fk_disc_doc_conseil FOREIGN KEY (conseil_id) REFERENCES discipline_conseils(id) ON DELETE CASCADE'
        );

        $addColumnIfNeeded(
            'discipline_notifications',
            'conseil_id',
            'conseil_id INT NULL REFERENCES discipline_conseils(id) ON DELETE SET NULL',
            'conseil_id INT NULL, ADD CONSTRAINT fk_disc_notif_conseil FOREIGN KEY (conseil_id) REFERENCES discipline_conseils(id) ON DELETE SET NULL'
        );
    } catch (PDOException $e) {
        echo "Migration 27 Warning (extending Phase 4 tables): " . $e->getMessage() . "\n";
    }

    // 6. Seed Phase 6 RBAC Permissions
    try {
        if ($isSqlite) {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON CONFLICT(resource, action) DO UPDATE SET description=excluded.description");
        } else {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
        }

        $phase6_perms = [
            ['discipline', 'view_councils', 'Consulter les séances et procès-verbaux des Conseils de Discipline'],
            ['discipline', 'manage_councils', 'Planifier, convoquer et gerer les séances du Conseil de Discipline'],
            ['discipline', 'manage_council_decisions', 'Enregistrer les delibérations et prononcer les décisions d\'instance']
        ];

        foreach ($phase6_perms as $perm) {
            $stmt_ins_perm->execute([
                'resource' => $perm[0],
                'action' => $perm[1],
                'description' => $perm[2]
            ]);
        }
        echo "Migration 27: Seeded discipline Phase 6 permissions.\n";

        // Map permissions to roles
        $insert_ignore_keyword = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";

        // Super Admin (1, 2), Admin Local (3), Censeur (4) -> view_councils, manage_councils, manage_council_decisions
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action IN ('view_councils', 'manage_councils', 'manage_council_decisions')
              AND (r.id_role IN (1, 2, 3, 4) OR r.nom_role LIKE '%admin%' OR r.nom_role LIKE '%censeur%')
        ");

        // Surveillant Général (5) -> view_councils, manage_councils
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action IN ('view_councils', 'manage_councils')
              AND (r.id_role = 5 OR r.nom_role LIKE '%surveillant%')
        ");

        // Enseignant (6) -> view_councils
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'discipline' AND p.action = 'view_councils'
              AND (r.id_role = 6 OR r.nom_role LIKE '%enseignant%' OR r.nom_role LIKE '%professeur%')
        ");

        echo "Migration 27: Mapped Phase 6 discipline permissions to roles.\n";
    } catch (PDOException $e) {
        echo "Migration 27 Error (permissions): " . $e->getMessage() . "\n";
        throw $e;
    }

    echo "Migration 27 completed successfully.\n";
}

// Execute migration directly when called
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/../../src/config/database.php';
    migrate_27(Database::getInstance());
}
?>
