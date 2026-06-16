<?php

require_once __DIR__ . '/../models/Deblocage.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class DeblocageController {

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

        $deblocages = Deblocage::findAll();

        View::render('evaluations/deblocage_index', [
            'deblocages' => $deblocages,
            'title' => 'Gestion des Déblocages des Notes'
        ]);
    }

    public function create() {
        $this->checkAccess();

        $classes = Classe::findByLycee(Auth::getLyceeId());
        $matieres = Matiere::findByLycee(Auth::getLyceeId());
        $enseignants = User::findTeachers(Auth::getLyceeId());
        $sequences = Sequence::findAll();

        View::render('evaluations/deblocage_form', [
            'classes' => $classes,
            'matieres' => $matieres,
            'enseignants' => $enseignants,
            'sequences' => $sequences,
            'title' => 'Nouveau Déblocage'
        ]);
    }

    public function store() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'type' => $_POST['type'],
                'classe_id' => !empty($_POST['classe_id']) ? $_POST['classe_id'] : null,
                'matiere_id' => !empty($_POST['matiere_id']) ? $_POST['matiere_id'] : null,
                'enseignant_id' => !empty($_POST['enseignant_id']) ? $_POST['enseignant_id'] : null,
                'sequence_id' => !empty($_POST['sequence_id']) ? $_POST['sequence_id'] : null,
                'type_evaluation' => $_POST['type_evaluation'] ?? 'tous',
                'date_debut' => $_POST['date_debut'],
                'date_fin' => $_POST['date_fin'],
                'motif' => $_POST['motif']
            ];

            if (Deblocage::save($data)) {
                header('Location: /evaluations/deblocage?success=deblocage_created');
            } else {
                header('Location: /evaluations/deblocage/create?error=deblocage_failed');
            }
            exit();
        }
    }

    public function delete() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if ($id && Deblocage::delete($id)) {
            header('Location: /evaluations/deblocage?success=deblocage_deleted');
        } else {
            header('Location: /evaluations/deblocage?error=delete_failed');
        }
        exit();
    }
}
