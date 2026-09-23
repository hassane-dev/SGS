<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../services/EleveDashboardService.php';

class EleveDashboardController {

    private function checkAccess() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $canView = Auth::can('view_all', 'eleve')
            || Auth::can('view_stats', 'eleve')
            || Auth::can('inscrire', 'eleve')
            || Auth::can('reinscrire', 'eleve')
            || Auth::can('edit', 'eleve');

        if (!$canView) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    /**
     * Renders the main Student Effectifs Dashboard HTML View
     */
    public function index() {
        $this->checkAccess();

        $userId = Auth::getUserId();
        $lyceeId = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();
        $anneeId = $activeYear ? (int)$activeYear['id'] : null;

        $canViewAll = Auth::can('view_all', 'eleve') || Auth::can('view_stats', 'eleve');

        // Available Lycées
        $lycees = $canViewAll && Auth::can('view_all_lycees', 'reporting') ? Lycee::findAll() : [];

        // Permitted Cycles
        $cycles = AuthorizationScopeService::getPermittedCycles($lyceeId);

        // Active Academic Years
        $annees = AnneeAcademique::findAll();

        // Available Classes
        if ($canViewAll) {
            $classes = Classe::findAll($lyceeId);
        } else {
            $teacherAssignments = User::getTeacherAssignments($userId, $anneeId, $lyceeId);
            $classes = [];
            $cSeen = [];
            foreach ($teacherAssignments as $a) {
                $cId = (int)($a['id_classe'] ?? $a['classe_id']);
                if (empty($cSeen[$cId])) {
                    $cSeen[$cId] = true;
                    $classes[] = [
                        'id' => $cId,
                        'id_classe' => $cId,
                        'niveau' => $a['niveau'] ?? '',
                        'serie' => $a['serie'] ?? '',
                        'numero' => $a['numero'] ?? ''
                    ];
                }
            }
        }

        // Filter parameters from GET
        $filters = [
            'lycee_id' => $_GET['lycee_id'] ?? $lyceeId,
            'annee_academique_id' => $_GET['annee_academique_id'] ?? $anneeId,
            'cycle_id' => $_GET['cycle_id'] ?? null,
            'niveau' => $_GET['niveau'] ?? null,
            'classe_id' => $_GET['classe_id'] ?? null
        ];

        // Retrieve initial dashboard payload
        $dashboardData = [];
        try {
            $dashboardData = EleveDashboardService::getDashboardData($filters, $userId);
        } catch (Exception $e) {
            $dashboardData = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        View::render('eleves/dashboard', [
            'title' => _("Dashboard Élèves & Effectifs"),
            'filters' => $filters,
            'lycees' => $lycees,
            'cycles' => $cycles,
            'classes' => $classes,
            'annees' => $annees,
            'dashboardData' => $dashboardData,
            'canViewAll' => $canViewAll
        ]);
    }

    /**
     * AJAX Endpoint returning JSON dashboard data payload
     */
    public function data() {
        if (!Auth::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
            exit();
        }

        $canView = Auth::can('view_all', 'eleve')
            || Auth::can('view_stats', 'eleve')
            || Auth::can('inscrire', 'eleve')
            || Auth::can('reinscrire', 'eleve')
            || Auth::can('edit', 'eleve');

        if (!$canView) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit();
        }

        $userId = Auth::getUserId();
        $filters = [
            'lycee_id' => $_GET['lycee_id'] ?? $_POST['lycee_id'] ?? Auth::getLyceeId(),
            'annee_academique_id' => $_GET['annee_academique_id'] ?? $_POST['annee_academique_id'] ?? null,
            'cycle_id' => $_GET['cycle_id'] ?? $_POST['cycle_id'] ?? null,
            'niveau' => $_GET['niveau'] ?? $_POST['niveau'] ?? null,
            'classe_id' => $_GET['classe_id'] ?? $_POST['classe_id'] ?? null
        ];

        header('Content-Type: application/json');
        try {
            $data = EleveDashboardService::getDashboardData($filters, $userId);
            echo json_encode($data);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }
}
?>
