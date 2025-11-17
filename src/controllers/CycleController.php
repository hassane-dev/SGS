<?php

require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Classe.php'; // Required for fetching levels
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Auth.php';     // Required for getting lycee_id

class CycleController {

    private function checkAccess() {
        if (!Auth::can('manage', 'cycle')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $cycles = Cycle::findAll();
        // Pass a potential error message from delete action
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../views/cycles/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = Auth::getLyceeId();
        $niveaux = Classe::getDistinctNiveaux($lycee_id);
        require_once __DIR__ . '/../views/cycles/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Cycle::save($data);
        }
        header('Location: /cycles');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /cycles');
            exit();
        }
        $cycle = Cycle::findById($id);
        require_once __DIR__ . '/../views/cycles/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Cycle::save($data);
        }
        header('Location: /cycles');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
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
