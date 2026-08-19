<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../services/PaieWorkflowService.php';

class PaiePeriodesController {

    public function index() {
        Auth::requirePermission('paie', 'view');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $periodes = PaiePeriode::findAllForLycee($lyceeId);

        include __DIR__ . '/../views/paie/periodes/index.php';
    }

    public function show() {
        Auth::requirePermission('paie', 'view');
        $id = (int)($_GET['id'] ?? 0);
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
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, date_debut, date_fin FROM comptabilite_periodes WHERE lycee_id = :lycee_id AND (est_cloturee = 0 OR est_cloturee IS NULL) ORDER BY date_debut DESC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $comptaPeriodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../views/paie/periodes/create.php';
    }

    public function store() {
        Auth::requirePermission('paie', 'create');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $userId = Auth::getUserId();

        $mois = (int)($_POST['mois'] ?? date('n'));
        $annee = (int)($_POST['annee'] ?? date('Y'));
        $codePeriode = "PAIE-" . sprintf('%04d-%02d', $annee, $mois);
        $dateDebut = $_POST['date_debut'] ?? sprintf('%04d-%02d-01', $annee, $mois);
        $dateFin = $_POST['date_fin'] ?? date('Y-m-t', strtotime($dateDebut));
        $periodeComptableId = (int)($_POST['periode_comptable_id'] ?? 1);

        try {
            $id = PaieWorkflowService::createPeriod($lyceeId, $periodeComptableId, $codePeriode, $mois, $annee, $dateDebut, $dateFin, $userId);
            $_SESSION['success_message'] = _("Période de paie créée avec succès.");
            header('Location: /paie/periodes/show?id=' . $id);
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /paie/periodes/create');
            exit();
        }
    }

    public function calculate() {
        Auth::requirePermission('paie', 'calculate');
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $userId = Auth::getUserId();
        $idempotencyKey = $_POST['idempotency_key'] ?? null;

        try {
            PaieWorkflowService::generateBulletinsForPeriod($periodeId, $userId, $idempotencyKey);
            $_SESSION['success_message'] = _("Calcul des bulletins effectué avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/periodes/show?id=' . $periodeId);
        exit();
    }

    public function close() {
        Auth::requirePermission('paie', 'close');
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $userId = Auth::getUserId();

        try {
            PaiePeriode::updateStatus($periodeId, 'cloture', $userId);
            $_SESSION['success_message'] = _("Période de paie clôturée avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/periodes/show?id=' . $periodeId);
        exit();
    }
}
