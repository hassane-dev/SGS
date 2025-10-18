<?php

require_once __DIR__ . '/../models/Evaluation.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/EnseignantMatiere.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class EvaluationController {

    private function checkAccess($permission = 'note:create_own') { // Assuming a new permission
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    // Step 1: Teacher selects the class and subject they teach
    public function selectClass() {
        $this->checkAccess();
        $enseignant_id = Auth::getUserId();

        // Find all unique class/subject pairs taught by this teacher
        $subjects_taught = User::findSubjectsTaughtByTeacher($enseignant_id);

        View::render('evaluations/select_class', [
            'subjects_taught' => $subjects_taught,
            'title' => 'Saisie des Notes - Étape 1/2'
        ]);
    }

    // Step 2: Teacher selects the specific evaluation (sequence)
    public function selectEvaluation() {
        $this->checkAccess();
        $classe_id = $_POST['classe_id'] ?? null;
        $matiere_id = $_POST['matiere_id'] ?? null;

        if (!$classe_id || !$matiere_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        // Security check: ensure teacher is assigned to this class/subject
        // This should be a dedicated method in a model, but for now, we'll do a basic check
        $subjects_taught = User::findSubjectsTaughtByTeacher(Auth::getUserId());
        $is_authorized = false;
        foreach($subjects_taught as $sub) {
            if($sub['classe_id'] == $classe_id && $sub['matiere_id'] == $matiere_id) {
                $is_authorized = true;
                break;
            }
        }
        if(!$is_authorized) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        $available_evaluations = Evaluation::getAvailableEvaluations($classe_id, $matiere_id);

        View::render('evaluations/select_evaluation', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'evaluations' => $available_evaluations,
            'title' => 'Saisie des Notes - Étape 2/2'
        ]);
    }

    // Step 3: Show the grading form
    public function showForm() {
        $this->checkAccess();
        $classe_id = $_GET['classe_id'] ?? null;
        $matiere_id = $_GET['matiere_id'] ?? null;
        $sequence_id = $_GET['sequence_id'] ?? null;

        if (!$classe_id || !$matiere_id || !$sequence_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        // Security check: Verify the grading window is still open
        if (!Evaluation::isGradingWindowOpen($classe_id, $matiere_id, $sequence_id)) {
            View::render('evaluations/error', [
                'message' => "La période de saisie pour cette évaluation est fermée ou n'a pas encore commencé.",
                'title' => 'Accès Refusé'
            ]);
            exit();
        }

        $eleves = Eleve::findByClass($classe_id);
        $existing_grades = Evaluation::getGradesForEvaluation($classe_id, $matiere_id, $sequence_id);

        // The coefficient is defined in the `classe_matieres` table
        $classe_matiere_details = Classe::findMatiereDetails($classe_id, $matiere_id);

        View::render('evaluations/form', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'sequence_id' => $sequence_id,
            'coefficient' => $classe_matiere_details['coefficient'],
            'eleves' => $eleves,
            'grades' => $existing_grades,
            'title' => 'Saisie des Notes'
        ]);
    }

    // Step 4: Save the grades
    public function save() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'];
            $matiere_id = $_POST['matiere_id'];
            $sequence_id = $_POST['sequence_id'];

            // Security check before saving
            if (!Evaluation::isGradingWindowOpen($classe_id, $matiere_id, $sequence_id)) {
                 View::render('evaluations/error', [
                    'message' => "La période de saisie pour cette évaluation est terminée. Les notes n'ont pas été enregistrées.",
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }

            $data_to_save = [
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'sequence_id' => $sequence_id,
                'coefficient' => $_POST['coefficient'],
                'enseignant_id' => Auth::getUserId(),
                'grades' => $_POST['grades']
            ];

            Evaluation::saveGrades($data_to_save);
        }

        header('Location: /evaluations/select_class?success=grades_saved');
        exit();
    }
}
?>