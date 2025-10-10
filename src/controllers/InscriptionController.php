<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/Settings.php'; // To get current academic year

class InscriptionController {

    private function checkAccess() {
        if (!Auth::can('manage_inscriptions')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function showForm() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }

        $eleve = Eleve::findById($eleve_id);

        $lycee_id = !Auth::can('manage_all_lycees') ? Auth::get('lycee_id') : null;

        // A super admin needs to know which lycee this student might belong to.
        // This is a simplification; a real app might need a more robust way to determine this.
        if (Auth::can('manage_all_lycees') && !$lycee_id) {
            // Find the lycee of the first class the student was in, if any.
            $enrollments = Etude::findByEleveId($eleve_id);
            if (!empty($enrollments)) {
                $first_enrollment_classe = Classe::findById($enrollments[0]['classe_id']);
                $lycee_id = $first_enrollment_classe['lycee_id'];
            } else {
                // If the student is new, the super admin must choose a lycee.
                // For now, we'll just show all classes which is not ideal.
            }
        }

        $classes = Classe::findAll($lycee_id);

        // Get the current academic year from settings (assuming lycee_id 1 for now)
        $settings = Settings::getByLyceeId($lycee_id ?? 1);
        $annee_academique = $settings['annee_academique'] ?? date('Y') . '-' . (date('Y') + 1);

        require_once __DIR__ . '/../views/inscriptions/enroll.php';
    }

    public function enroll() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Etude::create($_POST);
        }
        // Redirect back to the main student list
        header('Location: /eleves');
        exit();
    }
}
?>
