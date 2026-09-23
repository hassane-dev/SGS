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
        header('Location: /paie/historique', true, 302);
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function create() {
        $_SESSION['error_message'] = _("La création directe de paiement de salaire legacy est désactivée. Veuillez utiliser le workflow canonique des bulletins de paie.");
        header('Location: /paie/bulletins/prepare', true, 302);
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function store() {
        $this->checkAccess();
        $_SESSION['error_message'] = _("Action interdite : la saisie directe de salaire legacy est définitivement fermée. Tout règlement salarial doit passer par le workflow Paie Lot 2 (Bulletins -> Règlement -> Trésorerie).");
        header('Location: /paie/bulletins/prepare', true, 302);
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
