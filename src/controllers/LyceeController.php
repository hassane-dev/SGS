<?php

require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../core/Validator.php';

class LyceeController {

    private function checkAccess() {
        if (!Auth::can('view_all_lycees', 'lycee')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/lycees/index.php';
    }

    public function create() {
        $this->checkAccess();
        require_once __DIR__ . '/../views/lycees/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Lycee::save($data);
        }
        header('Location: /lycees');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /lycees');
            exit();
        }
        $lycee = Lycee::findById($id);
        require_once __DIR__ . '/../views/lycees/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Lycee::save($data);
        }
        header('Location: /lycees');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Lycee::delete($id);
        }
        header('Location: /lycees');
        exit();
    }
}
?>
