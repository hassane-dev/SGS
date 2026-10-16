<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/ParamTypeEvaluation.php';
require_once __DIR__ . '/AuthorizationScopeService.php';
require_once __DIR__ . '/EvaluationCalculationService.php';

class GradeDashboardService {

    /**
     * Main entry point to compile complete dashboard data according to filters and user permissions.
     */
    public static function getDashboardData(array $filters, int $userId): array {
        $db = Database::getInstance();

        // 1. Resolve Lycee ID and check authorization
        $lyceeId = !empty($filters['lycee_id']) ? (int)$filters['lycee_id'] : Auth::getLyceeId();
        if (!AuthorizationScopeService::canAccessLycee($lyceeId)) {
            throw new Exception("Accès refusé au lycée spécifié.");
        }

        // 2. Resolve Academic Year
        $anneeId = !empty($filters['annee_academique_id']) ? (int)$filters['annee_academique_id'] : null;
        if (!$anneeId) {
            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear ? (int)$activeYear['id'] : null;
        }
        if (!$anneeId) {
            return self::emptyDashboardResponse("Aucune année académique active trouvée.");
        }

        // 3. Resolve Teacher Assignment Scope if user cannot view all notes
        $canViewAll = Auth::can('view_all', 'note') || Auth::can('generate', 'bulletin');
        $teacherAssignments = [];
        if (!$canViewAll) {
            $teacherAssignments = User::getTeacherAssignments($userId, $anneeId, $lyceeId);
            if (empty($teacherAssignments)) {
                return self::emptyDashboardResponse("Aucune affectation pédagogique active trouvée pour cet enseignant.");
            }
        }

        // 4. Resolve Sequence
        $sequenceId = !empty($filters['sequence_id']) ? (int)$filters['sequence_id'] : null;
        $sequence = null;
        if ($sequenceId) {
            $stmtSeq = $db->prepare("SELECT * FROM sequences WHERE id = :id AND lycee_id = :lycee_id");
            $stmtSeq->execute(['id' => $sequenceId, 'lycee_id' => $lyceeId]);
            $sequence = $stmtSeq->fetch(PDO::FETCH_ASSOC);
        }
        if (!$sequence) {
            $stmtSeqActive = $db->prepare("
                SELECT * FROM sequences
                WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id AND statut = 'ouverte'
                ORDER BY date_debut ASC LIMIT 1
            ");
            $stmtSeqActive->execute(['lycee_id' => $lyceeId, 'annee_id' => $anneeId]);
            $sequence = $stmtSeqActive->fetch(PDO::FETCH_ASSOC);
        }
        if (!$sequence) {
            // Fallback to latest closed sequence for year
            $stmtSeqLast = $db->prepare("
                SELECT * FROM sequences
                WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id
                ORDER BY date_fin DESC LIMIT 1
            ");
            $stmtSeqLast->execute(['lycee_id' => $lyceeId, 'annee_id' => $anneeId]);
            $sequence = $stmtSeqLast->fetch(PDO::FETCH_ASSOC);
        }
        if (!$sequence) {
            return self::emptyDashboardResponse("Aucune séquence trouvée pour l'année académique.");
        }

        $sequenceId = (int)$sequence['id'];
        $isClosed = ($sequence['statut'] === 'fermee');

        // 5. Build filter constraints & validated IDs
        $cycleId = !empty($filters['cycle_id']) ? (int)$filters['cycle_id'] : null;
        if ($cycleId && !AuthorizationScopeService::canAccessCycle($cycleId)) {
            throw new Exception("Accès refusé au cycle spécifié.");
        }

        $niveau = !empty($filters['niveau']) ? trim($filters['niveau']) : null;
        $classeId = !empty($filters['classe_id']) ? (int)$filters['classe_id'] : null;
        $matiereId = !empty($filters['matiere_id']) ? (int)$filters['matiere_id'] : null;
        $typeEvalId = !empty($filters['type_evaluation_id']) ? (int)$filters['type_evaluation_id'] : null;

        // Verify class & subject against teacher assignments if restricted
        if (!$canViewAll) {
            $assignedClassIds = array_unique(array_map(fn($a) => (int)($a['id_classe'] ?? $a['classe_id']), $teacherAssignments));
            if ($classeId && !in_array($classeId, $assignedClassIds)) {
                throw new Exception("Accès refusé : classe hors affectations pédagogiques.");
            }
            if ($matiereId) {
                $assignedMatiereIds = array_unique(array_map(fn($a) => (int)($a['id_matiere'] ?? $a['matiere_id']), $teacherAssignments));
                if (!in_array($matiereId, $assignedMatiereIds)) {
                    throw new Exception("Accès refusé : matière hors affectations pédagogiques.");
                }
            }
            if ($classeId && $matiereId) {
                $pairValid = false;
                foreach ($teacherAssignments as $a) {
                    $c = (int)($a['id_classe'] ?? $a['classe_id']);
                    $m = (int)($a['id_matiere'] ?? $a['matiere_id']);
                    if ($c === $classeId && $m === $matiereId) {
                        $pairValid = true;
                        break;
                    }
                }
                if (!$pairValid) {
                    throw new Exception("Accès refusé : association classe-matière hors affectations pédagogiques.");
                }
            }
        }

        // 6. Compute Completion Data (always from live evaluations & param_type_evaluation)
        $completion = self::getCompletionData($lyceeId, $anneeId, $sequenceId, $cycleId, $niveau, $classeId, $matiereId, $typeEvalId, $canViewAll, $teacherAssignments);

        // 7. Compute Academic Performance Data (Live for open sequence, Official Snapshots for closed sequence)
        if ($isClosed) {
            $performance = self::getOfficialSnapshotPerformanceData($lyceeId, $anneeId, $sequenceId, $cycleId, $niveau, $classeId, $matiereId, $canViewAll, $teacherAssignments);
            $statusLabel = "Résultats officiels scellés";
            $statusBadgeClass = "bg-light-success text-success";
        } else {
            $performance = self::getLivePerformanceData($lyceeId, $anneeId, $sequenceId, $cycleId, $niveau, $classeId, $matiereId, $canViewAll, $teacherAssignments);
            $statusLabel = "Résultats en temps réel";
            $statusBadgeClass = "bg-light-warning text-warning";
        }

        // 8. Generate Factual Alerts
        $alerts = self::generateFactualAlerts($completion, $performance, $isClosed);

        return [
            'success' => true,
            'lycee_id' => $lyceeId,
            'annee_academique_id' => $anneeId,
            'sequence' => [
                'id' => $sequenceId,
                'nom' => $sequence['nom'],
                'statut' => $sequence['statut'],
                'is_closed' => $isClosed,
                'status_label' => $statusLabel,
                'status_badge_class' => $statusBadgeClass,
                'date_debut' => $sequence['date_debut'],
                'date_fin' => $sequence['date_fin']
            ],
            'completion' => $completion,
            'performance' => $performance,
            'alerts' => $alerts
        ];
    }

    /**
     * Calculates evaluation completion statistics based on actual enrolled students and configured evaluation types.
     */
    public static function getCompletionData(
        int $lyceeId, int $anneeId, int $sequenceId,
        ?int $cycleId, ?string $niveau, ?int $classeId, ?int $matiereId, ?int $typeEvalId,
        bool $canViewAll, array $teacherAssignments
    ): array {
        $db = Database::getInstance();

        // A. Resolve Active Evaluation Types for establishment
        $typeParams = ParamTypeEvaluation::findActive($lyceeId);
        if ($typeEvalId) {
            $typeParams = array_filter($typeParams, fn($t) => (int)$t['id'] === $typeEvalId);
        }
        if (empty($typeParams)) {
            return [
                'expected_evaluations' => 0,
                'recorded_evaluations' => 0,
                'missing_evaluations' => 0,
                'completion_rate' => 0.0,
                'assessed_students' => 0,
                'total_students' => 0,
                'incomplete_classes' => [],
                'incomplete_subjects' => []
            ];
        }

        // B. Query active class-subject-student combinations
        // Truth source for curriculum is classes -> classe_matieres -> matieres
        $params = [
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId
        ];
        $whereClauses = ["c.lycee_id = :lycee_id"];

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
        if ($matiereId) {
            $whereClauses[] = "cm.matiere_id = :matiere_id";
            $params['matiere_id'] = $matiereId;
        }

        if (!$canViewAll && !empty($teacherAssignments)) {
            $orClauses = [];
            foreach ($teacherAssignments as $idx => $assign) {
                $cKey = ":tc_cls_" . $idx;
                $mKey = ":tc_mat_" . $idx;
                $orClauses[] = "(c.id_classe = {$cKey} AND cm.matiere_id = {$mKey})";
                $params["tc_cls_" . $idx] = (int)($assign['id_classe'] ?? $assign['classe_id']);
                $params["tc_mat_" . $idx] = (int)($assign['id_matiere'] ?? $assign['matiere_id']);
            }
            if (!empty($orClauses)) {
                $whereClauses[] = "(" . implode(" OR ", $orClauses) . ")";
            }
        }

        $whereSql = implode(" AND ", $whereClauses);

        // Calculate expected evaluations ($E_{att}$) starting from classes -> classe_matieres -> matieres
        // LEFT JOIN on etudes ensures configured class-subject pairs are retained even with 0 enrolled students or 0 evaluations
        $sqlExpected = "
            SELECT
                c.id_classe,
                CONCAT(c.niveau, ' ', IFNULL(c.serie, ''), ' ', c.numero) AS nom_classe,
                cm.matiere_id,
                m.nom_matiere,
                COUNT(DISTINCT et.eleve_id) AS student_count
            FROM classes c
            JOIN classe_matieres cm ON cm.classe_id = c.id_classe
            JOIN matieres m ON cm.matiere_id = m.id_matiere
            LEFT JOIN etudes et ON et.classe_id = c.id_classe AND et.annee_academique_id = :annee_id AND et.is_active = 1
            WHERE {$whereSql}
            GROUP BY c.id_classe, cm.matiere_id, m.nom_matiere
        ";

        $stmtExp = $db->prepare($sqlExpected);
        $stmtExp->execute($params);
        $classSubjectCounts = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

        $totalExpected = 0;
        $subjectExpectations = []; // [ "classe_id:matiere_id" => ['nom_classe', 'nom_matiere', 'students', 'expected', 'recorded'] ]

        foreach ($classSubjectCounts as $row) {
            $cId = (int)$row['id_classe'];
            $mId = (int)$row['matiere_id'];
            $stuCount = (int)$row['student_count'];
            $key = "{$cId}:{$mId}";

            $expectedForPair = 0;
            foreach ($typeParams as $tp) {
                $nbOcc = max(1, (int)($tp['nombre_evaluation'] ?? 1));
                $expectedForPair += ($stuCount * $nbOcc);
            }

            $totalExpected += $expectedForPair;
            $subjectExpectations[$key] = [
                'classe_id' => $cId,
                'nom_classe' => trim($row['nom_classe']),
                'matiere_id' => $mId,
                'nom_matiere' => $row['nom_matiere'],
                'student_count' => $stuCount,
                'expected' => $expectedForPair,
                'recorded' => 0
            ];
        }

        // Query recorded evaluations ($E_{rec}$)
        $evalParams = ['sequence_id' => $sequenceId, 'lycee_id' => $lyceeId, 'annee_id' => $anneeId];
        $evalWhere = ["e.sequence_id = :sequence_id", "e.lycee_id = :lycee_id", "e.annee_academique_id = :annee_id"];

        if ($cycleId) {
            $evalWhere[] = "c.cycle_id = :cycle_id";
            $evalParams['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $evalWhere[] = "c.niveau = :niveau";
            $evalParams['niveau'] = $niveau;
        }
        if ($classeId) {
            $evalWhere[] = "e.classe_id = :classe_id";
            $evalParams['classe_id'] = $classeId;
        }
        if ($matiereId) {
            $evalWhere[] = "e.matiere_id = :matiere_id";
            $evalParams['matiere_id'] = $matiereId;
        }
        if ($typeEvalId) {
            $evalWhere[] = "e.type_evaluation_id = :type_eval_id";
            $evalParams['type_eval_id'] = $typeEvalId;
        }

        if (!$canViewAll && !empty($teacherAssignments)) {
            $orClauses = [];
            foreach ($teacherAssignments as $idx => $assign) {
                $cKey = ":e_tc_cls_" . $idx;
                $mKey = ":e_tc_mat_" . $idx;
                $orClauses[] = "(e.classe_id = {$cKey} AND e.matiere_id = {$mKey})";
                $evalParams["e_tc_cls_" . $idx] = (int)($assign['id_classe'] ?? $assign['classe_id']);
                $evalParams["e_tc_mat_" . $idx] = (int)($assign['id_matiere'] ?? $assign['matiere_id']);
            }
            if (!empty($orClauses)) {
                $evalWhere[] = "(" . implode(" OR ", $orClauses) . ")";
            }
        }

        $evalWhereSql = implode(" AND ", $evalWhere);

        $sqlRecorded = "
            SELECT
                e.classe_id,
                e.matiere_id,
                COUNT(DISTINCT e.id) AS recorded_count,
                COUNT(DISTINCT e.eleve_id) AS assessed_students
            FROM evaluations e
            JOIN classes c ON e.classe_id = c.id_classe
            WHERE {$evalWhereSql}
            GROUP BY e.classe_id, e.matiere_id
        ";

        $stmtRec = $db->prepare($sqlRecorded);
        $stmtRec->execute($evalParams);
        $recordedRows = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

        $totalRecorded = 0;
        foreach ($recordedRows as $r) {
            $key = "{$r['classe_id']}:{$r['matiere_id']}";
            $recCount = (int)$r['recorded_count'];
            $totalRecorded += $recCount;

            if (isset($subjectExpectations[$key])) {
                $subjectExpectations[$key]['recorded'] = $recCount;
            }
        }

        // Count overall unique assessed students
        $sqlStudentsAssessed = "
            SELECT COUNT(DISTINCT e.eleve_id) AS assessed_total
            FROM evaluations e
            JOIN classes c ON e.classe_id = c.id_classe
            WHERE {$evalWhereSql}
        ";
        $stmtStu = $db->prepare($sqlStudentsAssessed);
        $stmtStu->execute($evalParams);
        $assessedTotal = (int)$stmtStu->fetchColumn();

        // Total enrolled students in target scope
        $sqlTotalStudents = "
            SELECT COUNT(DISTINCT et.eleve_id) AS total_enrolled
            FROM etudes et
            JOIN classes c ON et.classe_id = c.id_classe
            JOIN classe_matieres cm ON cm.classe_id = c.id_classe
            WHERE {$whereSql} AND et.annee_academique_id = :annee_id AND et.is_active = 1
        ";
        $stmtTot = $db->prepare($sqlTotalStudents);
        $stmtTot->execute($params);
        $totalEnrolled = (int)$stmtTot->fetchColumn();

        $missing = max(0, $totalExpected - $totalRecorded);
        $rate = ($totalExpected > 0) ? round(($totalRecorded / $totalExpected) * 100, 1) : 0.0;

        // Group incomplete subjects & classes
        $incompleteSubjects = [];
        $classIncompleteStats = [];

        foreach ($subjectExpectations as $pair) {
            $exp = $pair['expected'];
            $rec = $pair['recorded'];
            $cId = $pair['classe_id'];
            $nomClasse = $pair['nom_classe'];
            $stuCount = $pair['student_count'];

            if (!isset($classIncompleteStats[$cId])) {
                $classIncompleteStats[$cId] = [
                    'classe_id' => $cId,
                    'nom_classe' => $nomClasse,
                    'expected' => 0,
                    'recorded' => 0,
                    'has_students' => false
                ];
            }
            $classIncompleteStats[$cId]['expected'] += $exp;
            $classIncompleteStats[$cId]['recorded'] += $rec;
            if ($stuCount > 0) {
                $classIncompleteStats[$cId]['has_students'] = true;
            }

            // Flag as incomplete if recorded < expected OR if configured subject has 0 students enrolled and 0 recorded evaluations
            $isIncomplete = ($rec < $exp) || ($stuCount === 0 && $rec === 0);

            if ($isIncomplete) {
                $incompleteSubjects[] = [
                    'classe_id' => $cId,
                    'nom_classe' => $nomClasse,
                    'matiere_id' => $pair['matiere_id'],
                    'nom_matiere' => $pair['nom_matiere'],
                    'student_count' => $stuCount,
                    'expected' => $exp,
                    'recorded' => $rec,
                    'missing' => ($exp > $rec) ? ($exp - $rec) : 0,
                    'rate' => ($exp > 0) ? round(($rec / $exp) * 100, 1) : 0.0
                ];
            }
        }

        $incompleteClasses = [];
        foreach ($classIncompleteStats as $cStat) {
            $cExp = $cStat['expected'];
            $cRec = $cStat['recorded'];
            $hasStudents = $cStat['has_students'];

            // Class is incomplete if recorded < expected OR if class is configured without active students and 0 recorded evaluations
            $isClassIncomplete = ($cRec < $cExp) || (!$hasStudents && $cRec === 0);

            if ($isClassIncomplete) {
                $incompleteClasses[] = [
                    'classe_id' => $cStat['classe_id'],
                    'nom_classe' => $cStat['nom_classe'],
                    'expected' => $cExp,
                    'recorded' => $cRec,
                    'missing' => ($cExp > $cRec) ? ($cExp - $cRec) : 0,
                    'rate' => ($cExp > 0) ? round(($cRec / $cExp) * 100, 1) : 0.0
                ];
            }
        }

        usort($incompleteSubjects, fn($a, $b) => $a['rate'] <=> $b['rate']);
        usort($incompleteClasses, fn($a, $b) => $a['rate'] <=> $b['rate']);

        return [
            'expected_evaluations' => $totalExpected,
            'recorded_evaluations' => $totalRecorded,
            'missing_evaluations' => $missing,
            'completion_rate' => $rate,
            'assessed_students' => $assessedTotal,
            'total_students' => $totalEnrolled,
            'incomplete_classes' => $incompleteClasses,
            'incomplete_subjects' => $incompleteSubjects
        ];
    }

    /**
     * Computes LIVE performance data for an open sequence using EvaluationCalculationService logic.
     */
    public static function getLivePerformanceData(
        int $lyceeId, int $anneeId, int $sequenceId,
        ?int $cycleId, ?string $niveau, ?int $classeId, ?int $matiereId,
        bool $canViewAll, array $teacherAssignments
    ): array {
        $db = Database::getInstance();

        // Query target students
        $params = ['lycee_id' => $lyceeId, 'annee_id' => $anneeId];
        $whereClauses = ["c.lycee_id = :lycee_id", "et.annee_academique_id = :annee_id", "et.is_active = 1"];

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

        if (!$canViewAll && !empty($teacherAssignments)) {
            $assignedClassIds = array_unique(array_map(fn($a) => (int)($a['id_classe'] ?? $a['classe_id']), $teacherAssignments));
            if (!empty($assignedClassIds)) {
                $inClause = implode(',', $assignedClassIds);
                $whereClauses[] = "c.id_classe IN ({$inClause})";
            }
        }

        $whereSql = implode(" AND ", $whereClauses);

        $sqlStudents = "
            SELECT et.eleve_id, et.classe_id, CONCAT(c.niveau, ' ', IFNULL(c.serie, ''), ' ', c.numero) AS nom_classe
            FROM etudes et
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE {$whereSql}
        ";
        $stmtStu = $db->prepare($sqlStudents);
        $stmtStu->execute($params);
        $students = $stmtStu->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            return self::emptyPerformanceResponse();
        }

        $studentAverages = [];
        $classAverages = [];
        $subjectReports = [];

        $assignedMatiereIds = (!$canViewAll && !empty($teacherAssignments))
            ? array_unique(array_map(fn($a) => (int)($a['id_matiere'] ?? $a['matiere_id']), $teacherAssignments))
            : [];

        $assignedPairs = [];
        if (!$canViewAll && !empty($teacherAssignments)) {
            foreach ($teacherAssignments as $a) {
                $c = (int)($a['id_classe'] ?? $a['classe_id']);
                $m = (int)($a['id_matiere'] ?? $a['matiere_id']);
                $assignedPairs[$c][$m] = true;
            }
        }

        foreach ($students as $stu) {
            $eId = (int)$stu['eleve_id'];
            $cId = (int)$stu['classe_id'];
            $nomClasse = trim($stu['nom_classe']);

            // Re-use EvaluationCalculationService official math
            $report = EvaluationCalculationService::computeStudentSequenceReport($eId, $sequenceId);
            if (empty($report['matieres'])) {
                continue; // No evaluated grades yet
            }

            // Filter report matieres to target subject filter AND teacher assigned scope if restricted
            $targetMatieres = [];
            foreach ($report['matieres'] as $mId => $m) {
                $mIdInt = (int)$mId;
                if ($matiereId && $mIdInt !== $matiereId) {
                    continue;
                }
                if (!$canViewAll) {
                    if (empty($assignedPairs[$cId][$mIdInt])) {
                        continue; // Skip subject not assigned to teacher in this class
                    }
                }
                $targetMatieres[$mIdInt] = $m;
            }

            if (empty($targetMatieres)) {
                continue;
            }

            // Compute student average for the allowed scope
            if ($matiereId) {
                $stuAvg = (float)reset($targetMatieres)['moyenne'];
            } elseif (!$canViewAll) {
                // Restricted teacher without explicit single subject filter:
                // Calculate average strictly over assigned subjects for this class
                $totPoints = 0.0;
                $totCoeff = 0.0;
                foreach ($targetMatieres as $m) {
                    $mAvg = (float)$m['moyenne'];
                    $coef = (float)($m['coefficient'] ?? 1.0);
                    $totPoints += ($mAvg * $coef);
                    $totCoeff += $coef;
                }
                $stuAvg = ($totCoeff > 0) ? round($totPoints / $totCoeff, 2) : 0.0;
            } else {
                // Global user (view_all) without subject filter: official general average
                $stuAvg = (float)$report['moyenne_generale'];
            }

            $studentAverages[] = $stuAvg;

            if (!isset($classAverages[$cId])) {
                $classAverages[$cId] = ['nom_classe' => $nomClasse, 'sum' => 0.0, 'count' => 0];
            }
            $classAverages[$cId]['sum'] += $stuAvg;
            $classAverages[$cId]['count']++;

            foreach ($targetMatieres as $mIdInt => $m) {
                if (!isset($subjectReports[$mIdInt])) {
                    $subjectReports[$mIdInt] = ['nom_matiere' => $m['nom'], 'sum' => 0.0, 'count' => 0];
                }
                $subjectReports[$mIdInt]['sum'] += (float)$m['moyenne'];
                $subjectReports[$mIdInt]['count']++;
            }
        }

        return self::formatPerformanceMetrics($studentAverages, $classAverages, $subjectReports);
    }

    /**
     * Retrieves OFFICIAL SNAPSHOT performance data for a closed sequence directly from bulletins & bulletin_details.
     */
    public static function getOfficialSnapshotPerformanceData(
        int $lyceeId, int $anneeId, int $sequenceId,
        ?int $cycleId, ?string $niveau, ?int $classeId, ?int $matiereId,
        bool $canViewAll, array $teacherAssignments
    ): array {
        $db = Database::getInstance();

        $params = ['sequence_id' => $sequenceId, 'lycee_id' => $lyceeId, 'annee_id' => $anneeId];
        $whereClauses = ["b.sequence_id = :sequence_id", "b.lycee_id = :lycee_id", "b.annee_academique_id = :annee_id"];

        if ($cycleId) {
            $whereClauses[] = "c.cycle_id = :cycle_id";
            $params['cycle_id'] = $cycleId;
        }
        if ($niveau) {
            $whereClauses[] = "c.niveau = :niveau";
            $params['niveau'] = $niveau;
        }
        if ($classeId) {
            $whereClauses[] = "b.classe_id = :classe_id";
            $params['classe_id'] = $classeId;
        }

        if (!$canViewAll && !empty($teacherAssignments)) {
            $assignedClassIds = array_unique(array_map(fn($a) => (int)($a['id_classe'] ?? $a['classe_id']), $teacherAssignments));
            if (!empty($assignedClassIds)) {
                $inClause = implode(',', $assignedClassIds);
                $whereClauses[] = "b.classe_id IN ({$inClause})";
            }
        }

        $whereSql = implode(" AND ", $whereClauses);

        $assignedMatiereIds = (!$canViewAll && !empty($teacherAssignments))
            ? array_unique(array_map(fn($a) => (int)($a['id_matiere'] ?? $a['matiere_id']), $teacherAssignments))
            : [];

        $studentAverages = [];
        $classAverages = [];
        $subjectReports = [];

        $assignedPairs = [];
        if (!$canViewAll && !empty($teacherAssignments)) {
            foreach ($teacherAssignments as $a) {
                $c = (int)($a['id_classe'] ?? $a['classe_id']);
                $m = (int)($a['id_matiere'] ?? $a['matiere_id']);
                $assignedPairs[$c][$m] = true;
            }
        }

        if ($matiereId) {
            // When filtering by a specific subject, read subject averages from bulletin_details
            $sqlSubSnapshots = "
                SELECT
                    b.id AS bulletin_id,
                    b.eleve_id,
                    b.classe_id,
                    IFNULL(b.nom_classe_snapshot, CONCAT(c.niveau, ' ', IFNULL(c.serie, ''), ' ', c.numero)) AS nom_classe,
                    bd.matiere_id,
                    bd.nom_matiere_snapshot,
                    bd.moyenne_matiere
                FROM bulletins b
                JOIN classes c ON b.classe_id = c.id_classe
                JOIN bulletin_details bd ON bd.bulletin_id = b.id
                WHERE {$whereSql} AND bd.matiere_id = :filter_matiere_id
            ";
            $params['filter_matiere_id'] = $matiereId;
            $stmtS = $db->prepare($sqlSubSnapshots);
            $stmtS->execute($params);
            $rows = $stmtS->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return self::emptyPerformanceResponse();
            }

            foreach ($rows as $r) {
                $cId = (int)$r['classe_id'];
                $mId = (int)$r['matiere_id'];

                if (!$canViewAll && empty($assignedPairs[$cId][$mId])) {
                    continue;
                }

                $subAvg = (float)$r['moyenne_matiere'];
                $nomClasse = trim($r['nom_classe']);

                $studentAverages[] = $subAvg;

                if (!isset($classAverages[$cId])) {
                    $classAverages[$cId] = ['nom_classe' => $nomClasse, 'sum' => 0.0, 'count' => 0];
                }
                $classAverages[$cId]['sum'] += $subAvg;
                $classAverages[$cId]['count']++;

                if (!isset($subjectReports[$mId])) {
                    $subjectReports[$mId] = ['nom_matiere' => $r['nom_matiere_snapshot'], 'sum' => 0.0, 'count' => 0];
                }
                $subjectReports[$mId]['sum'] += $subAvg;
                $subjectReports[$mId]['count']++;
            }
        } else {
            // General bulletins snapshots across all subjects
            $sqlBulletins = "
                SELECT
                    b.id AS bulletin_id,
                    b.eleve_id,
                    b.classe_id,
                    IFNULL(b.nom_classe_snapshot, CONCAT(c.niveau, ' ', IFNULL(c.serie, ''), ' ', c.numero)) AS nom_classe,
                    b.moyenne_generale
                FROM bulletins b
                JOIN classes c ON b.classe_id = c.id_classe
                WHERE {$whereSql}
            ";

            $stmtB = $db->prepare($sqlBulletins);
            $stmtB->execute($params);
            $bulletins = $stmtB->fetchAll(PDO::FETCH_ASSOC);

            if (empty($bulletins)) {
                return self::emptyPerformanceResponse();
            }

            $bulletinIds = array_column($bulletins, 'bulletin_id');
            $inBulIds = implode(',', array_map('intval', $bulletinIds));

            $subWhere = "bd.bulletin_id IN ({$inBulIds})";
            if (!$canViewAll && !empty($assignedMatiereIds)) {
                $inMatIds = implode(',', array_map('intval', $assignedMatiereIds));
                $subWhere .= " AND bd.matiere_id IN ({$inMatIds})";
            }

            $sqlDetails = "
                SELECT
                    bd.bulletin_id,
                    bd.matiere_id,
                    bd.nom_matiere_snapshot,
                    bd.moyenne_matiere,
                    bd.coefficient_snapshot
                FROM bulletin_details bd
                WHERE {$subWhere}
            ";
            $stmtD = $db->query($sqlDetails);
            $details = $stmtD->fetchAll(PDO::FETCH_ASSOC);

            $detailsByBulletin = [];
            foreach ($details as $d) {
                $bId = (int)$d['bulletin_id'];
                $detailsByBulletin[$bId][] = $d;
            }

            foreach ($bulletins as $b) {
                $bId = (int)$b['bulletin_id'];
                $cId = (int)$b['classe_id'];
                $nomClasse = trim($b['nom_classe']);

                $bDetails = $detailsByBulletin[$bId] ?? [];

                if (!$canViewAll) {
                    $validDetails = array_filter($bDetails, function($d) use ($assignedPairs, $cId) {
                        return !empty($assignedPairs[$cId][(int)$d['matiere_id']]);
                    });

                    if (empty($validDetails)) {
                        continue;
                    }

                    $totPoints = 0.0;
                    $totCoeff = 0.0;
                    foreach ($validDetails as $d) {
                        $mAvg = (float)$d['moyenne_matiere'];
                        $coef = (float)($d['coefficient_snapshot'] ?? 1.0);
                        $totPoints += ($mAvg * $coef);
                        $totCoeff += $coef;
                    }
                    $stuAvg = ($totCoeff > 0) ? round($totPoints / $totCoeff, 2) : 0.0;
                } else {
                    $stuAvg = (float)$b['moyenne_generale'];
                    $validDetails = $bDetails;
                }

                $studentAverages[] = $stuAvg;

                if (!isset($classAverages[$cId])) {
                    $classAverages[$cId] = ['nom_classe' => $nomClasse, 'sum' => 0.0, 'count' => 0];
                }
                $classAverages[$cId]['sum'] += $stuAvg;
                $classAverages[$cId]['count']++;

                foreach ($validDetails as $d) {
                    $mId = (int)$d['matiere_id'];
                    if (!isset($subjectReports[$mId])) {
                        $subjectReports[$mId] = ['nom_matiere' => $d['nom_matiere_snapshot'], 'sum' => 0.0, 'count' => 0];
                    }
                    $subjectReports[$mId]['sum'] += (float)$d['moyenne_matiere'];
                    $subjectReports[$mId]['count']++;
                }
            }
        }

        return self::formatPerformanceMetrics($studentAverages, $classAverages, $subjectReports);
    }

    /**
     * Formats performance data, distribution buckets, and class comparisons.
     */
    private static function formatPerformanceMetrics(array $studentAverages, array $classAverages, array $subjectReports): array {
        $count = count($studentAverages);
        if ($count === 0) {
            return self::emptyPerformanceResponse();
        }

        sort($studentAverages);
        $min = round($studentAverages[0], 2);
        $max = round($studentAverages[$count - 1], 2);
        $sum = array_sum($studentAverages);
        $overallAverage = round($sum / $count, 2);

        // Strict Distribution Buckets: [0-5[, [5-10[, [10-12[, [12-14[, [14-16[, [16-20]
        $distribution = [
            '0_5' => 0,
            '5_10' => 0,
            '10_12' => 0,
            '12_14' => 0,
            '14_16' => 0,
            '16_20' => 0
        ];

        foreach ($studentAverages as $avg) {
            if ($avg < 5.0) {
                $distribution['0_5']++;
            } elseif ($avg < 10.0) {
                $distribution['5_10']++;
            } elseif ($avg < 12.0) {
                $distribution['10_12']++;
            } elseif ($avg < 14.0) {
                $distribution['12_14']++;
            } elseif ($avg < 16.0) {
                $distribution['14_16']++;
            } else {
                $distribution['16_20']++;
            }
        }

        // Format Class Comparisons
        $classComparison = [];
        foreach ($classAverages as $cId => $cData) {
            $cAvg = ($cData['count'] > 0) ? round($cData['sum'] / $cData['count'], 2) : 0.0;
            $classComparison[] = [
                'classe_id' => $cId,
                'nom_classe' => $cData['nom_classe'],
                'student_count' => $cData['count'],
                'moyenne' => $cAvg
            ];
        }
        usort($classComparison, fn($a, $b) => $b['moyenne'] <=> $a['moyenne']);

        // Format Subject Averages
        $formattedSubjects = [];
        foreach ($subjectReports as $mId => $mData) {
            $mAvg = ($mData['count'] > 0) ? round($mData['sum'] / $mData['count'], 2) : 0.0;
            $formattedSubjects[] = [
                'matiere_id' => $mId,
                'nom_matiere' => $mData['nom_matiere'],
                'evaluated_students' => $mData['count'],
                'moyenne' => $mAvg,
                'appreciation' => EvaluationCalculationService::getInstitutionalAppreciation($mAvg)
            ];
        }
        usort($formattedSubjects, fn($a, $b) => $b['moyenne'] <=> $a['moyenne']);

        return [
            'assessed_students_count' => $count,
            'moyenne_generale' => $overallAverage,
            'min_moyenne' => $min,
            'max_moyenne' => $max,
            'distribution' => $distribution,
            'class_comparison' => $classComparison,
            'subject_averages' => $formattedSubjects
        ];
    }

    /**
     * Generates factual, objective descriptive alerts without subjective judgments on teachers.
     */
    private static function generateFactualAlerts(array $completion, array $performance, bool $isClosed): array {
        $alerts = [];

        if ($completion['completion_rate'] < 100.0 && $completion['missing_evaluations'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Évaluations incomplètes',
                'message' => "Il reste {$completion['missing_evaluations']} note(s) non saisie(s) dans le périmètre sélectionné (Taux de complétude : {$completion['completion_rate']}%)."
            ];
        }

        if (!empty($completion['incomplete_subjects'])) {
            $topIncomplete = array_slice($completion['incomplete_subjects'], 0, 3);
            $subjectListStr = implode(', ', array_map(fn($s) => "{$s['nom_matiere']} ({$s['nom_classe']})", $topIncomplete));
            $alerts[] = [
                'type' => 'info',
                'title' => 'Matières avec saisies partielles',
                'message' => "Saisie incomplète observée notamment pour : {$subjectListStr}."
            ];
        }

        if ($isClosed) {
            $alerts[] = [
                'type' => 'success',
                'title' => 'Séquence clôturée',
                'message' => "Cette séquence est clôturée. Les performances affichées proviennent des snapshots officiels scellés du bulletin."
            ];
        }

        return $alerts;
    }

    private static function emptyPerformanceResponse(): array {
        return [
            'assessed_students_count' => 0,
            'moyenne_generale' => 0.0,
            'min_moyenne' => 0.0,
            'max_moyenne' => 0.0,
            'distribution' => ['0_5' => 0, '5_10' => 0, '10_12' => 0, '12_14' => 0, '14_16' => 0, '16_20' => 0],
            'class_comparison' => [],
            'subject_averages' => []
        ];
    }

    private static function emptyDashboardResponse(string $message): array {
        return [
            'success' => false,
            'message' => $message,
            'sequence' => ['status_label' => 'Séquence indisponible', 'status_badge_class' => 'bg-light-secondary text-secondary', 'is_closed' => false],
            'completion' => ['expected_evaluations' => 0, 'recorded_evaluations' => 0, 'missing_evaluations' => 0, 'completion_rate' => 0.0, 'assessed_students' => 0, 'total_students' => 0, 'incomplete_classes' => [], 'incomplete_subjects' => []],
            'performance' => self::emptyPerformanceResponse(),
            'alerts' => [['type' => 'warning', 'title' => 'Information', 'message' => $message]]
        ];
    }

    /**
     * Lightweight summary KPIs method for Global Dashboard.
     */
    public static function getSummaryKpis(int $lyceeId, ?int $anneeId = null, ?int $sequenceId = null): array {
        $db = Database::getInstance();
        if (!$anneeId) {
            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear ? (int)$activeYear['id'] : null;
        }
        if (!$anneeId) {
            return [
                'average_grade' => null,
                'completion_rate' => 0.0,
                'active_sequence_name' => _('Aucune séquence'),
                'sequence_statut' => 'fermee',
                'total_evaluations' => 0
            ];
        }

        // Active sequence
        $sequence = null;
        if ($sequenceId) {
            $stmtSeq = $db->prepare("SELECT * FROM sequences WHERE id = :id AND lycee_id = :lycee_id");
            $stmtSeq->execute(['id' => $sequenceId, 'lycee_id' => $lyceeId]);
            $sequence = $stmtSeq->fetch(PDO::FETCH_ASSOC);
        }
        if (!$sequence) {
            $stmtSeqActive = $db->prepare("
                SELECT * FROM sequences
                WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id AND statut = 'ouverte'
                ORDER BY id ASC LIMIT 1
            ");
            $stmtSeqActive->execute(['lycee_id' => $lyceeId, 'annee_id' => $anneeId]);
            $sequence = $stmtSeqActive->fetch(PDO::FETCH_ASSOC);
        }

        if (!$sequence) {
            $stmtSeqLast = $db->prepare("
                SELECT * FROM sequences
                WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id
                ORDER BY id DESC LIMIT 1
            ");
            $stmtSeqLast->execute(['lycee_id' => $lyceeId, 'annee_id' => $anneeId]);
            $sequence = $stmtSeqLast->fetch(PDO::FETCH_ASSOC);
        }

        $seqId = $sequence ? (int)$sequence['id'] : 0;
        $isClosed = $sequence && $sequence['statut'] === 'fermee';

        // Overall Average
        $avgGrade = null;
        if ($isClosed) {
            $stmtAvg = $db->prepare("
                SELECT AVG(moyenne_generale)
                FROM bulletins
                WHERE lycee_id = :lycee_id AND sequence_id = :seq_id AND moyenne_generale IS NOT NULL
            ");
            $stmtAvg->execute(['lycee_id' => $lyceeId, 'seq_id' => $seqId]);
            $val = $stmtAvg->fetchColumn();
            if ($val !== false && $val !== null) {
                $avgGrade = round((float)$val, 2);
            }
        } else if ($seqId > 0) {
            $stmtAvg = $db->prepare("
                SELECT AVG((note / bareme_snapshot) * 20)
                FROM evaluations
                WHERE lycee_id = :lycee_id AND sequence_id = :seq_id AND note IS NOT NULL AND bareme_snapshot > 0
            ");
            $stmtAvg->execute(['lycee_id' => $lyceeId, 'seq_id' => $seqId]);
            $val = $stmtAvg->fetchColumn();
            if ($val !== false && $val !== null) {
                $avgGrade = round((float)$val, 2);
            }
        }

        // Total Recorded Evaluations
        $totalEvals = 0;
        if ($seqId > 0) {
            $stmtCount = $db->prepare("
                SELECT COUNT(id) FROM evaluations
                WHERE lycee_id = :lycee_id AND sequence_id = :seq_id AND note IS NOT NULL
            ");
            $stmtCount->execute(['lycee_id' => $lyceeId, 'seq_id' => $seqId]);
            $totalEvals = (int)$stmtCount->fetchColumn();
        }

        // Fast completion rate calculation
        $completionRate = 0.0;
        if ($seqId > 0) {
            $stmtClasses = $db->prepare("
                SELECT COUNT(DISTINCT CONCAT(c.id_classe, '_', cm.matiere_id))
                FROM classes c
                JOIN classe_matieres cm ON c.id_classe = cm.classe_id
                WHERE c.lycee_id = :lycee_id
            ");
            $stmtClasses->execute(['lycee_id' => $lyceeId]);
            $expectedPairs = (int)$stmtClasses->fetchColumn();

            $stmtRecordedPairs = $db->prepare("
                SELECT COUNT(DISTINCT CONCAT(classe_id, '_', matiere_id))
                FROM evaluations
                WHERE lycee_id = :lycee_id AND sequence_id = :seq_id AND note IS NOT NULL
            ");
            $stmtRecordedPairs->execute(['lycee_id' => $lyceeId, 'seq_id' => $seqId]);
            $recordedPairs = (int)$stmtRecordedPairs->fetchColumn();

            if ($expectedPairs > 0) {
                $completionRate = min(100.0, round(($recordedPairs / $expectedPairs) * 100, 1));
            }
        }

        return [
            'average_grade' => $avgGrade,
            'completion_rate' => $completionRate,
            'active_sequence_name' => $sequence ? ($sequence['nom_sequence'] ?? "Séquence {$sequence['numero_sequence']}") : _('Aucune'),
            'sequence_statut' => $sequence ? $sequence['statut'] : 'non_definie',
            'total_evaluations' => $totalEvals
        ];
    }
}
?>