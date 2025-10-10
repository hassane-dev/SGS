<?php

require_once __DIR__ . '/../models/Eleve.php';

class EleveController {

    const UPLOAD_DIR = '/uploads/photos/';


    public function index() {
        if (!Auth::can('eleve', 'view_all')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $lycee_id = !Auth::can('system', 'view_all_lycees') ? Auth::get('lycee_id') : null;

        $eleves = Eleve::findAll($lycee_id);
        require_once __DIR__ . '/../views/eleves/index.php';
    }

    public function create() {
        if (!Auth::can('eleve', 'create')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        require_once __DIR__ . '/../views/eleves/create.php';
    }

    public function store() {
        if (!Auth::can('eleve', 'create')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['photo'] = $this->handlePhotoUpload($_FILES['photo']);
            Eleve::save($data);
        }
        header('Location: /eleves');
        exit();
    }

    public function edit() {
        if (!Auth::can('eleve', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($id);
        require_once __DIR__ . '/../views/eleves/edit.php';
    }

    public function update() {
        if (!Auth::can('eleve', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
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
        if (!Auth::can('eleve', 'delete')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_POST['id'] ?? null;
        if ($id) {
            Eleve::delete($id);
        }
        header('Location: /eleves');
        exit();
    }

    public function details() {
        // Anyone who can view student lists can see details. Scoping should apply here if needed.
        if (!Auth::can('eleve', 'view_all')) { http_response_code(403); echo "Accès Interdit."; exit(); }
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
