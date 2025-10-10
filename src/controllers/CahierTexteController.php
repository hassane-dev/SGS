<?php

require_once __DIR__ . '/../models/CahierTexte.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';

class CahierTexteController {


    public function index() {
        // A user must be able to view all entries or create their own to see this page.
        if (!Auth::can('cahier_texte', 'view_all') && !Auth::can('cahier_texte', 'create_own')) {
            http_response_code(403); echo "Accès Interdit."; exit();
        }

        $user_id = Auth::get('id');
        $lycee_id = Auth::get('lycee_id');
        $is_admin = Auth::can('cahier_texte', 'view_all');

        $filters = [
            'personnel_id_filter' => $_GET['personnel_id'] ?? null,
            'classe_id_filter' => $_GET['classe_id'] ?? null,
            'date_filter' => $_GET['date'] ?? null,
        ];

        $entries = [];
        $teachers = [];
        $classes = [];

        if ($is_admin) {
            $entries = CahierTexte::findAllByPersonnel(null, $lycee_id, $filters);
            $teachers = User::findAllByRoleName('enseignant', $lycee_id);
            $classes = Classe::findAll($lycee_id);
        } else {
            $entries = CahierTexte::findAllByPersonnel($user_id, $lycee_id);
        }

        require_once __DIR__ . '/../views/cahier_texte/index.php';
    }

    public function create() {
        if (!Auth::can('cahier_texte', 'create_own')) { http_response_code(403); echo "Accès Interdit."; exit(); }

        $professeur_id = Auth::get('id');
        $assignments = User::getTeacherAssignments($professeur_id);

        $entry = [];
        $is_edit = false;
        require_once __DIR__ . '/../views/cahier_texte/create.php';
    }

    public function store() {
        if (!Auth::can('cahier_texte', 'create_own')) { http_response_code(403); echo "Accès Interdit."; exit(); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['personnel_id'] = Auth::get('id');
            $data['ecole_id'] = Auth::get('lycee_id');

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
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /cahier-texte'); exit(); }

        $entry = CahierTexte::findById($id);
        if (!$entry) { header('Location: /cahier-texte'); exit(); }

        // Security check: must be able to edit own entries OR all entries.
        if (!Auth::can('cahier_texte', 'edit_own') && !Auth::can('cahier_texte', 'edit_all')) {
             http_response_code(403); echo "Accès Interdit."; exit();
        }
        // Scope check: if user CANNOT edit all, they must be the owner.
        if (!Auth::can('cahier_texte', 'edit_all') && $entry['personnel_id'] != Auth::get('id')) {
            http_response_code(403); echo "Accès Interdit."; exit();
        }

        $professeur_id = $entry['personnel_id'];
        $assignments = User::getTeacherAssignments($professeur_id);
        $is_edit = true;
        require_once __DIR__ . '/../views/cahier_texte/edit.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $entry = CahierTexte::findById($data['cahier_id']);
            if (!$entry) { header('Location: /cahier-texte'); exit(); }

            // Security check
            if (!Auth::can('cahier_texte', 'edit_all') && $entry['personnel_id'] != Auth::get('id')) {
                 http_response_code(403); echo "Accès Interdit."; exit();
            }

            $data['personnel_id'] = $entry['personnel_id'];
            $data['ecole_id'] = $entry['ecole_id'];

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
        $id = $_POST['id'] ?? null;
        if (!$id) { header('Location: /cahier-texte'); exit(); }

        $entry = CahierTexte::findById($id);
        if (!$entry) { header('Location: /cahier-texte'); exit(); }

        // Let's assume only admins can delete for now. This can be a new permission later.
        if (!Auth::can('cahier_texte', 'edit_all')) {
             http_response_code(403); echo "Accès Interdit."; exit();
        }

        CahierTexte::delete($id);
        header('Location: /cahier-texte');
        exit();
    }
}
?>