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

        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_BASE_DIR . '/boutique/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create boutique upload directory: " . $uploadDir);
                }
            }
            $fileName = uniqid() . '-' . basename($_FILES['image']['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                // Delete old image if it's an update
                if ($isUpdate && !empty($data['current_image'])) {
                    $oldImagePath = __DIR__ . '/../../public' . $data['current_image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $data['image'] = UPLOAD_PUBLIC_PATH . '/boutique/' . $fileName;
            } else {
                error_log("Failed to move boutique uploaded file to: " . $targetFilePath);
                $data['image'] = $data['current_image'] ?? null;
            }
        } else {
            if (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
                error_log("Boutique image upload error: " . $_FILES['image']['error']);
            }
            $data['image'] = $data['current_image'] ?? null;
        }

        $sql = $isUpdate
            ? "UPDATE boutique_articles SET nom_article = :nom_article, categorie = :categorie, prix = :prix, ancien_prix = :ancien_prix, stock = :stock, image = :image, lycee_id = :lycee_id WHERE id_article = :id_article"
            : "INSERT INTO boutique_articles (nom_article, categorie, prix, ancien_prix, stock, image, lycee_id) VALUES (:nom_article, :categorie, :prix, :ancien_prix, :stock, :image, :lycee_id)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'nom_article' => $data['nom_article'],
            'categorie' => $data['categorie'] ?? null,
            'prix' => $data['prix'],
            'ancien_prix' => !empty($data['ancien_prix']) ? $data['ancien_prix'] : null,
            'stock' => $data['stock'] ?: 0,
            'image' => $data['image'],
            'lycee_id' => $data['lycee_id'],
        ];

        if ($isUpdate) {
            $params['id_article'] = $data['id_article'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        // Delete image file before deleting record
        $article = self::findById($id);
        if ($article && !empty($article['image'])) {
            $imagePath = __DIR__ . '/../../public' . $article['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $stmt = $db->prepare("DELETE FROM boutique_articles WHERE id_article = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
