<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/ParamTypeEvaluation.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../services/GradeDashboardService.php';

class GradeDashboardController {

    private function checkAccess() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $canView = Auth::can('view_all', 'note')
            || Auth::can('create_own', 'note')
            || Auth::can('manage_settings', 'evaluation')
            || Auth::can('generate', 'bulletin')
            || Auth::can('validate', 'bulletin')
            || Auth::can('print', 'bulletin')
            || Auth::can('edit_appreciation_conseil', 'bulletin');

        if (!$canView) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    /**
     * Renders the main Dashboard HTML View
     */
    public function index() {
        $this->checkAccess();

        $userId = Auth::getUserId();
        $lyceeId = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();
        $anneeId = $activeYear ? (int)$activeYear['id'] : null;

        $canViewAll = Auth::can('view_all', 'note') || Auth::can('generate', 'bulletin');

        // Available Lycées
        $lycees = $canViewAll && Auth::can('view_all_lycees', 'reporting') ? Lycee::findAll() : [];

        // Permitted Cycles
        $cycles = AuthorizationScopeService::getPermittedCycles($lyceeId);

        // Active Academic Years
        $annees = AnneeAcademique::findAll();

        // Sequences for active year & lycee
        $sequences = [];
        if ($anneeId) {
            $db = Database::getInstance();
            $stmtSeq = $db->prepare("
                SELECT * FROM sequences
                WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id
                ORDER BY date_debut ASC
            ");
            $stmtSeq->execute(['lycee_id' => $lyceeId, 'annee_id' => $anneeId]);
            $sequences = $stmtSeq->fetchAll(PDO::FETCH_ASSOC);
        }

        // Active Evaluation Types
        $typesEvaluation = ParamTypeEvaluation::findActive($lyceeId);

        // Available Classes and Matieres for Filter Options
        if ($canViewAll) {
            $classes = Classe::findAllByLycee($lyceeId);
            $matieres = Matiere::findAllByLycee($lyceeId);
        } else {
            $teacherAssignments = User::getTeacherAssignments($userId, $anneeId, $lyceeId);
            $classes = [];
            $matieres = [];
            $cSeen = [];
            $mSeen = [];
            foreach ($teacherAssignments as $a) {
                $cId = (int)($a['id_classe'] ?? $a['classe_id']);
                $mId = (int)($a['id_matiere'] ?? $a['matiere_id']);
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
                if (empty($mSeen[$mId])) {
                    $mSeen[$mId] = true;
                    $matieres[] = [
                        'id' => $mId,
                        'id_matiere' => $mId,
                        'nom' => $a['nom_matiere'] ?? '',
                        'nom_matiere' => $a['nom_matiere'] ?? ''
                    ];
                }
            }
        }

        // Filter parameters from GET
        $filters = [
            'lycee_id' => $_GET['lycee_id'] ?? $lyceeId,
            'annee_academique_id' => $_GET['annee_academique_id'] ?? $anneeId,
            'sequence_id' => $_GET['sequence_id'] ?? null,
            'cycle_id' => $_GET['cycle_id'] ?? null,
            'niveau' => $_GET['niveau'] ?? null,
            'classe_id' => $_GET['classe_id'] ?? null,
            'matiere_id' => $_GET['matiere_id'] ?? null,
            'type_evaluation_id' => $_GET['type_evaluation_id'] ?? null
        ];

        // Retrieve initial dashboard payload
        $dashboardData = [];
        try {
            $dashboardData = GradeDashboardService::getDashboardData($filters, $userId);
        } catch (Exception $e) {
            $dashboardData = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        View::render('evaluations/dashboard', [
            'title' => _("Notes & Évaluations"),
            'filters' => $filters,
            'lycees' => $lycees,
            'cycles' => $cycles,
            'classes' => $classes,
            'matieres' => $matieres,
            'annees' => $annees,
            'sequences' => $sequences,
            'typesEvaluation' => $typesEvaluation,
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

        $canView = Auth::can('view_all', 'note')
            || Auth::can('create_own', 'note')
            || Auth::can('manage_settings', 'evaluation')
            || Auth::can('generate', 'bulletin')
            || Auth::can('validate', 'bulletin')
            || Auth::can('print', 'bulletin')
            || Auth::can('edit_appreciation_conseil', 'bulletin');

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
            'sequence_id' => $_GET['sequence_id'] ?? $_POST['sequence_id'] ?? null,
            'cycle_id' => $_GET['cycle_id'] ?? $_POST['cycle_id'] ?? null,
            'niveau' => $_GET['niveau'] ?? $_POST['niveau'] ?? null,
            'classe_id' => $_GET['classe_id'] ?? $_POST['classe_id'] ?? null,
            'matiere_id' => $_GET['matiere_id'] ?? $_POST['matiere_id'] ?? null,
            'type_evaluation_id' => $_GET['type_evaluation_id'] ?? $_POST['type_evaluation_id'] ?? null
        ];

        header('Content-Type: application/json');
        try {
            $data = GradeDashboardService::getDashboardData($filters, $userId);
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