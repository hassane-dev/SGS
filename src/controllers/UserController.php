<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/TypeContrat.php';
require_once __DIR__ . '/../core/Validator.php';

class UserController {

    private function checkAccess($action) {
        if (!Auth::can($action, 'user')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    private function handlePhotoUpload($file) {
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes) || $file['size'] > 5000000) { // 5MB limit
                return null;
            }

            $fileName = uniqid() . '-' . basename($file['name']);
            $targetFilePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                return '/uploads/photos/' . $fileName; // Return path relative to public dir
            }
        }
        return null;
    }

    public function index() {
        $this->checkAccess('view_all');
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::get('lycee_id') : null;
        $users = User::findAll($lycee_id);
        require_once __DIR__ . '/../views/users/index.php';
    }

    public function create() {
        $this->checkAccess('create');
        $lycee_id = Auth::get('lycee_id');
        $lycees = (Auth::can('view_all_lycees', 'lycee')) ? Lycee::findAll() : [];
        $contrats = TypeContrat::findAll($lycee_id);
        $roles = Role::findAll($lycee_id);
        $user = [];
        $is_edit = false;
        require_once __DIR__ . '/../views/users/create.php';
    }

    public function store() {
        $this->checkAccess('create');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }

            $photoPath = $this->handlePhotoUpload($_FILES['photo'] ?? null);
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }

            User::save($data);
        }
        header('Location: /users');
        exit();
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /users');
            exit();
        }

        // A user can edit their own profile, OR an admin can edit users.
        if ($id != Auth::get('id')) {
            $this->checkAccess('edit');
        }

        $user = User::findById($id);
        if (!$user) {
            header('Location: /users');
            exit();
        }

        // Admin scope check: can only edit users in their school unless they are a super admin
        if ($id != Auth::get('id') && !Auth::can('view_all_lycees', 'lycee') && $user['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $lycees = (Auth::can('view_all_lycees', 'lycee')) ? Lycee::findAll() : [];
        $contrats = TypeContrat::findAll($user['lycee_id']);
        $roles = Role::findAll($user['lycee_id']);
        $is_edit = true;
        require_once __DIR__ . '/../views/users/edit.php';
    }

    public function view() {
        $this->checkAccess('view_one');
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

        if (!Auth::can('view_all_lycees', 'lycee') && $user['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $contrat = !empty($user['contrat_id']) ? TypeContrat::findById($user['contrat_id']) : null;
        $role = !empty($user['role_id']) ? Role::findById($user['role_id']) : null;
        require_once __DIR__ . '/../views/users/view.php';
    }

    public function update() {
        $this->checkAccess('edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $currentUser = User::findById($data['id_user']);
                if ($currentUser && !empty($currentUser['photo'])) {
                    $oldPhotoPath = __DIR__ . '/../../public' . $currentUser['photo'];
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }
                $photoPath = $this->handlePhotoUpload($_FILES['photo']);
                if ($photoPath) {
                    $data['photo'] = $photoPath;
                }
            }

            User::save($data);
        }
        header('Location: /users');
        exit();
    }

    public function destroy() {
        $this->checkAccess('delete');
        $id = $_POST['id'] ?? null;
        if ($id && $id != Auth::get('id')) {
            $user = User::findById($id);
            if ($user && !empty($user['photo'])) {
                $photoPath = __DIR__ . '/../../public' . $user['photo'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            User::delete($id);
        }
        header('Location: /users');
        exit();
    }
}
?>