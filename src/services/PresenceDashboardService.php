<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/AuthorizationScopeService.php';
require_once __DIR__ . '/ReportingService.php';

class PresenceDashboardService {

    /**
     * Entry point to compile complete dashboard data for Attendance module.
     */
    public static function getDashboardData(array $filters, int $userId): array {
        $db = Database::getInstance();

        // 1. Multi-tenant isolation check
        $lyceeId = !empty($filters['lycee_id']) ? (int)$filters['lycee_id'] : Auth::getLyceeId();
        if (!AuthorizationScopeService::canAccessLycee($lyceeId)) {
            throw new Exception("Accès refusé au lycée spécifié.");
        }

        // 2. Academic Year resolution
        $anneeId = !empty($filters['annee_academique_id']) ? (int)$filters['annee_academique_id'] : null;
        if (!$anneeId) {
            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear ? (int)$activeYear['id'] : null;
        }
        if (!$anneeId) {
            return self::emptyDashboardResponse("Aucune année académique active trouvée.");
        }

        // 3. Cycle access scope check
        $cycleId = !empty($filters['cycle_id']) ? (int)$filters['cycle_id'] : null;
        if ($cycleId && !AuthorizationScopeService::canAccessCycle($cycleId)) {
            throw new Exception("Accès refusé au cycle spécifié.");
        }

        // 4. Teacher pedagogical assignment scope
        $canViewAll = Auth::can('view_all', 'presence') || Auth::can('manage', 'presence');
        $teacherAssignments = [];
        $assignedClassIds = [];

        if (!$canViewAll) {
            $teacherAssignments = User::getTeacherAssignments($userId, $anneeId, $lyceeId);
            if (empty($teacherAssignments)) {
                return self::emptyDashboardResponse("Aucune affectation pédagogique active trouvée pour cet enseignant.");
            }
            $assignedClassIds = array_unique(array_filter(array_map(fn($a) => (int)($a['id_classe'] ?? $a['classe_id']), $teacherAssignments)));
            if (empty($assignedClassIds)) {
                return self::emptyDashboardResponse("Aucune classe valide attribuée.");
            }
        }

        $classeId = !empty($filters['classe_id']) ? (int)$filters['classe_id'] : null;
        if (!$canViewAll && $classeId && !in_array($classeId, $assignedClassIds, true)) {
            throw new Exception("Accès refusé : classe hors affectations pédagogiques.");
        }

        $niveau = !empty($filters['niveau']) ? trim($filters['niveau']) : null;
        $dateDebut = !empty($filters['date_debut']) ? $filters['date_debut'] : null;
        $dateFin = !empty($filters['date_fin']) ? $filters['date_fin'] : null;

        // Default period: last 30 days if no explicit date filter
        if (!$dateDebut && !$dateFin) {
            $dateFin = date('Y-m-d');
            $dateDebut = date('Y-m-d', strtotime('-30 days'));
        } elseif ($dateDebut && !$dateFin) {
            $dateFin = date('Y-m-d');
        } elseif (!$dateDebut && $dateFin) {
            $dateDebut = date('Y-m-d', strtotime($dateFin . ' -30 days'));
        }

        // 5. Query Active Roster Enrolled Students Count (Phase 6 SSoT)
        $totalEnrolledStudents = self::getEnrolledStudentCount($lyceeId, $anneeId, $cycleId, $niveau, $classeId, $canViewAll, $assignedClassIds);

        // 6. Macro KPI Cards (matiere_id IS NULL)
        $kpis = self::getMacroKpis($lyceeId, $anneeId, $cycleId, $niveau, $classeId, $dateDebut, $dateFin, $canViewAll, $assignedClassIds);

        // 7. ApexCharts Timeline (4 mutually exclusive statuses, matiere_id IS NULL)
        $timeline = self::getTimelineData($lyceeId, $anneeId, $cycleId, $niveau, $classeId, $dateDebut, $dateFin, $canViewAll, $assignedClassIds);

        // 8. Class Absence Rate Table (matiere_id IS NULL)
        $classRates = self::getClassAbsenceRates($lyceeId, $anneeId, $cycleId, $niveau, $classeId, $dateDebut, $dateFin, $canViewAll, $assignedClassIds);

        // 9. Repeated Absences Alert List (Absenteeism threshold via configuration, matiere_id IS NULL)
        $thresholdConfig = self::getAbsenteeismThreshold($lyceeId);
        $repeatedAbsences = self::getRepeatedAbsences($lyceeId, $anneeId, $cycleId, $niveau, $classeId, $thresholdConfig, $canViewAll, $assignedClassIds);

        // 10. Generate Factual Descriptive Alerts
        $alerts = self::generateFactualAlerts($kpis, $classRates, $repeatedAbsences, $thresholdConfig);

        return [
            'success' => true,
            'lycee_id' => $lyceeId,
            'annee_academique_id' => $anneeId,
            'period' => [
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ],
            'total_enrolled_students' => $totalEnrolledStudents,
            'kpis' => $kpis,
            'timeline' => $timeline,
            'class_rates' => $classRates,
            'repeated_absences' => [
                'threshold' => $thresholdConfig,
                'list' => $repeatedAbsences
            ],
            'alerts' => $alerts
        ];
    }

    /**
     * Resolves the configured absenteeism threshold for the school (fallback 5).
     */
    public static function getAbsenteeismThreshold(int $lyceeId): int {
        try {
            $thresholds = ReportingService::getThresholds($lyceeId);
            if (isset($thresholds['absenteisme_seuil']) && !empty($thresholds['absenteisme_seuil']['seuil_warning']) && (int)$thresholds['absenteisme_seuil']['seuil_warning'] > 0) {
                return (int)$thresholds['absenteisme_seuil']['seuil_warning'];
            }
        } catch (Exception $e) {
            // Silently fallback if reporting table unconfigured
        }
        return 5;
    }

    /**
     * Counts active enrolled students adhering strictly to Phase 6 canonical join:
     * et.annee_academique_id = :annee_id AND et.is_active = 1 AND et.status = 'active' AND e.statut = 'actif'
     */
    private static function getEnrolledStudentCount(
        int $lyceeId, int $anneeId, ?int $cycleId, ?string $niveau, ?int $classeId,
        bool $canViewAll, array $assignedClassIds
    ): int {
        $db = Database::getInstance();
        $params = ['lycee_id' => $lyceeId, 'annee_id' => $anneeId];
        $whereClauses = [
            "e.lycee_id = :lycee_id",
            "et.annee_academique_id = :annee_id",
            "et.is_active = 1",
            "et.status = 'active'",
            "e.statut = 'actif'"
        ];

        if ($cycleId) {
            $whereClauses[] = "c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $whereClauses[] = "c.niveau = :niveau";
            $params['niveau'] = $niveau;
        }
        if ($classeId) {
            $whereClauses[] = "c.id_classe = :classe_id";
            $params['classe_id'] = $classeId;
        }

        if (!$canViewAll && !empty($assignedClassIds)) {
            $inClause = implode(',', array_map('intval', $assignedClassIds));
            $whereClauses[] = "c.id_classe IN ({$inClause})";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $sql = "
            SELECT COUNT(DISTINCT e.id_eleve)
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE {$whereSql}
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Computes Macro KPI Cards (p.matiere_id IS NULL).
     */
    private static function getMacroKpis(
        int $lyceeId, int $anneeId, ?int $cycleId, ?string $niveau, ?int $classeId,
        string $dateDebut, string $dateFin, bool $canViewAll, array $assignedClassIds
    ): array {
        $db = Database::getInstance();
        $today = date('Y-m-d');

        $params = [
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'today' => $today
        ];

        $whereClauses = [
            "p.lycee_id = :lycee_id",
            "p.annee_academique_id = :annee_id",
            "p.matiere_id IS NULL"
        ];

        if ($cycleId) {
            $whereClauses[] = "c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $whereClauses[] = "c.niveau = :niveau";
            $params['niveau'] = $niveau;
        }
        if ($classeId) {
            $whereClauses[] = "p.classe_id = :classe_id";
            $params['classe_id'] = $classeId;
        }

        if (!$canViewAll && !empty($assignedClassIds)) {
            $inClause = implode(',', array_map('intval', $assignedClassIds));
            $whereClauses[] = "p.classe_id IN ({$inClause})";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $sql = "
            SELECT
                -- Period Totals
                COUNT(CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin THEN p.id END) AS total_occurrences_period,
                COUNT(CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut IN ('present', 'retard') THEN p.id END) AS effective_present_period,

                -- Absences Period
                COUNT(CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut IN ('absent', 'justifie') THEN p.id END) AS absences_occurrences_period,
                COUNT(DISTINCT CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut IN ('absent', 'justifie') THEN p.eleve_id END) AS absences_students_period,

                -- Absences Unjustified Period
                COUNT(CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut = 'absent' THEN p.id END) AS unjustified_occurrences_period,
                COUNT(DISTINCT CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut = 'absent' THEN p.eleve_id END) AS unjustified_students_period,

                -- Absences Justified Period
                COUNT(CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut = 'justifie' THEN p.id END) AS justified_occurrences_period,
                COUNT(DISTINCT CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut = 'justifie' THEN p.eleve_id END) AS justified_students_period,

                -- Delays Period
                COUNT(CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut = 'retard' THEN p.id END) AS delays_occurrences_period,
                COUNT(DISTINCT CASE WHEN p.date_presence BETWEEN :date_debut AND :date_fin AND p.statut = 'retard' THEN p.eleve_id END) AS delays_students_period,

                -- Today Metrics
                COUNT(CASE WHEN p.date_presence = :today AND p.statut IN ('absent', 'justifie') THEN p.id END) AS today_absences_occurrences,
                COUNT(DISTINCT CASE WHEN p.date_presence = :today AND p.statut IN ('absent', 'justifie') THEN p.eleve_id END) AS today_absences_students,

                COUNT(CASE WHEN p.date_presence = :today AND p.statut = 'retard' THEN p.id END) AS today_delays_occurrences,
                COUNT(DISTINCT CASE WHEN p.date_presence = :today AND p.statut = 'retard' THEN p.eleve_id END) AS today_delays_students
            FROM presences p
            JOIN classes c ON p.classe_id = c.id_classe
            WHERE {$whereSql}
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalOccurrences = (int)($row['total_occurrences_period'] ?? 0);
        $effectivePresent = (int)($row['effective_present_period'] ?? 0);

        $presenceRate = ($totalOccurrences > 0)
            ? round(($effectivePresent / $totalOccurrences) * 100, 1)
            : 100.0; // Default 100% when no records yet

        return [
            'presence_rate' => $presenceRate,
            'total_occurrences_period' => $totalOccurrences,
            'today_absences' => [
                'occurrences' => (int)($row['today_absences_occurrences'] ?? 0),
                'students' => (int)($row['today_absences_students'] ?? 0)
            ],
            'today_delays' => [
                'occurrences' => (int)($row['today_delays_occurrences'] ?? 0),
                'students' => (int)($row['today_delays_students'] ?? 0)
            ],
            'period_absences' => [
                'occurrences' => (int)($row['absences_occurrences_period'] ?? 0),
                'students' => (int)($row['absences_students_period'] ?? 0)
            ],
            'unjustified_absences' => [
                'occurrences' => (int)($row['unjustified_occurrences_period'] ?? 0),
                'students' => (int)($row['unjustified_students_period'] ?? 0)
            ],
            'justified_absences' => [
                'occurrences' => (int)($row['justified_occurrences_period'] ?? 0),
                'students' => (int)($row['justified_students_period'] ?? 0)
            ],
            'delays' => [
                'occurrences' => (int)($row['delays_occurrences_period'] ?? 0),
                'students' => (int)($row['delays_students_period'] ?? 0)
            ]
        ];
    }

    /**
     * ApexCharts timeline data grouped by date_presence with 4 mutually exclusive statuses (p.matiere_id IS NULL).
     */
    private static function getTimelineData(
        int $lyceeId, int $anneeId, ?int $cycleId, ?string $niveau, ?int $classeId,
        string $dateDebut, string $dateFin, bool $canViewAll, array $assignedClassIds
    ): array {
        $db = Database::getInstance();

        $params = [
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];

        $whereClauses = [
            "p.lycee_id = :lycee_id",
            "p.annee_academique_id = :annee_id",
            "p.matiere_id IS NULL",
            "p.date_presence BETWEEN :date_debut AND :date_fin"
        ];

        if ($cycleId) {
            $whereClauses[] = "c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $whereClauses[] = "c.niveau = :niveau";
            $params['niveau'] = $niveau;
        }
        if ($classeId) {
            $whereClauses[] = "p.classe_id = :classe_id";
            $params['classe_id'] = $classeId;
        }

        if (!$canViewAll && !empty($assignedClassIds)) {
            $inClause = implode(',', array_map('intval', $assignedClassIds));
            $whereClauses[] = "p.classe_id IN ({$inClause})";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $sql = "
            SELECT
                p.date_presence,
                COUNT(CASE WHEN p.statut = 'present' THEN 1 END) AS count_present,
                COUNT(CASE WHEN p.statut = 'retard' THEN 1 END) AS count_retard,
                COUNT(CASE WHEN p.statut = 'absent' THEN 1 END) AS count_absent,
                COUNT(CASE WHEN p.statut = 'justifie' THEN 1 END) AS count_justifie
            FROM presences p
            JOIN classes c ON p.classe_id = c.id_classe
            WHERE {$whereSql}
            GROUP BY p.date_presence
            ORDER BY p.date_presence ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categories = [];
        $seriesPresent = [];
        $seriesRetard = [];
        $seriesAbsent = [];
        $seriesJustifie = [];

        foreach ($rows as $r) {
            $categories[] = $r['date_presence'];
            $seriesPresent[] = (int)$r['count_present'];
            $seriesRetard[] = (int)$r['count_retard'];
            $seriesAbsent[] = (int)$r['count_absent'];
            $seriesJustifie[] = (int)$r['count_justifie'];
        }

        return [
            'categories' => $categories,
            'series' => [
                ['name' => _('Présents'), 'data' => $seriesPresent],
                ['name' => _('Retards'), 'data' => $seriesRetard],
                ['name' => _('Absences non justifiées'), 'data' => $seriesAbsent],
                ['name' => _('Absences justifiées'), 'data' => $seriesJustifie]
            ]
        ];
    }

    /**
     * Class Absence Rate Table (p.matiere_id IS NULL) using Phase 6 canonical roster join.
     */
    private static function getClassAbsenceRates(
        int $lyceeId, int $anneeId, ?int $cycleId, ?string $niveau, ?int $classeId,
        string $dateDebut, string $dateFin, bool $canViewAll, array $assignedClassIds
    ): array {
        $db = Database::getInstance();

        $params = [
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];

        $whereClauses = [
            "c.lycee_id = :lycee_id",
            "et.annee_academique_id = :annee_id",
            "et.is_active = 1",
            "et.status = 'active'",
            "e.statut = 'actif'"
        ];

        if ($cycleId) {
            $whereClauses[] = "c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $whereClauses[] = "c.niveau = :niveau";
            $params['niveau'] = $niveau;
        }
        if ($classeId) {
            $whereClauses[] = "c.id_classe = :classe_id";
            $params['classe_id'] = $classeId;
        }

        if (!$canViewAll && !empty($assignedClassIds)) {
            $inClause = implode(',', array_map('intval', $assignedClassIds));
            $whereClauses[] = "c.id_classe IN ({$inClause})";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $sql = "
            SELECT
                c.id_classe,
                CONCAT(c.niveau, ' ', IFNULL(c.serie, ''), ' ', c.numero) AS nom_classe,
                COUNT(DISTINCT e.id_eleve) AS effectif_actif,
                COUNT(p.id) AS occurrences_enregistrees,
                COUNT(CASE WHEN p.statut IN ('absent', 'justifie') THEN p.id END) AS occurrences_absence,
                COUNT(DISTINCT CASE WHEN p.statut IN ('absent', 'justifie') THEN p.eleve_id END) AS eleves_concernes
            FROM classes c
            JOIN etudes et ON et.classe_id = c.id_classe
            JOIN eleves e ON e.id_eleve = et.eleve_id
            LEFT JOIN presences p ON p.eleve_id = e.id_eleve
                                 AND p.classe_id = c.id_classe
                                 AND p.annee_academique_id = et.annee_academique_id
                                 AND p.matiere_id IS NULL
                                 AND p.date_presence BETWEEN :date_debut AND :date_fin
            WHERE {$whereSql}
            GROUP BY c.id_classe, c.niveau, c.serie, c.numero
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $recOcc = (int)$r['occurrences_enregistrees'];
            $absOcc = (int)$r['occurrences_absence'];
            $tauxAbsence = ($recOcc > 0) ? round(($absOcc / $recOcc) * 100, 1) : 0.0;

            $result[] = [
                'classe_id' => (int)$r['id_classe'],
                'nom_classe' => trim($r['nom_classe']),
                'effectif_actif' => (int)$r['effectif_actif'],
                'occurrences_enregistrees' => $recOcc,
                'occurrences_absence' => $absOcc,
                'eleves_concernes' => (int)$r['eleves_concernes'],
                'taux_absence' => $tauxAbsence
            ];
        }

        usort($result, fn($a, $b) => $b['taux_absence'] <=> $a['taux_absence']);

        return $result;
    }

    /**
     * Repeated Absences Alert List (Absenteeism threshold, p.matiere_id IS NULL) using Phase 6 canonical roster join.
     */
    private static function getRepeatedAbsences(
        int $lyceeId, int $anneeId, ?int $cycleId, ?string $niveau, ?int $classeId,
        int $threshold, bool $canViewAll, array $assignedClassIds
    ): array {
        $db = Database::getInstance();

        $params = [
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId
        ];

        $whereClauses = [
            "p.lycee_id = :lycee_id",
            "p.annee_academique_id = :annee_id",
            "et.is_active = 1",
            "et.status = 'active'",
            "e.statut = 'actif'",
            "p.statut IN ('absent', 'justifie')",
            "p.matiere_id IS NULL"
        ];

        if ($cycleId) {
            $whereClauses[] = "c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $whereClauses[] = "c.niveau = :niveau";
            $params['niveau'] = $niveau;
        }
        if ($classeId) {
            $whereClauses[] = "c.id_classe = :classe_id";
            $params['classe_id'] = $classeId;
        }

        if (!$canViewAll && !empty($assignedClassIds)) {
            $inClause = implode(',', array_map('intval', $assignedClassIds));
            $whereClauses[] = "c.id_classe IN ({$inClause})";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $sql = "
            SELECT
                e.id_eleve,
                e.identifiant_public AS matricule,
                e.nom,
                e.prenom,
                c.id_classe,
                CONCAT(c.niveau, ' ', IFNULL(c.serie, ''), ' ', c.numero) AS nom_classe,
                COUNT(p.id) AS total_occurrences_absence,
                COUNT(CASE WHEN p.statut = 'absent' THEN 1 END) AS absences_unjustified,
                COUNT(CASE WHEN p.statut = 'justifie' THEN 1 END) AS absences_justified
            FROM presences p
            JOIN eleves e ON p.eleve_id = e.id_eleve
            JOIN etudes et ON e.id_eleve = et.eleve_id AND et.annee_academique_id = p.annee_academique_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE {$whereSql}
            GROUP BY e.id_eleve, e.identifiant_public, e.nom, e.prenom, c.id_classe, c.niveau, c.serie, c.numero
            HAVING COUNT(p.id) >= :threshold
            ORDER BY total_occurrences_absence DESC
            LIMIT 50
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':threshold', (int)$threshold, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generates descriptive alerts.
     */
    private static function generateFactualAlerts(array $kpis, array $classRates, array $repeatedAbsences, int $threshold): array {
        $alerts = [];

        if (!empty($repeatedAbsences)) {
            $countRepeated = count($repeatedAbsences);
            $alerts[] = [
                'type' => 'warning',
                'title' => _('Alerte absentéisme individuel'),
                'message' => sprintf(_("%d élève(s) ont atteint ou dépassé le seuil de %d absences sur l'année académique active."), $countRepeated, $threshold)
            ];
        }

        if (!empty($classRates)) {
            $highAbsenceClasses = array_filter($classRates, fn($c) => $c['taux_absence'] >= 15.0);
            if (!empty($highAbsenceClasses)) {
                $classNames = implode(', ', array_map(fn($c) => "{$c['nom_classe']} ({$c['taux_absence']}%)", array_slice($highAbsenceClasses, 0, 3)));
                $alerts[] = [
                    'type' => 'info',
                    'title' => _("Taux d'absence élevé par classe"),
                    'message' => sprintf(_("Taux d'absence élevé (>= 15%%) observé pour : %s."), $classNames)
                ];
            }
        }

        if ($kpis['today_absences']['occurrences'] > 0) {
            $alerts[] = [
                'type' => 'primary',
                'title' => _("Situation du jour"),
                'message' => sprintf(_("%d occurrence(s) d'absence enregistrée(s) aujourd'hui pour %d élève(s) distinct(s)."), $kpis['today_absences']['occurrences'], $kpis['today_absences']['students'])
            ];
        }

        return $alerts;
    }

    private static function emptyDashboardResponse(string $message): array {
        return [
            'success' => false,
            'message' => $message,
            'period' => ['date_debut' => date('Y-m-d'), 'date_fin' => date('Y-m-d')],
            'total_enrolled_students' => 0,
            'kpis' => [
                'presence_rate' => 100.0,
                'total_occurrences_period' => 0,
                'today_absences' => ['occurrences' => 0, 'students' => 0],
                'today_delays' => ['occurrences' => 0, 'students' => 0],
                'period_absences' => ['occurrences' => 0, 'students' => 0],
                'unjustified_absences' => ['occurrences' => 0, 'students' => 0],
                'justified_absences' => ['occurrences' => 0, 'students' => 0],
                'delays' => ['occurrences' => 0, 'students' => 0]
            ],
            'timeline' => ['categories' => [], 'series' => []],
            'class_rates' => [],
            'repeated_absences' => ['threshold' => 5, 'list' => []],
            'alerts' => [['type' => 'info', 'title' => _('Information'), 'message' => $message]]
        ];
    }
}
?>