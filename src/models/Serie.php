<?php

require_once __DIR__ . '/../config/database.php';

class Serie {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        if ($lycee_id) {
            $stmt = $db->prepare("SELECT * FROM series WHERE lycee_id = :lycee_id OR lycee_id IS NULL ORDER BY nom_serie ASC");
            $stmt->execute(['lycee_id' => $lycee_id]);
        } else {
            $stmt = $db->query("SELECT * FROM series ORDER BY nom_serie ASC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM series WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $db = Database::getInstance();
        if (isset($data['id']) && !empty($data['id'])) {
            $stmt = $db->prepare("UPDATE series SET nom_serie = :nom, categorie = :cat, lycee_id = :lycee_id WHERE id = :id");
            return $stmt->execute([
                'nom' => $data['nom_serie'],
                'cat' => $data['categorie'],
                'lycee_id' => $data['lycee_id'] ?? null,
                'id' => $data['id']
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO series (nom_serie, categorie, lycee_id) VALUES (:nom, :cat, :lycee_id)");
            return $stmt->execute([
                'nom' => $data['nom_serie'],
                'cat' => $data['categorie'],
                'lycee_id' => $data['lycee_id'] ?? null
            ]);
        }
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM series WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
