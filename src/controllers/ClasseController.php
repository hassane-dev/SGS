<?php

require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Lycee.php';

class ClasseController {


    public function index() {
        if (!Auth::can('class', 'manage')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $lycee_id = !Auth::can('system', 'view_all_lycees') ? Auth::get('lycee_id') : null;

        $classes = Classe::findAll($lycee_id);
        require_once __DIR__ . '/../views/classes/index.php';
    }

    public function create() {
        if (!Auth::can('class', 'manage')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $cycles = Cycle::findAll();
        $lycees = Lycee::findAll(); // Needed for super_admin
        require_once __DIR__ . '/../views/classes/create.php';
    }

    public function store() {
        if (!Auth::can('class', 'manage')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // If user is a local admin, force their lycee_id
            if (!Auth::can('system', 'view_all_lycees')) {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            Classe::save($_POST);
        }
        header('Location: /classes');
        exit();
    }

    public function edit() {
        if (!Auth::can('class', 'manage')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /classes');
            exit();
        }
        $classe = Classe::findById($id);
        // Security check: local admin can only edit classes from their lycee
        if (!Auth::can('system', 'view_all_lycees') && $classe['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $cycles = Cycle::findAll();
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/classes/edit.php';
    }

    public function update() {
        if (!Auth::can('class', 'manage')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Security check
            if (!Auth::can('system', 'view_all_lycees')) {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            Classe::save($_POST);
        }
        header('Location: /classes');
        exit();
    }

    public function destroy() {
        if (!Auth::can('class', 'manage')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_POST['id'] ?? null;
        if ($id) {
            // Optional: Add security check here too before deleting
            Classe::delete($id);
        }
        header('Location: /classes');
        exit();
    }
}
?>
