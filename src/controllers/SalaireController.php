<?php

require_once __DIR__ . '/../models/Salaire.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../core/Validator.php';

class SalaireController {

    private function checkAccess() {
        if (!Auth::can('manage', 'salaire')) { // Reuse this permission
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::get('lycee_id') : null;
        $salaires = Salaire::findAll($lycee_id);
        View::render('salaires/index', [
            'salaires' => $salaires,
            'title' => 'Gestion des Salaires'
        ]);
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::get('lycee_id') : null;
        $personnels = User::findAll($lycee_id); // Simplified, should filter by contract type
        View::render('salaires/create', [
            'personnels' => $personnels,
            'title' => 'Enregistrer un Paiement de Salaire'
        ]);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $lyceeId = Auth::get('lycee_id') ?? Auth::getLyceeId();
            $data['lycee_id'] = $lyceeId;

            $data['periode_mois'] = (int)($data['mois'] ?? date('m'));
            $data['periode_annee'] = (int)($data['annee'] ?? date('Y'));
            $data['montant'] = (float)($data['montant_net'] ?? 0);
            $data['mode_paiement'] = $data['mode_paiement'] ?? 'Espèces';
            $data['etat_paiement'] = 'paye';

            require_once __DIR__ . '/../models/AnneeAcademique.php';
            $activeYear = AnneeAcademique::findActive();
            $data['annee_id'] = $activeYear ? $activeYear['id'] : null;

            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                // Save salary payment (fix undefined method create() crash)
                Salaire::save($data);
                $salaireId = $db->lastInsertId();

                // Hook to TreasuryService (Phase 1)
                require_once __DIR__ . '/../models/TreasuryService.php';

                // Resolve payment account type
                $typeCompte = 'caisse';
                if (in_array(strtolower($data['mode_paiement']), ['chèque', 'cheque', 'virement', 'banque'])) {
                    $typeCompte = 'banque';
                } elseif (in_array(strtolower($data['mode_paiement']), ['mobile money', 'momo', 'paiement mobile', 'mobile'])) {
                    $typeCompte = 'mobile_money';
                }

                // Check active cash session if paid in cash
                $compteId = null;
                if ($typeCompte === 'caisse') {
                    require_once __DIR__ . '/../models/SessionCaisse.php';
                    $activeSession = SessionCaisse::findActiveByUser(Auth::getUserId(), $lyceeId);
                    if (!$activeSession) {
                        throw new Exception(_("Veuillez ouvrir votre session de caisse journalière avant d'effectuer un paiement de salaire en espèces."));
                    }
                    $compteId = $activeSession['compte_id'];
                }

                // Register outflow movement
                $mvtId = TreasuryService::registerMovement([
                    'lycee_id' => $lyceeId,
                    'compte_id' => $compteId,
                    'type_mouvement' => 'sortie',
                    'montant' => $data['montant'],
                    'mode_paiement' => $data['mode_paiement'],
                    'reference_transaction' => 'SAL-' . $data['periode_mois'] . '-' . $data['periode_annee'] . '-' . $salaireId,
                    'source_type' => 'salaires',
                    'source_id' => $salaireId,
                    'evenement_type' => 'encaissement',
                    'motif' => "Paiement de salaire - Mois " . $data['periode_mois'] . "/" . $data['periode_annee'],
                    'user_id' => Auth::getUserId()
                ]);

                // Hook to ComptabiliteService (Phase 5)
                require_once __DIR__ . '/../services/ComptabiliteService.php';
                ComptabiliteService::genererEcritureAutomatique(
                    'salaire',
                    $data['montant'],
                    $lyceeId,
                    $data['periode_mois'] . '-' . $data['periode_annee'],
                    Auth::getUserId(),
                    'salaires',
                    $salaireId,
                    $data['date_paiement'] ?? date('Y-m-d')
                );

                $db->commit();
                $_SESSION['success_message'] = _("Paiement de salaire enregistré et comptabilisé avec succès.");
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $_SESSION['error_message'] = $e->getmessage();
            }
        }
        header('Location: /salaires');
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function genererFiche() {
        $this->checkAccess();
        $salaire_id = $_GET['id'] ?? null;
        if (!$salaire_id) { die('ID de salaire manquant.'); }

        $salaire = Salaire::findById($salaire_id);
        if (!$salaire) { die('Salaire non trouvé.'); }

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(40, 10, 'Fiche de Paie');
        $pdf->Ln(20);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 10, 'Employe: ' . utf8_decode($salaire['prenom'] . ' ' . $salaire['nom']));
        $pdf->Ln(10);
        $pdf->Cell(40, 10, 'Mois: ' . $salaire['mois'] . '/' . $salaire['annee']);
        $pdf->Ln(10);
        $pdf->Cell(40, 10, 'Salaire Net: ' . $salaire['montant_net'] . ' EUR'); // Currency should be a setting
        $pdf->Output('D', 'fiche_de_paie_' . $salaire_id . '.pdf');
        exit();
    }
}
?>
