<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Mensualite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';

class MensualiteController {

    private function checkAccess() {
        if (!Auth::can('manage_paiements')) {
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
        $activeYear = AnneeAcademique::findActive();

        require_once __DIR__ . '/../views/mensualites/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eleves');
            exit();
        }

        $eleve_id = $_POST['eleve_id'];
        $eleve = Eleve::findById($eleve_id);
        $activeYear = AnneeAcademique::findActive();

        Mensualite::create([
            'eleve_id' => $eleve_id,
            'lycee_id' => $eleve['lycee_id'],
            'annee_academique_id' => $activeYear['id'],
            'mois_ou_sequence' => $_POST['mois_ou_sequence'],
            'montant_verse' => $_POST['montant_verse'],
            'user_id' => Auth::get('id_user')
        ]);

        header('Location: /eleves/details?id=' . $eleve_id);
        exit();
    }
}
?>