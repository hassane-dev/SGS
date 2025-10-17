<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../core/View.php';

class EleveController {

    const UPLOAD_DIR = '/uploads/photos/';

    private function checkAccess() {
        if (!Auth::can('manage_eleves')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = !Auth::can('manage_all_lycees') ? Auth::get('lycee_id') : null;

        $eleves = Eleve::findAll($lycee_id);
        View::render('eleves/index', [
            'title' => 'Liste des Élèves',
            'eleves' => $eleves
        ]);
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = Auth::get('lycee_id');
        $classes = Classe::findAll($lycee_id);
        View::render('eleves/create', [
            'title' => 'Inscrire un nouvel Élève',
            'classes' => $classes
        ]);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eleves/create');
            exit();
        }

        $data = $_POST;
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
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($id);
        $lycee_id = $eleve['lycee_id'];
        $classes = Classe::findAll($lycee_id);
        View::render('eleves/edit', [
            'title' => 'Modifier l\'Élève',
            'eleve' => $eleve,
            'classes' => $classes
        ]);
    }

    public function update() {
        $this->checkAccess();
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
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Eleve::delete($id);
        }
        header('Location: /eleves');
        exit();
    }

    public function details() {
        $this->checkAccess();
        $eleve_id = $_GET['id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        $etudes = Etude::findByEleveId($eleve_id);
        $inscriptions = Inscription::findByEleveId($eleve_id);
        $mensualites = Mensualite::findByEleveId($eleve_id);

        View::render('eleves/details', [
            'title' => 'Détails de l\'Élève',
            'eleve' => $eleve,
            'etudes' => $etudes,
            'inscriptions' => $inscriptions,
            'mensualites' => $mensualites
        ]);
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