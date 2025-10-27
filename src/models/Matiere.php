<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class Matiere {

    public static function findAll() {
        try {
            $db = Database::getInstance();
            $lycee_id = Auth::getLyceeId();
            if (!$lycee_id) {
                return [];
            }
            $stmt = $db->prepare("SELECT * FROM matieres WHERE lycee_id = :lycee_id ORDER BY nom_matiere ASC");
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Matiere::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        try {
            $db = Database::getInstance();
            $lycee_id = Auth::getLyceeId();
            if (!$lycee_id) {
                return false;
            }
            $stmt = $db->prepare("SELECT * FROM matieres WHERE id_matiere = :id AND lycee_id = :lycee_id");
            $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Matiere::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function save($data) {
        // --- Validation ---
        if (empty($data['nom_matiere'])) {
            throw new InvalidArgumentException("Le nom de la matière est obligatoire.");
        }
        // --- End Validation ---

        $isUpdate = !empty($data['id_matiere']);
        $lycee_id = Auth::getLyceeId();

        if (!$lycee_id) {
            error_log("Error in Matiere::save: Missing lycee_id");
            return false;
        }

        $sql = $isUpdate
            ? "UPDATE matieres SET nom_matiere = :nom_matiere, description = :description, type = :type, cycle_concerne = :cycle_concerne, statut = :statut WHERE id_matiere = :id_matiere AND lycee_id = :lycee_id"
            : "INSERT INTO matieres (nom_matiere, description, type, cycle_concerne, statut, lycee_id) VALUES (:nom_matiere, :description, :type, :cycle_concerne, :statut, :lycee_id)";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $params = [
                'nom_matiere' => $data['nom_matiere'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? null,
                'cycle_concerne' => $data['cycle_concerne'] ?? null,
                'statut' => $data['statut'],
                'lycee_id' => $lycee_id,
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
            $lycee_id = Auth::getLyceeId();
            if (!$lycee_id) {
                return false;
            }
            $stmt = $db->prepare("DELETE FROM matieres WHERE id_matiere = :id AND lycee_id = :lycee_id");
            return $stmt->execute(['id' => $id, 'lycee_id' => $lycee_id]);
        } catch (PDOException $e) {
            error_log("Error in Matiere::delete: " . $e->getMessage());
            // Foreign key constraint violation
            if ($e->getCode() == '23000') {
                return false;
            }
            return false;
        }
    }

    // --- Association with Classes ---

    public static function findByClassId($class_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*, cm.coefficient, cm.statut as statut_classe
            FROM matieres m
            JOIN classe_matieres cm ON m.id_matiere = cm.matiere_id
            WHERE cm.classe_id = :class_id
            ORDER BY m.nom_matiere ASC
        ");
        $stmt->execute(['class_id' => $class_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function assignToClass($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO classe_matieres (classe_id, matiere_id, coefficient, statut) VALUES (:classe_id, :matiere_id, :coefficient, :statut)";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'classe_id' => $data['classe_id'],
                'matiere_id' => $data['matiere_id'],
                'coefficient' => $data['coefficient'],
                'statut' => $data['statut']
            ]);
        } catch (PDOException $e) {
            error_log("Error in Matiere::assignToClass: " . $e->getMessage());
            return false;
        }
    }

    public static function removeFromClass($classe_matiere_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM classe_matieres WHERE id = :id");
        return $stmt->execute(['id' => $classe_matiere_id]);
    }
}
?>