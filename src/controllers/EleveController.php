<?php

require_once __DIR__ . '/../models/Eleve.php';

class EleveController {

    const UPLOAD_DIR = '/uploads/photos/';

    private function checkAdmin() {
        if (!Auth::check() || !in_array(Auth::get('role'), ['admin_local', 'super_admin_national'])) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAdmin();
        $user_role = Auth::get('role');
        $lycee_id = ($user_role === 'admin_local') ? Auth::get('lycee_id') : null;

        $eleves = Eleve::findAll($lycee_id);
        require_once __DIR__ . '/../views/eleves/index.php';
    }

    public function create() {
        $this->checkAdmin();
        require_once __DIR__ . '/../views/eleves/create.php';
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['photo'] = $this->handlePhotoUpload($_FILES['photo']);
            Eleve::save($data);
        }
        header('Location: /eleves');
        exit();
    }

    public function edit() {
        $this->checkAdmin();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($id);
        require_once __DIR__ . '/../views/eleves/edit.php';
    }

    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                $data['photo'] = $this->handlePhotoUpload($_FILES['photo']);
            }
            Eleve::save($data);
        }
        header('Location: /eleves');
        exit();
    }

    public function destroy() {
        $this->checkAdmin();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Eleve::delete($id);
        }
        header('Location: /eleves');
        exit();
    }

    public function details() {
        $this->checkAdmin();
        $eleve_id = $_GET['id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        $etudes = Etude::findByEleveId($eleve_id);

        require_once __DIR__ . '/../views/eleves/details.php';
    }

    private function handlePhotoUpload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $upload_path = __DIR__ . '/../../public' . self::UPLOAD_DIR;
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $filename = uniqid() . '-' . basename($file['name']);
        $target_path = $upload_path . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return self::UPLOAD_DIR . $filename;
        }

        return null;
    }
}
?>
