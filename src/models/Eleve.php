<?php

require_once __DIR__ . '/../config/database.php';

class Eleve {

    /**
     * Find all students. Can be filtered by lycee.
     * This will require a join through etudes and classes.
     * @param int|null $lycee_id
     * @return array
     */
    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        // This is a simplified query. A real one might need to check the current academic year.
        // For now, we list all students that have ever been in a class of a given lycee.
        $sql = "SELECT DISTINCT e.*
                FROM eleves e";

        if ($lycee_id !== null) {
            $sql .= " JOIN etudes et ON e.id_eleve = et.eleve_id
                      JOIN classes c ON et.classe_id = c.id_classe
                      WHERE c.lycee_id = :lycee_id";
        }
        $sql .= " ORDER BY e.nom, e.prenom ASC";

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
        $stmt = $db->prepare("SELECT * FROM eleves WHERE id_eleve = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_eleve']);

        $sql = $isUpdate
            ? "UPDATE eleves SET nom = :nom, prenom = :prenom, date_naissance = :date_naissance, email = :email, telephone = :telephone"
            : "INSERT INTO eleves (nom, prenom, date_naissance, email, telephone) VALUES (:nom, :prenom, :date_naissance, :email, :telephone)";

        if ($isUpdate) {
            if (!empty($data['photo'])) {
                $sql .= ", photo = :photo";
            }
            $sql .= " WHERE id_eleve = :id_eleve";
        } else {
            if (!empty($data['photo'])) {
                $sql = "INSERT INTO eleves (nom, prenom, date_naissance, email, telephone, photo) VALUES (:nom, :prenom, :date_naissance, :email, :telephone, :photo)";
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'date_naissance' => $data['date_naissance'] ?: null,
            'email' => $data['email'] ?: null,
            'telephone' => $data['telephone'] ?: null,
        ];
        if (!empty($data['photo'])) {
            $params['photo'] = $data['photo'];
        }
        if ($isUpdate) {
            $params['id_eleve'] = $data['id_eleve'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        // Before deleting, we might want to remove the photo file from the server
        $eleve = self::findById($id);
        if ($eleve && !empty($eleve['photo'])) {
            // Assuming photos are in public/uploads/photos/
            $photo_path = __DIR__ . '/../../public/' . $eleve['photo'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM eleves WHERE id_eleve = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
