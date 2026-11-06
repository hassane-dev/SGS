<?php
/**
 * Migration 37: Create param_promotion_seuils table and seed promotion_threshold permissions.
 */

function migrate_37($db) {
    echo "Running Migration 37: Create param_promotion_seuils table and seed permissions...\n";
    $isSqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';

    // 1. Create table param_promotion_seuils
    if ($isSqlite) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS param_promotion_seuils (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INTEGER NOT NULL,
                cycle_id INTEGER DEFAULT NULL,
                niveau TEXT DEFAULT NULL,
                serie TEXT DEFAULT NULL,
                portee_type TEXT NOT NULL,
                target_cycle_id INTEGER GENERATED ALWAYS AS (COALESCE(cycle_id, 0)) STORED,
                target_niveau TEXT GENERATED ALWAYS AS (COALESCE(niveau, '')) STORED,
                target_serie TEXT GENERATED ALWAYS AS (COALESCE(serie, '')) STORED,
                seuil_admission_directe REAL NOT NULL,
                seuil_deliberation_min REAL NOT NULL,
                seuil_deliberation_max REAL NOT NULL,
                seuil_redoublement_max REAL NOT NULL,
                seuil_exclusion_max REAL DEFAULT NULL,
                cree_par INTEGER DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE RESTRICT,
                FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
                CONSTRAINT chk_prom_portee_coherence CHECK (
                    (portee_type = 'etablissement' AND cycle_id IS NULL     AND niveau IS NULL     AND serie IS NULL) OR
                    (portee_type = 'cycle'         AND cycle_id IS NOT NULL AND niveau IS NULL     AND serie IS NULL) OR
                    (portee_type = 'niveau'        AND cycle_id IS NOT NULL AND niveau IS NOT NULL AND serie IS NULL) OR
                    (portee_type = 'niveau_serie'  AND cycle_id IS NOT NULL AND niveau IS NOT NULL AND serie IS NOT NULL)
                ),
                UNIQUE (lycee_id, portee_type, target_cycle_id, target_niveau, target_serie)
            );
        ");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_prom_lookup ON param_promotion_seuils (lycee_id, target_cycle_id, target_niveau);");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS param_promotion_seuils (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lycee_id INT NOT NULL,
                cycle_id INT NULL,
                niveau VARCHAR(50) NULL,
                serie VARCHAR(50) NULL,
                portee_type ENUM('etablissement', 'cycle', 'niveau', 'niveau_serie') NOT NULL,
                target_cycle_id INT GENERATED ALWAYS AS (COALESCE(cycle_id, 0)) STORED,
                target_niveau VARCHAR(50) GENERATED ALWAYS AS (COALESCE(niveau, '')) STORED,
                target_serie VARCHAR(50) GENERATED ALWAYS AS (COALESCE(serie, '')) STORED,
                seuil_admission_directe DECIMAL(4,2) NOT NULL,
                seuil_deliberation_min DECIMAL(4,2) NOT NULL,
                seuil_deliberation_max DECIMAL(4,2) NOT NULL,
                seuil_redoublement_max DECIMAL(4,2) NOT NULL,
                seuil_exclusion_max DECIMAL(4,2) NULL,
                cree_par INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (lycee_id) REFERENCES param_lycee(id) ON DELETE CASCADE,
                FOREIGN KEY (cycle_id) REFERENCES cycles(id_cycle) ON DELETE RESTRICT,
                FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_user) ON DELETE SET NULL,
                CONSTRAINT chk_prom_portee_coherence CHECK (
                    (portee_type = 'etablissement' AND cycle_id IS NULL     AND niveau IS NULL     AND serie IS NULL) OR
                    (portee_type = 'cycle'         AND cycle_id IS NOT NULL AND niveau IS NULL     AND serie IS NULL) OR
                    (portee_type = 'niveau'        AND cycle_id IS NOT NULL AND niveau IS NOT NULL AND serie IS NULL) OR
                    (portee_type = 'niveau_serie'  AND cycle_id IS NOT NULL AND niveau IS NOT NULL AND serie IS NOT NULL)
                ),
                UNIQUE KEY uk_prom_scope_unique (
                    lycee_id,
                    portee_type,
                    target_cycle_id,
                    target_niveau,
                    target_serie
                ),
                INDEX idx_prom_lookup (lycee_id, target_cycle_id, target_niveau)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
    echo "Migration 37: Table param_promotion_seuils created or verified.\n";

    // 2. Seed Permissions
    $permissionsToSeed = [
        ['resource' => 'promotion_threshold', 'action' => 'view', 'description' => 'Consulter les seuils de promotion scolaire'],
        ['resource' => 'promotion_threshold', 'action' => 'manage', 'description' => 'Créer, modifier et supprimer les seuils de promotion scolaire']
    ];

    foreach ($permissionsToSeed as $perm) {
        if ($isSqlite) {
            $stmt = $db->prepare("
                INSERT OR IGNORE INTO permissions (resource, action, description)
                VALUES (:resource, :action, :description)
            ");
            $stmt->execute($perm);
        } else {
            $stmt = $db->prepare("
                INSERT INTO permissions (resource, action, description)
                VALUES (:resource, :action, :description)
                ON DUPLICATE KEY UPDATE description = VALUES(description)
            ");
            $stmt->execute($perm);
        }
    }

    // 3. Assign permissions to relevant roles:
    // - Super Admin Createur (1), Super Admin National (2): view + manage
    // - Admin Local (3): view + manage
    // - Proviseur / Censeur (7, 10 or by role name): view
    $insertIgnore = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";

    // Manage & View to Super Admins (1, 2) and Admin Local (3)
    $db->exec("
        {$insertIgnore} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE p.resource = 'promotion_threshold'
          AND (
              r.id_role IN (1, 2, 3)
              OR r.nom_role LIKE '%super_admin%'
              OR r.nom_role LIKE '%admin_local%'
          )
    ");

    // View to Censeur / Proviseur / Direction roles if they exist
    $db->exec("
        {$insertIgnore} INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE p.resource = 'promotion_threshold' AND p.action = 'view'
          AND (
              r.id_role IN (7, 10)
              OR r.nom_role LIKE '%censeur%'
              OR r.nom_role LIKE '%proviseur%'
              OR r.nom_role LIKE '%directeur%'
          )
    ");

    echo "Migration 37 completed successfully.\n";
}
