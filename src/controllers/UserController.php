<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/TypeContrat.php';
require_once __DIR__ . '/../core/View.php';

class UserController {

    private function checkAccess() {
        if (!Auth::can('user_manage')) {
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
        $this->checkAccess();
        $lycee_id = !Auth::can('manage_all_lycees') ? Auth::get('lycee_id') : null;
        $users = User::findAll($lycee_id);
        View::render('users/index', ['title' => 'Gestion des Utilisateurs', 'users' => $users]);
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = Auth::get('lycee_id');
        $lycees = (Auth::can('manage_all_lycees')) ? Lycee::findAll() : [];
        $contrats = TypeContrat::findAll($lycee_id);
        $roles = Role::findAll($lycee_id);
        View::render('users/create', [
            'title' => 'Créer un Utilisateur',
            'lycees' => $lycees,
            'contrats' => $contrats,
            'roles' => $roles,
            'user' => [],
            'is_edit' => false
        ]);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            if (!Auth::can('manage_all_lycees')) {
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
        if (!$id) { header('Location: /users'); exit(); }

        if ($id != Auth::get('id') && !Auth::can('manage_users')) { $this->forbidden(); }

        $user = User::findById($id);
        if (!$user) { header('Location: /users'); exit(); }

        if ($id != Auth::get('id') && !Auth::can('manage_all_lycees') && $user['lycee_id'] != Auth::get('lycee_id')) { $this->forbidden(); }

        $lycees = (Auth::can('manage_all_lycees')) ? Lycee::findAll() : [];
        $contrats = TypeContrat::findAll($user['lycee_id']);
        $roles = Role::findAll($user['lycee_id']);
        View::render('users/edit', [
            'title' => 'Modifier l\'Utilisateur',
            'user' => $user,
            'lycees' => $lycees,
            'contrats' => $contrats,
            'roles' => $roles,
            'is_edit' => true
        ]);
    }

    public function view() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /users'); exit(); }

        $user = User::findById($id);
        if (!$user) { header('Location: /users'); exit(); }

        if (!Auth::can('manage_all_lycees') && $user['lycee_id'] != Auth::get('lycee_id')) { $this->forbidden(); }

        $contrat = !empty($user['contrat_id']) ? TypeContrat::findById($user['contrat_id']) : null;
        $role = !empty($user['role_id']) ? Role::findById($user['role_id']) : null;
        View::render('users/view', [
            'title' => 'Profil Utilisateur',
            'user' => $user,
            'contrat' => $contrat,
            'role' => $role
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            if (!Auth::can('manage_all_lycees')) {
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
        $this->checkAccess();
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