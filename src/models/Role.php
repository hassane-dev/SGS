<?php

require_once __DIR__ . '/../config/database.php';

class Role {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        // Find global roles (lycee_id IS NULL) and roles for the specific lycee
        $sql = "SELECT r.*, l.nom_lycee FROM roles r LEFT JOIN param_lycee l ON r.lycee_id = l.id WHERE r.lycee_id IS NULL";
        if ($lycee_id !== null) {
            $sql .= " OR r.lycee_id = :lycee_id";
        }
        $sql .= " ORDER BY nom_role ASC";

        $stmt = $db->prepare($sql);
        if ($lycee_id !== null) {
            $stmt->execute(['lycee_id' => $lycee_id]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM roles WHERE id_role = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_role']);

        $sql = $isUpdate
            ? "UPDATE roles SET nom_role = :nom_role, lycee_id = :lycee_id WHERE id_role = :id_role"
            : "INSERT INTO roles (nom_role, lycee_id) VALUES (:nom_role, :lycee_id)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'nom_role' => $data['nom_role'],
            'lycee_id' => !empty($data['lycee_id']) ? $data['lycee_id'] : null,
        ];

        if ($isUpdate) {
            $params['id_role'] = $data['id_role'];
        }

        $result = $stmt->execute($params);

        if ($isUpdate) {
            return $result;
        } else {
            return $result ? $db->lastInsertId() : false;
        }
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM roles WHERE id_role = :id");
        return $stmt->execute(['id' => $id]);
    }

    public static function getPermissions($role_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT p.resource, p.action
            FROM permissions p
            JOIN role_permissions rp ON p.id_permission = rp.permission_id
            WHERE rp.role_id = :role_id
        ");
        $stmt->execute(['role_id' => $role_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $permissions = [];
        foreach ($results as $row) {
            if (!isset($permissions[$row['resource']])) {
                $permissions[$row['resource']] = [];
            }
            $permissions[$row['resource']][] = $row['action'];
        }
        return $permissions;
    }

    public static function getPermissionIds($role_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT permission_id FROM role_permissions WHERE role_id = :role_id
        ");
        $stmt->execute(['role_id' => $role_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function setPermissions($role_id, $permission_ids) {
        $db = Database::getInstance();

        // This method must be called within a transaction managed by the caller.
        // It will throw a PDOException on failure, which should be caught by the caller.

        // First, remove all existing permissions for this role
        $stmt_delete = $db->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
        $stmt_delete->execute(['role_id' => $role_id]);

        // Now, add the new permissions
        if (!empty($permission_ids)) {
            $stmt_insert = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
            foreach ($permission_ids as $permission_id) {
                $stmt_insert->execute(['role_id' => $role_id, 'permission_id' => $permission_id]);
            }
        }
    }
}
?>
