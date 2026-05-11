<?php

require_once __DIR__ . '/../config/database.php';

class Cycle {

    /**
     * Find all cycles for a given school.
     */
    public static function findByLycee($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM cycles WHERE lycee_id = :lycee_id OR lycee_id IS NULL");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find all cycles.
     */
    public static function findAll() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM cycles");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a cycle by ID.
     */
    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM cycles WHERE id_cycle = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Save a new cycle or update an existing one.
     */
    public static function save($data) {
        $db = Database::getInstance();

        if (isset($data['id_cycle']) && !empty($data['id_cycle'])) {
            // Update
            $stmt = $db->prepare("
                UPDATE cycles
                SET nom_cycle = :nom_cycle, niveau_debut = :niveau_debut, niveau_fin = :niveau_fin, lycee_id = :lycee_id
                WHERE id_cycle = :id_cycle
            ");
            return $stmt->execute([
                'nom_cycle' => $data['nom_cycle'],
                'niveau_debut' => $data['niveau_debut'],
                'niveau_fin' => $data['niveau_fin'],
                'lycee_id' => $data['lycee_id'] ?? null,
                'id_cycle' => $data['id_cycle']
            ]);
        } else {
            // Create
            $stmt = $db->prepare("
                INSERT INTO cycles (nom_cycle, niveau_debut, niveau_fin, lycee_id)
                VALUES (:nom_cycle, :niveau_debut, :niveau_fin, :lycee_id)
            ");
            return $stmt->execute([
                'nom_cycle' => $data['nom_cycle'],
                'niveau_debut' => $data['niveau_debut'],
                'niveau_fin' => $data['niveau_fin'],
                'lycee_id' => $data['lycee_id'] ?? null
            ]);
        }
    }

    /**
     * Delete a cycle.
     */
    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM cycles WHERE id_cycle = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
