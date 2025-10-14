<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../core/Auth.php';

class RecuController {

    public function showInscriptionRecu() {
        // A basic permission check, can be refined
        if (!Auth::isLoggedIn()) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $eleve_id = $_GET['id'] ?? null;
        if (!$eleve_id) {
            die("ID de l'élève manquant.");
        }

        $eleve = Eleve::findById($eleve_id);

        // Find the most recent inscription for this student
        $inscriptions = Inscription::findByEleveId($eleve_id);
        if (empty($inscriptions)) {
            die("Aucune inscription trouvée pour cet élève.");
        }
        $inscription = $inscriptions[0]; // Get the most recent one

        $lycee = Lycee::findById($eleve['lycee_id']);

        require_once __DIR__ . '/../views/recus/inscription.php';
    }
}
?>