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

    // Step 2: Redirects to directSaisie for automatic server context resolution
    public function selectEvaluation() {
        $this->checkAccess();
        $classe_id = $_POST['classe_id'] ?? $_GET['classe_id'] ?? null;
        $matiere_id = $_POST['matiere_id'] ?? $_GET['matiere_id'] ?? null;

        if (!$classe_id || !$matiere_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        $this->directSaisie((int)$classe_id, (int)$matiere_id);
    }

    // Step 3: Show the grading form
    public function showForm() {
        $this->checkAccess();
        $classe_id = $_GET['classe_id'] ?? null;
        $matiere_id = $_GET['matiere_id'] ?? null;
        $requested_sequence_id = (int)($_GET['sequence_id'] ?? 0);
        $requested_type = $_GET['type'] ?? null;
        $numero = (int)($_GET['numero'] ?? 1);

        if (!$classe_id || !$matiere_id) {
            header('Location: /evaluations/select_class');
            exit();
        }

        // Server resolves allowed types for active sequence
        $allowedTypes = EvaluationSaisieService::getAllowedEvaluationTypes((int)$classe_id, (int)$matiere_id, $requested_sequence_id);

        if (empty($allowedTypes)) {
            View::render('evaluations/error', [
                'message' => _("Aucune période de saisie n'est actuellement ouverte pour cette classe et cette matière."),
                'title' => 'Saisie Fermée'
            ]);
            exit();
        }

        if (!empty($requested_type)) {
            if (!in_array($requested_type, $allowedTypes, true)) {
                View::render('evaluations/error', [
                    'message' => sprintf(_("La saisie pour le type '%s' n'est pas autorisée dans la période actuelle."), htmlspecialchars($requested_type)),
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }
            $type = $requested_type;
        } else {
            $type = $allowedTypes[0];
        }

        // Strict Server Context Validation: Sequence, Type and Occurrence must be authorized
        $decision = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, $requested_sequence_id, (string)$type, null, null, null, true, $numero);
        if (!$decision['allowed']) {
            View::render('evaluations/error', [
                'message' => $decision['reason'],
                'title' => 'Accès Refusé'
            ]);
            exit();
        }

        $serverSequenceId = (int)$decision['context']['sequence_id'];
        $serverTypeCode = (string)$decision['context']['type_code'];

        $typeRec = ParamTypeEvaluation::findByCode($serverTypeCode);
        $maxOccurrences = (int)($typeRec['nombre_evaluation'] ?? 1);

        if ($numero < 1 || $numero > $maxOccurrences) {
            View::render('evaluations/error', [
                'message' => sprintf(_("L'occurrence N°%d n'est pas autorisée pour le type '%s' (Maximum autorisé pour votre établissement : %d)."), $numero, htmlspecialchars($typeRec['libelle'] ?? $serverTypeCode), $maxOccurrences),
                'title' => 'Accès Refusé'
            ]);
            exit();
        }

        $baremeDefault = (!empty($typeRec['bareme_defaut']) && (float)$typeRec['bareme_defaut'] > 0) ? (float)$typeRec['bareme_defaut'] : 20.00;

        $eleves = Eleve::findByClass($classe_id);
        $existing_grades = Evaluation::getGradesForEvaluation($classe_id, $matiere_id, $serverSequenceId, $serverTypeCode, $numero);

        $classe_matiere_details = Classe::findMatiereDetails($classe_id, $matiere_id);

        View::render('evaluations/form', [
            'classe' => Classe::findById($classe_id),
            'matiere' => Matiere::findById($matiere_id),
            'sequence_id' => $serverSequenceId,
            'active_sequence' => Sequence::findById($serverSequenceId),
            'type' => $serverTypeCode,
            'type_rec' => $typeRec,
            'numero_evaluation' => $numero,
            'max_occurrences' => $maxOccurrences,
            'bareme_defaut' => $baremeDefault,
            'allowed_types' => $allowedTypes,
            'coefficient' => $classe_matiere_details['coefficient'] ?? 1,
            'eleves' => $eleves,
            'grades' => $existing_grades,
            'title' => 'Saisie des Notes - ' . ($typeRec['libelle'] ?? ucfirst($serverTypeCode)) . ' ' . $numero
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

            $numeroEval = (int)($_POST['numero_evaluation'] ?? 1);

            // Centralized anti-tampering verification
            $decision = EvaluationSaisieService::canTeacherGradeContext((int)$classe_id, (int)$matiere_id, (int)$sequence_id, (string)$type, null, null, null, true, $numeroEval);
            if (!$decision['allowed']) {
                View::render('evaluations/error', [
                    'message' => $decision['reason'],
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }

            $numeroEval = (int)($_POST['numero_evaluation'] ?? 1);
            $typeRec = ParamTypeEvaluation::findByCode($type);
            $maxOccurrences = (int)($typeRec['nombre_evaluation'] ?? 1);

            if ($numeroEval < 1 || $numeroEval > $maxOccurrences) {
                View::render('evaluations/error', [
                    'message' => sprintf(_("L'occurrence N°%d n'est pas autorisée pour le type '%s' (Maximum autorisé pour votre établissement : %d)."), $numeroEval, htmlspecialchars($typeRec['libelle'] ?? $type), $maxOccurrences),
                    'title' => 'Accès Refusé'
                ]);
                exit();
            }

            $data_to_save = [
                'classe_id' => $classe_id,
                'matiere_id' => $matiere_id,
                'sequence_id' => $sequence_id,
                'type' => $type,
                'numero_evaluation' => $numeroEval,
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

        // Server resolves allowed types for active sequence (0 triggers auto-resolution)
        $allowed_types = EvaluationSaisieService::getAllowedEvaluationTypes((int)$classe_id, (int)$matiere_id, 0);

        if (empty($allowed_types)) {
            View::render('evaluations/error', [
                'message' => _("Aucune période de saisie des notes n'est actuellement ouverte pour ce cours."),
                'title' => 'Saisie Fermée'
            ]);
            exit();
        }

        $type = $allowed_types[0];
        header("Location: /evaluations/form?classe_id=$classe_id&matiere_id=$matiere_id&type=$type&numero=1");
        exit();
    }
}
?>