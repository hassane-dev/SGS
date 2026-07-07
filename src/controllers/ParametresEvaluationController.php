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
        list($resource, $action) = explode(':', $permission);
        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();

        $params = ParametresEvaluation::findAll();

        View::render('evaluations/parametres_index', [
            'params' => $params,
            'title' => 'Gestion des Périodes de Saisie'
        ]);
    }

    public function create() {
        $this->checkAccess();

        $classes = Classe::findAll(Auth::getLyceeId());
        $matieres = Matiere::findAll();
        $enseignants = User::findTeachers(Auth::getLyceeId());
        $sequences = Sequence::findAll();

        View::render('evaluations/parametres_form', [
            'classes' => $classes,
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'sequences' => $sequences,
            'title' => 'Nouveaux Paramètres de Saisie'
        ]);
    }

    public function store() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type'];

            // Basic validation based on type
            if ($type === 'classe' && empty($_POST['classe_id'])) {
                header('Location: /evaluations/settings/create?error=missing_classe');
                exit();
            }
            if ($type === 'matiere' && empty($_POST['matiere_id'])) {
                header('Location: /evaluations/settings/create?error=missing_matiere');
                exit();
            }
            if (($type === 'classe_matiere' || $type === 'enseignant') && (empty($_POST['classe_id']) || empty($_POST['matiere_id']))) {
                header('Location: /evaluations/settings/create?error=missing_classe_or_matiere');
                exit();
            }

            $data = [
                'type' => $type,
                'classe_id' => !empty($_POST['classe_id']) ? $_POST['classe_id'] : null,
                'matiere_id' => !empty($_POST['matiere_id']) ? $_POST['matiere_id'] : null,
                'enseignant_id' => !empty($_POST['enseignant_id']) ? $_POST['enseignant_id'] : null,
                'sequence_id' => !empty($_POST['sequence_id']) ? $_POST['sequence_id'] : null,
                'type_evaluation' => $_POST['type_evaluation'] ?? 'tous',
                'date_ouverture_saisie' => $_POST['date_ouverture'],
                'date_fermeture_saisie' => $_POST['date_fermeture'],
                'commentaire' => $_POST['commentaire']
            ];

            if (ParametresEvaluation::save($data)) {
                header('Location: /evaluations/settings?success=settings_created');
            } else {
                header('Location: /evaluations/settings/create?error=settings_failed');
            }
            exit();
        }
    }

    public function delete() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if ($id && ParametresEvaluation::delete($id)) {
            header('Location: /evaluations/settings?success=settings_deleted');
        } else {
            header('Location: /evaluations/settings?error=delete_failed');
        }
        exit();
    }

    // Keep compatibility for legacy class-subject settings view if needed,
    // but update it to use the new model logic.
    public function legacySettings() {
        $this->checkAccess();

        $classe_id = $_GET['classe_id'] ?? null;
        $matiere_id = $_GET['matiere_id'] ?? null;

        if (!$classe_id || !$matiere_id) {
            header('Location: /classes');
            exit();
        }

        $classe = Classe::findById($classe_id);
        $matiere = Matiere::findById($matiere_id);

        if (!$classe || !$matiere || $classe['lycee_id'] != Auth::getLyceeId() || $matiere['lycee_id'] != Auth::getLyceeId()) {
             http_response_code(404);
             View::render('errors/404');
             exit();
        }

        $sequences = Sequence::findAll();
        $teacher_assignments = EnseignantMatiere::findAssignmentsForClass($classe_id);
        $existing_settings = ParametresEvaluation::findByClassAndMatiere($classe_id, $matiere_id);

        $enseignant_id = $teacher_assignments[$matiere_id]['enseignant_id'] ?? null;
        if(!$enseignant_id) {
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

    public function saveLegacy() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'];
            $matiere_id = $_POST['matiere_id'];
            $settings = $_POST['settings'];

            foreach ($settings as $sequence_id => $types_data) {
                foreach($types_data as $type => $data) {
                    if (!empty($data['date_ouverture']) && !empty($data['date_fermeture'])) {
                        ParametresEvaluation::save([
                            'type' => 'enseignant',
                            'classe_id' => $classe_id,
                            'matiere_id' => $matiere_id,
                            'sequence_id' => $sequence_id,
                            'type_evaluation' => $type,
                            'enseignant_id' => $_POST['enseignant_id'],
                            'date_ouverture_saisie' => $data['date_ouverture'],
                            'date_fermeture_saisie' => $data['date_fermeture'],
                            'commentaire' => $data['commentaire']
                        ]);
                    }
                }
            }
        }

        header('Location: /classes/show?id=' . $classe_id . '&success=settings_saved');
        exit();
    }
}
?>