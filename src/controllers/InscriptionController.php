<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';

class InscriptionController {

    private function checkAccess() {
        if (!Auth::can('manage_inscriptions')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function showForm() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }

        $eleve = Eleve::findById($eleve_id);
        // An admin can only enroll a student in their own lycee
        $classes = Classe::findAll($eleve['lycee_id']);
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            // Handle case where no academic year is active
            die("Aucune année académique active. Veuillez en activer une.");
        }

        require_once __DIR__ . '/../views/inscriptions/enroll.php';
    }

    public function enroll() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eleves');
            exit();
        }

        $eleve_id = $_POST['eleve_id'];
        $classe_id = $_POST['classe_id'];
        $montant_verse = $_POST['montant_verse'];

        $eleve = Eleve::findById($eleve_id);
        $activeYear = AnneeAcademique::findActive();

        // 1. Get applicable fees
        $frais = Frais::getForClasse($classe_id, $activeYear['id']);
        if (!$frais) {
            die("La grille tarifaire pour cette classe n'a pas été définie pour l'année en cours.");
        }

        $montant_total = $frais['frais_inscription'];
        $details_frais = ['frais_inscription' => $frais['frais_inscription']];

        if (!empty($frais['autres_frais'])) {
            $autres_frais = json_decode($frais['autres_frais'], true);
            foreach($autres_frais as $key => $value) {
                $montant_total += $value;
                $details_frais[$key] = $value;
            }
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            // 2. Create the academic record (etude)
            Etude::create([
                'eleve_id' => $eleve_id,
                'classe_id' => $classe_id,
                'annee_academique_id' => $activeYear['id'],
                'actif' => 1 // Enrollment makes the student active in the class
            ]);

            // 3. Create the financial record (inscription)
            Inscription::create([
                'eleve_id' => $eleve_id,
                'classe_id' => $classe_id,
                'lycee_id' => $eleve['lycee_id'],
                'annee_academique_id' => $activeYear['id'],
                'montant_total' => $montant_total,
                'montant_verse' => $montant_verse,
                'reste_a_payer' => $montant_total - $montant_verse,
                'details_frais' => $details_frais,
                'user_id' => Auth::get('id_user')
            ]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            // Log the error and show a user-friendly message
            error_log($e->getMessage());
            die("Une erreur est survenue lors de l'inscription. Veuillez réessayer.");
        }

        header('Location: /eleves/details?id=' . $eleve_id);
        exit();
    }
}
?>