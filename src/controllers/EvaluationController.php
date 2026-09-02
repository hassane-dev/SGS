<?php

require_once __DIR__ . '/../models/Evaluation.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/AffectationPedagogique.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/ParamTypeEvaluation.php';
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

        $subjects_taught = User::findSubjectsTaughtByTeacher($enseignant_id);

        View::render('evaluations/select_class', [
            'subjects_taught' => $subjects_taught,
            'title' => 'Saisie des Notes - Étape 1/2'
        ]);
    }

    // Step 2: Teacher selects the specific evaluation type & sequence
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

        $active_year = AnneeAcademique::findActive();
        $sequences = Sequence::findOpenSequences();
        $active_types = ParamTypeEvaluation::findActive();

        if (empty($active_types)) {
            $active_types = [
                ['id' => 1, 'code' => 'devoir', 'libelle' => 'Devoir', 'bareme_defaut' => 20.00],
                ['id' => 2, 'code' => 'composition', 'libelle' => 'Composition', 'bareme_defaut' => 20.00]
            ];
        }

        // Resolve allowed types across open sequences
        $allowed_type_codes = [];
        $sequence_id = !empty($sequences) ? $sequences[0]['id'] : null;

        if ($sequence_id) {
            $allowed_type_codes = EvaluationSaisieService::getAllowedEvaluationTypes((int)$classe_id, (int)$matiere_id, (int)$sequence_id);
        }

        $selected_type = null;
        if ($requested_type && in_array($requested_type, $allowed_type_codes, true)) {
            $selected_type = $requested_type;
        } elseif (!empty($allowed_type_codes)) {
            $selected_type = $allowed_type_codes[0];
        } else {
            $selected_type = 'devoir';
        }

        View::render('evaluations/select_evaluation', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'type' => $selected_type,
            'active_types' => $active_types,
            'allowed_types' => $allowed_type_codes,
            'sequences' => $sequences,
            'title' => 'Saisie des Notes - Choix de l\'Évaluation'
        ]);
    }

    // Step 3: Show the grading form
    public function showForm() {
        $this->checkAccess();
        $classe_id = $_GET['classe_id'] ?? null;
        $matiere_id = $_GET['matiere_id'] ?? null;
        $sequence_id = $_GET['sequence_id'] ?? null;
        $raw_type = $_GET['type'] ?? null;
        $numero = (int)($_GET['numero'] ?? 1);

        if (!$classe_id || !$matiere_id || !$sequence_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        $allowedTypes = EvaluationSaisieService::getAllowedEvaluationTypes((int)$classe_id, (int)$matiere_id, (int)$sequence_id);

        if (empty($allowedTypes)) {
            $devDecision = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$sequence_id, 'devoir');
            View::render('evaluations/error', [
                'message' => $devDecision['reason'],
                'title' => 'Accès Refusé'
            ]);
            exit();
        }

        $type = null;
        if ($raw_type !== null) {
            if (!in_array($raw_type, $allowedTypes, true)) {
                View::render('evaluations/error', [
                    'message' => sprintf(_("La saisie pour le type '%s' n'est pas autorisée par le paramétrage de l'évaluation."), htmlspecialchars($raw_type)),
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }
            $type = $raw_type;
        } else {
            if (count($allowedTypes) === 1) {
                $type = $allowedTypes[0];
            } else {
                header("Location: /evaluations/select_evaluation?classe_id=$classe_id&matiere_id=$matiere_id");
                exit();
            }
        }

        $typeRec = ParamTypeEvaluation::findByCode($type);
        $baremeDefault = (!empty($typeRec['bareme_defaut']) && (float)$typeRec['bareme_defaut'] > 0) ? (float)$typeRec['bareme_defaut'] : 20.00;

        $eleves = Eleve::findByClass($classe_id);
        $existing_grades = Evaluation::getGradesForEvaluation($classe_id, $matiere_id, $sequence_id, $type, $numero);

        $classe_matiere_details = Classe::findMatiereDetails($classe_id, $matiere_id);

        View::render('evaluations/form', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'sequence_id' => $sequence_id,
            'active_sequence' => Sequence::findById($sequence_id),
            'type' => $type,
            'type_rec' => $typeRec,
            'numero_evaluation' => $numero,
            'bareme_defaut' => $baremeDefault,
            'allowed_types' => $allowedTypes,
            'coefficient' => $classe_matiere_details['coefficient'] ?? 1,
            'eleves' => $eleves,
            'grades' => $existing_grades,
            'title' => 'Saisie des Notes - ' . ($typeRec['libelle'] ?? ucfirst($type)) . ' ' . $numero
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

            // Centralized anti-tampering verification
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
                'numero_evaluation' => (int)($_POST['numero_evaluation'] ?? 1),
                'libelle_evaluation' => !empty($_POST['libelle_evaluation']) ? trim($_POST['libelle_evaluation']) : null,
                'bareme' => (!empty($_POST['bareme']) && (float)$_POST['bareme'] > 0) ? (float)$_POST['bareme'] : 20.00,
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
            if ($raw_requested !== null && in_array($raw_requested, $allowed_types, true)) {
                $type = $raw_requested;
                header("Location: /evaluations/form?classe_id=$classe_id&matiere_id=$matiere_id&sequence_id=$sequence_id&type=$type");
                exit();
            } else {
                header("Location: /evaluations/select_evaluation?classe_id=$classe_id&matiere_id=$matiere_id");
                exit();
            }
        }
    }
}
?>