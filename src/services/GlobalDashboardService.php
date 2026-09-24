<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/GradeDashboardService.php';
require_once __DIR__ . '/PresenceDashboardService.php';
require_once __DIR__ . '/DisciplineSearchService.php';
require_once __DIR__ . '/TreasuryDashboardService.php';
require_once __DIR__ . '/PaieDashboardService.php';
require_once __DIR__ . '/EleveDashboardService.php';

class GlobalDashboardService {

    /**
     * Centralized aggregator for the Executive 360° Global Dashboard.
     * Consumes lightweight domain summary APIs without loading heavy operational dashboard structures.
     * Enforces multi-tenant isolation and granular server-side RBAC.
     */
    public static function getDashboardData(int $lyceeId, array $permissions, ?int $anneeId = null): array {
        if (!$lyceeId) {
            throw new InvalidArgumentException("Établissement non spécifié.");
        }

        // Active Academic Year
        if (!$anneeId) {
            $activeYear = AnneeAcademique::findActive();
            $anneeId = $activeYear ? (int)$activeYear['id'] : null;
        }

        $userId = Auth::getUserId();
        $userRole = Auth::get('role_name');

        $payload = [
            'lycee_id' => $lyceeId,
            'annee_id' => $anneeId,
            'permissions' => $permissions,
            'kpis' => []
        ];

        // 1. Academic Pillar
        if (!empty($permissions['canSeeAcademic'])) {
            $payload['kpis']['academic'] = GradeDashboardService::getSummaryKpis($lyceeId, $anneeId);
        }

        // 2. Presence Pillar
        if (!empty($permissions['canSeePresence'])) {
            $payload['kpis']['presence'] = PresenceDashboardService::getSummaryKpis($lyceeId, $anneeId, $userId);
        }

        // 3. Discipline Pillar
        if (!empty($permissions['canSeeDiscipline'])) {
            $hasGlobalDiscipline = Auth::can('view_all', 'eleve') || Auth::can('manage_incident', 'discipline') || Auth::can('manage_sanctions', 'discipline');
            $payload['kpis']['discipline'] = DisciplineSearchService::getSummaryKpis($lyceeId, $anneeId, $userId, $userRole, $hasGlobalDiscipline);
        }

        // 4. Finance & Treasury Pillar
        if (!empty($permissions['canSeeFinance'])) {
            $payload['kpis']['finance'] = TreasuryDashboardService::getSummaryKpis($lyceeId, $anneeId);
        }

        // 5. HR & Payroll Pillar
        if (!empty($permissions['canSeeRh'])) {
            $payload['kpis']['rh'] = PaieDashboardService::getSummaryKpis($lyceeId);
        }

        // 6. Student Effectifs Pillar
        if (!empty($permissions['canSeeEffectifs'])) {
            $payload['kpis']['effectifs'] = EleveDashboardService::getSummaryKpis($lyceeId, $anneeId);
        }

        return $payload;
    }
}
?>
