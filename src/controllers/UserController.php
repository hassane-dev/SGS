<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';

class UserController {

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

        $users = User::findAll($lycee_id);
        require_once __DIR__ . '/../views/users/index.php';
    }

    public function create() {
        $this->checkAdmin();
        $lycees = (Auth::get('role') === 'super_admin_national') ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/users/create.php';
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Auth::get('role') === 'admin_local') {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            User::save($_POST);
        }
        header('Location: /users');
        exit();
    }

    public function edit() {
        $this->checkAdmin();
        $id = $_GET['id'] ?? null;
        if (!$id || $id == Auth::get('id')) { // Prevent user from editing themselves for now
            header('Location: /users');
            exit();
        }

        $user = User::findById($id);
        if (!$user) {
            header('Location: /users');
            exit();
        }

        // Security check
        if (Auth::get('role') === 'admin_local' && $user['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $lycees = (Auth::get('role') === 'super_admin_national') ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/users/edit.php';
    }

    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Security check
            if (Auth::get('role') === 'admin_local') {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            User::save($_POST);
        }
        header('Location: /users');
        exit();
    }

    public function destroy() {
        $this->checkAdmin();
        $id = $_POST['id'] ?? null;
        if ($id && $id != Auth::get('id')) { // Prevent self-delete
            User::delete($id);
        }
        header('Location: /users');
        exit();
    }
}
?>
