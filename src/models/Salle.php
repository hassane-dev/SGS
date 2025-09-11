<?php

class Salle {

    /**
     * Find a room by its ID.
     * @param int $id The room ID.
     * @return array|false The room data or false if not found.
     */
    public static function findById($id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM salles WHERE id_salle = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Find all rooms for a given high school.
     * @param int $lycee_id The high school ID.
     * @return array An array of rooms.
     */
    public static function findByLycee($lycee_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM salles WHERE lycee_id = :lycee_id ORDER BY nom_salle");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetchAll();
    }

    /**
     * Save a room (create or update).
     * @param array $data The room data.
     * @return bool True on success, false on failure.
     */
    public static function save($data) {
        $pdo = Database::getInstance();
        if (isset($data['id_salle']) && !empty($data['id_salle'])) {
            // Update
            $stmt = $pdo->prepare("UPDATE salles SET nom_salle = :nom_salle, capacite = :capacite, lycee_id = :lycee_id WHERE id_salle = :id_salle");
            return $stmt->execute([
                'nom_salle' => $data['nom_salle'],
                'capacite' => $data['capacite'],
                'lycee_id' => $data['lycee_id'],
                'id_salle' => $data['id_salle']
            ]);
        } else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO salles (nom_salle, capacite, lycee_id) VALUES (:nom_salle, :capacite, :lycee_id)");
            return $stmt->execute([
                'nom_salle' => $data['nom_salle'],
                'capacite' => $data['capacite'],
                'lycee_id' => $data['lycee_id']
            ]);
        }
    }

    /**
     * Delete a room.
     * @param int $id The room ID.
     * @return bool True on success, false on failure.
     */
    public static function delete($id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM salles WHERE id_salle = :id");
        return $stmt->execute(['id' => $id]);
    }
}
