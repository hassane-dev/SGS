<?php

// Force file recognition
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/ModeleCarte.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Etude.php';

class CarteController {

    private function checkAccess() {
        if (!Auth::check()) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function generer() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            die("ID de l'élève manquant.");
        }

        $eleve = Eleve::findById($eleve_id);
        if (!$eleve) {
            die("Élève non trouvé.");
        }

        // Find the student's current lycee to get the correct card template
        $etudes = Etude::findByEleveId($eleve_id);
        if (empty($etudes)) {
            die("L'élève n'a aucune inscription active.");
        }
        $current_etude = $etudes[0]; // Assume the first one is the most recent
        $classe = Classe::findById($current_etude['classe_id']);
        $lycee_id = $classe['lycee_id'];

        $modele = ModeleCarte::findByLyceeId($lycee_id);
        if (!$modele) {
            die("Aucun modèle de carte trouvé pour ce lycée.");
        }

        $data = [
            'eleve' => $eleve,
            'classe' => $classe,
            'modele' => $modele
        ];

        require_once __DIR__ . '/../views/carte/generer.php';
    }

}
?>
