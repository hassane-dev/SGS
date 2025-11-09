<?php

require_once __DIR__ . '/../config/database.php';

class Discipline {

    /**
     * Save a new discipline record.
     *
     * @param array $data
     * @return bool
     */
    public static function save($data) {
        $sql = "
            INSERT INTO discipline (eleve_id, rapporteur_id, annee_academique_id, date_incident, type, description, lycee_id)
            VALUES (:eleve_id, :rapporteur_id, :annee_academique_id, :date_incident, :type, :description, :lycee_id)
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'eleve_id' => $data['eleve_id'],
                'rapporteur_id' => $data['rapporteur_id'],
                'annee_academique_id' => $data['annee_academique_id'],
                'date_incident' => $data['date_incident'] ?? date('Y-m-d H:i:s'),
                'type' => $data['type'],
                'description' => $data['description'],
                'lycee_id' => $data['lycee_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error in Discipline::save: " . $e->getMessage());
            return false;
        }
    }
}
