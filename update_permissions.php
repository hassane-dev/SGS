<?php
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/core/Auth.php'; // Needed for Role::findAll logic if it used Auth, but let's be careful

$db = Database::getInstance();

$permissions = [
    [35, 'class', 'manage', 'Global permission for class management section'],
    [69, 'cahier_texte', 'manage', 'Global permission for cahier de texte section'],
    [96, 'tests_entree', 'manage', 'Gérer les tests d\'entrée'],
    [100, 'cycle', 'manage', 'Can manage academic cycles']
];

try {
    $db->beginTransaction();

    foreach ($permissions as $p) {
        $stmt = $db->prepare("INSERT IGNORE INTO permissions (id_permission, resource, action, description) VALUES (?, ?, ?, ?)");
        $stmt->execute($p);
    }

    // Assign to Super Admins
    $stmt = $db->prepare("
        INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT r.id_role, p.id_permission
        FROM roles r, permissions p
        WHERE r.nom_role IN ('super_admin_createur', 'super_admin_national')
        AND p.id_permission IN (35, 69, 96, 100)
    ");
    $stmt->execute();

    // Assign to Admin Local (Role ID 3)
    $localAdminPerms = [35, 69, 96, 100, 70]; // Added 70 (paiement:manage) just in case
    foreach ($localAdminPerms as $pid) {
        $stmt = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, ?)");
        $stmt->execute([$pid]);
    }

    $db->commit();
    echo "Permissions updated successfully.\n";
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Error updating permissions: " . $e->getMessage() . "\n";
}
