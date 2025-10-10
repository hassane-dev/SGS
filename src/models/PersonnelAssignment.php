<?php

require_once __DIR__ . '/../config/database.php';

class PersonnelAssignment {

    /**
     * Finds all assignments for a specific user.
     *
     * @param int $personnel_id The ID of the user.
     * @return array A list of assignments.
     */
    public static function findByPersonnelId($personnel_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT pa.*,
                   CASE
                       WHEN pa.assignment_type = 'supervises_class' THEN c.nom_classe
                       ELSE 'N/A'
                   END as target_name
            FROM personnel_assignments pa
            LEFT JOIN classes c ON pa.target_id = c.id_classe AND pa.assignment_type = 'supervises_class'
            WHERE pa.personnel_id = :personnel_id
        ");
        $stmt->execute(['personnel_id' => $personnel_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adds a new assignment for a user.
     *
     * @param array $data The assignment data.
     * @return bool True on success, false on failure.
     */
    public static function add($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO personnel_assignments (personnel_id, assignment_type, target_id, lycee_id)
                VALUES (:personnel_id, :assignment_type, :target_id, :lycee_id)";

        $stmt = $db->prepare($sql);

        try {
            return $stmt->execute([
                'personnel_id' => $data['personnel_id'],
                'assignment_type' => $data['assignment_type'],
                'target_id' => $data['target_id'],
                'lycee_id' => $data['lycee_id']
            ]);
        } catch (PDOException $e) {
            // It's likely a duplicate entry error, which we can ignore.
            // In a real app, you might want to log this.
            return false;
        }
    }

    /**
     * Deletes a specific assignment by its ID.
     *
     * @param int $assignment_id The ID of the assignment to delete.
     * @return bool True on success, false on failure.
     */
    public static function delete($assignment_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM personnel_assignments WHERE id_assignment = :id_assignment");
        return $stmt->execute(['id_assignment' => $assignment_id]);
    }
}
?>