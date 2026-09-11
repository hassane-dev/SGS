<?php
// db/migrations/20240115_21_add_appreciation_conseil_classe_and_permission.php

/**
 * Migration 21:
 * 1. Extends `bulletins` table with `appreciation_conseil_classe` column for storing
 *    individual Class Council Appreciations entered by the Main Teacher (Professeur Principal).
 * 2. Seeds new RBAC permission `bulletin:edit_appreciation_conseil` and maps it to relevant roles.
 */
function migrate_21($db) {
    echo "Running Migration 21: Adding appreciation_conseil_classe and RBAC permission...\n";

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // 1. Add appreciation_conseil_classe column to bulletins table if not existing
    try {
        $exists = false;
        if ($isSqlite) {
            $stmt = $db->prepare("PRAGMA table_info(`bulletins`)");
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                if ($col['name'] === 'appreciation_conseil_classe') {
                    $exists = true;
                    break;
                }
            }
        } else {
            $stmt = $db->query("SHOW COLUMNS FROM `bulletins` LIKE 'appreciation_conseil_classe'");
            if ($stmt && $stmt->fetch()) {
                $exists = true;
            }
        }

        if (!$exists) {
            $db->exec("ALTER TABLE `bulletins` ADD COLUMN `appreciation_conseil_classe` TEXT NULL");
            echo "Migration 21: Added column `appreciation_conseil_classe` to `bulletins`.\n";
        }
    } catch (PDOException $e) {
        echo "Migration 21 notice (column appreciation_conseil_classe on bulletins): " . $e->getMessage() . "\n";
    }

    // 2. Seed RBAC Permission bulletin:edit_appreciation_conseil
    try {
        if ($isSqlite) {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON CONFLICT(resource, action) DO UPDATE SET description=excluded.description");
        } else {
            $stmt_ins_perm = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description) ON DUPLICATE KEY UPDATE description=VALUES(description)");
        }

        $stmt_ins_perm->execute([
            'resource' => 'bulletin',
            'action' => 'edit_appreciation_conseil',
            'description' => "Saisir l'appréciation du conseil de classe (Professeur Principal)"
        ]);
        echo "Migration 21: Seeded permission bulletin:edit_appreciation_conseil.\n";

        // Map permission to roles: Super Admin, Admin Local, Enseignant
        $insert_ignore_keyword = $isSqlite ? "INSERT OR IGNORE" : "INSERT IGNORE";

        // Map to Super Admins (by role_id OR nom_role)
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'bulletin' AND p.action = 'edit_appreciation_conseil'
              AND (r.id_role IN (1, 2) OR r.nom_role LIKE '%super_admin%')
        ");

        // Map to Admin Local
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'bulletin' AND p.action = 'edit_appreciation_conseil'
              AND (r.id_role = 3 OR r.nom_role LIKE '%admin_local%' OR r.nom_role LIKE '%administrateur%')
        ");

        // Map to Enseignant
        $db->exec("
            {$insert_ignore_keyword} INTO role_permissions (role_id, permission_id)
            SELECT r.id_role, p.id_permission
            FROM roles r, permissions p
            WHERE p.resource = 'bulletin' AND p.action = 'edit_appreciation_conseil'
              AND (r.id_role = 6 OR r.nom_role LIKE '%enseignant%' OR r.nom_role LIKE '%professeur%')
        ");

        echo "Migration 21: Assigned bulletin:edit_appreciation_conseil permission to roles.\n";
    } catch (PDOException $e) {
        echo "Migration 21 notice (permissions): " . $e->getMessage() . "\n";
    }

    echo "Migration 21 completed successfully.\n";
}
?>