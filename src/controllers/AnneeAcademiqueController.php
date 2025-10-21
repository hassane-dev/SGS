<?php

require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Validator.php';

class AnneeAcademiqueController {

    private function checkAccess() {
        // For now, only super admins can manage academic years
        if (!Auth::can('system', 'view_all_lycees')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $annees = AnneeAcademique::findAll();
        require_once __DIR__ . '/../views/annees_academiques/index.php';
    }

    public function create() {
        $this->checkAccess();
        require_once __DIR__ . '/../views/annees_academiques/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            AnneeAcademique::save($data);
        }
        header('Location: /annees-academiques');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /annees-academiques'); exit(); }
        $annee = AnneeAcademique::findById($id);
        require_once __DIR__ . '/../views/annees_academiques/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            AnneeAcademique::save($data);
        }
        header('Location: /annees-academiques');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            AnneeAcademique::delete($id);
        }
        header('Location: /annees-academiques');
        exit();
    }

    public function activate() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            AnneeAcademique::setActive($id);
        }
        header('Location: /annees-academiques');
        exit();
    }
}
?>