<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/TypeContrat.php';

class UserController {


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
        if (!Auth::can('user', 'view_all')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $currentUser = Auth::user();
        $lycee_id = !Auth::can('system', 'view_all_lycees') ? $currentUser['lycee_id'] : null;

        // Apply data scoping for supervisors
        if ($currentUser['role_name'] === 'surveillant') {
            $users = User::findTeachersBySupervisor($currentUser['id']);
        } else {
            $users = User::findAll($lycee_id);
        }

        require_once __DIR__ . '/../views/users/index.php';
    }

    public function create() {
        if (!Auth::can('user', 'create')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
        $lycee_id = Auth::get('lycee_id');
        $lycees = (Auth::can('system', 'view_all_lycees')) ? Lycee::findAll() : [];
        $contrats = TypeContrat::findAll($lycee_id);
        $roles = Role::findAll($lycee_id);
        $user = [];
        $is_edit = false;
        require_once __DIR__ . '/../views/users/create.php';
    }

    public function store() {
        if (!Auth::can('user', 'create')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            if (!Auth::can('system', 'view_all_lycees')) {
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

        $user = User::findById($id);
        if (!$user) {
            header('Location: /users');
            exit();
        }

        if (!Auth::can('user', 'edit', $user)) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $lycees = (Auth::can('system', 'view_all_lycees')) ? Lycee::findAll() : [];
        $contrats = TypeContrat::findAll($user['lycee_id']);
        $roles = Role::findAll($user['lycee_id']);
        $is_edit = true;
        require_once __DIR__ . '/../views/users/edit.php';
    }

    public function view() {
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

        if (!Auth::can('user', 'view_one', $user)) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        $contrat = !empty($user['contrat_id']) ? TypeContrat::findById($user['contrat_id']) : null;
        $role = !empty($user['role_id']) ? Role::findById($user['role_id']) : null;

        // --- New data for assignments ---
        $assignments = [];
        $assignable_classes = [];
        // Only fetch assignment data if the role is relevant (e.g., surveillant)
        // and the admin has permission to edit users.
        if ($role && $role['nom_role'] === 'surveillant' && Auth::can('user', 'edit')) {
            require_once __DIR__ . '/../models/PersonnelAssignment.php';
            require_once __DIR__ . '/../models/Classe.php';
            $assignments = PersonnelAssignment::findByPersonnelId($id);
            $assignable_classes = Classe::findAll($user['lycee_id']);
        }
        // --- End new data ---

        require_once __DIR__ . '/../views/users/view.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $user = User::findById($data['id_user']);

            if (!$user || !Auth::can('user', 'edit', $user)) {
                 http_response_code(403);
                 echo "Accès Interdit.";
                 exit();
            }

            if (!Auth::can('system', 'view_all_lycees') && isset($data['lycee_id'])) {
                // Prevent non-super-admins from changing the lycee_id
                $data['lycee_id'] = $user['lycee_id'];
            }

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                if ($user && !empty($user['photo'])) {
                    $oldPhotoPath = __DIR__ . '/../../public' . $user['photo'];
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
        $id = $_POST['id'] ?? null;
        if (!$id) {
            header('Location: /users');
            exit();
        }

        $user = User::findById($id);
        if (!$user || !Auth::can('user', 'delete', $user) || $id == Auth::get('id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        if ($user && !empty($user['photo'])) {
            $photoPath = __DIR__ . '/../../public' . $user['photo'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }
        User::delete($id);

        header('Location: /users');
        exit();
    }

    public function assignTeaches() {
        if (!Auth::can('user', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }

        $teacher_id = $_GET['id'] ?? null;
        if (!$teacher_id) {
            header('Location: /users');
            exit();
        }

        $teacher = User::findById($teacher_id);
        if (!$teacher) {
            header('Location: /users');
            exit();
        }

        // Security check
        if (!Auth::can('system', 'view_all_lycees') && $teacher['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403); echo "Accès Interdit."; exit();
        }

        $classes = Classe::findAll($teacher['lycee_id']);
        $matieres = Matiere::findAll();
        $assignments = User::getTeacherAssignments($teacher_id);

        require_once __DIR__ . '/../views/users/assign_teaches.php';
    }

    public function updateTeacherAssignments() {
        if (!Auth::can('user', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $teacher_id = $_POST['teacher_id'] ?? null;
            $new_assignments = $_POST['assignments'] ?? [];

            if (!$teacher_id) {
                header('Location: /users');
                exit();
            }

            $current_assignments_raw = User::getTeacherAssignments($teacher_id);
            $current_assignments = [];
            foreach ($current_assignments_raw as $assignment) {
                $current_assignments[] = $assignment['id_classe'] . '-' . $assignment['id_matiere'];
            }

            $to_add = array_diff($new_assignments, $current_assignments);
            $to_remove = array_diff($current_assignments, $new_assignments);

            foreach ($to_add as $assignment_str) {
                list($class_id, $matiere_id) = explode('-', $assignment_str);
                User::assignTeacherToClass($teacher_id, $class_id, $matiere_id);
            }

            foreach ($to_remove as $assignment_str) {
                list($class_id, $matiere_id) = explode('-', $assignment_str);
                User::unassignTeacherFromClass($teacher_id, $class_id, $matiere_id);
            }
        }

        header('Location: /users/assign?id=' . $teacher_id);
        exit();
    }
}
?>