<?php

require_once __DIR__ . '/../config/database.php';

class Permission {

    public static function findAll() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM permissions ORDER BY resource, action ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM permissions WHERE id_permission = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save(array $data) {
        $db = Database::getInstance();
        if (isset($data['id_permission']) && !empty($data['id_permission'])) {
            // Update
            $stmt = $db->prepare("UPDATE permissions SET resource = :resource, action = :action, description = :description WHERE id_permission = :id");
            $stmt->execute([
                'resource' => $data['resource'],
                'action' => $data['action'],
                'description' => $data['description'],
                'id' => $data['id_permission']
            ]);
        } else {
            // Create
            $stmt = $db->prepare("INSERT INTO permissions (resource, action, description) VALUES (:resource, :action, :description)");
            $stmt->execute([
                'resource' => $data['resource'],
                'action' => $data['action'],
                'description' => $data['description']
            ]);
        }
        return true;
    }

    public static function delete(int $id) {
        $db = Database::getInstance();

        // D'abord, supprimer les associations dans la table role_permissions
        $stmt_assoc = $db->prepare("DELETE FROM role_permissions WHERE permission_id = :id");
        $stmt_assoc->execute(['id' => $id]);

        // Ensuite, supprimer la permission elle-même
        $stmt = $db->prepare("DELETE FROM permissions WHERE id_permission = :id");
        $stmt->execute(['id' => $id]);

        return true;
    }
}
?>