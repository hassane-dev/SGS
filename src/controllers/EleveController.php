<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/PersonnelAssignment.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class EleveController {

    const UPLOAD_DIR = '/uploads/photos/';

    public function index() {
        $user_role = Auth::getRoleName();
        $user_id = Auth::getUserId();
        $lycee_id = Auth::getLyceeId();

        $eleves = [];

        // Censeur/Proviseur/Admin can see all students in the lycee
        if (Auth::can('view_all', 'eleve')) {
            $eleves = Eleve::findAll($lycee_id);
        }
        // Surveillant can see students from their assigned classes
        elseif ($user_role === 'surveillant') {
            $assigned_class_ids = PersonnelAssignment::findAssignedClassIdsBySupervisor($user_id);
            if (!empty($assigned_class_ids)) {
                $eleves = Eleve::findAllByClassIds($assigned_class_ids);
            }
        } else {
            // Other roles (like teacher) cannot see the student list view
            $this->forbidden();
        }

        View::render('eleves/index', [
            'eleves' => $eleves,
            'title' => 'Liste des Élèves'
        ]);
    }

    public function create() {
        if (!Auth::can('inscrire', 'eleve')) { $this->forbidden(); }
        $lycee_id = Auth::get('lycee_id');
        $classes = Classe::findAll($lycee_id);
        require_once __DIR__ . '/../views/eleves/create.php';
    }

    public function store() {
        if (!Auth::can('inscrire', 'eleve')) { $this->forbidden(); }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eleves/create');
            exit();
        }

        $data = Validator::sanitize($_POST);
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // 1. Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                $data['photo'] = $this->handlePhotoUpload($_FILES['photo']);
            }

            // 2. Save student data
            if (!Auth::can('manage_all_lycees')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            Eleve::save($data);
            $eleve_id = $db->lastInsertId();

            // 3. Pre-enroll the student in the selected class
            $activeYear = AnneeAcademique::findActive();
            Etude::create([
                'eleve_id' => $eleve_id,
                'classe_id' => $data['classe_id'],
                'annee_academique_id' => $activeYear['id'],
                'actif' => 0 // Inactive until payment validation
            ]);

            // 4. Notify accountants
            $eleve = Eleve::findById($eleve_id);
            $message = "Nouvelle pré-inscription pour " . $eleve['prenom'] . " " . $eleve['nom'] . ".";
            $link = "/comptable/validate-form?eleve_id=" . $eleve_id;
            Notification::notifyAccountants($data['lycee_id'], $message, $link);

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Student creation failed: " . $e->getMessage());
            // Redirect back with an error message
            header('Location: /eleves/create?error=1');
            exit();
        }

        header('Location: /eleves?success=1');
        exit();
    }

    public function edit() {
        if (!Auth::can('edit', 'eleve')) { $this->forbidden(); }
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($id);
        $lycee_id = $eleve['lycee_id'];
        $classes = Classe::findAll($lycee_id);
        require_once __DIR__ . '/../views/eleves/edit.php';
    }

    public function update() {
        if (!Auth::can('edit', 'eleve')) { $this->forbidden(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                $data['photo'] = $this->handlePhotoUpload($_FILES['photo']);
            }
            Eleve::save($data);
        }
        header('Location: /eleves');
        exit();
    }

    public function destroy() {
        if (!Auth::can('delete', 'eleve')) { $this->forbidden(); }
        $id = $_POST['id'] ?? null;
        if ($id) {
            Eleve::delete($id);
        }
        header('Location: /eleves');
        exit();
    }

    public function details() {
        if (!Auth::can('view_all', 'eleve')) { $this->forbidden(); }
        $eleve_id = $_GET['id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        $etudes = Etude::findByEleveId($eleve_id);
        $inscriptions = Inscription::findByEleveId($eleve_id);
        $mensualites = Mensualite::findByEleveId($eleve_id);

        require_once __DIR__ . '/../views/eleves/details.php';
    }

    private function forbidden() {
        http_response_code(403);
        View::render('errors/403');
        exit();
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
