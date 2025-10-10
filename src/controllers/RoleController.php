<?php

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/Lycee.php';

class RoleController {


    public function index() {
        if (!Auth::can('role', 'view_all')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $user_lycee_id = Auth::get('lycee_id');
        // Local admins see global roles + their own lycee's roles
        $roles = Role::findAll($user_lycee_id);
        require_once __DIR__ . '/../views/roles/index.php';
    }

    public function create() {
        if (!Auth::can('role', 'create')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $permissions = Permission::findAll();
        $lycees = (Auth::can('system', 'view_all_lycees')) ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/roles/create.php';
    }

    public function store() {
        if (!Auth::can('role', 'create')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Local admins can only create roles for their own lycee
            if (!Auth::can('system', 'view_all_lycees')) {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            Role::save($_POST);
        }
        header('Location: /roles');
        exit();
    }

    public function edit() {
        if (!Auth::can('role', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /roles'); exit(); }

        $role = Role::findById($id);
        if (!$role) { header('Location: /roles'); exit(); }

        // Security check for local admins
        if (!Auth::can('system', 'view_all_lycees') && $role['lycee_id'] != Auth::get('lycee_id')) {
             http_response_code(403); echo "Accès Interdit."; exit();
        }

        $permissions = Permission::findAll();
        $role_permissions = Role::getPermissions($id);
        $lycees = (Auth::can('system', 'view_all_lycees')) ? Lycee::findAll() : [];

        require_once __DIR__ . '/../views/roles/edit.php';
    }

    public function update() {
        if (!Auth::can('role', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $role_id = $_POST['id_role'];
            $permission_ids = $_POST['permissions'] ?? [];

            // Security check for local admins
            $role = Role::findById($role_id);
            if (!Auth::can('system', 'view_all_lycees') && $role['lycee_id'] != Auth::get('lycee_id')) {
                http_response_code(403); echo "Accès Interdit."; exit();
            }

            Role::save($_POST);
            Role::setPermissions($role_id, $permission_ids);
        }
        header('Location: /roles');
        exit();
    }

    public function destroy() {
        if (!Auth::can('role', 'delete')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_POST['id'] ?? null;
        if ($id) {
            $role = Role::findById($id);
            if (!Auth::can('system', 'view_all_lycees') && $role['lycee_id'] != Auth::get('lycee_id')) {
                 http_response_code(403); echo "Accès Interdit."; exit();
            }
            Role::delete($id);
        }
        header('Location: /roles');
        exit();
    }
}
?>
