<?php

require_once __DIR__ . '/../models/Salaire.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

class SalaireController {

    private function checkAccess() {
        if (!Auth::can('manage_paiements')) { // Reuse this permission
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = !Auth::can('manage_all_lycees') ? Auth::get('lycee_id') : null;
        $salaires = Salaire::findAll($lycee_id);
        require_once __DIR__ . '/../views/salaires/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = !Auth::can('manage_all_lycees') ? Auth::get('lycee_id') : null;
        $personnels = User::findAll($lycee_id); // Simplified, should filter by contract type
        require_once __DIR__ . '/../views/salaires/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_POST['lycee_id'] = Auth::get('lycee_id'); // Assuming only local admins do this
            // The 'save' method is more appropriate now as it handles both create and update
            Salaire::save($_POST);
        }
        header('Location: /salaires');
        exit();
    }

    public function generer() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lycee_id = Auth::get('lycee_id');
            $annee = $_POST['annee'] ?? date('Y');
            $mois = $_POST['mois'] ?? date('m');

            if (!Auth::can('manage_all_lycees')) {
                 $lycee_id_user = Auth::get('lycee_id');
                 if($lycee_id_user != $lycee_id && !is_null($lycee_id)){
                    http_response_code(403);
                    echo "Accès Interdit.";
                    exit();
                 }
                 $lycee_id = $lycee_id_user;
            }

            $nombreSalaires = Salaire::genererSalaires($lycee_id, $annee, $mois);

            // TODO: Implement session flash messages for better user feedback
            header('Location: /salaires?generated=' . $nombreSalaires);
            exit();
        }
        // Redirect if accessed via GET
        header('Location: /salaires');
        exit();
    }

    public function genererFiche() {
        $this->checkAccess();
        $salaire_id = $_GET['id'] ?? null;
        if (!$salaire_id) { die('ID de salaire manquant.'); }

        $salaire = Salaire::findById($salaire_id);
        if (!$salaire) { die('Salaire non trouvé.'); }

        $user = User::findById($salaire['personnel_id']);
        $contrat = TypeContrat::findById($user['contrat_id']);
        $lycee = Lycee::findById($salaire['lycee_id']);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, utf8_decode('FICHE DE PAIE'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, utf8_decode($lycee['nom_lycee']), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode($lycee['adresse']), 0, 1, 'C');
        $pdf->Ln(10);

        // Informations sur l'employé et la période
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Informations sur l\'employé'), 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 6, utf8_decode('Nom & Prénom(s):'), 0);
        $pdf->Cell(0, 6, utf8_decode($user['nom'] . ' ' . $user['prenom']), 0, 1);
        $pdf->Cell(40, 6, 'Fonction:', 0);
        $pdf->Cell(0, 6, utf8_decode($user['fonction']), 0, 1);
        $pdf->Cell(40, 6, utf8_decode('Période de paie:'), 0);
        $pdf->Cell(0, 6, utf8_decode(strftime('%B %Y', mktime(0, 0, 0, $salaire['periode_mois'], 1, $salaire['periode_annee']))), 0, 1);
        $pdf->Ln(10);

        // Détails du salaire
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, utf8_decode('Détails de la Rémunération'), 0, 1);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(130, 7, 'Description', 1, 0, 'C', true);
        $pdf->Cell(60, 7, 'Montant', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 10);

        if ($salaire['mode_paiement'] == 'mensuel') {
            $pdf->Cell(130, 7, utf8_decode('Salaire de base (Fixe)'), 1);
            $pdf->Cell(60, 7, number_format($salaire['montant'], 2, ',', ' ') . ' XAF', 1, 1, 'R');
        } else { // Horaire
            $pdf->Cell(130, 7, utf8_decode('Heures travaillées'), 1);
            $pdf->Cell(60, 7, number_format($salaire['nb_heures_travaillees'], 2, ',', ' ') . ' h', 1, 1, 'R');

            $pdf->Cell(130, 7, utf8_decode('Taux horaire'), 1);
            $pdf->Cell(60, 7, number_format($contrat['taux_horaire'], 2, ',', ' ') . ' XAF', 1, 1, 'R');

            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(130, 7, utf8_decode('Salaire brut (calculé)'), 1);
            $pdf->Cell(60, 7, number_format($salaire['montant'], 2, ',', ' ') . ' XAF', 1, 1, 'R');
        }

        // Total
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(130, 10, 'NET A PAYER', 1, 0, 'R');
        $pdf->Cell(60, 10, number_format($salaire['montant'], 2, ',', ' ') . ' XAF', 1, 1, 'R');

        $pdf->Ln(20);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, utf8_decode('Fait le ' . date('d/m/Y')), 0, 1, 'R');

        $filename = 'Fiche_de_Paie_' . $user['nom'] . '_' . $salaire['periode_mois'] . '-' . $salaire['periode_annee'] . '.pdf';
        $pdf->Output('D', $filename);
        exit();
    }
}
?>
