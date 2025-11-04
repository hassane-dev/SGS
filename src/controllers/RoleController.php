<?php

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../core/Validator.php';

class RoleController {

    private function checkAccess() {
        // Only creator and national admin can manage roles in general
        if (!Auth::can('manage', 'role')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $user_lycee_id = Auth::get('lycee_id');
        // Local admins see global roles + their own lycee's roles
        $roles = Role::findAll($user_lycee_id);
        require_once __DIR__ . '/../views/roles/index.php';
    }

    public function create() {
        $this->checkAccess();
        $permissions = Permission::findAll();
        $lycees = (Auth::get('role_name') === 'super_admin_createur' || Auth::get('role_name') === 'super_admin_national') ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/roles/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            // Local admins can only create roles for their own lycee
            if (Auth::get('role_name') === 'admin_local') {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            Role::save($data);
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

        // Security check for local admins
        if (Auth::get('role_name') === 'admin_local' && $role['lycee_id'] != Auth::get('lycee_id')) {
             http_response_code(403); echo "Accès Interdit."; exit();
        }

        $permissions = Permission::findAll();
        $role_permissions = Role::getPermissions($id);
        $lycees = (Auth::get('role_name') !== 'admin_local') ? Lycee::findAll() : [];

        require_once __DIR__ . '/../views/roles/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $role_id = $data['id_role'];
            $permission_ids = $data['permissions'] ?? [];

            // Security check for local admins
            $role = Role::findById($role_id);
            if (Auth::get('role_name') === 'admin_local' && $role['lycee_id'] != Auth::get('lycee_id')) {
                http_response_code(403); echo "Accès Interdit."; exit();
            }

            Role::save($data);
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
