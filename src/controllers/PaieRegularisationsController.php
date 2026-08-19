<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaieRegularisation.php';
require_once __DIR__ . '/../services/PaieWorkflowService.php';

class PaieRegularisationsController {

    public function index() {
        Auth::requirePermission('paie', 'view');
        $periodeId = (int)($_GET['periode_id'] ?? 0);
        $regularisations = $periodeId ? PaieRegularisation::findByDestinationPeriod($periodeId) : [];

        include __DIR__ . '/../views/paie/regularisations/index.php';
    }

    public function store() {
        Auth::requirePermission('paie', 'regularize');
        $sourceBulletinId = (int)($_POST['bulletin_source_id'] ?? 0);
        $destinationPeriodId = (int)($_POST['periode_destination_id'] ?? 0);
        $typeRegu = $_POST['type_regularisation'] ?? 'rappel_salaire';
        $motif = $_POST['motif'] ?? '';
        $brutDelta = (float)($_POST['montant_brut_delta'] ?? 0.00);
        $netDelta = (float)($_POST['montant_net_delta'] ?? 0.00);
        $userId = Auth::getUserId();

        try {
            PaieWorkflowService::createRegularizationInN1($sourceBulletinId, $destinationPeriodId, $typeRegu, $motif, $brutDelta, $netDelta, $userId);
            $_SESSION['success_message'] = _("Régularisation créée avec succès pour la période N+1.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/regularisations?periode_id=' . $destinationPeriodId);
        exit();
    }
}
