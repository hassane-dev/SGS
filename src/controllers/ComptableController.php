<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';

class ComptableController {

    private function checkAccess() {
        // We might need a more specific permission like 'validate_inscriptions'
        if (!Auth::can('manage_paiements')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function listPending() {
        $this->checkAccess();
        $lycee_id = Auth::get('lycee_id');

        // Find students in this lycee with 'en_attente_paiement' status
        // This requires adding a new method to the Eleve model.
        $eleves_en_attente = Eleve::findByStatus('en_attente_paiement', $lycee_id);

        require_once __DIR__ . '/../views/comptable/pending.php';
    }

    public function showValidationForm() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) { header('Location: /comptable/pending'); exit(); }

        $eleve = Eleve::findById($eleve_id);
        $activeYear = AnneeAcademique::findActive();

        // Find the inactive 'etude' record for the current year
        $etude = Etude::findPendingEnrollment($eleve_id, $activeYear['id']);
        if (!$etude) { die("Aucune inscription en attente trouvée pour cet élève cette année."); }

        $frais = Frais::getForClasse($etude['classe_id'], $activeYear['id']);

        require_once __DIR__ . '/../views/comptable/validate.php';
    }

    public function processValidation() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /comptable/pending'); exit(); }

        $eleve_id = $_POST['eleve_id'];
        $etude_id = $_POST['etude_id'];
        $montant_verse = $_POST['montant_verse'];

        $eleve = Eleve::findById($eleve_id);
        $activeYear = AnneeAcademique::findActive();
        $frais = Frais::getForClasse($_POST['classe_id'], $activeYear['id']);

        $montant_total = $frais['frais_inscription'];
        // Add other fees if they exist

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // 1. Activate the academic record
            Etude::activate($etude_id);

            // 2. Update student status to 'actif'
            $eleve['statut'] = 'actif';
            Eleve::save($eleve);

            // 3. Create the financial record (inscription)
            Inscription::create([
                'eleve_id' => $eleve_id,
                'classe_id' => $_POST['classe_id'],
                'lycee_id' => $eleve['lycee_id'],
                'annee_academique_id' => $activeYear['id'],
                'montant_total' => $montant_total,
                'montant_verse' => $montant_verse,
                'reste_a_payer' => $montant_total - $montant_verse,
                'details_frais' => ['frais_inscription' => $frais['frais_inscription']],
                'user_id' => Auth::get('id_user')
            ]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            error_log($e->getMessage());
            die("Une erreur est survenue lors de la validation. Veuillez réessayer.");
        }

        // Redirect to receipt page or student details
        header('Location: /recu/inscription?id=' . $eleve_id); // Placeholder for receipt generation
        exit();
    }
}
?>