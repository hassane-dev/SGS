<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';

class UserController {

    private function checkAccess() {
        if (!Auth::can('manage_users')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = !Auth::can('manage_all_lycees') ? Auth::get('lycee_id') : null;

        $users = User::findAll($lycee_id);
        require_once __DIR__ . '/../views/users/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = Auth::get('lycee_id');
        $lycees = (Auth::can('manage_all_lycees')) ? Lycee::findAll() : [];
        $contrats = TypeContrat::findAll($lycee_id);
        $roles = Role::findAll($lycee_id); // Fetch roles
        $user = []; // Empty user for the form
        $is_edit = false;
        require_once __DIR__ . '/../views/users/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::can('manage_all_lycees')) {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            User::save($_POST);
        }
        header('Location: /users');
        exit();
    }

    public function edit() {
        $this->checkAccess();
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
        if (!Auth::can('manage_all_lycees') && $user['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $lycees = (Auth::can('manage_all_lycees')) ? Lycee::findAll() : [];
        $contrats = TypeContrat::findAll($user['lycee_id']);
        $roles = Role::findAll($user['lycee_id']);
        $is_edit = true;
        require_once __DIR__ . '/../views/users/edit.php';
    }

    public function view() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /users');
            exit();
        }

        $user = User::findById($id);
        if (!$user) {
            header('Location: /users');
            exit();
        }

        // Security check
        if (!Auth::can('manage_all_lycees') && $user['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $contrat = !empty($user['contrat_id']) ? TypeContrat::findById($user['contrat_id']) : null;
        $role = !empty($user['role_id']) ? Role::findById($user['role_id']) : null;

        require_once __DIR__ . '/../views/users/view.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Security check
            if (!Auth::can('manage_all_lycees')) {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            User::save($_POST);
        }
        header('Location: /users');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id && $id != Auth::get('id')) { // Prevent self-delete
            User::delete($id);
        }
        header('Location: /users');
        exit();
    }
}
?>
