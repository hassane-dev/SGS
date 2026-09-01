<?php

require_once __DIR__ . '/../models/Evaluation.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/AffectationPedagogique.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../services/EvaluationSaisieService.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class EvaluationController {

    private function checkAccess($permission = 'note:create_own') {
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
        $requested_type = $_POST['type'] ?? $_GET['type'] ?? null;

        if (!$classe_id || !$matiere_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        // Security check: ensure teacher is assigned to this class/subject
        $subjects_taught = User::findSubjectsTaughtByTeacher(Auth::getUserId());
        $is_authorized = false;
        foreach ($subjects_taught as $sub) {
            if ($sub['classe_id'] == $classe_id && $sub['matiere_id'] == $matiere_id) {
                $is_authorized = true;
                break;
            }
        }
        if (!$is_authorized && !Auth::can('view_all', 'note')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        $evals_devoir = Evaluation::getAvailableEvaluations($classe_id, $matiere_id, 'devoir');
        $evals_comp = Evaluation::getAvailableEvaluations($classe_id, $matiere_id, 'composition');

        $is_devoir_available = !empty($evals_devoir);
        $is_comp_available = !empty($evals_comp);

        if ($is_comp_available && !$is_devoir_available) {
            $type = 'composition';
            $available_evaluations = $evals_comp;
        } elseif ($is_devoir_available && !$is_comp_available) {
            $type = 'devoir';
            $available_evaluations = $evals_devoir;
        } else {
            $type = ($requested_type === 'composition') ? 'composition' : 'devoir';
            $available_evaluations = ($type === 'composition') ? $evals_comp : $evals_devoir;
        }

        View::render('evaluations/select_evaluation', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'type' => $type,
            'is_devoir_open' => $is_devoir_available,
            'is_composition_open' => $is_comp_available,
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

        // Délégation centralisée à EvaluationSaisieService pour les deux types
        $decisionDevoir = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$sequence_id, 'devoir');
        $decisionComp = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$sequence_id, 'composition');

        $is_devoir_open = $decisionDevoir['allowed'];
        $is_composition_open = $decisionComp['allowed'];

        // Vérification stricte anti-tampering sur le type demandé
        $requestedDecision = ($type === 'composition') ? $decisionComp : $decisionDevoir;
        if (!$requestedDecision['allowed']) {
            View::render('evaluations/error', [
                'message' => $requestedDecision['reason'],
                'title' => 'Accès Refusé'
            ]);
            exit();
        }

        $eleves = Eleve::findByClass($classe_id);
        $existing_grades = Evaluation::getGradesForEvaluation($classe_id, $matiere_id, $sequence_id, $type);

        $classe_matiere_details = Classe::findMatiereDetails($classe_id, $matiere_id);

        View::render('evaluations/form', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'sequence_id' => $sequence_id,
            'active_sequence' => Sequence::findById($sequence_id),
            'type' => $type,
            'is_devoir_open' => $is_devoir_open,
            'is_composition_open' => $is_composition_open,
            'coefficient' => $classe_matiere_details['coefficient'] ?? 1,
            'eleves' => $eleves,
            'grades' => $existing_grades,
            'title' => 'Saisie des Notes - ' . ucfirst($type)
        ]);
    }

    // Step 4: Save the grades
    public function save() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'] ?? null;
            $matiere_id = $_POST['matiere_id'] ?? null;
            $sequence_id = $_POST['sequence_id'] ?? null;
            $type = $_POST['type'] ?? 'devoir';

            if (!$classe_id || !$matiere_id || !$sequence_id) {
                View::render('evaluations/error', [
                    'message' => _("Paramètres d'évaluation manquants."),
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }

            // Vérification centralisée anti-tampering avant sauvegarde
            $decision = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$sequence_id, (string)$type);
            if (!$decision['allowed']) {
                View::render('evaluations/error', [
                    'message' => $decision['reason'],
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }

            $data_to_save = [
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'sequence_id' => $sequence_id,
                'type' => $type,
                'coefficient' => $_POST['coefficient'] ?? 1,
                'enseignant_id' => Auth::getUserId(),
                'grades' => $_POST['grades'] ?? []
            ];

            Evaluation::saveGrades($data_to_save);
        }

        header('Location: /evaluations/select_class?success=grades_saved');
        exit();
    }

    public function directSaisie($classe_id, $matiere_id) {
        $this->checkAccess();

        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            View::render('evaluations/error', [
                'message' => _("Aucune année académique n'est actuellement active. Veuillez contacter l'administration."),
                'title' => 'Erreur de Configuration'
            ]);
            exit();
        }

        $open_sequences = Sequence::findOpenSequences();
        if (empty($open_sequences)) {
            View::render('evaluations/error', [
                'message' => _("Aucune séquence active n'est actuellement ouverte pour la saisie des notes. Veuillez contacter l'administration."),
                'title' => 'Saisie Fermée'
            ]);
            exit();
        }

        $target_sequence = null;
        $is_devoir_open = false;
        $is_composition_open = false;
        $lastDecision = null;

        foreach ($open_sequences as $seq) {
            $devDecision = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$seq['id'], 'devoir');
            $compDecision = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$seq['id'], 'composition');

            $dev_open = $devDecision['allowed'];
            $comp_open = $compDecision['allowed'];

            if ($dev_open || $comp_open) {
                $target_sequence = $seq;
                $is_devoir_open = $dev_open;
                $is_composition_open = $comp_open;
                break;
            } else {
                $lastDecision = $devDecision;
            }
        }

        if (!$target_sequence) {
            $target_sequence = $open_sequences[0];
            $errorMessage = $lastDecision['reason'] ?? sprintf(_("La période de saisie pour la séquence actuelle (%s) est fermée ou n'a pas encore commencé."), $target_sequence['nom']);
            View::render('evaluations/error', [
                'message' => $errorMessage,
                'title' => 'Saisie Fermée'
            ]);
            exit();
        }

        $sequence_id = $target_sequence['id'];

        $requested_type = $_GET['type'] ?? $_POST['type'] ?? null;
        if ($is_composition_open && !$is_devoir_open) {
            $type = 'composition';
        } elseif ($is_devoir_open && !$is_composition_open) {
            $type = 'devoir';
        } else {
            $type = ($requested_type === 'composition') ? 'composition' : 'devoir';
        }

        header("Location: /evaluations/form?classe_id=$classe_id&matiere_id=$matiere_id&sequence_id=$sequence_id&type=$type");
        exit();
    }
}
?>