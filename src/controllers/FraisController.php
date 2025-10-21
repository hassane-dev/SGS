<?php

require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

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

        $data = Validator::sanitize($_POST);
        $lycee_id = Auth::get('lycee_id');
        $activeYear = AnneeAcademique::findActive();

        // Basic validation
        if (empty($data['niveau']) || empty($data['frais_inscription']) || empty($data['frais_mensuel'])) {
            // Handle error: redirect back with an error message
            header('Location: /frais?error=missing_fields');
            exit();
        }

        Frais::save([
            'lycee_id' => $lycee_id,
            'niveau' => $data['niveau'],
            'serie' => $data['serie'] ?? '',
            'annee_academique_id' => $activeYear['id'],
            'frais_inscription' => $data['frais_inscription'],
            'frais_mensuel' => $data['frais_mensuel'],
            'autres_frais' => null // For now, can be extended later
        ]);

        header('Location: /frais');
        exit();
    }
}
?>