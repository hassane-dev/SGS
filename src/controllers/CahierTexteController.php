<?php

require_once __DIR__ . '/../models/CahierTexte.php';
require_once __DIR__ . '/../models/User.php';

class CahierTexteController {

    private function checkTeacher() {
        if (!Auth::check() || Auth::get('role_name') !== 'enseignant') {
            http_response_code(403);
            echo "Accès Interdit. Cette section est réservée aux enseignants.";
            exit();
        }
    }

    public function index() {
        $this->checkTeacher();
        $professeur_id = Auth::get('id');
        $lycee_id = Auth::get('lycee_id');
        $entries = CahierTexte::findByTeacher($professeur_id, $lycee_id);
        require_once __DIR__ . '/../views/cahier_texte/index.php';
    }

    public function create() {
        $this->checkTeacher();
        $professeur_id = Auth::get('id');
        // Get the classes/subjects this teacher is assigned to
        $assignments = User::getTeacherAssignments($professeur_id);
        require_once __DIR__ . '/../views/cahier_texte/create.php';
    }

    public function store() {
        $this->checkTeacher();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['professeur_id'] = Auth::get('id');
            $data['lycee_id'] = Auth::get('lycee_id');

            // The form will submit a combined value for class and subject
            list($classe_id, $matiere_id) = explode('-', $data['class_subject']);
            $data['classe_id'] = $classe_id;
            $data['matiere_id'] = $matiere_id;

            CahierTexte::create($data);
        }
        header('Location: /cahier-texte');
        exit();
    }

    public function destroy() {
        $this->checkTeacher();
        $id = $_POST['id'] ?? null;
        if ($id) {
            // Add security check to ensure teacher can only delete their own entries
            CahierTexte::delete($id);
        }
        header('Location: /cahier-texte');
        exit();
    }
}
?>
