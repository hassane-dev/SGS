<?php

require_once __DIR__ . '/../config/database.php';

class Matiere {

    public static function findAll() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM matieres ORDER BY nom_matiere ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Matiere::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM matieres WHERE id_matiere = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Matiere::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_matiere']);

        $sql = $isUpdate
            ? "UPDATE matieres SET nom_matiere = :nom_matiere, coef = :coef WHERE id_matiere = :id_matiere"
            : "INSERT INTO matieres (nom_matiere, coef) VALUES (:nom_matiere, :coef)";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $params = [
                'nom_matiere' => $data['nom_matiere'],
                'coef' => $data['coef'] ?: null,
            ];

            if ($isUpdate) {
                $params['id_matiere'] = $data['id_matiere'];
            }

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in Matiere::save: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM matieres WHERE id_matiere = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error in Matiere::delete: " . $e->getMessage());
            if ($e->getCode() == '23000') {
                return false; // In use
            }
            return false;
        }
    }

    // --- Association with Classes ---

    public static function findByClassId($class_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.* FROM matieres m
            JOIN classe_matieres cm ON m.id_matiere = cm.matiere_id
            WHERE cm.classe_id = :class_id
            ORDER BY m.nom_matiere ASC
        ");
        $stmt->execute(['class_id' => $class_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function assignToClass($class_id, $matiere_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT IGNORE INTO classe_matieres (classe_id, matiere_id) VALUES (:class_id, :matiere_id)");
        return $stmt->execute(['class_id' => $class_id, 'matiere_id' => $matiere_id]);
    }

    public static function removeFromClass($class_id, $matiere_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM classe_matieres WHERE classe_id = :class_id AND matiere_id = :matiere_id");
        return $stmt->execute(['class_id' => $class_id, 'matiere_id' => $matiere_id]);
    }

}
?>
