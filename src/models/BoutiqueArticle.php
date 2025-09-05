<?php

require_once __DIR__ . '/../config/database.php';

class BoutiqueArticle {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM boutique_articles";
        if ($lycee_id !== null) {
            $sql .= " WHERE lycee_id = :lycee_id";
        }
        $sql .= " ORDER BY nom_article ASC";

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
        $stmt = $db->prepare("SELECT * FROM boutique_articles WHERE id_article = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_article']);

        $sql = $isUpdate
            ? "UPDATE boutique_articles SET nom_article = :nom_article, prix = :prix, stock = :stock, lycee_id = :lycee_id WHERE id_article = :id_article"
            : "INSERT INTO boutique_articles (nom_article, prix, stock, lycee_id) VALUES (:nom_article, :prix, :stock, :lycee_id)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'nom_article' => $data['nom_article'],
            'prix' => $data['prix'],
            'stock' => $data['stock'] ?: 0,
            'lycee_id' => $data['lycee_id'],
        ];

        if ($isUpdate) {
            $params['id_article'] = $data['id_article'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM boutique_articles WHERE id_article = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
