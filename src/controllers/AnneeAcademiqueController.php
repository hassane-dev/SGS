<?php

require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/View.php';

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
        View::render('annees_academiques/index', [
            'title' => 'Années Académiques',
            'annees' => $annees
        ]);
    }

    public function create() {
        $this->checkAccess();
        View::render('annees_academiques/create', ['title' => 'Créer une Année Académique']);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AnneeAcademique::save($_POST);
        }
        header('Location: /annees-academiques');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /annees-academiques'); exit(); }
        $annee = AnneeAcademique::findById($id);
        View::render('annees_academiques/edit', [
            'title' => 'Modifier l\'Année Académique',
            'annee' => $annee
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AnneeAcademique::save($_POST);
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