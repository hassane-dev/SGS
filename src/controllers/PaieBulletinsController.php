<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaieBulletin.php';
require_once __DIR__ . '/../models/PaieBulletinLigne.php';
require_once __DIR__ . '/../models/PaieBulletinHeure.php';
require_once __DIR__ . '/../models/PaieBulletinContratSnapshot.php';
require_once __DIR__ . '/../models/PaieBulletinRegleSnapshot.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../services/PaieWorkflowService.php';
require_once __DIR__ . '/../services/PersonnelContractService.php';

class PaieBulletinsController {

    public function prepare() {
        Auth::requirePermission('paie', 'view');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $periodeId = (int)($_GET['periode_id'] ?? 0);
        $periodes = PaiePeriode::findAllForLycee($lyceeId);

        // Auto-select latest open period if not specified
        if (!$periodeId && !empty($periodes)) {
            foreach ($periodes as $p) {
                if ($p['statut'] !== 'cloture') {
                    $periodeId = (int)$p['id'];
                    break;
                }
            }
            if (!$periodeId) {
                $periodeId = (int)$periodes[0]['id'];
            }
        }

        $selectedPeriode = $periodeId ? PaiePeriode::findById($periodeId) : null;
        $eligibleContracts = $periodeId ? PersonnelContractService::getEligibleContractsForPeriod($periodeId, $lyceeId) : [];
        $cycles = Cycle::findByLycee($lyceeId);

        include __DIR__ . '/../views/paie/bulletins/prepare.php';
    }

    public function preview() {
        Auth::requirePermission('paie', 'calculate');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paie/bulletins/prepare');
            exit();
        }

        $lyceeId = Auth::getLyceeId() ?: 1;
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $scope = $_POST['scope'] ?? 'all';
        $selectedPersonnelIds = $_POST['personnel_ids'] ?? [];

        if (!$periodeId) {
            $_SESSION['error_message'] = _("Veuillez sélectionner une période de paie valide.");
            header('Location: /paie/bulletins/prepare');
            exit();
        }

        if ($scope === 'selection' && empty($selectedPersonnelIds)) {
            $_SESSION['error_message'] = _("Veuillez sélectionner au moins un membre du personnel pour la prévisualisation.");
            header("Location: /paie/bulletins/prepare?periode_id={$periodeId}");
            exit();
        }

        $personnelFilter = ($scope === 'selection') ? array_map('intval', $selectedPersonnelIds) : null;

        try {
            $previewData = PaieWorkflowService::previewBulletinsForPeriod($periodeId, $personnelFilter, $lyceeId);
            $selectedPeriode = $previewData['periode'];
            $previewItems = $previewData['items'];

            include __DIR__ . '/../views/paie/bulletins/prepare.php';
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header("Location: /paie/bulletins/prepare?periode_id={$periodeId}");
            exit();
        }
    }

    public function calculate() {
        Auth::requirePermission('paie', 'calculate');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paie/bulletins/prepare');
            exit();
        }

        $lyceeId = Auth::getLyceeId() ?: 1;
        $userId = Auth::getUserId();
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $scope = $_POST['scope'] ?? 'all';
        $selectedPersonnelIds = $_POST['personnel_ids'] ?? [];
        $idempotencyKey = $_POST['idempotency_key'] ?? null;

        if (!$periodeId) {
            $_SESSION['error_message'] = _("Veuillez sélectionner une période de paie valide.");
            header('Location: /paie/bulletins/prepare');
            exit();
        }

        if ($scope === 'selection' && empty($selectedPersonnelIds)) {
            $_SESSION['error_message'] = _("Veuillez sélectionner au moins un membre du personnel pour la génération.");
            header("Location: /paie/bulletins/prepare?periode_id={$periodeId}");
            exit();
        }

        $personnelFilter = ($scope === 'selection') ? array_map('intval', $selectedPersonnelIds) : null;

        try {
            $bulletinIds = PaieWorkflowService::generateBulletinsForPeriod($periodeId, $userId, $personnelFilter, $idempotencyKey, $lyceeId);
            $count = count($bulletinIds);
            $_SESSION['success_message'] = sprintf(_("Génération réussie de %d bulletin(s) de paie pour la période."), $count);
            header("Location: /paie/bulletins?periode_id={$periodeId}");
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header("Location: /paie/bulletins/prepare?periode_id={$periodeId}");
            exit();
        }
    }

    public function index() {
        Auth::requirePermission('paie', 'view');
        $periodeId = (int)($_GET['periode_id'] ?? 0);
        $personnelId = (int)($_GET['personnel_id'] ?? 0);
        $lyceeId = Auth::getLyceeId() ?: 1;
        $periodes = PaiePeriode::findAllForLycee($lyceeId);

        $selectedTeacher = null;
        $activeContract = null;
        $individualBulletin = null;
        $selectedPeriode = null;

        if ($personnelId > 0 && $periodeId > 0) {
            $db = Database::getInstance();
            $stmtU = $db->prepare("SELECT id_user, nom, prenom, identifiant_public FROM utilisateurs WHERE id_user = :id");
            $stmtU->execute(['id' => $personnelId]);
            $selectedTeacher = $stmtU->fetch(PDO::FETCH_ASSOC);

            $selectedPeriode = PaiePeriode::findById($periodeId);
            $activeContract = PersonnelContractService::getActiveContract($personnelId, $selectedPeriode['date_fin'] ?? date('Y-m-d'));

            if ($activeContract) {
                $entiteJuridiqueId = (int)($activeContract['entite_juridique_id'] ?? 1);
                $individualBulletin = PaieBulletin::findActiveForContractAndPeriod($personnelId, $entiteJuridiqueId, (int)$activeContract['id'], $periodeId);
            }
        }

        if ($periodeId > 0) {
            $bulletins = PaieBulletin::findByPeriod($periodeId);
        } else {
            // Fetch active or latest bulletins across periods
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT b.*, u.nom, u.prenom, u.identifiant_public, p.code_periode
                FROM paie_bulletins b
                JOIN utilisateurs u ON b.personnel_id = u.id_user
                JOIN paie_periodes p ON b.periode_id = p.id
                WHERE p.lycee_id = :lycee_id
                ORDER BY b.id DESC LIMIT 100
            ");
            $stmt->execute(['lycee_id' => $lyceeId]);
            $bulletins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        include __DIR__ . '/../views/paie/bulletins/index.php';
    }

    public function generateIndividual() {
        Auth::requirePermission('paie', 'calculate');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paie/bulletins');
            exit();
        }

        $personnelId = (int)($_POST['personnel_id'] ?? 0);
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $userId = Auth::getUserId();

        if (!$personnelId || !$periodeId) {
            $_SESSION['error_message'] = _("Identifiants du personnel ou de la période manquants.");
            header("Location: /paie/bulletins");
            exit();
        }

        try {
            $periode = PaiePeriode::findById($periodeId);
            if (!$periode) {
                throw new InvalidArgumentException("Période de paie introuvable ID #{$periodeId}");
            }

            $contract = PersonnelContractService::getActiveContract($personnelId, $periode['date_fin']);
            if (!$contract) {
                throw new LogicException("Aucun contrat actif trouvé pour ce membre du personnel.");
            }

            $bulletinId = PaieWorkflowService::generateBulletinForEmployee(
                $periodeId,
                $personnelId,
                (int)$contract['id'],
                $userId
            );

            $_SESSION['success_message'] = _("Bulletin de paie individuel généré avec succès.");
            header("Location: /paie/bulletins/{$bulletinId}");
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header("Location: /paie/bulletins?personnel_id={$personnelId}&periode_id={$periodeId}");
            exit();
        }
    }

    public function show($id = null) {
        Auth::requirePermission('paie', 'view');
        $id = (int)($id ?: ($_GET['id'] ?? 0));
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
        $comptesFinanciers = CompteFinancier::findByLycee($lyceeId);

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
            header('Location: /paie/bulletins/' . $newBulletinId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /paie/bulletins/' . $id);
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

        header('Location: /paie/bulletins/' . $id);
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

        header('Location: /paie/bulletins/' . $id);
        exit();
    }
}
