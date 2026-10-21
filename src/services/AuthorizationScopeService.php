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

        // 4. Query active assignments across all academic assignment tables for non-admin users
        $today = date('Y-m-d');
        $raw_cycle_ids = [];

        // 4a. Explicit cycle assignments (personnel_cycles_assignments)
        try {
            $stmt = $db->prepare("
                SELECT cycle_id
                FROM personnel_cycles_assignments
                WHERE personnel_id = :personnel_id
                  AND actif = 1
                  AND (date_debut IS NULL OR date_debut <= :today_start)
                  AND (date_fin IS NULL OR date_fin = '' OR date_fin = '0000-00-00' OR date_fin >= :today_end)
            ");
            $stmt->execute([
                'personnel_id' => $user_id,
                'today_start' => $today,
                'today_end' => $today
            ]);
            $raw_cycle_ids = array_merge($raw_cycle_ids, $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {}

        // 4b. Resource assignments (personnel_assignments: cycle or classe target)
        try {
            $stmt = $db->prepare("
                SELECT target_id AS cycle_id
                FROM personnel_assignments
                WHERE personnel_id = :personnel_id
                  AND assignment_type = 'cycle'
            ");
            $stmt->execute(['personnel_id' => $user_id]);
            $raw_cycle_ids = array_merge($raw_cycle_ids, $stmt->fetchAll(PDO::FETCH_COLUMN));

            $stmtClass = $db->prepare("
                SELECT c.cycle_id
                FROM personnel_assignments pa
                JOIN classes c ON pa.target_id = c.id_classe
                WHERE pa.personnel_id = :personnel_id
                  AND pa.assignment_type = 'classe'
                  AND c.cycle_id IS NOT NULL
            ");
            $stmtClass->execute(['personnel_id' => $user_id]);
            $raw_cycle_ids = array_merge($raw_cycle_ids, $stmtClass->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {}

        // 4c. New surveillant_affectations table
        try {
            $stmtYear = $db->prepare("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
            $stmtYear->execute();
            $annee_id = (int)$stmtYear->fetchColumn();

            if ($annee_id > 0) {
                // Direct class or group
                $stmtAff = $db->prepare("
                    SELECT DISTINCT c.cycle_id
                    FROM surveillant_affectations sa
                    LEFT JOIN classes c ON sa.classe_id = c.id_classe
                    LEFT JOIN surveillant_groupe_classes sgc ON sa.groupe_id = sgc.groupe_id
                    LEFT JOIN classes gc ON sgc.classe_id = gc.id_classe
                    WHERE sa.surveillant_id = :uid
                      AND sa.annee_academique_id = :aid
                      AND sa.statut = 'actif'
                ");
                $stmtAff->execute(['uid' => $user_id, 'aid' => $annee_id]);
                $raw_cycle_ids = array_merge($raw_cycle_ids, $stmtAff->fetchAll(PDO::FETCH_COLUMN));

                // Cycle portee
                $stmtCycAff = $db->prepare("
                    SELECT sa.cycle_id
                    FROM surveillant_affectations sa
                    WHERE sa.surveillant_id = :uid
                      AND sa.annee_academique_id = :aid
                      AND sa.statut = 'actif'
                      AND sa.portee_type = 'cycle'
                      AND sa.cycle_id IS NOT NULL
                ");
                $stmtCycAff->execute(['uid' => $user_id, 'aid' => $annee_id]);
                $raw_cycle_ids = array_merge($raw_cycle_ids, $stmtCycAff->fetchAll(PDO::FETCH_COLUMN));
            }
        } catch (Throwable $e) {}

        // 4d. Supervisor class assignments (surveillant_classes)
        try {
            $stmt = $db->prepare("
                SELECT c.cycle_id
                FROM surveillant_classes sc
                JOIN classes c ON sc.classe_id = c.id_classe
                WHERE sc.surveillant_id = :surveillant_id
                  AND c.cycle_id IS NOT NULL
            ");
            $stmt->execute(['surveillant_id' => $user_id]);
            $raw_cycle_ids = array_merge($raw_cycle_ids, $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {}

        // 4d. Supervisor level assignments (surveillant_niveaux)
        try {
            $stmt = $db->prepare("
                SELECT DISTINCT c.cycle_id
                FROM surveillant_niveaux sn
                JOIN classes c ON sn.niveau = c.niveau AND (c.lycee_id = sn.lycee_id OR sn.lycee_id IS NULL)
                WHERE sn.surveillant_id = :surveillant_id
                  AND c.cycle_id IS NOT NULL
            ");
            $stmt->execute(['surveillant_id' => $user_id]);
            $raw_cycle_ids = array_merge($raw_cycle_ids, $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {}

        // 4e. Supervisor general assignments (surveillant_general)
        try {
            $stmt = $db->prepare("
                SELECT c.id_cycle
                FROM surveillant_general sg
                JOIN cycles c ON (c.lycee_id = sg.lycee_id OR c.lycee_id IS NULL)
                WHERE sg.surveillant_id = :surveillant_id
                  AND c.id_cycle IS NOT NULL
            ");
            $stmt->execute(['surveillant_id' => $user_id]);
            $raw_cycle_ids = array_merge($raw_cycle_ids, $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {}

        // 4f. Teacher pedagogical assignments (affectations_pedagogiques)
        try {
            $stmt = $db->prepare("
                SELECT c.cycle_id
                FROM affectations_pedagogiques ap
                JOIN classes c ON ap.classe_id = c.id_classe
                WHERE ap.enseignant_id = :enseignant_id
                  AND (ap.statut IS NULL OR ap.statut = 'actif')
                  AND (ap.date_debut IS NULL OR ap.date_debut <= :today_start)
                  AND (ap.date_fin IS NULL OR ap.date_fin = '' OR ap.date_fin = '0000-00-00' OR ap.date_fin >= :today_end)
                  AND c.cycle_id IS NOT NULL
            ");
            $stmt->execute([
                'enseignant_id' => $user_id,
                'today_start' => $today,
                'today_end' => $today
            ]);
            $raw_cycle_ids = array_merge($raw_cycle_ids, $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {}

        $cycle_ids = array_values(array_unique(array_map('intval', array_filter($raw_cycle_ids, fn($val) => $val !== null && $val !== ''))));
        $_SESSION['user']['authorized_cycles'] = $cycle_ids;
        return $cycle_ids;
    }

    /**
     * Retrieves cycle records that the currently authenticated user is permitted to access for a given lycee.
     * @param int|null $lycee_id
     * @return array List of cycle records (array of associative arrays with id_cycle, nom_cycle, etc.)
     */
    public static function getPermittedCycles(?int $lycee_id = null): array {
        $authorized_ids = self::getAuthorizedCycleIds();
        if (empty($authorized_ids)) {
            return [];
        }

        $lycee_id = $lycee_id ?: Auth::getLyceeId();
        $all_cycles = $lycee_id ? Cycle::findByLycee($lycee_id) : Cycle::findAll();

        return array_values(array_filter($all_cycles, function($c) use ($authorized_ids) {
            $cId = (int)($c['id_cycle'] ?? $c['id'] ?? 0);
            return in_array($cId, $authorized_ids, true);
        }));
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
     * Retrieves the list of authorized class IDs for the currently logged-in user.
     * Returns NULL if the user has unrestricted global class permissions.
     * Returns [] if restricted to explicit classes but currently has 0 active classes.
     * Returns array of integer class IDs if restricted to explicit classes.
     *
     * @param int|null $lycee_id
     * @return array|null
     */
    public static function getAuthorizedClassIds(?int $lycee_id = null): ?array {
        self::startSession();
        if (!Auth::check()) {
            return [];
        }

        // Global bypass permissions
        if (Auth::can('view_all_lycees', 'lycee') ||
            Auth::can('view_all_classes', 'class') ||
            Auth::can('manage_all_classes', 'class') ||
            Auth::can('class', 'view_all') ||
            Auth::can('class', 'manage_all')) {
            return null; // Null indicates unrestricted access
        }

        $user_id = Auth::getUserId();
        $lycee_id = $lycee_id ?: Auth::getLyceeId();
        $db = Database::getInstance();

        // Check active academic year
        $stmtYear = $db->prepare("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $stmtYear->execute();
        $annee_id = (int)$stmtYear->fetchColumn();

        $raw_class_ids = [];

        // 1. New surveillant_affectations table
        if ($annee_id > 0) {
            try {
                // Direct class
                $stmtCls = $db->prepare("
                    SELECT classe_id FROM surveillant_affectations
                    WHERE surveillant_id = :uid AND annee_academique_id = :aid
                      AND statut = 'actif' AND portee_type = 'classe' AND classe_id IS NOT NULL
                ");
                $stmtCls->execute(['uid' => $user_id, 'aid' => $annee_id]);
                $raw_class_ids = array_merge($raw_class_ids, $stmtCls->fetchAll(PDO::FETCH_COLUMN));

                // Group
                $stmtGrp = $db->prepare("
                    SELECT sgc.classe_id
                    FROM surveillant_affectations sa
                    JOIN surveillant_groupe_classes sgc ON sa.groupe_id = sgc.groupe_id
                    WHERE sa.surveillant_id = :uid AND sa.annee_academique_id = :aid
                      AND sa.statut = 'actif' AND sa.portee_type = 'groupe_classes'
                ");
                $stmtGrp->execute(['uid' => $user_id, 'aid' => $annee_id]);
                $raw_class_ids = array_merge($raw_class_ids, $stmtGrp->fetchAll(PDO::FETCH_COLUMN));

                // Level
                $stmtNiv = $db->prepare("
                    SELECT c.id_classe
                    FROM surveillant_affectations sa
                    JOIN classes c ON sa.niveau_code = c.niveau AND (c.lycee_id = sa.lycee_id OR sa.lycee_id IS NULL)
                    WHERE sa.surveillant_id = :uid AND sa.annee_academique_id = :aid
                      AND sa.statut = 'actif' AND sa.portee_type = 'niveau'
                ");
                $stmtNiv->execute(['uid' => $user_id, 'aid' => $annee_id]);
                $raw_class_ids = array_merge($raw_class_ids, $stmtNiv->fetchAll(PDO::FETCH_COLUMN));

                // Cycle
                $stmtCyc = $db->prepare("
                    SELECT c.id_classe
                    FROM surveillant_affectations sa
                    JOIN classes c ON sa.cycle_id = c.cycle_id
                    WHERE sa.surveillant_id = :uid AND sa.annee_academique_id = :aid
                      AND sa.statut = 'actif' AND sa.portee_type = 'cycle'
                ");
                $stmtCyc->execute(['uid' => $user_id, 'aid' => $annee_id]);
                $raw_class_ids = array_merge($raw_class_ids, $stmtCyc->fetchAll(PDO::FETCH_COLUMN));

                // Etablissement
                $stmtEtab = $db->prepare("
                    SELECT c.id_classe
                    FROM surveillant_affectations sa
                    JOIN classes c ON (c.lycee_id = sa.lycee_id OR sa.lycee_id IS NULL)
                    WHERE sa.surveillant_id = :uid AND sa.annee_academique_id = :aid
                      AND sa.statut = 'actif' AND sa.portee_type = 'etablissement'
                ");
                $stmtEtab->execute(['uid' => $user_id, 'aid' => $annee_id]);
                $raw_class_ids = array_merge($raw_class_ids, $stmtEtab->fetchAll(PDO::FETCH_COLUMN));
            } catch (Throwable $e) {}
        }

        // 2. Legacy fallback tables if no active surveillant_affectations exist for user
        try {
            $stmtLegacyCls = $db->prepare("
                SELECT sc.classe_id FROM surveillant_classes sc WHERE sc.surveillant_id = :uid
            ");
            $stmtLegacyCls->execute(['uid' => $user_id]);
            $raw_class_ids = array_merge($raw_class_ids, $stmtLegacyCls->fetchAll(PDO::FETCH_COLUMN));

            $stmtLegacyNiv = $db->prepare("
                SELECT c.id_classe FROM surveillant_niveaux sn
                JOIN classes c ON sn.niveau = c.niveau AND (c.lycee_id = sn.lycee_id OR sn.lycee_id IS NULL)
                WHERE sn.surveillant_id = :uid
            ");
            $stmtLegacyNiv->execute(['uid' => $user_id]);
            $raw_class_ids = array_merge($raw_class_ids, $stmtLegacyNiv->fetchAll(PDO::FETCH_COLUMN));

            $stmtLegacyGen = $db->prepare("
                SELECT c.id_classe FROM surveillant_general sg
                JOIN classes c ON (c.lycee_id = sg.lycee_id OR sg.lycee_id IS NULL)
                WHERE sg.surveillant_id = :uid
            ");
            $stmtLegacyGen->execute(['uid' => $user_id]);
            $raw_class_ids = array_merge($raw_class_ids, $stmtLegacyGen->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {}

        // 3. Teacher pedagogical assignments (affectations_pedagogiques)
        $today = date('Y-m-d');
        try {
            $stmtAp = $db->prepare("
                SELECT ap.classe_id
                FROM affectations_pedagogiques ap
                WHERE ap.enseignant_id = :uid
                  AND (ap.statut IS NULL OR ap.statut = 'actif')
                  AND (ap.date_debut IS NULL OR ap.date_debut <= :today_start)
                  AND (ap.date_fin IS NULL OR ap.date_fin = '' OR ap.date_fin = '0000-00-00' OR ap.date_fin >= :today_end)
            ");
            $stmtAp->execute(['uid' => $user_id, 'today_start' => $today, 'today_end' => $today]);
            $raw_class_ids = array_merge($raw_class_ids, $stmtAp->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {}

        $class_ids = array_values(array_unique(array_map('intval', array_filter($raw_class_ids, fn($val) => $val !== null && $val !== '' && (int)$val > 0))));

        return $class_ids;
    }

    /**
     * Checks if the user can access a specific class.
     */
    public static function canAccessClass(int $classe_id): bool {
        if ($classe_id <= 0) {
            return false;
        }
        $authorized = self::getAuthorizedClassIds();
        if ($authorized === null) {
            return true; // Null indicates unrestricted access
        }
        return in_array($classe_id, $authorized, true);
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
     * Enforces multi-lycee, multi-cycle, and multi-class authorization at the object level.
     * Throws a 403 error or exception on failure.
     */
    public static function assertAccessToObject($resource_lycee_id, $resource_cycle_id = null, $resource_classe_id = null) {
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

        // 3. Class Check
        if ($resource_classe_id !== null) {
            if (!self::canAccessClass((int)$resource_classe_id)) {
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
