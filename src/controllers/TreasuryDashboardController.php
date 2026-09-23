<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../services/TreasuryDashboardService.php';

class TreasuryDashboardController {

    private function checkAccess() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $canView = Auth::can('view', 'sessions_caisse')
            || Auth::can('create', 'sessions_caisse')
            || Auth::can('edit', 'sessions_caisse')
            || Auth::can('validate', 'sessions_caisse')
            || Auth::can('view', 'comptes_financiers')
            || Auth::can('view', 'mouvements_tresorerie')
            || Auth::can('view', 'paiement')
            || Auth::can('manage', 'paiement')
            || Auth::can('view', 'depense')
            || Auth::can('pay', 'depense')
            || Auth::can('view_reports', 'finance')
            || Auth::can('view_control', 'finance');

        if (!$canView) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    /**
     * Renders the Caisse / Trésorerie Dashboard HTML View (/treasury/dashboard)
     */
    public function index() {
        $this->checkAccess();

        $userId = Auth::getUserId();
        $lyceeId = Auth::getLyceeId();

        // Extract GET filters
        $period = $_GET['period'] ?? 'today';
        $dateDebut = $_GET['date_debut'] ?? null;
        $dateFin = $_GET['date_fin'] ?? null;
        $compteId = !empty($_GET['compte_id']) ? (int)$_GET['compte_id'] : null;

        $filters = [
            'period' => $period,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'compte_id' => $compteId
        ];

        // Retrieve active cash accounts for the filter dropdown
        $allComptes = CompteFinancier::findByLycee($lyceeId);
        $caissesList = array_filter($allComptes, function($c) {
            return $c['type_compte'] === 'caisse' && empty($c['est_coffre']) && $c['statut'] === 'actif';
        });

        // Retrieve Dashboard Data Payload
        $dashboardData = TreasuryDashboardService::getDashboardData($lyceeId, $userId, $filters);

        View::render('treasury/dashboard', [
            'title' => _("Hub Caisse & Trésorerie"),
            'filters' => $filters,
            'caissesList' => $caissesList,
            'dashboardData' => $dashboardData
        ]);
    }

    /**
     * AJAX Endpoint returning JSON payload for dynamic updates
     */
    public function data() {
        if (!Auth::check()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non authentifié.']);
            exit();
        }

        $userId = Auth::getUserId();
        $lyceeId = Auth::getLyceeId();

        $filters = [
            'period' => $_GET['period'] ?? $_POST['period'] ?? 'today',
            'date_debut' => $_GET['date_debut'] ?? $_POST['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? $_POST['date_fin'] ?? null,
            'compte_id' => !empty($_GET['compte_id']) ? (int)$_GET['compte_id'] : (!empty($_POST['compte_id']) ? (int)$_POST['compte_id'] : null)
        ];

        header('Content-Type: application/json');
        try {
            $data = TreasuryDashboardService::getDashboardData($lyceeId, $userId, $filters);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}
?>