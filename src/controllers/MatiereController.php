<?php

require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class MatiereController {

    private function checkAccess($permission) {
        if (!Auth::can($permission)) {
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
        View::render('matieres/create', [
            'title' => 'Nouvelle Matière'
        ]);
    }

    public function store() {
        $this->checkAccess('matiere:create');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Simple validation
            if (empty($_POST['nom_matiere']) || empty($_POST['statut'])) {
                 View::render('matieres/create', [
                    'title' => 'Nouvelle Matière',
                    'error' => 'Veuillez remplir tous les champs obligatoires.'
                ]);
                return;
            }
            Matiere::save($_POST);
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
        View::render('matieres/edit', [
            'matiere' => $matiere,
            'title' => 'Modifier la Matière'
        ]);
    }

    public function update() {
        $this->checkAccess('matiere:edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             if (empty($_POST['nom_matiere']) || empty($_POST['statut'])) {
                $matiere = Matiere::findById($_POST['id_matiere']);
                View::render('matieres/edit', [
                    'matiere' => $matiere,
                    'title' => 'Modifier la Matière',
                    'error' => 'Veuillez remplir tous les champs obligatoires.'
                ]);
                return;
            }
            Matiere::save($_POST);
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