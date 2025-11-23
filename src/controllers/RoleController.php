<?php

require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/View.php';

class RoleController {

    private function checkAccess() {
        // Only creator and national admin can manage roles in general
        if (!Auth::can('manage', 'role')) {
            http_response_code(403);
            View::render('errors/403');
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /roles');
            exit();
        }

        $data = Validator::sanitize($_POST);
        $permission_ids = $data['permissions'] ?? [];

        // Local admins can only create roles for their own lycee
        if (Auth::is_local_admin()) {
            $data['lycee_id'] = Auth::getLyceeId();
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            $role_id = Role::save($data);
            if (!$role_id) {
                throw new Exception("Failed to create the role.");
            }

            // Note: The create form doesn't have permission checkboxes yet.
            // If it did, we would call setPermissions here.
            // Role::setPermissions($role_id, $permission_ids);

            $db->commit();
            $_SESSION['success_message'] = _("Role created successfully.");
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Role creation failed: " . $e->getMessage());
            $_SESSION['error_message'] = _("Failed to create role. Please try again.");
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

        // Security check for local admins: they can only edit roles for their own lycee.
        // They cannot edit global roles (where lycee_id is NULL).
        if (Auth::is_local_admin() && $role['lycee_id'] != Auth::getLyceeId()) {
             http_response_code(403);
             View::render('errors/403');
             exit();
        }

        $permissions = Permission::findAll();
        $role_permission_ids = Role::getPermissionIds($id);
        $lycees = (Auth::can('view_all_lycees', 'lycee')) ? Lycee::findAll() : [];


        require_once __DIR__ . '/../views/roles/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /roles');
            exit();
        }

        $data = Validator::sanitize($_POST);
        $role_id = $data['id_role'] ?? null;
        $permission_ids = $data['permissions'] ?? [];

        if (!$role_id) {
            // Handle error: No role ID provided
            $_SESSION['error_message'] = _("Role ID is missing.");
            header('Location: /roles');
            exit();
        }

        // Security check for local admins: they can only update roles for their own lycee.
        $role = Role::findById($role_id);
        if (!$role || (Auth::is_local_admin() && $role['lycee_id'] != Auth::getLyceeId())) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            Role::save($data);
            Role::setPermissions($role_id, $permission_ids);

            $db->commit();
            $_SESSION['success_message'] = _("Role updated successfully.");
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Role update failed: " . $e->getMessage());
            $_SESSION['error_message'] = _("Failed to update role. Please try again.");
        }

        header('Location: /roles');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;

        if (!$id) {
            $_SESSION['error_message'] = _("Role ID is missing.");
            header('Location: /roles');
            exit();
        }

        $role = Role::findById($id);
        if (!$role) {
            $_SESSION['error_message'] = _("Role not found.");
            header('Location: /roles');
            exit();
        }

        // Security check: Local admin can only delete roles from their own lycee
        if (Auth::is_local_admin() && $role['lycee_id'] != Auth::getLyceeId()) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        // Basic protection for default roles
        if ($id <= 8) {
             $_SESSION['error_message'] = _("Default roles cannot be deleted.");
             header('Location: /roles');
             exit();
        }

        if (Role::delete($id)) {
            $_SESSION['success_message'] = _("Role deleted successfully.");
        } else {
            $_SESSION['error_message'] = _("Failed to delete the role.");
        }

        header('Location: /roles');
        exit();
    }
}
?>
