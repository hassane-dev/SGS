<?php

require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../models/Cycle.php';

class MatiereController {

    private function checkAccess($permission) {
        list($resource, $action) = explode(':', $permission);
        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess('matiere:view');
        $matieres = Matiere::findAll();
        View::render('matieres/index', [
            'matieres' => $matieres,
            'title' => 'Gestion des Matières'
        ]);
    }

    public function create() {
        $this->checkAccess('matiere:create');
        $cycles = Cycle::findAll();
        View::render('matieres/create', [
            'title' => 'Nouvelle Matière',
            'cycles' => $cycles
        ]);
    }

    public function store() {
        $this->checkAccess('matiere:create');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            // Simple validation
            if (empty($data['nom_matiere']) || empty($data['statut'])) {
                 $cycles = Cycle::findAll();
                 View::render('matieres/create', [
                    'title' => 'Nouvelle Matière',
                    'error' => 'Veuillez remplir tous les champs obligatoires.',
                    'matiere' => $data,
                    'cycles' => $cycles
                ]);
                return;
            }
            Matiere::save($data);
        }
        header('Location: /matieres');
        exit();
    }

    public function edit() {
        $this->checkAccess('matiere:edit');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /matieres');
            exit();
        }
        $matiere = Matiere::findById($id);
        if (!$matiere) {
            http_response_code(404);
            View::render('errors/404');
            exit();
        }
        $cycles = Cycle::findAll();
        View::render('matieres/edit', [
            'matiere' => $matiere,
            'title' => 'Modifier la Matière',
            'cycles' => $cycles
        ]);
    }

    public function update() {
        $this->checkAccess('matiere:edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
             if (empty($data['nom_matiere']) || empty($data['statut'])) {
                $cycles = Cycle::findAll();
                View::render('matieres/edit', [
                    'matiere' => $data,
                    'title' => 'Modifier la Matière',
                    'error' => 'Veuillez remplir tous les champs obligatoires.',
                    'cycles' => $cycles
                ]);
                return;
            }
            Matiere::save($data);
        }
        header('Location: /matieres');
        exit();
    }

    public function destroy() {
        $this->checkAccess('matiere:delete');
        $id = $_POST['id'] ?? null;
        if ($id) {
            $success = Matiere::delete($id);
            if (!$success) {
                // This could be because the subject is in use.
                // Redirect with an error message.
                header('Location: /matieres?error=delete_failed');
                exit();
            }
        }
        header('Location: /matieres');
        exit();
    }
}
?>