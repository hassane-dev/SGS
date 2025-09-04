<?php

require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Lycee.php';

class ClasseController {

    private function checkAdmin() {
        if (!Auth::check() || !in_array(Auth::get('role'), ['admin_local', 'super_admin_national'])) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAdmin();
        $user_role = Auth::get('role');
        $lycee_id = ($user_role === 'admin_local') ? Auth::get('lycee_id') : null;

        $classes = Classe::findAll($lycee_id);
        require_once __DIR__ . '/../views/classes/index.php';
    }

    public function create() {
        $this->checkAdmin();
        $cycles = Cycle::findAll();
        $lycees = Lycee::findAll(); // Needed for super_admin
        require_once __DIR__ . '/../views/classes/create.php';
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // If user is a local admin, force their lycee_id
            if (Auth::get('role') === 'admin_local') {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            Classe::save($_POST);
        }
        header('Location: /classes');
        exit();
    }

    public function edit() {
        $this->checkAdmin();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /classes');
            exit();
        }
        $classe = Classe::findById($id);
        // Security check: local admin can only edit classes from their lycee
        if (Auth::get('role') === 'admin_local' && $classe['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $cycles = Cycle::findAll();
        $lycees = Lycee::findAll();
        require_once __DIR__ . '/../views/classes/edit.php';
    }

    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Security check
            if (Auth::get('role') === 'admin_local') {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            Classe::save($_POST);
        }
        header('Location: /classes');
        exit();
    }

    public function destroy() {
        $this->checkAdmin();
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
