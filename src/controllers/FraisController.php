<?php

require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';

class FraisController {

    private function checkAccess() {
        if (!Auth::can('manage_frais')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = Auth::get('lycee_id');
        $frais = Frais::findByLyceeId($lycee_id);
        $activeYear = AnneeAcademique::findActive();

        require_once __DIR__ . '/../views/frais/index.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /frais');
            exit();
        }

        $lycee_id = Auth::get('lycee_id');
        $activeYear = AnneeAcademique::findActive();

        // Basic validation
        if (empty($_POST['niveau']) || empty($_POST['frais_inscription']) || empty($_POST['frais_mensuel'])) {
            // Handle error: redirect back with an error message
            header('Location: /frais?error=missing_fields');
            exit();
        }

        Frais::save([
            'lycee_id' => $lycee_id,
            'niveau' => $_POST['niveau'],
            'serie' => $_POST['serie'] ?? '',
            'annee_academique_id' => $activeYear['id'],
            'frais_inscription' => $_POST['frais_inscription'],
            'frais_mensuel' => $_POST['frais_mensuel'],
            'autres_frais' => null // For now, can be extended later
        ]);

        header('Location: /frais');
        exit();
    }
}
?>