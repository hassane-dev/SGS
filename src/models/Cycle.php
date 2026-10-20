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
        $utiliseSeries = (isset($data['utilise_series']) && $data['utilise_series'] !== '') ? (int)$data['utilise_series'] : null;

        if (isset($data['id_cycle']) && !empty($data['id_cycle'])) {
            // Update
            $stmt = $db->prepare("
                UPDATE cycles
                SET nom_cycle = :nom_cycle, niveau_debut = :niveau_debut, niveau_fin = :niveau_fin, lycee_id = :lycee_id, utilise_series = :utilise_series
                WHERE id_cycle = :id_cycle
            ");
            return $stmt->execute([
                'nom_cycle' => $data['nom_cycle'],
                'niveau_debut' => $data['niveau_debut'],
                'niveau_fin' => $data['niveau_fin'],
                'lycee_id' => $data['lycee_id'] ?? null,
                'utilise_series' => $utiliseSeries,
                'id_cycle' => $data['id_cycle']
            ]);
        } else {
            // Create
            $stmt = $db->prepare("
                INSERT INTO cycles (nom_cycle, niveau_debut, niveau_fin, lycee_id, utilise_series)
                VALUES (:nom_cycle, :niveau_debut, :niveau_fin, :lycee_id, :utilise_series)
            ");
            return $stmt->execute([
                'nom_cycle' => $data['nom_cycle'],
                'niveau_debut' => $data['niveau_debut'],
                'niveau_fin' => $data['niveau_fin'],
                'lycee_id' => $data['lycee_id'] ?? null,
                'utilise_series' => $utiliseSeries
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

    /**
     * Find a cycle by its name.
     */
    public static function findByNom($nom, $lycee_id = null) {
        $db = Database::getInstance();
        if ($lycee_id !== null) {
            $stmt = $db->prepare("SELECT * FROM cycles WHERE nom_cycle = :nom AND (lycee_id = :lycee_id OR lycee_id IS NULL) LIMIT 1");
            $stmt->execute(['nom' => $nom, 'lycee_id' => $lycee_id]);
        } else {
            $stmt = $db->prepare("SELECT * FROM cycles WHERE nom_cycle = :nom LIMIT 1");
            $stmt->execute(['nom' => $nom]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
