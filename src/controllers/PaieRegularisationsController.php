<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaieRegularisation.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../services/PaieWorkflowService.php';

class PaieRegularisationsController {

    public function index() {
        Auth::requirePermission('paie', 'view');
        $periodeId = (int)($_GET['periode_id'] ?? 0);
        $lyceeId = Auth::getLyceeId() ?: 1;

        $db = Database::getInstance();

        // Fetch open destination periods
        $stmtP = $db->prepare("SELECT * FROM paie_periodes WHERE lycee_id = :lid AND statut != 'cloture' ORDER BY id DESC");
        $stmtP->execute(['lid' => $lyceeId]);
        $openPeriodes = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all periods for sources
        $stmtAllP = $db->prepare("SELECT * FROM paie_periodes WHERE lycee_id = :lid ORDER BY id DESC");
        $stmtAllP->execute(['lid' => $lyceeId]);
        $allPeriodes = $stmtAllP->fetchAll(PDO::FETCH_ASSOC);

        // Fetch active teachers
        $stmtT = $db->prepare("
            SELECT DISTINCT u.id_user, u.nom, u.prenom, u.identifiant_public
            FROM utilisateurs u
            JOIN personnel_contrats_historique c ON u.id_user = c.personnel_id
            WHERE c.statut_contrat = 'actif'
            ORDER BY u.nom ASC, u.prenom ASC
        ");
        $stmtT->execute();
        $teachers = $stmtT->fetchAll(PDO::FETCH_ASSOC);

        // Fetch active/valid bulletins for dropdowns
        $stmtB = $db->prepare("
            SELECT b.id, b.personnel_id, b.periode_id, b.version_num, b.net_a_payer, p.code_periode, u.nom, u.prenom
            FROM paie_bulletins b
            JOIN paie_periodes p ON b.periode_id = p.id
            JOIN utilisateurs u ON b.personnel_id = u.id_user
            WHERE b.est_version_active = 1
            ORDER BY b.id DESC
        ");
        $stmtB->execute();
        $bulletins = $stmtB->fetchAll(PDO::FETCH_ASSOC);

        $regularisations = $periodeId ? PaieRegularisation::findByDestinationPeriod($periodeId) : [];

        include __DIR__ . '/../views/paie/regularisations/index.php';
    }

    public function store() {
        Auth::requirePermission('paie', 'regularize');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paie/regularisations');
            exit();
        }

        $personnelId = (int)($_POST['personnel_id'] ?? 0);
        $destinationPeriodId = (int)($_POST['periode_destination_id'] ?? 0);
        $sourceType = $_POST['source_type'] ?? 'bulletin';
        $periodeSourceId = !empty($_POST['periode_source_id']) ? (int)$_POST['periode_source_id'] : null;
        $bulletinSourceId = !empty($_POST['bulletin_source_id']) ? (int)$_POST['bulletin_source_id'] : null;
        $typeRegu = $_POST['type_regularisation'] ?? 'rappel_salaire';
        $motif = trim($_POST['motif'] ?? '');
        $brutDelta = (float)($_POST['montant_brut_delta'] ?? 0.00);
        $netDelta = (float)($_POST['montant_net_delta'] ?? 0.00);
        $userId = Auth::getUserId();

        try {
            PaieWorkflowService::createRegularization(
                $personnelId,
                $destinationPeriodId,
                $sourceType,
                $periodeSourceId,
                $bulletinSourceId,
                $typeRegu,
                $motif,
                $brutDelta,
                $netDelta,
                $userId
            );

            $_SESSION['success_message'] = _("Régularisation enregistrée avec succès pour la période destination.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/regularisations?periode_id=' . $destinationPeriodId);
        exit();
    }
}
