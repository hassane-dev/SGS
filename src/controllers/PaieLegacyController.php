<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../services/LegacySalaryFacade.php';

class PaieLegacyController {

    public function import() {
        Auth::requirePermission('paie', 'create');
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $userId = Auth::getUserId();

        try {
            $count = LegacySalaryFacade::importLegacySalairesToPaie($periodeId, $userId);
            $_SESSION['success_message'] = sprintf(_("%d enregistrements de salaires historiques importés dans la paie."), $count);
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/periodes/show?id=' . $periodeId);
        exit();
    }
}
