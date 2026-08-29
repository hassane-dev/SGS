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

        // Uniqueness check for lycee_id + annee + mois
        $existingMoisAnnee = PaiePeriode::findByLyceeAndMoisAnnee($lyceeId, $annee, $mois);
        if ($existingMoisAnnee) {
            $_SESSION['error_message'] = sprintf(_("Une période de paie existe déjà pour le mois %02d/%04d dans cet établissement."), $mois, $annee);
            header('Location: /paie/periodes/create');
            exit();
        }

        // Overlap check
        if (PaiePeriode::hasOverlap($lyceeId, $dateDebut, $dateFin)) {
            $_SESSION['error_message'] = _("Les dates de la période chevauchent une autre période de paie existante.");
            header('Location: /paie/periodes/create');
            exit();
        }

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

    public function edit($id = null) {
        if (!Auth::can('edit', 'paie') && !Auth::can('create', 'paie')) {
            Auth::requirePermission('paie', 'edit');
        }
        $lyceeId = Auth::getLyceeId() ?: 1;
        $id = (int)($id ?: ($_GET['id'] ?? 0));
        $periode = PaiePeriode::findById($id);

        if (!$periode || (int)$periode['lycee_id'] !== (int)$lyceeId) {
            $_SESSION['error_message'] = _("Période de paie introuvable ou accès non autorisé.");
            header('Location: /paie/periodes');
            exit();
        }

        $lockReason = PaiePeriode::getLockReason($periode);
        if ($lockReason !== null) {
            $_SESSION['error_message'] = sprintf(_("Modification impossible : %s"), $lockReason);
            header('Location: /paie/periodes/' . $id);
            exit();
        }

        $comptaPeriodes = ComptabilitePeriode::findOpenForLycee($lyceeId);
        include __DIR__ . '/../views/paie/periodes/edit.php';
    }

    public function update($id = null) {
        if (!Auth::can('edit', 'paie') && !Auth::can('create', 'paie')) {
            Auth::requirePermission('paie', 'edit');
        }
        $lyceeId = Auth::getLyceeId() ?: 1;
        $id = (int)($id ?: ($_POST['id'] ?? ($_GET['id'] ?? 0)));
        $periode = PaiePeriode::findById($id);

        if (!$periode || (int)$periode['lycee_id'] !== (int)$lyceeId) {
            $_SESSION['error_message'] = _("Période de paie introuvable ou accès non autorisé.");
            header('Location: /paie/periodes');
            exit();
        }

        $lockReason = PaiePeriode::getLockReason($periode);
        if ($lockReason !== null) {
            $_SESSION['error_message'] = sprintf(_("Modification impossible : %s"), $lockReason);
            header('Location: /paie/periodes/' . $id);
            exit();
        }

        $mois = (int)($_POST['mois'] ?? $periode['mois']);
        $annee = (int)($_POST['annee'] ?? $periode['annee']);

        if ($mois < 1 || $mois > 12 || $annee < 2000 || $annee > 2100) {
            $_SESSION['error_message'] = _("Le mois et l'année spécifiés sont invalides.");
            header('Location: /paie/periodes/' . $id . '/edit');
            exit();
        }

        $periodeComptableId = (int)($_POST['periode_comptable_id'] ?? $periode['periode_comptable_id']);
        if (!$periodeComptableId) {
            $_SESSION['error_message'] = _("Aucune période comptable valide n'a été sélectionnée.");
            header('Location: /paie/periodes/' . $id . '/edit');
            exit();
        }

        $comptaPeriode = ComptabilitePeriode::findById($periodeComptableId);
        if (!$comptaPeriode || (int)$comptaPeriode['lycee_id'] !== (int)$lyceeId || !empty($comptaPeriode['est_cloturee'])) {
            $_SESSION['error_message'] = _("La période comptable sélectionnée est invalide ou clôturée.");
            header('Location: /paie/periodes/' . $id . '/edit');
            exit();
        }

        $dateDebut = $comptaPeriode['date_debut'];
        $dateFin = $comptaPeriode['date_fin'];

        if (strtotime($dateFin) < strtotime($dateDebut)) {
            $_SESSION['error_message'] = _("La date de fin doit être supérieure ou égale à la date de début.");
            header('Location: /paie/periodes/' . $id . '/edit');
            exit();
        }

        // Uniqueness check for lycee_id + annee + mois
        $existingMoisAnnee = PaiePeriode::findByLyceeAndMoisAnnee($lyceeId, $annee, $mois);
        if ($existingMoisAnnee && (int)$existingMoisAnnee['id'] !== $id) {
            $_SESSION['error_message'] = sprintf(_("Une période de paie existe déjà pour le mois %02d/%04d dans cet établissement."), $mois, $annee);
            header('Location: /paie/periodes/' . $id . '/edit');
            exit();
        }

        // Overlap check
        if (PaiePeriode::hasOverlap($lyceeId, $dateDebut, $dateFin, $id)) {
            $_SESSION['error_message'] = _("Les dates de la période chevauchent une autre période de paie existante.");
            header('Location: /paie/periodes/' . $id . '/edit');
            exit();
        }

        $codePeriode = "PAIE-" . sprintf('%04d-%02d', $annee, $mois);

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Re-verify lock inside transaction for concurrency protection
            $freshPeriode = PaiePeriode::findById($id);
            $freshLockReason = PaiePeriode::getLockReason($freshPeriode);
            if ($freshLockReason !== null) {
                throw new LogicException(sprintf(_("Modification impossible : %s"), $freshLockReason));
            }

            PaiePeriode::update($id, [
                'periode_comptable_id' => $periodeComptableId,
                'code_periode' => $codePeriode,
                'mois' => $mois,
                'annee' => $annee,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]);

            $db->commit();
            $_SESSION['success_message'] = _("Période de paie modifiée avec succès.");
            header('Location: /paie/periodes/' . $id);
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /paie/periodes/' . $id . '/edit');
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
