<?php

require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Auth.php';

class PermissionController {

    private function checkAccess() {
        // Nous utiliserons une nouvelle permission 'manage_permissions' pour contrôler l'accès.
        if (!Auth::can('manage_permissions')) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Accès Interdit']);
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $permissions = Permission::findAll();
        View::render('permissions/index', [
            'title' => 'Gestion des Permissions',
            'permissions' => $permissions
        ]);
    }

    public function create() {
        $this->checkAccess();
        View::render('permissions/create', ['title' => 'Créer une Permission']);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'resource' => $_POST['resource'],
                'action' => $_POST['action'],
                'description' => $_POST['description']
            ];
            Permission::save($data);
            header('Location: /permissions');
            exit();
        }
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /permissions');
            exit();
        }
        $permission = Permission::findById($id);
        View::render('permissions/edit', [
            'title' => 'Modifier la Permission',
            'permission' => $permission
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id_permission' => $_POST['id_permission'],
                'resource' => $_POST['resource'],
                'action' => $_POST['action'],
                'description' => $_POST['description']
            ];
            Permission::save($data);
            header('Location: /permissions');
            exit();
        }
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            // Ajouter une vérification pour ne pas supprimer des permissions critiques
            Permission::delete($id);
        }
        header('Location: /permissions');
        exit();
    }
}