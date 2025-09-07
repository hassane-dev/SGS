<?php

require_once __DIR__ . '/../models/Licence.php';
require_once __DIR__ . '/../models/Lycee.php';

class LicenceController {

    private function checkAccess() {
        if (!Auth::can('manage_licences')) {
            http_response_code(403);
            echo "Accès Interdit. Cette section est réservée au créateur de l'application.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $licences = Licence::findAll();
        require_once __DIR__ . '/../views/licences/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/licences/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Licence::save($_POST);
        }
        header('Location: /licences');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /licences');
            exit();
        }
        $licence = Licence::findById($id);
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/licences/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Licence::save($_POST);
        }
        header('Location: /licences');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Licence::delete($id);
        }
        header('Location: /licences');
        exit();
    }
}
?>
