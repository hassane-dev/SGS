<?php

require_once __DIR__ . '/../models/CahierTexte.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../core/Validator.php';

class CahierTexteController {

    private function checkAccess() {
        // Allow access if user is a teacher or has permission to manage logbooks
        if (Auth::get('role_name') === 'enseignant' || Auth::can('manage', 'cahier_texte') || Auth::can('view_all', 'cahier_texte')) {
            return true;
        }
        http_response_code(403);
        echo "Accès Interdit.";
        exit();
    }

    public function index() {
        $this->checkAccess();
        $user_id = Auth::getUserId();
        $lycee_id = Auth::getLyceeId();
        $is_admin = Auth::can('manage', 'cahier_texte') || Auth::can('view_all', 'cahier_texte');

        $filters = [
            'cycle_id' => !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null,
            'niveau' => $_GET['niveau'] ?? null,
            'serie' => $_GET['serie'] ?? null,
            'numero' => $_GET['numero'] ?? null,
            'classe_id' => !empty($_GET['classe_id']) ? (int)$_GET['classe_id'] : null,
            'matiere_id' => !empty($_GET['matiere_id']) ? (int)$_GET['matiere_id'] : null,
            'personnel_id_filter' => !empty($_GET['personnel_id']) ? (int)$_GET['personnel_id'] : null,
            'date_filter' => $_GET['date'] ?? null,
        ];

        // Security assertion on classe_id to prevent multi-tenant manipulation
        if (!empty($filters['classe_id'])) {
            $checkClass = Classe::findById($filters['classe_id']);
            if (!$checkClass || (int)$checkClass['lycee_id'] !== (int)$lycee_id) {
                $filters['classe_id'] = null;
            }
        }

        // Server-side filter consistency validation:
        // Ensure classe_id matches cycle_id/niveau/serie/numero if provided
        if (!empty($filters['classe_id'])) {
            $checkClass = Classe::findById($filters['classe_id']);
            if ($checkClass) {
                if (!empty($filters['cycle_id']) && (int)$checkClass['cycle_id'] !== (int)$filters['cycle_id']) {
                    $filters['classe_id'] = null;
                }
                if (!empty($filters['niveau']) && $checkClass['niveau'] !== $filters['niveau']) {
                    $filters['classe_id'] = null;
                }
                if (isset($filters['serie']) && $filters['serie'] !== '' && $checkClass['serie'] !== $filters['serie']) {
                    $filters['classe_id'] = null;
                }
                if (isset($filters['numero']) && $filters['numero'] !== '' && (string)$checkClass['numero'] !== (string)$filters['numero']) {
                    $filters['classe_id'] = null;
                }
            }
        }

        require_once __DIR__ . '/../models/Cycle.php';
        $cycles = Cycle::findByLycee($lycee_id);
        if (empty($cycles)) {
            $cycles = Cycle::findAll();
        }

        $entries = [];
        if ($is_admin) {
            // Admin / Supervisor view: fetch entries with active filters
            $entries = CahierTexte::findAllByPersonnel(null, $lycee_id, $filters);
        } else {
            // Teacher view: fetch only their own entries with active filters
            $entries = CahierTexte::findAllByPersonnel($user_id, $lycee_id, $filters);
        }

        require_once __DIR__ . '/../views/cahier_texte/index.php';
    }

    public function show() {
        $this->checkAccess();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $lycee_id = Auth::getLyceeId();

        if (!$id) {
            header('Location: /cahier-texte');
            if (!defined('TEST_MODE')) exit(); return;
        }

        $entry = CahierTexte::findDetailsById($id, $lycee_id);

        if (!$entry) {
            http_response_code(404);
            echo "Séance introuvable ou accès non autorisé.";
            if (!defined('TEST_MODE')) exit(); return;
        }

        // Security check: Teacher without global view permissions can only view their own entries
        $is_admin = Auth::can('manage', 'cahier_texte') || Auth::can('view_all', 'cahier_texte');
        if (!$is_admin && (int)$entry['personnel_id'] !== (int)Auth::getUserId()) {
            http_response_code(403);
            echo "Accès Interdit.";
            if (!defined('TEST_MODE')) exit(); return;
        }

        require_once __DIR__ . '/../views/cahier_texte/show.php';
    }

    public function create() {
        $this->checkAccess();
        $professeur_id = Auth::getUserId();
        $assignments = User::getTeacherAssignments($professeur_id);

        $entry = [];
        $is_edit = false;
        require_once __DIR__ . '/../views/cahier_texte/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $data['personnel_id'] = Auth::getUserId();
            $data['lycee_id'] = Auth::getLyceeId();
            $active_year = AnneeAcademique::findActive();
            $data['annee_id'] = $active_year['id'] ?? null;

            // Split the combined class_subject value
            if (!empty($data['class_subject'])) {
                list($data['classe_id'], $data['matiere_id']) = explode('-', $data['class_subject']);
                unset($data['class_subject']);
            }

            try {
                CahierTexte::save($data);
            } catch (Exception $e) {
                error_log("Error saving CahierTexte: " . $e->getMessage());
            }
        }
        header('Location: /cahier-texte');
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /cahier-texte'); exit(); }

        $entry = CahierTexte::findById($id);

        // Security check: Teacher can only edit their own entries
        if (Auth::get('role_name') === 'enseignant' && $entry['personnel_id'] != Auth::getUserId()) {
            http_response_code(403);
            echo "Accès Interdit.";
            if (!defined('TEST_MODE')) exit(); return;
        }

        $professeur_id = $entry['personnel_id'];
        $assignments = User::getTeacherAssignments($professeur_id);
        $is_edit = true;
        require_once __DIR__ . '/../views/cahier_texte/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $entry = CahierTexte::findById($data['cahier_id']);

            // Security check
            if (Auth::get('role_name') === 'enseignant' && $entry['personnel_id'] != Auth::getUserId()) {
                 http_response_code(403);
                 echo "Accès Interdit.";
                 if (!defined('TEST_MODE')) exit(); return;
            }

            // Preserve original author and school
            $data['personnel_id'] = $entry['personnel_id'];
            $data['lycee_id'] = $entry['lycee_id'];
            $data['annee_id'] = $entry['annee_id'];

            // Split the combined class_subject value
            if (!empty($data['class_subject'])) {
                list($data['classe_id'], $data['matiere_id']) = explode('-', $data['class_subject']);
                unset($data['class_subject']);
            }

            CahierTexte::save($data);
        }
        header('Location: /cahier-texte');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $entry = CahierTexte::findById($id);
            // Security check
            if (Auth::get('role_name') === 'enseignant' && $entry['personnel_id'] != Auth::getUserId()) {
                 http_response_code(403);
                 echo "Accès Interdit.";
                 if (!defined('TEST_MODE')) exit(); return;
            }
            CahierTexte::delete($id);
        }
        header('Location: /cahier-texte');
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function directCreate($classe_id, $matiere_id) {
        $this->checkAccess();
        $professeur_id = Auth::getUserId();
        $assignments = User::getTeacherAssignments($professeur_id);

        $entry = [
            'classe_id' => (int)$classe_id,
            'matiere_id' => (int)$matiere_id
        ];
        $is_edit = false;
        require_once __DIR__ . '/../views/cahier_texte/create.php';
    }
}
?>