<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class DisciplineDashboardController {

    public function index() {
        $canViewIncidents = Auth::can('view_incidents', 'discipline') || Auth::can('manage_incident', 'discipline') || Auth::can('view_all', 'eleve');
        $canViewSanctions = Auth::can('view_sanctions', 'discipline') || Auth::can('manage_sanctions', 'discipline') || Auth::can('view_all', 'eleve');

        if (!$canViewIncidents && !$canViewSanctions) {
            $this->forbidden();
        }

        $lycee_id = Auth::getLyceeId();
        $userId = Auth::get('id');
        $userRole = Auth::get('role_name');
        $isTeacher = ($userRole === 'enseignant') || !empty(User::getTeacherAssignments($userId));
        $hasGlobalView = Auth::can('view_all', 'eleve') || Auth::can('manage_incident', 'discipline') || Auth::can('manage_sanctions', 'discipline');

        // SSoT Active Academic Year
        $activeYear = AnneeAcademique::findActive();

        if ($isTeacher && !$hasGlobalView) {
            // Strict Teacher Scope Rule
            if (!$activeYear || empty($activeYear['id'])) {
                $this->forbidden();
            }

            // Reject historical year override in GET for teachers
            if (isset($_GET['annee_academique_id']) && (int)$_GET['annee_academique_id'] !== (int)$activeYear['id']) {
                $this->forbidden();
            }

            $anneeId = (int)$activeYear['id'];
            $assignments = User::getTeacherAssignments($userId);
            $assignedClassIds = array_keys($assignments);

            if (empty($assignedClassIds)) {
                $this->forbidden();
            }

            // If a class filter is passed, it MUST belong to assigned classes
            if (!empty($_GET['classe_id'])) {
                $requestedClassId = (int)$_GET['classe_id'];
                if (!in_array($requestedClassId, $assignedClassIds, true)) {
                    $this->forbidden();
                }
                $scopedClassIds = [$requestedClassId];
            } else {
                $scopedClassIds = $assignedClassIds;
            }
        } else {
            // Global User Scope
            if (!empty($_GET['annee_academique_id'])) {
                $anneeId = (int)$_GET['annee_academique_id'];
            } else {
                $anneeId = $activeYear ? (int)$activeYear['id'] : null;
            }

            if (!empty($_GET['classe_id'])) {
                $requestedClassId = (int)$_GET['classe_id'];
                // Verify class belongs to lycee_id
                $targetClass = Classe::findById($requestedClassId);
                if (!$targetClass || (int)$targetClass['lycee_id'] !== (int)$lycee_id) {
                    $this->forbidden();
                }
                $scopedClassIds = [$requestedClassId];
            } else {
                $scopedClassIds = null; // All classes in tenant
            }
        }

        $dateDebut = !empty($_GET['date_debut']) ? $_GET['date_debut'] : null;
        $dateFin = !empty($_GET['date_fin']) ? $_GET['date_fin'] : null;

        $db = Database::getInstance();

        // Prepare SQL conditions for scope
        $classFilterInc = "";
        $classFilterSanc = "";
        $paramsInc = [':lycee_id' => $lycee_id];
        $paramsSanc = [':lycee_id' => $lycee_id];

        if ($anneeId !== null) {
            $classFilterInc .= " AND i.annee_academique_id = :annee_id ";
            $classFilterSanc .= " AND s.annee_academique_id = :annee_id ";
            $paramsInc[':annee_id'] = $anneeId;
            $paramsSanc[':annee_id'] = $anneeId;
        }

        if (!empty($dateDebut)) {
            $classFilterInc .= " AND i.date_incident >= :date_debut ";
            $classFilterSanc .= " AND s.date_decision >= :date_debut ";
            $paramsInc[':date_debut'] = $dateDebut;
            $paramsSanc[':date_debut'] = $dateDebut;
        }

        if (!empty($dateFin)) {
            $classFilterInc .= " AND i.date_incident <= :date_fin ";
            $classFilterSanc .= " AND s.date_decision <= :date_fin ";
            $paramsInc[':date_fin'] = $dateFin;
            $paramsSanc[':date_fin'] = $dateFin;
        }

        if ($scopedClassIds !== null) {
            $inClause = implode(',', array_map('intval', $scopedClassIds));
            $classFilterInc .= " AND ie.classe_id IN ({$inClause}) ";
            $classFilterSanc .= " AND s.classe_id IN ({$inClause}) ";
        }

        // --- KPI 1: Incidents Totaux (Excluding 'classe_sans_suite') ---
        $stmtKpiInc = $db->prepare("
            SELECT COUNT(DISTINCT i.id)
            FROM discipline_incidents i
            JOIN discipline_incident_eleves ie ON ie.incident_id = i.id
            WHERE i.lycee_id = :lycee_id
              AND i.statut != 'classe_sans_suite'
              {$classFilterInc}
        ");
        $stmtKpiInc->execute($paramsInc);
        $totalIncidents = (int)$stmtKpiInc->fetchColumn();

        // --- KPI 2: Élèves Impliqués (All roles) ---
        $stmtKpiElevesImp = $db->prepare("
            SELECT COUNT(DISTINCT ie.eleve_id)
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            WHERE i.lycee_id = :lycee_id
              AND i.statut != 'classe_sans_suite'
              {$classFilterInc}
        ");
        $stmtKpiElevesImp->execute($paramsInc);
        $elevesImpliques = (int)$stmtKpiElevesImp->fetchColumn();

        // --- KPI 3: Élèves Responsables ('auteur_principal', 'co_auteur', 'complice') ---
        $stmtKpiElevesResp = $db->prepare("
            SELECT COUNT(DISTINCT ie.eleve_id)
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            WHERE i.lycee_id = :lycee_id
              AND i.statut != 'classe_sans_suite'
              AND ie.role_implication IN ('auteur_principal', 'co_auteur', 'complice')
              {$classFilterInc}
        ");
        $stmtKpiElevesResp->execute($paramsInc);
        $elevesResponsables = (int)$stmtKpiElevesResp->fetchColumn();

        // --- KPI 4: Élèves Récidivistes (>= 2 distinct incidents, 'auteur_principal' or 'co_auteur', excluding 'classe_sans_suite') ---
        $stmtKpiRecidive = $db->prepare("
            SELECT COUNT(*) FROM (
                SELECT ie.eleve_id
                FROM discipline_incident_eleves ie
                JOIN discipline_incidents i ON i.id = ie.incident_id
                WHERE i.lycee_id = :lycee_id
                  AND i.statut != 'classe_sans_suite'
                  AND ie.role_implication IN ('auteur_principal', 'co_auteur')
                  {$classFilterInc}
                GROUP BY ie.eleve_id
                HAVING COUNT(DISTINCT ie.incident_id) >= 2
            ) AS recidivistes
        ");
        $stmtKpiRecidive->execute($paramsInc);
        $elevesRecidivistes = (int)$stmtKpiRecidive->fetchColumn();

        // --- KPI 5: Total Sanctions & Breakdown by Status ---
        $stmtKpiSanctions = $db->prepare("
            SELECT
                COUNT(DISTINCT s.id) AS total,
                SUM(CASE WHEN s.statut = 'prononcee' THEN 1 ELSE 0 END) AS prononcee,
                SUM(CASE WHEN s.statut = 'en_cours' THEN 1 ELSE 0 END) AS en_cours,
                SUM(CASE WHEN s.statut = 'executee' THEN 1 ELSE 0 END) AS executee,
                SUM(CASE WHEN s.statut = 'levee' THEN 1 ELSE 0 END) AS levee,
                SUM(CASE WHEN s.statut = 'annulee' THEN 1 ELSE 0 END) AS annulee
            FROM discipline_sanctions s
            WHERE s.lycee_id = :lycee_id
              {$classFilterSanc}
        ");
        $stmtKpiSanctions->execute($paramsSanc);
        $sanctionsStats = $stmtKpiSanctions->fetch(PDO::FETCH_ASSOC);

        $totalSanctions = (int)($sanctionsStats['total'] ?? 0);
        $sanctionsBreakdown = [
            'prononcee' => (int)($sanctionsStats['prononcee'] ?? 0),
            'en_cours' => (int)($sanctionsStats['en_cours'] ?? 0),
            'executee' => (int)($sanctionsStats['executee'] ?? 0),
            'levee' => (int)($sanctionsStats['levee'] ?? 0),
            'annulee' => (int)($sanctionsStats['annulee'] ?? 0),
        ];

        // --- KPI 6: Delay Incident -> Decision de Sanction ---
        $stmtKpiDelay = $db->prepare("
            SELECT AVG(DATEDIFF(s.date_decision, i.date_incident)) AS avg_delay_days
            FROM discipline_sanctions s
            JOIN discipline_incidents i ON i.id = s.incident_id
            WHERE s.lycee_id = :lycee_id
              AND s.incident_id IS NOT NULL
              {$classFilterSanc}
        ");
        $stmtKpiDelay->execute($paramsSanc);
        $avgDelayRes = $stmtKpiDelay->fetchColumn();
        $avgDelayDays = ($avgDelayRes !== null && $avgDelayRes !== false) ? round((float)$avgDelayRes, 1) : null;

        // --- CHART 1: Monthly Evolution of Incidents and Sanctions ---
        $stmtIncMonthly = $db->prepare("
            SELECT DATE_FORMAT(i.date_incident, '%Y-%m') AS mois, COUNT(DISTINCT i.id) AS total
            FROM discipline_incidents i
            JOIN discipline_incident_eleves ie ON ie.incident_id = i.id
            WHERE i.lycee_id = :lycee_id
              AND i.statut != 'classe_sans_suite'
              {$classFilterInc}
            GROUP BY DATE_FORMAT(i.date_incident, '%Y-%m')
            ORDER BY mois ASC
        ");
        $stmtIncMonthly->execute($paramsInc);
        $incidentsMonthly = $stmtIncMonthly->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmtSancMonthly = $db->prepare("
            SELECT DATE_FORMAT(s.date_decision, '%Y-%m') AS mois, COUNT(DISTINCT s.id) AS total
            FROM discipline_sanctions s
            WHERE s.lycee_id = :lycee_id
              {$classFilterSanc}
            GROUP BY DATE_FORMAT(s.date_decision, '%Y-%m')
            ORDER BY mois ASC
        ");
        $stmtSancMonthly->execute($paramsSanc);
        $sanctionsMonthly = $stmtSancMonthly->fetchAll(PDO::FETCH_KEY_PAIR);

        $allMonths = array_unique(array_merge(array_keys($incidentsMonthly), array_keys($sanctionsMonthly)));
        sort($allMonths);

        $monthlySeries = [
            'months' => $allMonths,
            'incidents' => array_map(fn($m) => (int)($incidentsMonthly[$m] ?? 0), $allMonths),
            'sanctions' => array_map(fn($m) => (int)($sanctionsMonthly[$m] ?? 0), $allMonths),
        ];

        // --- CHART 2: Incidents by Severity Level ---
        $stmtSeverity = $db->prepare("
            SELECT COALESCE(ti.niveau_gravite, 'Non spécifié') AS gravite, COUNT(DISTINCT i.id) AS total
            FROM discipline_incidents i
            JOIN discipline_incident_eleves ie ON ie.incident_id = i.id
            LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            WHERE i.lycee_id = :lycee_id
              AND i.statut != 'classe_sans_suite'
              {$classFilterInc}
            GROUP BY ti.niveau_gravite
        ");
        $stmtSeverity->execute($paramsInc);
        $severityStats = $stmtSeverity->fetchAll(PDO::FETCH_KEY_PAIR);

        // --- CHART 4: Roles Distribution ---
        $stmtRoles = $db->prepare("
            SELECT ie.role_implication, COUNT(DISTINCT ie.id) AS total
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            WHERE i.lycee_id = :lycee_id
              AND i.statut != 'classe_sans_suite'
              {$classFilterInc}
            GROUP BY ie.role_implication
        ");
        $stmtRoles->execute($paramsInc);
        $roleStats = $stmtRoles->fetchAll(PDO::FETCH_KEY_PAIR);

        // --- TOP 5 Classes Most Concerned ---
        $stmtTopClasses = $db->prepare("
            SELECT
                c.id_classe,
                TRIM(CONCAT(COALESCE(c.niveau, ''), ' ', COALESCE(c.serie, ''), ' ', COALESCE(c.numero, ''))) AS nom_classe,
                COUNT(DISTINCT i.id) AS total_incidents
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            JOIN classes c ON c.id_classe = ie.classe_id
            WHERE i.lycee_id = :lycee_id
              AND i.statut != 'classe_sans_suite'
              {$classFilterInc}
            GROUP BY c.id_classe, c.niveau, c.serie, c.numero
            ORDER BY total_incidents DESC
            LIMIT 5
        ");
        $stmtTopClasses->execute($paramsInc);
        $topClasses = $stmtTopClasses->fetchAll(PDO::FETCH_ASSOC);

        // --- TOP 5 Incident Types ---
        $stmtTopTypes = $db->prepare("
            SELECT
                ti.id,
                ti.code,
                ti.libelle,
                ti.niveau_gravite,
                COUNT(DISTINCT i.id) AS total_incidents
            FROM discipline_incidents i
            JOIN discipline_incident_eleves ie ON ie.incident_id = i.id
            JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            WHERE i.lycee_id = :lycee_id
              AND i.statut != 'classe_sans_suite'
              {$classFilterInc}
            GROUP BY ti.id, ti.code, ti.libelle, ti.niveau_gravite
            ORDER BY total_incidents DESC
            LIMIT 5
        ");
        $stmtTopTypes->execute($paramsInc);
        $topTypes = $stmtTopTypes->fetchAll(PDO::FETCH_ASSOC);

        // Available Academic Years for filter
        $academicYears = [];
        if ($hasGlobalView) {
            $stmtYears = $db->query("SELECT id, libelle, est_active FROM annees_academiques ORDER BY date_debut DESC");
            $academicYears = $stmtYears->fetchAll(PDO::FETCH_ASSOC);
        }

        // Available Classes for filter
        $availableClasses = [];
        if ($isTeacher && !$hasGlobalView) {
            if (!empty($assignedClassIds)) {
                $inClauseAssigned = implode(',', array_map('intval', $assignedClassIds));
                $stmtClasses = $db->query("
                    SELECT id_classe, TRIM(CONCAT(COALESCE(niveau, ''), ' ', COALESCE(serie, ''), ' ', COALESCE(numero, ''))) AS nom_classe
                    FROM classes
                    WHERE id_classe IN ({$inClauseAssigned}) AND lycee_id = {$lycee_id}
                    ORDER BY niveau, serie, numero
                ");
                $availableClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $stmtClasses = $db->prepare("
                SELECT id_classe, TRIM(CONCAT(COALESCE(niveau, ''), ' ', COALESCE(serie, ''), ' ', COALESCE(numero, ''))) AS nom_classe
                FROM classes
                WHERE lycee_id = :lycee_id
                ORDER BY niveau, serie, numero
            ");
            $stmtClasses->execute([':lycee_id' => $lycee_id]);
            $availableClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);
        }

        View::render('discipline/dashboard/index', [
            'totalIncidents' => $totalIncidents,
            'elevesImpliques' => $elevesImpliques,
            'elevesResponsables' => $elevesResponsables,
            'elevesRecidivistes' => $elevesRecidivistes,
            'totalSanctions' => $totalSanctions,
            'sanctionsBreakdown' => $sanctionsBreakdown,
            'avgDelayDays' => $avgDelayDays,
            'monthlySeries' => $monthlySeries,
            'severityStats' => $severityStats,
            'roleStats' => $roleStats,
            'topClasses' => $topClasses,
            'topTypes' => $topTypes,
            'academicYears' => $academicYears,
            'availableClasses' => $availableClasses,
            'selectedAnneeId' => $anneeId,
            'selectedClasseId' => $_GET['classe_id'] ?? null,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'isTeacher' => $isTeacher && !$hasGlobalView,
            'activeYear' => $activeYear,
            'title' => 'Tableau de Bord Disciplinaire & Statistiques'
        ]);
    }

    private function forbidden() {
        http_response_code(403);
        View::render('errors/403');
        exit();
    }
}
