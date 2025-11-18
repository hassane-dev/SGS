<?php

require_once __DIR__ . '/../config/database.php';

class PersonnelAssignment {

    /**
     * Finds all class IDs assigned to a specific supervisor.
     *
     * @param int $user_id The ID of the supervisor.
     * @return array An array of class IDs.
     */
    public static function findAssignedClassIdsBySupervisor($user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT resource_id
            FROM personnel_assignments
            WHERE user_id = :user_id
            AND resource_type = 'classe'
        ");
        $stmt->execute(['user_id' => $user_id]);

        // Fetch all results and return just the resource_id column
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
