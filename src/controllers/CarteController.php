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
        $classe_id = $_GET['classe_id'] ?? null;

        if (!$eleve_id && !$classe_id) {
            die("ID de l'élève ou de la classe manquant.");
        }

        $eleves = [];
        $classe = null;
        $lycee_id = null;

        if ($eleve_id) {
            $eleve = Eleve::findById($eleve_id);
            if (!$eleve) {
                die("Élève non trouvé.");
            }
            $eleves[] = $eleve;

            $etudes = Etude::findByEleveId($eleve_id);
            if (empty($etudes)) {
                die("L'élève n'a aucune inscription active.");
            }
            $current_etude = $etudes[0];
            $classe = Classe::findById($current_etude['classe_id']);
            $lycee_id = $classe['lycee_id'];
        } elseif ($classe_id) {
            $classe = Classe::findById($classe_id);
            if (!$classe) {
                die("Classe non trouvée.");
            }
            $lycee_id = $classe['lycee_id'];
            $eleves = Eleve::findByClass($classe_id);
            if (empty($eleves)) {
                die("Aucun élève trouvé dans cette classe.");
            }
        }

        $modele = ModeleCarte::findByLyceeId($lycee_id);
        if (!$modele) {
            die("Aucun modèle de carte trouvé pour ce lycée.");
        }

        $params_lycee = ParamLycee::findByLyceeId($lycee_id);
        $annee = AnneeAcademique::findActive();

        $students_data = [];
        foreach ($eleves as $eleve) {
            // Secure QR Data: unique ID with signature
            $data_to_sign = $eleve['id_eleve'] . '-' . $lycee_id . '-' . ($annee['id'] ?? date('Y'));
            $signature = hash_hmac('sha256', $data_to_sign, CARD_SIGNATURE_SECRET);
            $secure_token = $data_to_sign . '|' . $signature;

            $students_data[] = [
                'eleve' => $eleve,
                'secure_token' => "https://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/verify-student?data=" . urlencode($secure_token)
            ];
        }

        $data = [
            'students' => $students_data,
            'classe' => $classe,
            'lycee' => $params_lycee,
            'annee' => $annee,
            'modele' => array_merge($modele, [
                'logo_lycee' => $params_lycee['logo'] ?? null
            ])
        ];

        require_once __DIR__ . '/../views/carte/generer.php';
    }

}
?>
