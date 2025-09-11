<?php

require_once __DIR__ . '/../models/EmploiDuTemps.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Salle.php';

class EmploiDuTempsController {

    private function checkAccess() {
        if (!Auth::can('manage_classes')) { // Reuse this permission for now
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();

        $lycee_id = !Auth::can('manage_all_lycees') ? Auth::get('lycee_id') : null;
        $classes = Classe::findAll($lycee_id);

        // Default to the first class if none is selected
        $view_classe_id = $_GET['classe_id'] ?? ($classes[0]['id_classe'] ?? null);

        $annee_academique = '2024-2025'; // This should come from settings

        $timetable_entries = EmploiDuTemps::getByContext($annee_academique, $view_classe_id, null);
        $timetable_grid = $this->buildGrid($timetable_entries);

        require_once __DIR__ . '/../views/emploi_du_temps/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = !Auth::can('manage_all_lycees') ? Auth::get('lycee_id') : null;

        $data = [
            'classes' => Classe::findAll($lycee_id),
            'matieres' => Matiere::findAll(),
            'professeurs' => User::findAll($lycee_id), // simplified
            'salles' => Salle::findAll($lycee_id),
        ];
        require_once __DIR__ . '/../views/emploi_du_temps/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!EmploiDuTemps::save($_POST)) {
                // Handle conflict error
                header('Location: /emploi-du-temps/create?error=conflict');
                exit();
            }
        }
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
