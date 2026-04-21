<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/PersonnelAssignment.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/Mensualite.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class EleveController {

    const UPLOAD_DIR = '/uploads/photos/';

    public function index() {
        $user_role = Auth::get('role_name');
        $user_id = Auth::get('id');
        $current_lycee_id = Auth::getLyceeId();

        $filters = [
            'lycee_id' => $_GET['lycee_id'] ?? $current_lycee_id,
            'cycle_id' => $_GET['cycle_id'] ?? null,
            'niveau'   => $_GET['niveau'] ?? null,
            'serie'    => $_GET['serie'] ?? null,
            'numero'   => $_GET['numero'] ?? null,
        ];

        $eleves = [];

        // Censeur/Proviseur/Admin can see all students in the lycee
        if (Auth::can('view_all', 'eleve')) {
            // If it's a super admin, they might not have a lycee_id fixed
            if (Auth::can('view_all_lycees', 'lycee')) {
                 $eleves = Eleve::findAll($filters);
            } else {
                $filters['lycee_id'] = $current_lycee_id;
                $eleves = Eleve::findAll($filters);
            }
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

        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];
        $cycles = Cycle::findAll();

        View::render('eleves/index', [
            'eleves' => $eleves,
            'lycees' => $lycees,
            'cycles' => $cycles,
            'filters' => $filters,
            'title' => 'Liste des Élèves'
        ]);
    }

    public function create() {
        if (!Auth::can('inscrire', 'eleve')) { $this->forbidden(); }
        // The $classes variable is no longer needed here as class assignment is now a separate step.
        require_once __DIR__ . '/../views/eleves/create.php';
    }

    public function store() {
        if (!Auth::can('inscrire', 'eleve')) { $this->forbidden(); }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eleves/create');
            exit();
        }

        $data = Validator::sanitize($_POST);

        // Handle photo upload BEFORE database operation
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $photoPath = $this->handlePhotoUpload($_FILES['photo']);
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // 1. Photo is already in $data

            // 2. Save student data
            if (empty($data['lycee_id'])) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            Eleve::save($data);
            $eleve_id = $db->lastInsertId();

            // Notification pour le comptable
            $eleve_nom_complet = $data['prenom'] . ' ' . $data['nom'];
            $message = "Un nouvel élève est en attente de paiement : {$eleve_nom_complet}. Cliquez pour procéder au paiement.";
            $link = "/paiements/show/{$eleve_id}";
            Notification::notifyRole('comptable', $data['lycee_id'], $message, $link);

            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Student creation failed: " . $e->getMessage());
            // Redirect back with an error message
            header('Location: /eleves/create?error=1');
            exit();
        }

        // Redirect to the new class assignment step
        header('Location: /eleves/assign-class?eleve_id=' . $eleve_id);
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
            $id = $data['id_eleve'] ?? null;

            if (!$id) {
                header('Location: /eleves');
                exit();
            }

            $currentEleve = Eleve::findById($id);
            if (!$currentEleve) {
                header('Location: /eleves');
                exit();
            }

            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                $photoPath = $this->handlePhotoUpload($_FILES['photo']);
                if ($photoPath) {
                    // Delete old photo if exists
                    if (!empty($currentEleve['photo'])) {
                        $oldPhotoPath = __DIR__ . '/../../public' . $currentEleve['photo'];
                        if (file_exists($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                        }
                    }
                    $data['photo'] = $photoPath;
                }
            }

            if (empty($data['lycee_id'])) {
                $data['lycee_id'] = $currentEleve['lycee_id'] ?? Auth::get('lycee_id');
            }

            try {
                Eleve::save($data);
                $_SESSION['success_message'] = "Les informations de l'élève ont été mises à jour.";
            } catch (Exception $e) {
                error_log("Student update failed: " . $e->getMessage());
                $_SESSION['error_message'] = "Erreur lors de la mise à jour : " . $e->getMessage();
            }
        }
        header('Location: /eleves');
        exit();
    }

    public function destroy() {
        if (!Auth::can('delete', 'eleve')) { $this->forbidden(); }
        $id = $_POST['id'] ?? null;
        if ($id) {
            Eleve::changeStatus($id, 'radié');
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
            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                error_log("Photo upload error for student: " . $file['error']);
            }
            return null;
        }

        $upload_path = __DIR__ . '/../../public' . self::UPLOAD_DIR;
        if (!is_dir($upload_path)) {
            if (!mkdir($upload_path, 0755, true)) {
                error_log("Failed to create student upload directory: " . $upload_path);
                return null;
            }
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($detectedType, $allowedTypes) || $file['size'] > 5000000) { // 5MB limit
            error_log("Invalid student photo file type or size. Type: " . $detectedType . ", Size: " . $file['size']);
            return null;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $target_path = $upload_path . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return self::UPLOAD_DIR . $filename;
        } else {
            error_log("Failed to move student uploaded file to: " . $target_path);
        }

        return null;
    }

    public function archives() {
        if (!Auth::can('view_all', 'eleve')) { $this->forbidden(); }
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::getLyceeId() : null;

        $eleves = Eleve::findAllArchived($lycee_id);
        View::render('eleves/archives', [
            'eleves' => $eleves,
            'title' => 'Élèves Archivés'
        ]);
    }

    public function assignClass() {
        if (!Auth::can('inscrire', 'eleve')) { $this->forbidden(); }

        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }

        $eleve = Eleve::findById($eleve_id);
        if (!$eleve) {
            // Handle student not found
            header('Location: /eleves');
            exit();
        }

        $cycles = Cycle::findAll();

        View::render('eleves/assign_class', [
            'eleve' => $eleve,
            'cycles' => $cycles,
            'title' => 'Assigner une Classe'
        ]);
    }

    public function processAssignment() {
        if (!Auth::can('inscrire', 'eleve')) { $this->forbidden(); }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /eleves');
            exit();
        }

        $data = Validator::sanitize($_POST);
        $eleve_id = $data['eleve_id'];
        $lycee_id = Auth::getLyceeId();

        $classe_id = Classe::findIdByDetails($lycee_id, $data['niveau'], $data['serie'] ?? null, $data['numero']);

        if ($classe_id) {
            $db = Database::getInstance();
            try {
                $db->beginTransaction();

                $activeYear = AnneeAcademique::findActive();
                if (!$activeYear) {
                    throw new Exception("Aucune année académique active n'a été trouvée.");
                }

                // Create the study record
                Etude::create([
                    'eleve_id' => $eleve_id,
                    'classe_id' => $classe_id,
                    'lycee_id' => $lycee_id,
                    'annee_academique_id' => $activeYear['id'],
                    'actif' => 0 // Inactive until payment validation
                ]);

                // Increment the class's current number of students
                Classe::incrementerEffectifActuel($classe_id, $activeYear['id']);

                $db->commit();

                $_SESSION['success_message'] = "L'élève a été assigné à la classe avec succès.";
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Erreur lors de l'assignation de la classe : " . $e->getMessage());
                $_SESSION['error_message'] = "Une erreur est survenue. L'assignation a été annulée.";
                header('Location: /eleves/assign-class?eleve_id=' . $eleve_id);
                exit();
            }
        } else {
            $_SESSION['error_message'] = "La classe sélectionnée n'a pas pu être trouvée.";
            header('Location: /eleves/assign-class?eleve_id=' . $eleve_id);
            exit();
        }

        header('Location: /eleves');
        exit();
    }
}
?>
