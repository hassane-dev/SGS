<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../services/GlobalDashboardService.php';

class GlobalDashboardController {

    private function checkAccess(): array {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $permissions = [
            'canSeeAcademic' => Auth::can('view_all', 'note')
                || Auth::can('create_own', 'note')
                || Auth::can('generate', 'bulletin')
                || Auth::can('validate', 'bulletin')
                || Auth::can('print', 'bulletin')
                || Auth::can('edit_appreciation_conseil', 'bulletin'),

            'canSeePresence' => Auth::can('view_all', 'presence')
                || Auth::can('manage', 'presence')
                || Auth::can('view', 'presence'),

            'canSeeDiscipline' => Auth::can('view_incidents', 'discipline')
                || Auth::can('view_sanctions', 'discipline')
                || Auth::can('manage_incident', 'discipline')
                || Auth::can('manage_sanctions', 'discipline')
                || Auth::can('view_all', 'eleve'),

            'canSeeFinance' => Auth::can('view', 'paiement')
                || Auth::can('manage', 'paiement')
                || Auth::can('view', 'sessions_caisse')
                || Auth::can('view', 'comptes_financiers')
                || Auth::can('view_reports', 'finance'),

            'canSeeRh' => Auth::can('view_all', 'drh')
                || Auth::can('view_one', 'drh')
                || Auth::can('view', 'paie'),

            'canSeeEffectifs' => Auth::can('view_all', 'eleve')
                || Auth::can('view_stats', 'eleve')
                || Auth::can('inscrire', 'eleve')
                || Auth::can('reinscrire', 'eleve')
        ];

        // If no pillar permission is held, throw 403 Forbidden
        $hasAnyAccess = array_reduce($permissions, function($carry, $item) {
            return $carry || $item;
        }, false);

        if (!$hasAnyAccess) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        return $permissions;
    }

    /**
     * Renders the canonical 360° Executive Global Dashboard (/dashboard)
     */
    public function index() {
        $permissions = $this->checkAccess();

        // Always resolve lycee_id from session context
        $lyceeId = Auth::getLyceeId();
        if (!$lyceeId) {
            $lyceeId = 1;
        }

        $activeYear = AnneeAcademique::findActive();
        $anneeId = $activeYear ? (int)$activeYear['id'] : null;

        $dashboardData = GlobalDashboardService::getDashboardData($lyceeId, $permissions, $anneeId);

        View::render('dashboard/index', [
            'title' => _("Dashboard Global"),
            'dashboardData' => $dashboardData,
            'activeYear' => $activeYear,
            'permissions' => $permissions
        ]);
    }
}
?>
