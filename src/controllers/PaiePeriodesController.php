<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../models/PaieBulletin.php';
require_once __DIR__ . '/../models/ComptabilitePeriode.php';
require_once __DIR__ . '/../services/PaieWorkflowService.php';

class PaiePeriodesController {

    public function index() {
        Auth::requirePermission('paie', 'view');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $periodes = PaiePeriode::findAllForLycee($lyceeId);

        include __DIR__ . '/../views/paie/periodes/index.php';
    }

    public function show($id = null) {
        Auth::requirePermission('paie', 'view');
        $id = (int)($id ?: ($_GET['id'] ?? 0));
        $periode = PaiePeriode::findById($id);

        if (!$periode) {
            $_SESSION['error_message'] = _("Période de paie introuvable.");
            header('Location: /paie/periodes');
            exit();
        }

        $bulletins = PaieBulletin::findByPeriod($id);
        include __DIR__ . '/../views/paie/periodes/show.php';
    }

    public function create() {
        Auth::requirePermission('paie', 'create');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $comptaPeriodes = ComptabilitePeriode::findOpenForLycee($lyceeId);

        include __DIR__ . '/../views/paie/periodes/create.php';
    }

    public function store() {
        Auth::requirePermission('paie', 'create');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $userId = Auth::getUserId();

        $mois = (int)($_POST['mois'] ?? date('n'));
        $annee = (int)($_POST['annee'] ?? date('Y'));
        $codePeriode = "PAIE-" . sprintf('%04d-%02d', $annee, $mois);
        $periodeComptableId = (int)($_POST['periode_comptable_id'] ?? 0);

        if (!$periodeComptableId) {
            $_SESSION['error_message'] = _("Aucune période comptable valide n'a été sélectionnée.");
            header('Location: /paie/periodes/create');
            exit();
        }

        $comptaPeriode = ComptabilitePeriode::findById($periodeComptableId);
        if (!$comptaPeriode || (int)$comptaPeriode['lycee_id'] !== (int)$lyceeId || !empty($comptaPeriode['est_cloturee'])) {
            $_SESSION['error_message'] = _("La période comptable sélectionnée est invalide ou clôturée.");
            header('Location: /paie/periodes/create');
            exit();
        }

        $dateDebut = $comptaPeriode['date_debut'];
        $dateFin = $comptaPeriode['date_fin'];

        try {
            $id = PaieWorkflowService::createPeriod($lyceeId, $periodeComptableId, $codePeriode, $mois, $annee, $dateDebut, $dateFin, $userId);
            $_SESSION['success_message'] = _("Période de paie créée avec succès.");
            header('Location: /paie/periodes/' . $id);
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /paie/periodes/create');
            exit();
        }
    }

    public function calculate() {
        Auth::requirePermission('paie', 'calculate');
        $periodeId = (int)($_POST['periode_id'] ?? ($_GET['id'] ?? 0));
        $userId = Auth::getUserId();
        $idempotencyKey = $_POST['idempotency_key'] ?? null;

        try {
            PaieWorkflowService::generateBulletinsForPeriod($periodeId, $userId, $idempotencyKey);
            $_SESSION['success_message'] = _("Calcul des bulletins effectué avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/periodes/' . $periodeId);
        exit();
    }

    public function close($id = null) {
        Auth::requirePermission('paie', 'close');
        $periodeId = (int)($id ?: ($_POST['periode_id'] ?? ($_GET['id'] ?? 0)));
        $userId = Auth::getUserId();

        try {
            PaiePeriode::updateStatus($periodeId, 'cloture', $userId);
            $_SESSION['success_message'] = _("Période de paie clôturée avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/periodes/' . $periodeId);
        exit();
    }
}
