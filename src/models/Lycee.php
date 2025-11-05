<?php

require_once __DIR__ . '/../config/database.php';

class Lycee {

    public static function findAll() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM param_lycee");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Lycee::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM param_lycee WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Lycee::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function save($data) {
        // --- Validation ---
        if (empty($data['nom_lycee'])) {
            throw new InvalidArgumentException("Le nom de l'école est obligatoire.");
        }
        // --- End Validation ---

        // Handle logo upload logic can be shared
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = uniqid() . '-' . basename($_FILES['logo']['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFilePath)) {
                $data['logo'] = '/uploads/logos/' . $fileName;
            } else {
                $data['logo'] = $data['current_logo'] ?? null;
            }
        } else {
            $data['logo'] = $data['current_logo'] ?? null;
        }

        if (isset($data['id']) && !empty($data['id'])) {
            // Update
            $sql = "UPDATE param_lycee SET
                        nom_lycee = :nom_lycee, sigle = :sigle, tel = :tel, email = :email,
                        ville = :ville, quartier = :quartier, ruelle = :ruelle,
                        boite_postale = :boite_postale, arrete = :arrete,
                        arrondissement = :arrondissement, devise = :devise, logo = :logo,
                        type_lycee = :type_lycee, boutique = :boutique
                    WHERE id = :id";
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare($sql);
                return $stmt->execute([
                    'id' => $data['id'],
                    'nom_lycee' => $data['nom_lycee'],
                    'sigle' => $data['sigle'],
                    'tel' => $data['tel'],
                    'email' => $data['email'],
                    'ville' => $data['ville'],
                    'quartier' => $data['quartier'],
                    'ruelle' => $data['ruelle'],
                    'boite_postale' => $data['boite_postale'],
                    'arrete' => $data['arrete'],
                    'arrondissement' => $data['arrondissement'],
                    'devise' => $data['devise'],
                    'logo' => $data['logo'],
                    'type_lycee' => $data['type_lycee'],
                    'boutique' => isset($data['boutique']) ? 1 : 0
                ]);
            } catch (PDOException $e) {
                error_log("Error in Lycee::save (update): " . $e->getMessage());
                return false;
            }
        } else {
            // Create
            $sql = "INSERT INTO param_lycee (nom_lycee, sigle, tel, email, ville, quartier, ruelle, boite_postale, arrete, arrondissement, devise, logo, type_lycee, boutique)
                    VALUES (:nom_lycee, :sigle, :tel, :email, :ville, :quartier, :ruelle, :boite_postale, :arrete, :arrondissement, :devise, :logo, :type_lycee, :boutique)";
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'nom_lycee' => $data['nom_lycee'],
                    'sigle' => $data['sigle'],
                    'tel' => $data['tel'],
                    'email' => $data['email'],
                    'ville' => $data['ville'],
                    'quartier' => $data['quartier'],
                    'ruelle' => $data['ruelle'],
                    'boite_postale' => $data['boite_postale'],
                    'arrete' => $data['arrete'],
                    'arrondissement' => $data['arrondissement'],
                    'devise' => $data['devise'],
                    'logo' => $data['logo'],
                    'type_lycee' => $data['type_lycee'],
                    'boutique' => isset($data['boutique']) ? 1 : 0
                ]);
                return $db->lastInsertId();
            } catch (PDOException $e) {
                error_log("Error in Lycee::save (create): " . $e->getMessage());
                return false;
            }
        }
    }

    public static function delete($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM param_lycee WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error in Lycee::delete: " . $e->getMessage());
            return false;
        }
    }
}
?>