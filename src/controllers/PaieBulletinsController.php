<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaieBulletin.php';
require_once __DIR__ . '/../models/PaieBulletinLigne.php';
require_once __DIR__ . '/../models/PaieBulletinHeure.php';
require_once __DIR__ . '/../models/PaieBulletinContratSnapshot.php';
require_once __DIR__ . '/../models/PaieBulletinRegleSnapshot.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../services/PaieWorkflowService.php';

class PaieBulletinsController {

    public function index() {
        Auth::requirePermission('paie', 'view');
        $periodeId = (int)($_GET['periode_id'] ?? 0);
        $bulletins = $periodeId ? PaieBulletin::findByPeriod($periodeId) : [];

        include __DIR__ . '/../views/paie/bulletins/index.php';
    }

    public function show() {
        Auth::requirePermission('paie', 'view');
        $id = (int)($_GET['id'] ?? 0);
        $bulletin = PaieBulletin::findById($id);

        if (!$bulletin) {
            $_SESSION['error_message'] = _("Bulletin de paie introuvable.");
            header('Location: /paie/periodes');
            exit();
        }

        $lignes = PaieBulletinLigne::findByBulletinId($id);
        $heures = PaieBulletinHeure::findByBulletinId($id);
        $contratSnapshot = PaieBulletinContratSnapshot::findByBulletinId($id);
        $reglesSnapshot = PaieBulletinRegleSnapshot::findByBulletinId($id);

        $lyceeId = Auth::getLyceeId() ?: 1;
        $comptesFinanciers = CompteFinancier::findAllForLycee($lyceeId);

        include __DIR__ . '/../views/paie/bulletins/show.php';
    }

    public function redraw() {
        Auth::requirePermission('paie', 'redraw');
        $id = (int)($_POST['bulletin_id'] ?? 0);
        $userId = Auth::getUserId();

        $manualAdjustments = [];
        if (isset($_POST['salaire_base'])) {
            $manualAdjustments['salaire_base'] = (float)$_POST['salaire_base'];
        }

        try {
            $newBulletinId = PaieWorkflowService::redrawBulletin($id, $userId, $manualAdjustments);
            $_SESSION['success_message'] = _("Re-tirage du bulletin (V2) exécuté avec succès.");
            header('Location: /paie/bulletins/show?id=' . $newBulletinId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /paie/bulletins/show?id=' . $id);
            exit();
        }
    }

    public function postAccounting() {
        Auth::requirePermission('paie', 'accounting');
        $id = (int)($_POST['bulletin_id'] ?? 0);
        $userId = Auth::getUserId();

        try {
            PaieWorkflowService::postAccounting($id, $userId);
            $_SESSION['success_message'] = _("Bulletin comptabilisé avec succès au Grand Livre.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/bulletins/show?id=' . $id);
        exit();
    }

    public function settle() {
        Auth::requirePermission('paie', 'settle');
        $id = (int)($_POST['bulletin_id'] ?? 0);
        $compteFinancierId = (int)($_POST['compte_financier_id'] ?? 0);
        $modeReglement = $_POST['mode_reglement'] ?? 'virement';
        $userId = Auth::getUserId();

        try {
            PaieWorkflowService::settlePayout($id, $compteFinancierId, $modeReglement, $userId);
            $_SESSION['success_message'] = _("Règlement du salaire enregistré avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/bulletins/show?id=' . $id);
        exit();
    }
}
