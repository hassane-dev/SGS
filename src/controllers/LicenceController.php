<?php

require_once __DIR__ . '/../models/Licence.php';
require_once __DIR__ . '/../models/Lycee.php';

class LicenceController {

    private function checkCreator() {
        if (!Auth::check() || Auth::get('role') !== 'super_admin_createur') {
            http_response_code(403);
            echo "Accès Interdit. Cette section est réservée au créateur de l'application.";
            exit();
        }
    }

    public function index() {
        $this->checkCreator();
        $licences = Licence::findAll();
        require_once __DIR__ . '/../views/licences/index.php';
    }

    public function create() {
        $this->checkCreator();
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/licences/create.php';
    }

    public function store() {
        $this->checkCreator();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Licence::save($_POST);
        }
        header('Location: /licences');
        exit();
    }

    public function edit() {
        $this->checkCreator();
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
        $this->checkCreator();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Licence::save($_POST);
        }
        header('Location: /licences');
        exit();
    }

    public function destroy() {
        $this->checkCreator();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Licence::delete($id);
        }
        header('Location: /licences');
        exit();
    }
}
?>
