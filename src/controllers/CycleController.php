<?php

require_once __DIR__ . '/../models/Cycle.php';

class CycleController {

    private function checkAdmin() {
        // Allow local admins and national super admins
        if (!Auth::check() || !in_array(Auth::get('role'), ['admin_local', 'super_admin_national'])) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAdmin();
        $cycles = Cycle::findAll();
        // Pass a potential error message from delete action
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../views/cycles/index.php';
    }

    public function create() {
        $this->checkAdmin();
        require_once __DIR__ . '/../views/cycles/create.php';
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Cycle::save($_POST);
        }
        header('Location: /cycles');
        exit();
    }

    public function edit() {
        $this->checkAdmin();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /cycles');
            exit();
        }
        $cycle = Cycle::findById($id);
        require_once __DIR__ . '/../views/cycles/edit.php';
    }

    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Cycle::save($_POST);
        }
        header('Location: /cycles');
        exit();
    }

    public function destroy() {
        $this->checkAdmin();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $success = Cycle::delete($id);
            if (!$success) {
                // Redirect with an error message if delete fails (e.g., foreign key constraint)
                header('Location: /cycles?error=delete_failed');
                exit();
            }
        }
        header('Location: /cycles');
        exit();
    }
}
?>
