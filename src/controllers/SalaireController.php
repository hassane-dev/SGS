<?php

require_once __DIR__ . '/../models/Salaire.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../core/Validator.php';

class SalaireController {

    private function checkAccess() {
        if (!Auth::can('manage', 'salaire')) { // Reuse this permission
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'system') ? Auth::get('lycee_id') : null;
        $salaires = Salaire::findAll($lycee_id);
        require_once __DIR__ . '/../views/salaires/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'system') ? Auth::get('lycee_id') : null;
        $personnels = User::findAll($lycee_id); // Simplified, should filter by contract type
        require_once __DIR__ . '/../views/salaires/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $data['lycee_id'] = Auth::get('lycee_id'); // Assuming only local admins do this
            Salaire::create($data);
        }
        header('Location: /salaires');
        exit();
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
