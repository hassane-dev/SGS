<?php
// src/services/AuthorizationScopeService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Lycee.php';

class AuthorizationScopeService {

    /**
     * Start the session if not already started.
     */
    private static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Returns the list of authorized lycée (tenant) IDs.
     * If the user has 'view_all_lycees' permission, returns all lycees.
     * Otherwise, returns strictly [Auth::getLyceeId()].
     */
    public static function getAuthorizedLyceeIds() {
        self::startSession();
        if (!Auth::check()) {
            return [];
        }

        if (Auth::can('view_all_lycees', 'lycee')) {
            $lycees = Lycee::findAll();
            return array_column($lycees, 'id');
        }

        $lycee_id = Auth::getLyceeId();
        return $lycee_id ? [(int)$lycee_id] : [];
    }

    /**
     * Retrieves the list of authorized cycle IDs for the currently logged-in user.
     * Enforces database validation and automatic session cache invalidation via auth_version.
     */
    public static function getAuthorizedCycleIds() {
        self::startSession();
        if (!Auth::check()) {
            return [];
        }

        $user_id = Auth::getUserId();

        // 1. Fetch current auth_version from the DB to check against session cache
        $db = Database::getInstance();
        $stmt_ver = $db->prepare("SELECT auth_version FROM utilisateurs WHERE id_user = :id");
        $stmt_ver->execute(['id' => $user_id]);
        $db_version = (int)$stmt_ver->fetchColumn() ?: 1;

        $sess_version = $_SESSION['user']['auth_version'] ?? null;

        // Invalidate cache if there's a version mismatch
        if ($sess_version !== $db_version) {
            unset($_SESSION['user']['authorized_cycles']);
            $_SESSION['user']['auth_version'] = $db_version;
        }

        // 2. Return cached cycles if valid
        if (isset($_SESSION['user']['authorized_cycles'])) {
            return $_SESSION['user']['authorized_cycles'];
        }

        // 3. Super admin or global cycle bypass
        if (Auth::can('view_all_lycees', 'lycee') || Auth::can('view_all_cycles', 'cycle')) {
            // Get all cycles for the active school
            $lycee_id = Auth::getLyceeId();
            if ($lycee_id) {
                $cycles = Cycle::findByLycee($lycee_id);
            } else {
                $cycles = Cycle::findAll();
            }
            $cycle_ids = array_map('intval', array_column($cycles, 'id_cycle'));
            $_SESSION['user']['authorized_cycles'] = $cycle_ids;
            return $cycle_ids;
        }

        // 4. Query active assignments for the current date from DB
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT cycle_id
            FROM personnel_cycles_assignments
            WHERE personnel_id = :personnel_id
              AND actif = 1
              AND date_debut <= :today_start
              AND (date_fin IS NULL OR date_fin >= :today_end)
        ");
        $stmt->execute([
            'personnel_id' => $user_id,
            'today_start' => $today,
            'today_end' => $today
        ]);

        $cycle_ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)) ?: [];
        $_SESSION['user']['authorized_cycles'] = $cycle_ids;
        return $cycle_ids;
    }

    /**
     * Checks if the user can access a specific lycée.
     */
    public static function canAccessLycee($lycee_id) {
        if (!$lycee_id) {
            return false;
        }
        $authorized = self::getAuthorizedLyceeIds();
        return in_array((int)$lycee_id, $authorized);
    }

    /**
     * Checks if the user can access a specific cycle.
     */
    public static function canAccessCycle($cycle_id) {
        if (!$cycle_id) {
            return false;
        }
        $authorized = self::getAuthorizedCycleIds();
        return in_array((int)$cycle_id, $authorized);
    }

    /**
     * Enforces multi-lycee and multi-cycle authorization at the object level.
     * Throws a 403 error or exception on failure.
     */
    public static function assertAccessToObject($resource_lycee_id, $resource_cycle_id = null) {
        self::startSession();

        // 1. Tenant Check
        if ($resource_lycee_id !== null) {
            if (!self::canAccessLycee($resource_lycee_id)) {
                self::forbidden();
            }
        }

        // 2. Cycle Check
        if ($resource_cycle_id !== null) {
            if (!self::canAccessCycle($resource_cycle_id)) {
                self::forbidden();
            }
        }
    }

    /**
     * Validates that there are no overlapping active assignments for a given personnel and cycle.
     * Throw an exception if an overlap is found.
     */
    public static function validateNonOverlapping($personnel_id, $cycle_id, $date_debut, $date_fin, $exclude_id = null) {
        if (!$date_debut) {
            throw new InvalidArgumentException("La date de début est requise.");
        }

        $db = Database::getInstance();

        // Define date_fin placeholder for MySQL/SQLite compatible comparison
        $end_date_comp = $date_fin ? $date_fin : '9999-12-31';

        $sql = "
            SELECT COUNT(*) FROM personnel_cycles_assignments
            WHERE personnel_id = :personnel_id
              AND cycle_id = :cycle_id
              AND actif = 1
        ";

        if ($exclude_id !== null) {
            $sql .= " AND id <> :exclude_id";
        }

        // Logic for overlap of intervals:
        // (StartA <= EndB) AND (EndA >= StartB)
        $sql .= "
            AND (
                (date_debut <= :end_date AND COALESCE(date_fin, '9999-12-31') >= :start_date)
            )
        ";

        $stmt = $db->prepare($sql);
        $params = [
            'personnel_id' => $personnel_id,
            'cycle_id' => $cycle_id,
            'start_date' => $date_debut,
            'end_date' => $end_date_comp
        ];
        if ($exclude_id !== null) {
            $params['exclude_id'] = $exclude_id;
        }

        $stmt->execute($params);
        $overlap_count = (int)$stmt->fetchColumn();

        if ($overlap_count > 0) {
            throw new Exception("Erreur de cohérence temporelle : Un chevauchement d'affectation active a été détecté pour ce personnel sur ce cycle.");
        }

        return true;
    }

    /**
     * Invalidates a user's authorization cache by incrementing their auth_version in the DB.
     */
    public static function invalidateUserCache($user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE utilisateurs SET auth_version = auth_version + 1 WHERE id_user = :id");
        $stmt->execute(['id' => $user_id]);

        // If it's the current user, unset their session cache immediately
        self::startSession();
        if (Auth::check() && Auth::getUserId() == $user_id) {
            unset($_SESSION['user']['authorized_cycles']);
            // Refresh version in session
            $stmt_ver = $db->prepare("SELECT auth_version FROM utilisateurs WHERE id_user = :id");
            $stmt_ver->execute(['id' => $user_id]);
            $_SESSION['user']['auth_version'] = (int)$stmt_ver->fetchColumn() ?: 1;
        }
    }

    /**
     * Terminate processing with a 403 Forbidden page.
     */
    private static function forbidden() {
        if (defined('TEST_MODE')) {
            throw new Exception("Accès Interdit (Vérification de Périmètre).");
        }
        http_response_code(403);
        if (file_exists(__DIR__ . '/../views/errors/403.php')) {
            require __DIR__ . '/../views/errors/403.php';
        } else {
            echo "Accès Interdit (Vérification de Périmètre).";
        }
        exit();
    }
}
