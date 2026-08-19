<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaiePeriode.php';

class PaieClotureController {

    public function process() {
        Auth::requirePermission('paie', 'close');
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $userId = Auth::getUserId();

        try {
            PaiePeriode::updateStatus($periodeId, 'cloture', $userId);
            $_SESSION['success_message'] = _("Clôture définitive de la période de paie enregistrée.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/periodes/show?id=' . $periodeId);
        exit();
    }
}
