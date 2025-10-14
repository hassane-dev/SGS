<?php

require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../core/Auth.php';

class ClasseController {

    public function index() {
        if (!Auth::can('view_classes')) { $this->forbidden(); }

        $lycee_id = !Auth::can('view_all_lycees') ? Auth::get('lycee_id') : null;
        $classes = Classe::findAll($lycee_id);
        require_once __DIR__ . '/../views/classes/index.php';
    }

    public function create() {
        if (!Auth::can('create_classes')) { $this->forbidden(); }

        $cycles = Cycle::findAll();
        $lycees = Auth::can('view_all_lycees') ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/classes/create.php';
    }

    public function store() {
        if (!Auth::can('create_classes')) { $this->forbidden(); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::can('view_all_lycees')) {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            Classe::save($_POST);
        }
        header('Location: /classes');
        exit();
    }

    public function edit() {
        if (!Auth::can('edit_classes')) { $this->forbidden(); }

        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /classes'); exit(); }

        $classe = Classe::findById($id);
        if (!Auth::can('view_all_lycees') && $classe['lycee_id'] != Auth::get('lycee_id')) {
            $this->forbidden();
        }

        $cycles = Cycle::findAll();
        $lycees = Auth::can('view_all_lycees') ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/classes/edit.php';
    }

    public function update() {
        if (!Auth::can('edit_classes')) { $this->forbidden(); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe = Classe::findById($_POST['id_classe']);
            if (!Auth::can('view_all_lycees') && $classe['lycee_id'] != Auth::get('lycee_id')) {
                $this->forbidden();
            }
            // Ensure lycee_id is not maliciously changed
            $_POST['lycee_id'] = $classe['lycee_id'];
            Classe::save($_POST);
        }
        header('Location: /classes');
        exit();
    }

    public function destroy() {
        if (!Auth::can('delete_classes')) { $this->forbidden(); }

        $id = $_POST['id'] ?? null;
        if ($id) {
            $classe = Classe::findById($id);
            if ($classe) {
                if (!Auth::can('view_all_lycees') && $classe['lycee_id'] != Auth::get('lycee_id')) {
                    $this->forbidden();
                }
                Classe::delete($id, $classe['lycee_id']);
            }
        }
        header('Location: /classes');
        exit();
    }

    private function forbidden() {
        http_response_code(403);
        echo "Accès Interdit.";
        exit();
    }
}
?>