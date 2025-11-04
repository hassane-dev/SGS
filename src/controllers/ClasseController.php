<?php

require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/EnseignantMatiere.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class ClasseController {

    private function checkAccess($permission) {
        list($resource, $action) = explode(':', $permission);
        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess('class:view');
        $lycee_id = !Auth::can('view_all_lycees', 'system') ? Auth::getLyceeId() : null;
        $classes = Classe::findAll($lycee_id);
        View::render('classes/index', [
            'classes' => $classes,
            'title' => 'Gestion des Classes'
        ]);
    }

    public function show() {
        $this->checkAccess('class:view');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /classes');
            exit();
        }

        $classe = Classe::findById($id);
        if (!$classe) {
            http_response_code(404);
            View::render('errors/404');
            exit();
        }

        $this->checkOwnership($classe['lycee_id']);

        $assigned_matieres = Matiere::findByClassId($id);
        $all_matieres = Matiere::findAll();
        $enseignants = User::findTeachers($classe['lycee_id']);
        $teacher_assignments = EnseignantMatiere::findAssignmentsForClass($id);

        View::render('classes/show', [
            'classe' => $classe,
            'assigned_matieres' => $assigned_matieres,
            'all_matieres' => $all_matieres,
            'enseignants' => $enseignants,
            'teacher_assignments' => $teacher_assignments,
            'title' => 'Détails de la Classe'
        ]);
    }

    public function create() {
        $this->checkAccess('class:create');
        $cycles = Cycle::findAll();
        $lycees = Auth::can('view_all_lycees', 'system') ? Lycee::findAll() : [];
        View::render('classes/create', [
            'cycles' => $cycles,
            'lycees' => $lycees,
            'title' => 'Nouvelle Classe'
        ]);
    }

    public function store() {
        $this->checkAccess('class:create');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (!Auth::can('view_all_lycees', 'system')) {
                $data['lycee_id'] = Auth::getLyceeId();
            }
            Classe::save($data);
        }
        header('Location: /classes');
        exit();
    }

    public function edit() {
        $this->checkAccess('class:edit');
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /classes'); exit(); }

        $classe = Classe::findById($id);
        $this->checkOwnership($classe['lycee_id']);

        $cycles = Cycle::findAll();
        $lycees = Auth::can('view_all_lycees', 'system') ? Lycee::findAll() : [];
        View::render('classes/edit', [
            'classe' => $classe,
            'cycles' => $cycles,
            'lycees' => $lycees,
            'title' => 'Modifier la Classe'
        ]);
    }

    public function update() {
        $this->checkAccess('class:edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $classe = Classe::findById($data['id_classe']);
            $this->checkOwnership($classe['lycee_id']);
            $data['lycee_id'] = $classe['lycee_id'];
            Classe::save($data);
        }
        header('Location: /classes');
        exit();
    }

    public function destroy() {
        $this->checkAccess('class:delete');
        $id = $_POST['id'] ?? null;
        if ($id) {
            $classe = Classe::findById($id);
            if ($classe) {
                $this->checkOwnership($classe['lycee_id']);
                Classe::delete($id, $classe['lycee_id']);
            }
        }
        header('Location: /classes');
        exit();
    }

    public function assignMatiere() {
        $this->checkAccess('class:edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'];
            $classe = Classe::findById($classe_id);
            $this->checkOwnership($classe['lycee_id']);

            Matiere::assignToClass($_POST);
        }
        header('Location: /classes/show?id=' . $classe_id);
        exit();
    }

    public function removeMatiere() {
        $this->checkAccess('class:edit');
        $classe_id = $_GET['classe_id'];
        $classe_matiere_id = $_GET['id'];

        $classe = Classe::findById($classe_id);
        $this->checkOwnership($classe['lycee_id']);

        Matiere::removeFromClass($classe_matiere_id);

        header('Location: /classes/show?id=' . $classe_id);
        exit();
    }

    public function assignEnseignant() {
        $this->checkAccess('class:edit'); // Or a more specific permission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'];
            $matiere_id = $_POST['matiere_id'];
            $enseignant_id = $_POST['enseignant_id'];

            $classe = Classe::findById($classe_id);
            $this->checkOwnership($classe['lycee_id']);

            EnseignantMatiere::assign($enseignant_id, $classe_id, $matiere_id);
        }
        header('Location: /classes/show?id=' . $classe_id);
        exit();
    }

    public function unassignEnseignant() {
        $this->checkAccess('class:edit'); // Or a more specific permission
        $classe_id = $_GET['classe_id'];
        $assignment_id = $_GET['assignment_id'];

        $classe = Classe::findById($classe_id);
        $this->checkOwnership($classe['lycee_id']);

        EnseignantMatiere::unassign($assignment_id);

        header('Location: /classes/show?id=' . $classe_id);
        exit();
    }

    private function checkOwnership($resource_lycee_id) {
        if (!Auth::can('view_all_lycees', 'system') && $resource_lycee_id != Auth::getLyceeId()) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }
}
?>