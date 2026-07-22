<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/TypeContrat.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../models/ParametreUtilisateur.php';

class UserController {

    private function checkAccess($action) {
        if (!Auth::can($action, 'user')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    private function handlePhotoUpload($file) {
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_BASE_DIR . '/photos/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    error_log("Failed to create upload directory: " . $uploadDir);
                    return null;
                }
            }
            chmod($uploadDir, 0777);

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedType = finfo_file($fileInfo, $file['tmp_name']);
            finfo_close($fileInfo);

            if (!in_array($detectedType, $allowedTypes) || $file['size'] > 5000000) { // 5MB limit
                error_log("Invalid file type or size. Type: " . $detectedType . ", Size: " . $file['size']);
                return null;
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $extension;
            $targetFilePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                return UPLOAD_PUBLIC_PATH . '/photos/' . $fileName; // Return path relative to public dir
            } else {
                error_log("Failed to move uploaded file to: " . $targetFilePath);
            }
        } elseif (isset($file) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            error_log("Photo upload error: " . $file['error']);
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

            // Ensure 'actif' is always set
            $data['actif'] = isset($data['actif']) ? 1 : 0;

            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photoPath = $this->handlePhotoUpload($_FILES['photo']);
                if ($photoPath) {
                    $data['photo'] = $photoPath;
                }
            }

            try {
                User::save($data);
                $_SESSION['success_message'] = "Le membre du personnel a été créé avec succès.";
                header('Location: /users');
                exit();
            } catch (Exception $e) {
                error_log("User creation failed: " . $e->getMessage());
                // Redisplay the form with an error message and pre-filled data
                $lycee_id = Auth::get('lycee_id');
                $lycees = (Auth::can('view_all_lycees', 'lycee')) ? Lycee::findAll() : [];
                $contrats = TypeContrat::findAll($lycee_id);
                $roles = Role::findAll($lycee_id);
                $user = $data;
                $is_edit = false;
                $error = $e->getMessage();
                require_once __DIR__ . '/../views/users/create.php';
                exit();
            }
        }
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
            View::render('errors/403');
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
            View::render('errors/403');
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

            // Ensure 'actif' is always set
            $data['actif'] = isset($data['actif']) ? 1 : 0;

            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photoPath = $this->handlePhotoUpload($_FILES['photo']);
                if ($photoPath) {
                    $currentUser = User::findById($data['id_user']);
                    if ($currentUser && !empty($currentUser['photo'])) {
                        $oldPhotoPath = __DIR__ . '/../../public' . $currentUser['photo'];
                        if (file_exists($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                        }
                    }
                    $data['photo'] = $photoPath;
                }
            }

            try {
                User::save($data);
                $_SESSION['success_message'] = "Le membre du personnel a été mis à jour avec succès.";
                header('Location: /users');
                exit();
            } catch (Exception $e) {
                error_log("User update failed: " . $e->getMessage());
                // Redisplay the form with an error message and pre-filled data
                $id = $data['id_user'];
                $user = $data; // Use submitted data to refill form
                $lycees = (Auth::can('view_all_lycees', 'lycee')) ? Lycee::findAll() : [];
                $contrats = TypeContrat::findAll($user['lycee_id'] ?? Auth::get('lycee_id'));
                $roles = Role::findAll($user['lycee_id'] ?? Auth::get('lycee_id'));
                $is_edit = true;
                $error = $e->getMessage();
                require_once __DIR__ . '/../views/users/edit.php';
                exit();
            }
        }
    }

    public function destroy() {
        $this->checkAccess('delete');
        $id = $_POST['id'] ?? null;

        if (!$id || $id == Auth::get('id_user')) {
            // Do not allow self-deletion or invalid ID
            header('Location: /users?error=delete_failed');
            exit();
        }

        $user = User::findById($id);
        if (!$user) {
            // User not found
            header('Location: /users?error=not_found');
            exit();
        }

        // Scope check: Super admin can delete anyone, others only within their lycee
        if (!Auth::can('view_all_lycees', 'lycee') && $user['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        // Attempt to delete the user's photo if it exists
        if (!empty($user['photo'])) {
            $photoPath = __DIR__ . '/../../public' . $user['photo'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        if (User::delete($id)) {
            header('Location: /users?success=delete');
        } else {
            header('Location: /users?error=delete_failed');
        }
        exit();
    }

    public function profile() {
        $user_id = Auth::get('id_user');
        $user = User::findById($user_id);

        if (!$user) {
            // Should not happen for a logged-in user
            header('Location: /');
            exit();
        }

        $parametres = ParametreUtilisateur::findByUserId($user_id);

        $data = [
            'user' => $user,
            'parametres' => $parametres,
            'title' => _('Mon Profil')
        ];

        require_once __DIR__ . '/../views/users/profile.php';
    }

    public function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = Auth::get('id_user');
            $data = Validator::sanitize($_POST);

            // 1. Verify current password
            $user = User::findById($user_id);
            if (!password_verify($data['current_password'], $user['mot_de_passe'])) {
                // Handle incorrect password
                $_SESSION['error_message'] = 'Le mot de passe actuel est incorrect.';
                header('Location: /profile');
                exit();
            }

            // 2. Check if new passwords match
            if ($data['new_password'] !== $data['confirm_password']) {
                $_SESSION['error_message'] = 'Les nouveaux mots de passe ne correspondent pas.';
                header('Location: /profile');
                exit();
            }

            // 3. Update the password
            if (User::updatePassword($user_id, $data['new_password'])) {
                $_SESSION['success_message'] = 'Mot de passe mis à jour avec succès.';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la mise à jour du mot de passe.';
            }

            header('Location: /profile');
            exit();
        }
    }

    public function updateSettings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = Auth::get('id_user');
            $user = User::findById($user_id);
            if (!$user) {
                $_SESSION['error_message'] = 'Utilisateur non trouvé.';
                header('Location: /profile');
                exit();
            }

            $parametres = ParametreUtilisateur::findByUserId($user_id);
            $parametres->lycee_id = $user['lycee_id'] ?? null;
            $parametres->langue_preferee = $_POST['langue_preferee'] ?? 'fr_FR';
            $parametres->theme_prefere = $_POST['theme_prefere'] ?? 'light';
            $parametres->notifications_actives = isset($_POST['notifications_actives']) ? 1 : 0;

            // Handle standard file uploads
            // 1. Signature file upload
            if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD_BASE_DIR . '/signatures/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                @chmod($uploadDir, 0777);

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $detectedType = mime_content_type($_FILES['signature_file']['tmp_name']);

                if (in_array($detectedType, $allowedTypes)) {
                    $extension = pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION);
                    $fileName = 'signature_' . $user_id . '_' . time() . '.' . $extension;
                    $targetFilePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $targetFilePath)) {
                        $parametres->signature = UPLOAD_PUBLIC_PATH . '/signatures/' . $fileName;
                    }
                }
            }
            // 2. Canvas Signature (Base64)
            elseif (!empty($_POST['signature_base64'])) {
                $base64 = $_POST['signature_base64'];
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
                    $imgData = substr($base64, strpos($base64, ',') + 1);
                    $imgData = base64_decode($imgData);

                    if ($imgData !== false) {
                        $uploadDir = UPLOAD_BASE_DIR . '/signatures/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        @chmod($uploadDir, 0777);

                        $fileName = 'signature_' . $user_id . '_' . time() . '.png';
                        $targetFilePath = $uploadDir . $fileName;

                        if (file_put_contents($targetFilePath, $imgData)) {
                            $parametres->signature = UPLOAD_PUBLIC_PATH . '/signatures/' . $fileName;
                        }
                    }
                }
            }

            // 3. Cachet file upload
            if (isset($_FILES['cachet_file']) && $_FILES['cachet_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = UPLOAD_BASE_DIR . '/tampons/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                @chmod($uploadDir, 0777);

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $detectedType = mime_content_type($_FILES['cachet_file']['tmp_name']);

                if (in_array($detectedType, $allowedTypes)) {
                    $extension = pathinfo($_FILES['cachet_file']['name'], PATHINFO_EXTENSION);
                    $fileName = 'cachet_' . $user_id . '_' . time() . '.' . $extension;
                    $targetFilePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['cachet_file']['tmp_name'], $targetFilePath)) {
                        $parametres->cachet = UPLOAD_PUBLIC_PATH . '/tampons/' . $fileName;
                    }
                }
            }

            // Save settings
            if ($parametres->save()) {
                $_SESSION['success_message'] = _('Paramètres et signatures enregistrés avec succès.');

                // Update active locale session dynamically for the current user!
                $_SESSION['lang'] = $parametres->langue_preferee;
            } else {
                $_SESSION['error_message'] = _('Erreur lors de l\'enregistrement des paramètres.');
            }

            header('Location: /profile');
            exit();
        }
    }

    public function updatePhoto() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $user_id = Auth::get('id_user');
        $user = User::findById($user_id);
        if (!$user) {
            $_SESSION['error_message'] = _('Utilisateur non trouvé.');
            header('Location: /profile');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cropped_photo'])) {
            $photoPath = $this->handleCroppedPhoto($_POST['cropped_photo']);
            if ($photoPath) {
                // Delete old photo if exists and is not default
                if (!empty($user['photo']) && $user['photo'] !== '/assets/img/default-avatar.png') {
                    $oldPhotoPath = __DIR__ . '/../../public' . $user['photo'];
                    if (file_exists($oldPhotoPath)) {
                        @unlink($oldPhotoPath);
                    }
                }

                $user['photo'] = $photoPath;
                // Unset 'mot_de_passe' to prevent User::save() from re-hashing the existing password hash
                unset($user['mot_de_passe']);
                try {
                    User::save($user);
                    $_SESSION['user']['photo'] = $photoPath;
                    $_SESSION['success_message'] = _('Photo de profil mise à jour avec succès.');
                } catch (Exception $e) {
                    $_SESSION['error_message'] = _('Erreur lors de la mise à jour de la photo: ') . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = _('Erreur lors du traitement de la photo de profil.');
            }
        } else {
            $_SESSION['error_message'] = _('Aucune photo fournie.');
        }

        header('Location: /profile');
        exit();
    }

    private function handleCroppedPhoto($base64_string) {
        $upload_path = UPLOAD_BASE_DIR . '/photos/';
        if (!is_dir($upload_path)) {
            if (!mkdir($upload_path, 0777, true)) {
                error_log("Failed to create user photo upload directory: " . $upload_path);
                return null;
            }
        }
        @chmod($upload_path, 0777);

        try {
            list($type, $data) = explode(';', $base64_string);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);

            if (strlen($data) > 5000000) { // 5MB limit
                error_log("Decoded cropped photo size exceeds 5MB.");
                return null;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($data);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                error_log("Invalid decoded MIME type: " . $mimeType);
                return null;
            }

            $filename = uniqid() . '.jpg';
            $target_path = $upload_path . $filename;

            if (file_put_contents($target_path, $data)) {
                return UPLOAD_PUBLIC_PATH . '/photos/' . $filename;
            } else {
                error_log("Failed to save cropped user photo to: " . $target_path);
            }
        } catch (Exception $e) {
            error_log("Error processing cropped user photo: " . $e->getMessage());
        }

        return null;
    }
}
?>