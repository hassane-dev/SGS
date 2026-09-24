<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../services/PaieDashboardService.php';

class PaieDashboardController {

    /**
     * Check server-side RBAC permissions for RH / Paie Hub access.
     */
    private function checkAccess(): void {
        if (!Auth::can('view_all', 'drh') && !Auth::can('view_one', 'drh') && !Auth::can('view', 'paie')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    /**
     * Render the main RH & Paie Dashboard Hub view.
     */
    public function index(): void {
        $this->checkAccess();

        $lyceeId = Auth::getLyceeId() ?: 1;
        $periodeId = !empty($_GET['periode_id']) ? (int)$_GET['periode_id'] : null;

        $dashboardData = PaieDashboardService::getDashboardData($lyceeId, $periodeId);

        View::render('drh/dashboard', array_merge($dashboardData, [
            'title' => _("Hub Opérationnel RH & Paie")
        ]));
    }

    /**
     * AJAX endpoint returning JSON payload for dynamic filter updates.
     */
    public function data(): void {
        $this->checkAccess();

        $lyceeId = Auth::getLyceeId() ?: 1;
        $periodeId = !empty($_GET['periode_id']) ? (int)$_GET['periode_id'] : null;

        $dashboardData = PaieDashboardService::getDashboardData($lyceeId, $periodeId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $dashboardData
        ]);
        if (!defined('TEST_MODE')) exit();
    }
}
