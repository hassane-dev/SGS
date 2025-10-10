<?php

require_once __DIR__ . '/../models/Lycee.php';

class LyceeController {


    public function index() {
        if (!Auth::can('lycee', 'view_all')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/lycees/index.php';
    }

    public function create() {
        if (!Auth::can('lycee', 'create')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        require_once __DIR__ . '/../views/lycees/create.php';
    }

    public function store() {
        if (!Auth::can('lycee', 'create')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Lycee::save($_POST);
        }
        header('Location: /lycees');
        exit();
    }

    public function edit() {
        if (!Auth::can('lycee', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /lycees');
            exit();
        }
        $lycee = Lycee::findById($id);
        require_once __DIR__ . '/../views/lycees/edit.php';
    }

    public function update() {
        if (!Auth::can('lycee', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Lycee::save($_POST);
        }
        header('Location: /lycees');
        exit();
    }

    public function destroy() {
        if (!Auth::can('lycee', 'delete')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_POST['id'] ?? null;
        if ($id) {
            Lycee::delete($id);
        }
        header('Location: /lycees');
        exit();
    }
}
?>
