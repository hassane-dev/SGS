<?php

require_once __DIR__ . '/../models/Lycee.php';

class LyceeController {

    private function checkAccess() {
        if (!Auth::can('system_view_all_lycees')) {
            http_response_code(403);
            echo "Accès Interdit. Vous devez être un super administrateur national.";
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
            Lycee::save($_POST);
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
            Lycee::save($_POST);
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
