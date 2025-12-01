<?php

require_once __DIR__ . '/../models/EmploiDuTemps.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Salle.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Auth.php';

class EmploiDuTempsController {

    private function checkAccess() {
        if (!Auth::can('manage', 'timetable')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();

        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::get('lycee_id') : null;
        $classes = Classe::findAll($lycee_id);
        $active_year = AnneeAcademique::getActive();
        $annee_academique_id = $active_year ? $active_year['id'] : null;

        // Default to the first class if none is selected
        $view_classe_id = $_GET['classe_id'] ?? ($classes[0]['id_classe'] ?? null);

        $timetable_entries = [];
        if ($annee_academique_id && $view_classe_id) {
            $timetable_entries = EmploiDuTemps::getByContext($annee_academique_id, $view_classe_id, null);
        }
        $timetable_grid = $this->buildGrid($timetable_entries);

        require_once __DIR__ . '/../views/emploi_du_temps/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::get('lycee_id') : null;
        $active_year = AnneeAcademique::getActive();
        if (!$active_year) {
            // Or handle this with a user-friendly error message
            die("Erreur : Aucune année académique active n'a été trouvée.");
        }

        $data = [
            'classes' => Classe::findAll($lycee_id),
            'matieres' => Matiere::findAll(),
            'professeurs' => User::findAll($lycee_id), // simplified
            'salles' => Salle::findAll($lycee_id),
            'annee_academique_id' => $active_year['id'],
        ];
        require_once __DIR__ . '/../views/emploi_du_temps/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // The active year ID is now passed from the form, so no need to fetch it again
            // as long as it's correctly set in the create form.
            if (!EmploiDuTemps::save($_POST)) {
                // Handle conflict error
                $_SESSION['error_message'] = "Conflit détecté ! Le professeur ou la classe est déjà occupé(e) à cette heure.";
                header('Location: /emploi-du-temps/create?error=conflict');
                exit();
            }
        }
        $_SESSION['success_message'] = "Le cours a été ajouté à l'emploi du temps avec succès.";
        header('Location: /emploi-du-temps');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            EmploiDuTemps::delete($id);
        }
        header('Location: /emploi-du-temps');
        exit();
    }

    private function buildGrid($entries) {
        $grid = [];
        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $hours = ['07:30', '08:30', '09:30', '10:30', '11:30', '12:30', '13:30', '14:30', '15:30', '16:30', '17:30'];

        foreach ($hours as $hour) {
            foreach ($days as $day) {
                $grid[$hour][$day] = null;
            }
        }

        foreach ($entries as $entry) {
            // This is a simplified mapping. A real implementation would handle multi-hour blocks.
            $start_hour = date('H:i', strtotime($entry['heure_debut']));
            if (isset($grid[$start_hour][$entry['jour']])) {
                $grid[$start_hour][$entry['jour']] = $entry;
            }
        }
        return $grid;
    }
}
?>
