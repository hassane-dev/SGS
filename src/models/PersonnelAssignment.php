<?php

require_once __DIR__ . '/../config/database.php';

class PersonnelAssignment {

    /**
     * Assign a personnel member to a specific resource (e.g., a supervisor to a class).
     *
     * @param int $personnel_id The ID of the user being assigned.
     * @param string $assignment_type The type of assignment (e.g., 'supervises_class').
     * @param int $target_id The ID of the resource (e.g., the class ID).
     * @param int $lycee_id The ID of the school.
     * @return bool True on success, false on failure.
     */
    public static function assign($personnel_id, $assignment_type, $target_id, $lycee_id) {
        $sql = "
            INSERT INTO personnel_assignments (personnel_id, assignment_type, target_id, lycee_id)
            VALUES (:personnel_id, :assignment_type, :target_id, :lycee_id)
            ON DUPLICATE KEY UPDATE personnel_id = VALUES(personnel_id) -- Avoids duplicate assignments
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'personnel_id' => $personnel_id,
                'assignment_type' => $assignment_type,
                'target_id' => $target_id,
                'lycee_id' => $lycee_id
            ]);
        } catch (PDOException $e) {
            error_log("Error in PersonnelAssignment::assign: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find all class IDs a specific supervisor is assigned to.
     *
     * @param int $personnel_id The ID of the supervisor.
     * @return array An array of class IDs.
     */
    public static function findAssignedClassIdsBySupervisor($personnel_id) {
        $sql = "
            SELECT target_id
            FROM personnel_assignments
            WHERE personnel_id = :personnel_id
            AND assignment_type = 'supervises_class'
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute(['personnel_id' => $personnel_id]);
            // Fetch a flat array of IDs
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error in PersonnelAssignment::findAssignedClassIdsBySupervisor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find all supervisors assigned to a specific class.
     *
     * @param int $class_id The ID of the class.
     * @return array An array of supervisor data.
     */
    public static function findSupervisorsByClass($class_id) {
        $sql = "
            SELECT u.id_user, u.nom, u.prenom
            FROM personnel_assignments pa
            JOIN utilisateurs u ON pa.personnel_id = u.id_user
            WHERE pa.target_id = :class_id
            AND pa.assignment_type = 'supervises_class'
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute(['class_id' => $class_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in PersonnelAssignment::findSupervisorsByClass: " . $e->getMessage());
            return [];
        }
    }
}
