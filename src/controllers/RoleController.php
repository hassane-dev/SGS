<?php

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../core/View.php';

class RoleController {

    private function checkAccess() {
        if (!Auth::can('manage_roles')) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Accès Interdit']);
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $user_lycee_id = Auth::get('lycee_id');
        $roles = Role::findAll($user_lycee_id);
        View::render('roles/index', [
            'title' => 'Gestion des Rôles',
            'roles' => $roles
        ]);
    }

    public function create() {
        $this->checkAccess();
        $permissions = Permission::findAll();
        $lycees = (Auth::can('manage_all_lycees')) ? Lycee::findAll() : [];
        View::render('roles/create', [
            'title' => 'Créer un Rôle',
            'permissions' => $permissions,
            'lycees' => $lycees
        ]);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Auth::get('role_name') === 'admin_local') {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            Role::save($_POST);
        }
        header('Location: /roles');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /roles'); exit(); }

        $role = Role::findById($id);
        if (!$role) { header('Location: /roles'); exit(); }

        if (Auth::get('role_name') === 'admin_local' && $role['lycee_id'] != Auth::get('lycee_id')) {
             http_response_code(403);
             View::render('errors/403', ['title' => 'Accès Interdit']);
             exit();
        }

        $all_permissions = Permission::findAll();
        $role_permission_ids = Role::getPermissionIds($id); // Nouvelle méthode à créer
        $lycees = (Auth::get('role_name') !== 'admin_local') ? Lycee::findAll() : [];

        View::render('roles/edit', [
            'title' => 'Modifier le Rôle',
            'role' => $role,
            'all_permissions' => $all_permissions,
            'role_permission_ids' => $role_permission_ids,
            'lycees' => $lycees
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $role_id = $_POST['id_role'];
            $permission_ids = $_POST['permissions'] ?? [];

            $role = Role::findById($role_id);
            if (Auth::get('role_name') === 'admin_local' && $role['lycee_id'] != Auth::get('lycee_id')) {
                http_response_code(403);
                View::render('errors/403', ['title' => 'Accès Interdit']);
                exit();
            }

            Role::save($_POST);
            Role::setPermissions($role_id, $permission_ids);
        }
        header('Location: /roles');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Role::delete($id);
        }
        header('Location: /roles');
        exit();
    }
}
?>