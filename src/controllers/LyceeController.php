<?php

require_once __DIR__ . '/../models/Lycee.php';

class LyceeController {

    private function checkAdmin() {
        if (!Auth::check() || Auth::get('role') !== 'super_admin_national') {
            http_response_code(403);
            echo "Accès Interdit. Vous devez être un super administrateur national.";
            exit();
        }
    }

    public function index() {
        $this->checkAdmin();
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/lycees/index.php';
    }

    public function create() {
        $this->checkAdmin();
        require_once __DIR__ . '/../views/lycees/create.php';
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Lycee::save($_POST);
        }
        header('Location: /lycees');
        exit();
    }

    public function edit() {
        $this->checkAdmin();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /lycees');
            exit();
        }
        $lycee = Lycee::findById($id);
        require_once __DIR__ . '/../views/lycees/edit.php';
    }

    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Lycee::save($_POST);
        }
        header('Location: /lycees');
        exit();
    }

    public function destroy() {
        $this->checkAdmin();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Lycee::delete($id);
        }
        header('Location: /lycees');
        exit();
    }
}
?>
