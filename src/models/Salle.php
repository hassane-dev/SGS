<?php

require_once __DIR__ . '/../config/database.php';

class Salle {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM salles";
        if ($lycee_id !== null) {
            $sql .= " WHERE lycee_id = :lycee_id";
        }
        $sql .= " ORDER BY nom_salle ASC";

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
        $stmt = $db->prepare("SELECT * FROM salles WHERE id_salle = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_salle']);

        $sql = $isUpdate
            ? "UPDATE salles SET nom_salle = :nom_salle, capacite = :capacite, lycee_id = :lycee_id WHERE id_salle = :id_salle"
            : "INSERT INTO salles (nom_salle, capacite, lycee_id) VALUES (:nom_salle, :capacite, :lycee_id)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'nom_salle' => $data['nom_salle'],
            'capacite' => $data['capacite'] ?: null,
            'lycee_id' => $data['lycee_id'],
        ];

        if ($isUpdate) {
            $params['id_salle'] = $data['id_salle'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM salles WHERE id_salle = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
