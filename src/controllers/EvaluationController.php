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
        } elseif ($is_devoir_available && $is_comp_available) {
            if ($requested_type === 'composition') {
                $type = 'composition';
                $available_evaluations = $evals_comp;
            } elseif ($requested_type === 'devoir') {
                $type = 'devoir';
                $available_evaluations = $evals_devoir;
            } else {
                $type = 'devoir';
                $available_evaluations = $evals_devoir;
            }
        } else {
            $type = ($requested_type === 'composition') ? 'composition' : 'devoir';
            $available_evaluations = [];
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
        $raw_type = $_GET['type'] ?? null;

        if (!$classe_id || !$matiere_id || !$sequence_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        // Délégation centralisée à EvaluationSaisieService pour les types autorisés
        $allowedTypes = EvaluationSaisieService::getAllowedEvaluationTypes((int)$classe_id, (int)$matiere_id, (int)$sequence_id);
        $is_devoir_open = in_array('devoir', $allowedTypes, true);
        $is_composition_open = in_array('composition', $allowedTypes, true);

        if (empty($allowedTypes)) {
            $devDecision = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$sequence_id, 'devoir');
            View::render('evaluations/error', [
                'message' => $devDecision['reason'],
                'title' => 'Accès Refusé'
            ]);
            exit();
        }

        // Détermination stricte du type selon le paramétrage
        $type = null;
        if ($raw_type !== null) {
            if (!in_array($raw_type, ['devoir', 'composition'], true)) {
                View::render('evaluations/error', [
                    'message' => sprintf(_("Type d'évaluation invalide '%s'."), htmlspecialchars($raw_type)),
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }
            if (!in_array($raw_type, $allowedTypes, true)) {
                View::render('evaluations/error', [
                    'message' => sprintf(_("La saisie pour le type '%s' n'est pas autorisée par le paramétrage de l'évaluation."), $raw_type),
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }
            $type = $raw_type;
        } else {
            // Aucun type n'est fourni
            if (count($allowedTypes) === 1) {
                $type = $allowedTypes[0];
            } else {
                // Pour 'tous' (les deux sont autorisés), le système ne doit pas imposer 'devoir' silencieusement:
                // Rediriger vers selectEvaluation pour présenter le choix Devoir / Composition.
                header("Location: /evaluations/select_evaluation?classe_id=$classe_id&matiere_id=$matiere_id");
                exit();
            }
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
            $type = $_POST['type'] ?? null;

            if (!$classe_id || !$matiere_id || !$sequence_id || !$type) {
                View::render('evaluations/error', [
                    'message' => _("Paramètres d'évaluation manquants."),
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }

            // Vérification stricte de la valeur du type
            if (!in_array($type, ['devoir', 'composition'], true)) {
                View::render('evaluations/error', [
                    'message' => sprintf(_("Type d'évaluation invalide '%s'."), htmlspecialchars((string)$type)),
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
        $allowed_types = [];
        $lastDecision = null;

        foreach ($open_sequences as $seq) {
            $allowed = EvaluationSaisieService::getAllowedEvaluationTypes((int)$classe_id, (int)$matiere_id, (int)$seq['id']);
            if (!empty($allowed)) {
                $target_sequence = $seq;
                $allowed_types = $allowed;
                break;
            } else {
                $lastDecision = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$seq['id'], 'devoir');
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
        $raw_requested = $_GET['type'] ?? $_POST['type'] ?? null;

        if (count($allowed_types) === 1) {
            $type = $allowed_types[0];
            header("Location: /evaluations/form?classe_id=$classe_id&matiere_id=$matiere_id&sequence_id=$sequence_id&type=$type");
            exit();
        } else {
            // cas 'tous'
            if ($raw_requested !== null && in_array($raw_requested, $allowed_types, true)) {
                $type = $raw_requested;
                header("Location: /evaluations/form?classe_id=$classe_id&matiere_id=$matiere_id&sequence_id=$sequence_id&type=$type");
                exit();
            } else {
                // Solliciter le choix de l'enseignant au lieu de supposer 'devoir'
                header("Location: /evaluations/select_evaluation?classe_id=$classe_id&matiere_id=$matiere_id");
                exit();
            }
        }
    }
}
?>