<?php

require_once __DIR__ . '/../config/database.php';

class Cycle {

    public static function findAll() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT * FROM cycles ORDER BY nom_cycle ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Cycle::findAll: " . $e->getMessage());
            return [];
        }
    }

    public static function findById($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM cycles WHERE id_cycle = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Cycle::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function save($data) {
        // --- Validation ---
        if (empty($data['nom_cycle'])) {
            throw new InvalidArgumentException("Le nom du cycle est obligatoire.");
        }
        // --- End Validation ---

        $isUpdate = !empty($data['id_cycle']);

        $sql = $isUpdate
            ? "UPDATE cycles SET nom_cycle = :nom_cycle, niveau_debut = :niveau_debut, niveau_fin = :niveau_fin WHERE id_cycle = :id_cycle"
            : "INSERT INTO cycles (nom_cycle, niveau_debut, niveau_fin) VALUES (:nom_cycle, :niveau_debut, :niveau_fin)";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);

            $params = [
                'nom_cycle' => $data['nom_cycle'],
                'niveau_debut' => $data['niveau_debut'] ?: null,
                'niveau_fin' => $data['niveau_fin'] ?: null,
            ];

            if ($isUpdate) {
                $params['id_cycle'] = $data['id_cycle'];
            }

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in Cycle::save: " . $e->getMessage());
            return false;
        }
    }

    public static function delete($id) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM cycles WHERE id_cycle = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error in Cycle::delete: " . $e->getMessage());
            // Check for foreign key constraint violation
            if ($e->getCode() == '23000') {
                return false; // Cannot delete, in use by classes
            }
            return false;
        }
    }
}
?>
