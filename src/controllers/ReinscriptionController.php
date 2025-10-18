<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Notification.php';

class ReinscriptionController {

    private function checkAccess() {
        if (!Auth::can('reinscrire', 'eleve')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        require_once __DIR__ . '/../views/reinscription/index.php';
    }

    public function search() {
        $this->checkAccess();
        $term = $_POST['search_term'] ?? null;
        if (!$term) {
            header('Location: /reinscription');
            exit();
        }

        $lycee_id = Auth::get('lycee_id');
        $eleves = Eleve::search($term, $lycee_id);

        require_once __DIR__ . '/../views/reinscription/results.php';
    }

    public function confirm() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /reinscription');
            exit();
        }

        $eleve = Eleve::findById($eleve_id);
        $lycee_id = $eleve['lycee_id'];
        $classes = Classe::findAll($lycee_id);
        $active_year = AnneeAcademique::findActive();

        require_once __DIR__ . '/../views/reinscription/confirm.php';
    }

    public function process() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /reinscription');
            exit();
        }

        $eleve_id = $_POST['eleve_id'];
        $classe_id = $_POST['classe_id'];
        $annee_id = $_POST['annee_academique_id'];

        if (Etude::isEnrolled($eleve_id, $annee_id)) {
            header('Location: /reinscription/confirm?eleve_id=' . $eleve_id . '&error=enrolled');
            exit();
        }

        Etude::create([
            'eleve_id' => $eleve_id,
            'classe_id' => $classe_id,
            'annee_academique_id' => $annee_id,
            'actif' => 0
        ]);

        $eleve = Eleve::findById($eleve_id);
        $message = "Demande de réinscription pour " . $eleve['prenom'] . " " . $eleve['nom'] . ".";
        $link = "/comptable/validate-form?eleve_id=" . $eleve_id;
        Notification::notifyAccountants($eleve['lycee_id'], $message, $link);

        header('Location: /reinscription?success=1');
        exit();
    }
}