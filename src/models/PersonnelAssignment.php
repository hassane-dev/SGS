<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class PersonnelAssignment {

    /**
     * Finds all class IDs assigned to a specific supervisor/personnel.
     * Checks both personnel_assignments and surveillant_affectations via AuthorizationScopeService.
     *
     * @param int $user_id The ID of the supervisor or personnel.
     * @return array An array of integer class IDs.
     */
    public static function findAssignedClassIdsBySupervisor($user_id) {
        $db = Database::getInstance();
        $class_ids = [];

        // 1. Check personnel_assignments with correct column names (personnel_id, assignment_type, target_id)
        try {
            $stmt = $db->prepare("
                SELECT target_id
                FROM personnel_assignments
                WHERE personnel_id = :user_id
                  AND assignment_type = 'classe'
            ");
            $stmt->execute(['user_id' => $user_id]);
            $res = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($res)) {
                $class_ids = array_merge($class_ids, array_map('intval', $res));
            }
        } catch (Throwable $e) {}

        // 2. Check surveillant_affectations via AuthorizationScopeService
        try {
            $scope_classes = AuthorizationScopeService::getAuthorizedClassIds();
            if (is_array($scope_classes)) {
                $class_ids = array_merge($class_ids, $scope_classes);
            }
        } catch (Throwable $e) {}

        return array_values(array_unique(array_filter($class_ids, fn($v) => $v > 0)));
    }
}
