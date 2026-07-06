<?php

require_once __DIR__ . '/../models/Evaluation.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/EnseignantMatiere.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class EvaluationController {

    private function checkAccess($permission = 'note:create_own') { // Assuming a new permission
        list($resource, $action) = explode(':', $permission);
        if (!Auth::can($action, $resource)) {
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
        $classe_id = $_POST['classe_id'] ?? $_GET['classe_id'] ?? null;
        $matiere_id = $_POST['matiere_id'] ?? $_GET['matiere_id'] ?? null;
        $type = $_POST['type'] ?? $_GET['type'] ?? 'devoir';

        if (!$classe_id || !$matiere_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        // Security check: ensure teacher is assigned to this class/subject
        $subjects_taught = User::findSubjectsTaughtByTeacher(Auth::getUserId());
        $is_authorized = false;
        foreach($subjects_taught as $sub) {
            if($sub['classe_id'] == $classe_id && $sub['matiere_id'] == $matiere_id) {
                $is_authorized = true;
                break;
            }
        }
        if(!$is_authorized && !Auth::can('view_all', 'note')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        $available_evaluations = Evaluation::getAvailableEvaluations($classe_id, $matiere_id, $type);

        View::render('evaluations/select_evaluation', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'type' => $type,
            'evaluations' => $available_evaluations,
            'title' => 'Saisie des Notes - ' . ucfirst($type)
        ]);
    }

    // Step 3: Show the grading form
    public function showForm() {
        $this->checkAccess();
        $classe_id = $_GET['classe_id'] ?? null;
        $matiere_id = $_GET['matiere_id'] ?? null;
        $sequence_id = $_GET['sequence_id'] ?? null;
        $type = $_GET['type'] ?? 'devoir';

        if (!$classe_id || !$matiere_id || !$sequence_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        // Security check: Verify the grading window is still open
        if (!Evaluation::isGradingWindowOpen($classe_id, $matiere_id, $sequence_id, $type)) {
            View::render('evaluations/error', [
                'message' => "La période de saisie pour cette évaluation (" . $type . ") est fermée ou n'a pas encore commencé.",
                'title' => 'Accès Refusé'
            ]);
            exit();
        }

        $eleves = Eleve::findByClass($classe_id);
        $existing_grades = Evaluation::getGradesForEvaluation($classe_id, $matiere_id, $sequence_id, $type);

        // The coefficient is defined in the `classe_matieres` table
        $classe_matiere_details = Classe::findMatiereDetails($classe_id, $matiere_id);

        View::render('evaluations/form', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'sequence_id' => $sequence_id,
            'active_sequence' => Sequence::findById($sequence_id),
            'type' => $type,
            'coefficient' => $classe_matiere_details['coefficient'],
            'eleves' => $eleves,
            'grades' => $existing_grades,
            'title' => 'Saisie des Notes - ' . ucfirst($type)
        ]);
    }

    // Step 4: Save the grades
    public function save() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'];
            $matiere_id = $_POST['matiere_id'];
            $sequence_id = $_POST['sequence_id'];
            $type = $_POST['type'] ?? 'devoir';

            // Security check before saving
            if (!Evaluation::isGradingWindowOpen($classe_id, $matiere_id, $sequence_id, $type)) {
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
                'type' => $type,
                'coefficient' => $_POST['coefficient'],
                'enseignant_id' => Auth::getUserId(),
                'grades' => $_POST['grades']
            ];

            Evaluation::saveGrades($data_to_save);
        }

        header('Location: /evaluations/select_class?success=grades_saved');
        exit();
    }

    public function directSaisie($classe_id, $matiere_id) {
        $this->checkAccess();

        // Security check: ensure teacher is assigned to this class/subject
        $enseignant_id = Auth::getUserId();
        $subjects_taught = User::findSubjectsTaughtByTeacher($enseignant_id);
        $is_authorized = false;
        foreach($subjects_taught as $sub) {
            if($sub['classe_id'] == $classe_id && $sub['matiere_id'] == $matiere_id) {
                $is_authorized = true;
                break;
            }
        }

        if(!$is_authorized && !Auth::can('view_all', 'note')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        // Automatic discovery of active context
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            View::render('evaluations/error', [
                'message' => "Aucune année académique n'est actuellement active. Veuillez contacter l'administration.",
                'title' => 'Erreur de Configuration'
            ]);
            exit();
        }

        $active_sequence = Sequence::findActive();
        if (!$active_sequence) {
            View::render('evaluations/error', [
                'message' => "Aucune séquence active n'est actuellement ouverte pour la saisie des notes. Veuillez contacter l'administration.",
                'title' => 'Saisie Fermée'
            ]);
            exit();
        }

        $sequence_id = $active_sequence['id'];

        // Check which evaluation types are open (devoir and/or composition)
        $is_devoir_open = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, $sequence_id, 'devoir');
        $is_composition_open = Evaluation::isGradingWindowOpen($classe_id, $matiere_id, $sequence_id, 'composition');

        if (!$is_devoir_open && !$is_composition_open) {
            View::render('evaluations/error', [
                'message' => "La période de saisie pour la séquence actuelle (" . htmlspecialchars($active_sequence['nom']) . ") est fermée ou n'a pas encore commencé.",
                'title' => 'Saisie Fermée'
            ]);
            exit();
        }

        // Determine type and redirect/show form
        $type = 'devoir'; // Default
        if ($is_composition_open && !$is_devoir_open) {
            $type = 'composition';
        }

        // If both are open, we might need a choice, but the prompt says "direct opening"
        // Most common logic is 'devoir' first or both are available.
        // If we want to strictly avoid select_evaluation, we can force a redirect or check if a specific one was requested.
        // For now, let's redirect to showForm with the determined type.

        header("Location: /evaluations/form?classe_id=$classe_id&matiere_id=$matiere_id&sequence_id=$sequence_id&type=$type");
        exit();
    }
}
?>