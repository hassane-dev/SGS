<?php

require_once __DIR__ . '/../models/CahierTexte.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../core/Validator.php';

class CahierTexteController {

    private function checkAccess() {
        // Allow access if user is a teacher or has permission to manage logbooks
        if (Auth::get('role_name') === 'enseignant' || Auth::can('manage_cahier_texte')) {
            return true;
        }
        http_response_code(403);
        echo "Accès Interdit.";
        exit();
    }

    public function index() {
        $this->checkAccess();
        $user_id = Auth::get('id');
        $lycee_id = Auth::get('lycee_id');
        $is_admin = Auth::can('manage_cahier_texte');

        $filters = [
            'personnel_id_filter' => $_GET['personnel_id'] ?? null,
            'classe_id_filter' => $_GET['classe_id'] ?? null,
            'date_filter' => $_GET['date'] ?? null,
        ];

        $entries = [];
        $teachers = [];
        $classes = [];

        if ($is_admin) {
            // Admin view: fetch all entries for the school with potential filters
            $entries = CahierTexte::findAllByPersonnel(null, $lycee_id, $filters);
            // Fetch data for filters
            $teachers = User::findAllByRoleName('enseignant', $lycee_id);
            $classes = Classe::findAll($lycee_id);
        } else {
            // Teacher view: fetch only their own entries
            $entries = CahierTexte::findAllByPersonnel($user_id, $lycee_id);
        }

        require_once __DIR__ . '/../views/cahier_texte/index.php';
    }

    public function create() {
        $this->checkAccess();
        $professeur_id = Auth::get('id');
        $assignments = User::getTeacherAssignments($professeur_id);

        $entry = [];
        $is_edit = false;
        require_once __DIR__ . '/../views/cahier_texte/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $data['personnel_id'] = Auth::get('id');
            $data['ecole_id'] = Auth::get('lycee_id');

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

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /cahier-texte'); exit(); }

        $entry = CahierTexte::findById($id);

        // Security check: Teacher can only edit their own entries
        if (Auth::get('role_name') === 'enseignant' && $entry['personnel_id'] != Auth::get('id')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
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
            if (Auth::get('role_name') === 'enseignant' && $entry['personnel_id'] != Auth::get('id')) {
                 http_response_code(403);
                 echo "Accès Interdit.";
                 exit();
            }

            // Preserve original author and school
            $data['personnel_id'] = $entry['personnel_id'];
            $data['ecole_id'] = $entry['ecole_id'];

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
            if (Auth::get('role_name') === 'enseignant' && $entry['personnel_id'] != Auth::get('id')) {
                 http_response_code(403);
                 echo "Accès Interdit.";
                 exit();
            }
            CahierTexte::delete($id);
        }
        header('Location: /cahier-texte');
        exit();
    }
}
?>