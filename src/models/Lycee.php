<?php

require_once __DIR__ . '/../config/database.php';

class Lycee {

    /**
     * Find all lycees.
     * @return array An array of lycee objects.
     */
    public static function findAll() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM lycees ORDER BY nom_lycee ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Lycee::findAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a single lycee by its ID.
     * @param int $id The lycee ID.
     * @return array|false The lycee data or false if not found.
     */
    public static function findById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM lycees WHERE id_lycee = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Lycee::findById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save a lycee (create or update).
     * @param array $data The lycee data.
     * @return bool True on success, false on failure.
     */
    public static function save($data) {
        $isUpdate = !empty($data['id_lycee']);

        $sql = $isUpdate
            ? "UPDATE lycees SET nom_lycee = :nom_lycee, type_lycee = :type_lycee, adresse = :adresse, ville = :ville, quartier = :quartier, telephone = :telephone, email = :email WHERE id_lycee = :id_lycee"
            : "INSERT INTO lycees (nom_lycee, type_lycee, adresse, ville, quartier, telephone, email) VALUES (:nom_lycee, :type_lycee, :adresse, :ville, :quartier, :telephone, :email)";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $params = [
                'nom_lycee' => $data['nom_lycee'],
                'type_lycee' => $data['type_lycee'],
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'quartier' => $data['quartier'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'email' => $data['email'] ?? null,
            ];

            if ($isUpdate) {
                $params['id_lycee'] = $data['id_lycee'];
            }

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in Lycee::save: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a lycee by its ID.
     * @param int $id The lycee ID.
     * @return bool True on success, false on failure.
     */
    public static function delete($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM lycees WHERE id_lycee = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error in Lycee::delete: " . $e->getMessage());
            return false;
        }
    }
}
?>
