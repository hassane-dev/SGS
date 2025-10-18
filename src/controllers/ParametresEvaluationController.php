<?php

require_once __DIR__ . '/../models/ParametresEvaluation.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/EnseignantMatiere.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class ParametresEvaluationController {

    private function checkAccess($permission = 'evaluation:manage_settings') {
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();

        $classe_id = $_GET['classe_id'] ?? null;
        $matiere_id = $_GET['matiere_id'] ?? null;

        if (!$classe_id || !$matiere_id) {
            header('Location: /classes');
            exit();
        }

        $classe = Classe::findById($classe_id);
        $matiere = Matiere::findById($matiere_id);

        // Security check
        if (!$classe || !$matiere || $classe['lycee_id'] != Auth::getLyceeId() || $matiere['lycee_id'] != Auth::getLyceeId()) {
             http_response_code(404);
             View::render('errors/404');
             exit();
        }

        $sequences = Sequence::findAll();
        $teacher_assignments = EnseignantMatiere::findAssignmentsForClass($classe_id);
        $existing_settings = ParametresEvaluation::findByClassAndMatiere($classe_id, $matiere_id);

        // Find the teacher for this specific subject
        $enseignant_id = $teacher_assignments[$matiere_id]['enseignant_id'] ?? null;
        if(!$enseignant_id) {
            // Redirect with an error if no teacher is assigned to the subject yet
            header('Location: /classes/show?id=' . $classe_id . '&error=no_teacher_assigned');
            exit();
        }

        View::render('evaluations/settings', [
            'classe' => $classe,
            'matiere' => $matiere,
            'sequences' => $sequences,
            'enseignant_id' => $enseignant_id,
            'existing_settings' => $existing_settings,
            'title' => 'Paramètres des Évaluations'
        ]);
    }

    public function save() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'];
            $matiere_id = $_POST['matiere_id'];
            $settings = $_POST['settings'];

            foreach ($settings as $sequence_id => $data) {
                // Prepare data for saving
                $save_data = [
                    'classe_id' => $classe_id,
                    'matiere_id' => $matiere_id,
                    'sequence_id' => $sequence_id,
                    'enseignant_id' => $_POST['enseignant_id'],
                    'date_ouverture_saisie' => $data['date_ouverture'],
                    'date_fermeture_saisie' => $data['date_fermeture'],
                    'commentaire' => $data['commentaire']
                ];

                // Save only if dates are provided
                if (!empty($save_data['date_ouverture_saisie']) && !empty($save_data['date_fermeture_saisie'])) {
                    ParametresEvaluation::save($save_data);
                }
            }
        }

        header('Location: /classes/show?id=' . $classe_id . '&success=settings_saved');
        exit();
    }
}
?>