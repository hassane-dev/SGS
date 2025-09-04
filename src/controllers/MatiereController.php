<?php

require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Classe.php';

class MatiereController {

    private function checkAdmin() {
        if (!Auth::check() || !in_array(Auth::get('role'), ['admin_local', 'super_admin_national'])) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    // --- Standard CRUD for Matieres ---

    public function index() {
        $this->checkAdmin();
        $matieres = Matiere::findAll();
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../views/matieres/index.php';
    }

    public function create() {
        $this->checkAdmin();
        require_once __DIR__ . '/../views/matieres/create.php';
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Matiere::save($_POST);
        }
        header('Location: /matieres');
        exit();
    }

    public function edit() {
        $this->checkAdmin();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /matieres');
            exit();
        }
        $matiere = Matiere::findById($id);
        require_once __DIR__ . '/../views/matieres/edit.php';
    }

    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Matiere::save($_POST);
        }
        header('Location: /matieres');
        exit();
    }

    public function destroy() {
        $this->checkAdmin();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $success = Matiere::delete($id);
            if (!$success) {
                header('Location: /matieres?error=delete_failed');
                exit();
            }
        }
        header('Location: /matieres');
        exit();
    }

    // --- Association with Classes ---

    public function assign() {
        $this->checkAdmin();
        $class_id = $_GET['class_id'] ?? null;
        if (!$class_id) {
            header('Location: /classes');
            exit();
        }

        $classe = Classe::findById($class_id);
        $all_matieres = Matiere::findAll();
        $assigned_matieres_raw = Matiere::findByClassId($class_id);

        // Create an array of just the IDs for easy checking in the view
        $assigned_matieres_ids = array_column($assigned_matieres_raw, 'id_matiere');

        require_once __DIR__ . '/../views/matieres/assign.php';
    }

    public function updateAssignments() {
        $this->checkAdmin();
        $class_id = $_POST['class_id'] ?? null;
        $assigned_ids = $_POST['matieres'] ?? [];

        if (!$class_id) {
            header('Location: /classes');
            exit();
        }

        // Get currently assigned subjects
        $current_raw = Matiere::findByClassId($class_id);
        $current_ids = array_column($current_raw, 'id_matiere');

        // Subjects to add
        $to_add = array_diff($assigned_ids, $current_ids);
        foreach ($to_add as $matiere_id) {
            Matiere::assignToClass($class_id, $matiere_id);
        }

        // Subjects to remove
        $to_remove = array_diff($current_ids, $assigned_ids);
        foreach ($to_remove as $matiere_id) {
            Matiere::removeFromClass($class_id, $matiere_id);
        }

        header('Location: /classes');
        exit();
    }
}
?>
