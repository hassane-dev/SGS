<?php

require_once __DIR__ . '/../config/database.php';

class Permission {

    public static function findAll() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM permissions ORDER BY nom_permission ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
