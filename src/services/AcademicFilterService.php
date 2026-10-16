<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/AuthorizationScopeService.php';

/**
 * AcademicFilterService
 *
 * Centralized business filtering engine for SGS academic structure:
 * Lycée -> Cycle -> Niveau -> Série -> Numéro -> Classe
 *
 * Enforces the strict rule:
 * EFFECTIVE_SELECTION = AUTHORIZED_SCOPE ∩ USER_FILTERS
 *
 * Leverages AuthorizationScopeService as the Single Source of Truth (SSoT) for security/scope.
 */
class AcademicFilterService {

    /**
     * Scope types
     */
    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_RESTRICTED = 'restricted';
    public const SCOPE_NONE = 'none';

    /**
     * Computes the complete academic scope for a user.
     *
     * @param int|null $userId Defaults to current logged in user
     * @return array Scope array with keys:
     *   access_type: 'global'|'restricted'|'none'
     *   lycee_ids: int[]
     *   cycle_ids: int[]
     *   niveau_names: string[]|null (null if unrestricted)
     *   classe_ids: int[]|null (null if unrestricted)
     *   teacher_assignments: array|null
     *   is_global_admin: bool
     *   is_teacher: bool
     *   is_supervisor: bool
     */
    public static function getEffectiveScope(?int $userId = null): array {
        if (!Auth::check() && $userId === null) {
            return [
                'access_type' => self::SCOPE_NONE,
                'lycee_ids' => [],
                'cycle_ids' => [],
                'niveau_names' => null,
                'classe_ids' => [],
                'teacher_assignments' => null,
                'is_global_admin' => false,
                'is_teacher' => false,
                'is_supervisor' => false,
            ];
        }

        $userId = $userId ?: Auth::getUserId();
        $db = Database::getInstance();

        // Check user existence and role
        $stmtUser = $db->prepare("
            SELECT u.id_user, u.lycee_id, u.actif, r.nom_role, r.id_role
            FROM utilisateurs u
            LEFT JOIN roles r ON u.role_id = r.id_role
            WHERE u.id_user = :id
        ");
        $stmtUser->execute(['id' => $userId]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['actif'])) {
            return [
                'access_type' => self::SCOPE_NONE,
                'lycee_ids' => [],
                'cycle_ids' => [],
                'niveau_names' => null,
                'classe_ids' => [],
                'teacher_assignments' => null,
                'is_global_admin' => false,
                'is_teacher' => false,
                'is_supervisor' => false,
            ];
        }

        $isGlobalAdmin = Auth::can('view_all_lycees', 'lycee');
        $hasFullCycleAccess = $isGlobalAdmin || Auth::can('view_all_cycles', 'cycle');

        // 1. Authorized Lycee IDs
        $authorizedLyceeIds = AuthorizationScopeService::getAuthorizedLyceeIds();
        if (empty($authorizedLyceeIds)) {
            return [
                'access_type' => self::SCOPE_NONE,
                'lycee_ids' => [],
                'cycle_ids' => [],
                'niveau_names' => null,
                'classe_ids' => [],
                'teacher_assignments' => null,
                'is_global_admin' => $isGlobalAdmin,
                'is_teacher' => $isTeacher,
                'is_supervisor' => $isSupervisor,
            ];
        }

        // 2. Authorized Cycle IDs
        $authorizedCycleIds = AuthorizationScopeService::getAuthorizedCycleIds();

        // 3. Check for specific teacher or supervisor assignments
        $teacherAssignments = null;
        $restrictedClasseIds = null;
        $restrictedNiveauNames = null;

        // Check for specific teacher or supervisor assignments purely by DB assignment records rather than role names
        $activeYear = AnneeAcademique::findActive();
        $activeYearId = $activeYear ? (int)$activeYear['id'] : null;

        $teacherAssignments = User::getTeacherAssignments($userId, $activeYearId);
        $assignedTeacherClassIds = array_values(array_unique(array_filter(array_column($teacherAssignments, 'id_classe'))));

        $stmtSurvGen = $db->prepare("SELECT COUNT(*) FROM surveillant_general WHERE surveillant_id = :uid");
        $stmtSurvGen->execute(['uid' => $userId]);
        $isSurvGen = ((int)$stmtSurvGen->fetchColumn()) > 0;

        $stmtSurvCls = $db->prepare("SELECT classe_id FROM surveillant_classes WHERE surveillant_id = :uid");
        $stmtSurvCls->execute(['uid' => $userId]);
        $clsIds1 = array_map('intval', $stmtSurvCls->fetchAll(PDO::FETCH_COLUMN));

        $stmtPaCls = $db->prepare("SELECT target_id FROM personnel_assignments WHERE personnel_id = :uid AND assignment_type = 'classe'");
        $stmtPaCls->execute(['uid' => $userId]);
        $clsIds2 = array_map('intval', $stmtPaCls->fetchAll(PDO::FETCH_COLUMN));

        $stmtSurvNiv = $db->prepare("SELECT niveau FROM surveillant_niveaux WHERE surveillant_id = :uid");
        $stmtSurvNiv->execute(['uid' => $userId]);
        $niveaux = array_filter(array_map('trim', $stmtSurvNiv->fetchAll(PDO::FETCH_COLUMN)));

        $allAssignedSurvClassIds = array_values(array_unique(array_merge($clsIds1, $clsIds2)));

        if (!empty($niveaux)) {
            $restrictedNiveauNames = $niveaux;
            $inLycees = implode(',', array_map('intval', $authorizedLyceeIds));
            $inNiv = implode(',', array_map(function($n) use ($db) { return $db->quote($n); }, $niveaux));

            $stmtNivCls = $db->query("
                SELECT id_classe FROM classes
                WHERE lycee_id IN ($inLycees)
                  AND niveau IN ($inNiv)
            ");
            $nivClsIds = array_map('intval', $stmtNivCls->fetchAll(PDO::FETCH_COLUMN));
            $allAssignedSurvClassIds = array_values(array_unique(array_merge($allAssignedSurvClassIds, $nivClsIds)));
        }

        $isTeacher = !empty($teacherAssignments);
        $isSupervisor = $isSurvGen || !empty($allAssignedSurvClassIds) || !empty($niveaux);

        // Apply class-level restriction if user has specific assignments and no global view
        if (!empty($assignedTeacherClassIds) && !Auth::can('note', 'view_all')) {
            $restrictedClasseIds = array_map('intval', $assignedTeacherClassIds);
        } elseif (!$isSurvGen && !empty($allAssignedSurvClassIds) && !Auth::can('eleve', 'view_all')) {
            $restrictedClasseIds = array_map('intval', $allAssignedSurvClassIds);
        }

        if (empty($authorizedCycleIds)) {
            return [
                'access_type' => self::SCOPE_NONE,
                'lycee_ids' => $authorizedLyceeIds,
                'cycle_ids' => [],
                'niveau_names' => $restrictedNiveauNames,
                'classe_ids' => $restrictedClasseIds !== null ? array_map('intval', $restrictedClasseIds) : [],
                'teacher_assignments' => $teacherAssignments,
                'is_global_admin' => $isGlobalAdmin,
                'is_teacher' => $isTeacher,
                'is_supervisor' => $isSupervisor,
            ];
        }

        $accessType = ($hasFullCycleAccess && $restrictedClasseIds === null)
            ? self::SCOPE_GLOBAL
            : self::SCOPE_RESTRICTED;

        return [
            'access_type' => $accessType,
            'lycee_ids' => array_map('intval', $authorizedLyceeIds),
            'cycle_ids' => array_map('intval', $authorizedCycleIds),
            'niveau_names' => $restrictedNiveauNames,
            'classe_ids' => $restrictedClasseIds !== null ? array_map('intval', $restrictedClasseIds) : null,
            'teacher_assignments' => $teacherAssignments,
            'is_global_admin' => $isGlobalAdmin,
            'is_teacher' => $isTeacher,
            'is_supervisor' => $isSupervisor,
        ];
    }

    /**
     * Sanitizes, validates, and intersects requested user filters against the effective scope.
     * Enforces parent-child hierarchy and detects any parameter tampering or inconsistency.
     *
     * @param array $userFilters Raw input filters ($_GET or $_POST)
     * @param array|null $scope Scope array from getEffectiveScope() (or computed if null)
     * @return array Validated filter set:
     *   valid: bool
     *   lycee_id: int|null
     *   cycle_id: int|null
     *   niveau: string|null
     *   serie: string|null
     *   numero: int|null
     *   classe_id: int|null
     *   scope: array
     *   error: string|null
     */
    public static function sanitizeAndIntersectFilters(array $userFilters, ?array $scope = null): array {
        $scope = $scope ?: self::getEffectiveScope();

        if ($scope['access_type'] === self::SCOPE_NONE) {
            return [
                'valid' => false,
                'lycee_id' => null,
                'cycle_id' => null,
                'niveau' => null,
                'serie' => null,
                'numero' => null,
                'classe_id' => null,
                'scope' => $scope,
                'error' => 'Aucun accès académique autorisé pour cet utilisateur.'
            ];
        }

        $db = Database::getInstance();

        $reqLyceeId = !empty($userFilters['lycee_id']) ? (int)$userFilters['lycee_id'] : null;
        $reqCycleId = !empty($userFilters['cycle_id']) ? (int)$userFilters['cycle_id'] : null;
        $reqNiveau = !empty($userFilters['niveau']) ? trim((string)$userFilters['niveau']) : null;
        $reqSerie = isset($userFilters['serie']) && trim((string)$userFilters['serie']) !== '' ? trim((string)$userFilters['serie']) : null;
        $reqNumero = isset($userFilters['numero']) && trim((string)$userFilters['numero']) !== '' ? (int)$userFilters['numero'] : null;
        $reqClasseId = !empty($userFilters['classe_id']) ? (int)$userFilters['classe_id'] : null;

        // 1. Validate Lycee
        if ($reqLyceeId !== null) {
            if (!in_array($reqLyceeId, $scope['lycee_ids'], true)) {
                return self::invalidResult($scope, 'Lycée hors périmètre autorisé.');
            }
        } else {
            // Default to primary lycee if single
            if (count($scope['lycee_ids']) === 1) {
                $reqLyceeId = $scope['lycee_ids'][0];
            }
        }

        // 2. Validate Cycle
        if ($reqCycleId !== null) {
            if (!in_array($reqCycleId, $scope['cycle_ids'], true)) {
                return self::invalidResult($scope, 'Cycle hors périmètre autorisé.');
            }
        }

        // 3. Validate Niveau against scope
        if ($reqNiveau !== null) {
            if (!empty($scope['niveau_names'])) {
                if (!in_array($reqNiveau, $scope['niveau_names'], true)) {
                    return self::invalidResult($scope, 'Niveau hors périmètre autorisé.');
                }
            } else {
                // Assert niveau exists within user's authorized lycee_ids and cycle_ids
                $inL = implode(',', array_map('intval', $scope['lycee_ids']));
                $inC = implode(',', array_map('intval', $scope['cycle_ids']));

                $sqlNivChk = "SELECT COUNT(*) FROM classes WHERE lycee_id IN ($inL) AND cycle_id IN ($inC) AND niveau = :niv";
                $paramsNivChk = ['niv' => $reqNiveau];

                if ($reqLyceeId !== null) {
                    $sqlNivChk .= " AND lycee_id = :req_lid";
                    $paramsNivChk['req_lid'] = $reqLyceeId;
                }
                if ($reqCycleId !== null) {
                    $sqlNivChk .= " AND cycle_id = :req_cid";
                    $paramsNivChk['req_cid'] = $reqCycleId;
                }

                $stmtNivChk = $db->prepare($sqlNivChk);
                $stmtNivChk->execute($paramsNivChk);
                if (((int)$stmtNivChk->fetchColumn()) === 0) {
                    return self::invalidResult($scope, 'Niveau hors périmètre académique autorisé.');
                }
            }
        }

        // 4. Validate explicit classe_id or resolve from (lycee_id, cycle_id, niveau, serie, numero)
        if ($reqClasseId !== null) {
            $stmtCls = $db->prepare("
                SELECT id_classe, lycee_id, cycle_id, niveau, serie, numero
                FROM classes
                WHERE id_classe = :id
            ");
            $stmtCls->execute(['id' => $reqClasseId]);
            $targetClass = $stmtCls->fetch(PDO::FETCH_ASSOC);

            if (!$targetClass) {
                return self::invalidResult($scope, 'Classe spécifiée introuvable.');
            }

            // Parent/child hierarchy validations
            if (!in_array((int)$targetClass['lycee_id'], $scope['lycee_ids'], true)) {
                return self::invalidResult($scope, 'Incohérence hiérarchique : La classe appartient à un lycée hors périmètre.');
            }

            if (!in_array((int)$targetClass['cycle_id'], $scope['cycle_ids'], true)) {
                return self::invalidResult($scope, 'Incohérence hiérarchique : La classe appartient à un cycle hors périmètre.');
            }

            if ($scope['classe_ids'] !== null && !in_array($reqClasseId, $scope['classe_ids'], true)) {
                return self::invalidResult($scope, 'Classe hors périmètre attribué.');
            }

            // Cross-check with submitted filters
            if ($reqLyceeId !== null && (int)$targetClass['lycee_id'] !== $reqLyceeId) {
                return self::invalidResult($scope, 'Incohérence hiérarchique entre le lycée sélectionné et la classe.');
            }

            if ($reqCycleId !== null && (int)$targetClass['cycle_id'] !== $reqCycleId) {
                return self::invalidResult($scope, 'Incohérence hiérarchique entre le cycle sélectionné et la classe.');
            }

            if ($reqNiveau !== null && $targetClass['niveau'] !== $reqNiveau) {
                return self::invalidResult($scope, 'Incohérence hiérarchique entre le niveau sélectionné et la classe.');
            }

            if ($reqSerie !== null) {
                $clsSerie = trim((string)($targetClass['serie'] ?? ''));
                if ($reqSerie === 'none' || $reqSerie === 'sans_serie') {
                    if ($clsSerie !== '') {
                        return self::invalidResult($scope, 'Incohérence hiérarchique sur la série.');
                    }
                } elseif ($clsSerie !== $reqSerie) {
                    return self::invalidResult($scope, 'Incohérence hiérarchique entre la série sélectionnée et la classe.');
                }
            }

            if ($reqNumero !== null && (int)$targetClass['numero'] !== $reqNumero) {
                return self::invalidResult($scope, 'Incohérence hiérarchique entre le numéro sélectionné et la classe.');
            }

            // Adopt resolved parameters from target class
            $reqLyceeId = (int)$targetClass['lycee_id'];
            $reqCycleId = (int)$targetClass['cycle_id'];
            $reqNiveau = $targetClass['niveau'];
            $reqSerie = $targetClass['serie'];
            $reqNumero = $targetClass['numero'] !== null ? (int)$targetClass['numero'] : null;
        } elseif ($reqLyceeId && $reqNiveau && $reqNumero) {
            // Attempt resolving classe_id from details
            $resolvedClassId = Classe::findIdByDetails($reqLyceeId, $reqNiveau, $reqSerie ?? '', $reqNumero, $reqCycleId);
            if (!$resolvedClassId) {
                return self::invalidResult($scope, 'Aucune classe ne correspond aux critères sélectionnés (niveau, série, numéro).');
            }

            if ($scope['classe_ids'] !== null && !in_array((int)$resolvedClassId, $scope['classe_ids'], true)) {
                return self::invalidResult($scope, 'La classe résolue est hors périmètre attribué.');
            }

            $reqClasseId = (int)$resolvedClassId;
        }

        return [
            'valid' => true,
            'lycee_id' => $reqLyceeId,
            'cycle_id' => $reqCycleId,
            'niveau' => $reqNiveau,
            'serie' => $reqSerie,
            'numero' => $reqNumero,
            'classe_id' => $reqClasseId,
            'scope' => $scope,
            'error' => null
        ];
    }

    /**
     * Generates options for dynamic cascade dropdown selectors (UI / AJAX).
     *
     * OPTIONS_RETURNED = ALL_STRUCTURAL_OPTIONS ∩ AUTHORIZED_SCOPE
     *
     * @param array $userFilters Currently selected filters in UI
     * @param array|null $scope Scope array from getEffectiveScope()
     * @return array Cascading options:
     *   lycees: array
     *   cycles: array
     *   niveaux: array
     *   has_series: bool
     *   series: array
     *   numeros: array
     *   classes: array
     */
    public static function getCascadeOptions(array $userFilters = [], ?array $scope = null): array {
        $scope = $scope ?: self::getEffectiveScope();

        if ($scope['access_type'] === self::SCOPE_NONE) {
            return [
                'lycees' => [],
                'cycles' => [],
                'niveaux' => [],
                'has_series' => false,
                'series' => [],
                'numeros' => [],
                'classes' => [],
            ];
        }

        $db = Database::getInstance();
        $validated = self::sanitizeAndIntersectFilters($userFilters, $scope);

        // 1. Lycées
        $lycees = [];
        if (!empty($scope['lycee_ids'])) {
            $inL = implode(',', array_map('intval', $scope['lycee_ids']));
            $stmtL = $db->query("SELECT id, nom_lycee, sigle FROM param_lycee WHERE id IN ($inL) ORDER BY nom_lycee ASC");
            $lycees = $stmtL->fetchAll(PDO::FETCH_ASSOC);
        }

        $selectedLyceeId = $validated['valid'] ? $validated['lycee_id'] : ($scope['lycee_ids'][0] ?? null);

        // 2. Cycles for selected lycee
        $cycles = [];
        if ($selectedLyceeId && !empty($scope['cycle_ids'])) {
            $inC = implode(',', array_map('intval', $scope['cycle_ids']));
            $stmtC = $db->prepare("
                SELECT id_cycle, nom_cycle, niveau_debut, niveau_fin
                FROM cycles
                WHERE id_cycle IN ($inC)
                  AND (lycee_id = :lid OR lycee_id IS NULL)
                ORDER BY id_cycle ASC
            ");
            $stmtC->execute(['lid' => $selectedLyceeId]);
            $cycles = $stmtC->fetchAll(PDO::FETCH_ASSOC);
        }

        $selectedCycleId = $validated['valid'] ? $validated['cycle_id'] : null;

        // 3. Niveaux for selected cycle & lycee
        $niveaux = [];
        if ($selectedLyceeId) {
            $sqlN = "SELECT DISTINCT niveau FROM classes WHERE lycee_id = :lid";
            $paramsN = ['lid' => $selectedLyceeId];

            if ($selectedCycleId) {
                $sqlN .= " AND cycle_id = :cid";
                $paramsN['cid'] = $selectedCycleId;
            } elseif (!empty($scope['cycle_ids'])) {
                $inC = implode(',', array_map('intval', $scope['cycle_ids']));
                $sqlN .= " AND cycle_id IN ($inC)";
            }

            if (!empty($scope['niveau_names'])) {
                $inNiv = implode(',', array_map(function($n) use ($db) { return $db->quote($n); }, $scope['niveau_names']));
                $sqlN .= " AND niveau IN ($inNiv)";
            }

            if ($scope['classe_ids'] !== null) {
                if (empty($scope['classe_ids'])) {
                    $sqlN .= " AND 1=0";
                } else {
                    $inCls = implode(',', array_map('intval', $scope['classe_ids']));
                    $sqlN .= " AND id_classe IN ($inCls)";
                }
            }

            $sqlN .= " ORDER BY niveau ASC";
            $stmtN = $db->prepare($sqlN);
            $stmtN->execute($paramsN);
            $niveaux = $stmtN->fetchAll(PDO::FETCH_COLUMN);
        }

        $selectedNiveau = $validated['valid'] ? $validated['niveau'] : null;

        // 4. Series for selected niveau & cycle & lycee
        $hasSeries = false;
        $series = [];
        if ($selectedLyceeId && $selectedNiveau) {
            $sqlS = "
                SELECT DISTINCT serie FROM classes
                WHERE lycee_id = :lid AND niveau = :niv AND serie IS NOT NULL AND serie != ''
            ";
            $paramsS = ['lid' => $selectedLyceeId, 'niv' => $selectedNiveau];

            if ($selectedCycleId) {
                $sqlS .= " AND cycle_id = :cid";
                $paramsS['cid'] = $selectedCycleId;
            }

            if ($scope['classe_ids'] !== null) {
                if (empty($scope['classe_ids'])) {
                    $sqlS .= " AND 1=0";
                } else {
                    $inCls = implode(',', array_map('intval', $scope['classe_ids']));
                    $sqlS .= " AND id_classe IN ($inCls)";
                }
            }

            $sqlS .= " ORDER BY serie ASC";
            $stmtS = $db->prepare($sqlS);
            $stmtS->execute($paramsS);
            $series = $stmtS->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($series)) {
                $hasSeries = true;
            }
        }

        $selectedSerie = $validated['valid'] ? $validated['serie'] : null;

        // 5. Numeros for selected (niveau, serie, cycle, lycee)
        $numeros = [];
        if ($selectedLyceeId && $selectedNiveau) {
            $sqlNum = "SELECT DISTINCT numero FROM classes WHERE lycee_id = :lid AND niveau = :niv";
            $paramsNum = ['lid' => $selectedLyceeId, 'niv' => $selectedNiveau];

            if ($selectedCycleId) {
                $sqlNum .= " AND cycle_id = :cid";
                $paramsNum['cid'] = $selectedCycleId;
            }

            if ($selectedSerie === 'none' || $selectedSerie === 'sans_serie') {
                $sqlNum .= " AND (serie IS NULL OR serie = '')";
            } elseif (!empty($selectedSerie)) {
                $sqlNum .= " AND serie = :s";
                $paramsNum['s'] = $selectedSerie;
            }

            if ($scope['classe_ids'] !== null) {
                if (empty($scope['classe_ids'])) {
                    $sqlNum .= " AND 1=0";
                } else {
                    $inCls = implode(',', array_map('intval', $scope['classe_ids']));
                    $sqlNum .= " AND id_classe IN ($inCls)";
                }
            }

            $sqlNum .= " ORDER BY numero ASC";
            $stmtNum = $db->prepare($sqlNum);
            $stmtNum->execute($paramsNum);
            $numeros = array_map('intval', array_filter($stmtNum->fetchAll(PDO::FETCH_COLUMN), function($v) { return $v !== null; }));
        }

        // 6. Available Classes matching full context
        $classes = [];
        if ($selectedLyceeId) {
            $sqlCls = "SELECT id_classe, niveau, serie, numero, cycle_id FROM classes WHERE lycee_id = :lid";
            $paramsCls = ['lid' => $selectedLyceeId];

            if ($selectedCycleId) {
                $sqlCls .= " AND cycle_id = :cid";
                $paramsCls['cid'] = $selectedCycleId;
            } elseif (!empty($scope['cycle_ids'])) {
                $inC = implode(',', array_map('intval', $scope['cycle_ids']));
                $sqlCls .= " AND cycle_id IN ($inC)";
            }

            if ($selectedNiveau) {
                $sqlCls .= " AND niveau = :niv";
                $paramsCls['niv'] = $selectedNiveau;
            }

            if ($selectedSerie === 'none' || $selectedSerie === 'sans_serie') {
                $sqlCls .= " AND (serie IS NULL OR serie = '')";
            } elseif (!empty($selectedSerie)) {
                $sqlCls .= " AND serie = :s";
                $paramsCls['s'] = $selectedSerie;
            }

            if ($validated['valid'] && $validated['numero'] !== null) {
                $sqlCls .= " AND numero = :num";
                $paramsCls['num'] = $validated['numero'];
            }

            if ($scope['classe_ids'] !== null) {
                if (empty($scope['classe_ids'])) {
                    $sqlCls .= " AND 1=0";
                } else {
                    $inCls = implode(',', array_map('intval', $scope['classe_ids']));
                    $sqlCls .= " AND id_classe IN ($inCls)";
                }
            }

            $sqlCls .= " ORDER BY niveau ASC, serie ASC, numero ASC";
            $stmtCls = $db->prepare($sqlCls);
            $stmtCls->execute($paramsCls);
            $classes = $stmtCls->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'lycees' => $lycees,
            'cycles' => $cycles,
            'niveaux' => $niveaux,
            'has_series' => $hasSeries,
            'series' => $series,
            'numeros' => $numeros,
            'classes' => $classes,
        ];
    }

    /**
     * Injects WHERE conditions and PDO parameters into a SQL query string.
     * Guaranteed to be scope-aware and injection-safe.
     *
     * @param string $baseSql Base SQL statement
     * @param array $params Existing PDO parameters array
     * @param array $userFilters Raw or pre-sanitized user filters
     * @param string $classAlias Alias used for classes table (default 'c')
     * @param array|null $scope Scope array from getEffectiveScope()
     * @return array [ 'sql' => string, 'params' => array ]
     */
    public static function applyToQuery(string $baseSql, array $params, array $userFilters, string $classAlias = 'c', ?array $scope = null): array {
        $scope = $scope ?: self::getEffectiveScope();
        $validated = self::sanitizeAndIntersectFilters($userFilters, $scope);

        if (!$validated['valid'] || $scope['access_type'] === self::SCOPE_NONE) {
            // Force 0 results if scope or filters are invalid
            return [
                'sql' => $baseSql . (stripos($baseSql, 'WHERE') !== false ? " AND 1=0" : " WHERE 1=0"),
                'params' => $params
            ];
        }

        $conditions = [];

        // Determine whether query base includes 'eleves' table (e.g. alias 'e')
        $hasElevesTable = (stripos($baseSql, 'FROM eleves') !== false || stripos($baseSql, 'JOIN eleves') !== false);
        $eleveAlias = 'e';

        // 1. Lycee condition
        if ($validated['lycee_id'] !== null) {
            if ($hasElevesTable) {
                $conditions[] = "({$classAlias}.lycee_id = :afs_lycee_id1 OR ({$classAlias}.lycee_id IS NULL AND {$eleveAlias}.lycee_id = :afs_lycee_id2))";
                $params['afs_lycee_id1'] = $validated['lycee_id'];
                $params['afs_lycee_id2'] = $validated['lycee_id'];
            } else {
                $conditions[] = "{$classAlias}.lycee_id = :afs_lycee_id";
                $params['afs_lycee_id'] = $validated['lycee_id'];
            }
        } elseif (!empty($scope['lycee_ids'])) {
            $inL = implode(',', array_map('intval', $scope['lycee_ids']));
            if ($hasElevesTable) {
                $conditions[] = "({$classAlias}.lycee_id IN ($inL) OR ({$classAlias}.lycee_id IS NULL AND {$eleveAlias}.lycee_id IN ($inL)))";
            } else {
                $conditions[] = "{$classAlias}.lycee_id IN ($inL)";
            }
        }

        // 2. Cycle condition
        if ($validated['cycle_id'] !== null) {
            if ($hasElevesTable) {
                $conditions[] = "({$classAlias}.cycle_id = :afs_cycle_id OR {$classAlias}.cycle_id IS NULL)";
            } else {
                $conditions[] = "{$classAlias}.cycle_id = :afs_cycle_id";
            }
            $params['afs_cycle_id'] = $validated['cycle_id'];
        } elseif (!empty($scope['cycle_ids'])) {
            $inC = implode(',', array_map('intval', $scope['cycle_ids']));
            if ($hasElevesTable) {
                $conditions[] = "({$classAlias}.cycle_id IN ($inC) OR {$classAlias}.cycle_id IS NULL)";
            } else {
                $conditions[] = "{$classAlias}.cycle_id IN ($inC)";
            }
        }

        // 3. Niveau condition
        if ($validated['niveau'] !== null) {
            $conditions[] = "{$classAlias}.niveau = :afs_niveau";
            $params['afs_niveau'] = $validated['niveau'];
        } elseif (!empty($scope['niveau_names'])) {
            $db = Database::getInstance();
            $inNiv = implode(',', array_map(function($n) use ($db) { return $db->quote($n); }, $scope['niveau_names']));
            $conditions[] = "{$classAlias}.niveau IN ($inNiv)";
        }

        // 4. Serie condition
        if ($validated['serie'] !== null) {
            if ($validated['serie'] === 'none' || $validated['serie'] === 'sans_serie') {
                $conditions[] = "({$classAlias}.serie IS NULL OR {$classAlias}.serie = '')";
            } else {
                $conditions[] = "{$classAlias}.serie = :afs_serie";
                $params['afs_serie'] = $validated['serie'];
            }
        }

        // 5. Numero condition
        if ($validated['numero'] !== null) {
            $conditions[] = "{$classAlias}.numero = :afs_numero";
            $params['afs_numero'] = $validated['numero'];
        }

        // 6. Explicit Classe ID condition or Restricted Scope Classe IDs
        if ($validated['classe_id'] !== null) {
            $conditions[] = "{$classAlias}.id_classe = :afs_classe_id";
            $params['afs_classe_id'] = $validated['classe_id'];
        } elseif ($scope['classe_ids'] !== null) {
            if (empty($scope['classe_ids'])) {
                $conditions[] = "1=0";
            } else {
                $inCls = implode(',', array_map('intval', $scope['classe_ids']));
                $conditions[] = "{$classAlias}.id_classe IN ($inCls)";
            }
        }

        $sql = $baseSql;
        if (!empty($conditions)) {
            $whereClause = implode(" AND ", $conditions);
            if (stripos($baseSql, 'WHERE') !== false) {
                $sql .= " AND " . $whereClause;
            } else {
                $sql .= " WHERE " . $whereClause;
            }
        }

        return [
            'sql' => $sql,
            'params' => $params
        ];
    }

    /**
     * Server-side assertion: Verifies that a target academic context (lycee, cycle, classe) is strictly within scope.
     * Throws a 403 Forbidden error/exception if access is denied.
     *
     * @param array $targetContext Target IDs to verify:
     *   lycee_id: int|null
     *   cycle_id: int|null
     *   classe_id: int|null
     *   matiere_id: int|null (for teacher assignment checks)
     * @param array|null $scope Scope array from getEffectiveScope()
     */
    public static function assertAccess(array $targetContext, ?array $scope = null): void {
        $scope = $scope ?: self::getEffectiveScope();

        if ($scope['access_type'] === self::SCOPE_NONE) {
            self::forbidden('Accès Refusé : Aucun périmètre académique valide.');
        }

        $validated = self::sanitizeAndIntersectFilters($targetContext, $scope);
        if (!$validated['valid']) {
            self::forbidden('Accès Refusé : ' . ($validated['error'] ?? 'Tentative d\'accès à des données hors périmètre autorisé.'));
        }

        // Extra check for teachers on specific (classe_id, matiere_id)
        if ($scope['is_teacher'] && !empty($targetContext['matiere_id']) && !empty($targetContext['classe_id'])) {
            if (!empty($scope['teacher_assignments'])) {
                $match = false;
                foreach ($scope['teacher_assignments'] as $assign) {
                    if ((int)$assign['id_classe'] === (int)$targetContext['classe_id'] && (int)$assign['id_matiere'] === (int)$targetContext['matiere_id']) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    self::forbidden('Accès Refusé : Enseignant non affecté à cette classe et matière.');
                }
            }
        }
    }

    /**
     * Helper to fail with 403 response
     */
    private static function invalidResult(array $scope, string $error): array {
        return [
            'valid' => false,
            'lycee_id' => null,
            'cycle_id' => null,
            'niveau' => null,
            'serie' => null,
            'numero' => null,
            'classe_id' => null,
            'scope' => $scope,
            'error' => $error
        ];
    }

    /**
     * Terminates execution with 403 Forbidden.
     */
    private static function forbidden(string $message = 'Accès Interdit'): void {
        if (defined('TEST_MODE')) {
            throw new Exception($message);
        }
        http_response_code(403);
        if (file_exists(__DIR__ . '/../views/errors/403.php')) {
            require __DIR__ . '/../views/errors/403.php';
        } else {
            echo htmlspecialchars($message);
        }
        exit();
    }
}
