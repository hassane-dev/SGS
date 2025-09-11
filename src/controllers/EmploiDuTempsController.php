<?php

require_once __DIR__ . '/../models/EmploiDuTemps.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Salle.php';

class EmploiDuTempsController {

    private function checkAccess() {
        if (!Auth::check() || !Auth::can('manage_timetable')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function create() {
        $this->checkAccess();

        // Get data for the form
        $lycee_id = Auth::get('lycee_id');
        $settings = Settings::getByLyceeId($lycee_id);
        $annee_academique = $settings['annee_academique'] ?? date('Y') . '-' . (date('Y') + 1);
        $matieres = Matiere::findAll();
        $professeurs = User::findByRole('enseignant', $lycee_id);
        $salles = Salle::findByLycee($lycee_id);

        require_once __DIR__ . '/../views/emploi_du_temps/edit.php';
    }

    public function store() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'classe_id' => $_POST['classe_id'],
                'matiere_id' => $_POST['matiere_id'],
                'professeur_id' => $_POST['professeur_id'],
                'jour' => $_POST['jour'],
                'heure_debut' => $_POST['heure_debut'],
                'heure_fin' => $_POST['heure_fin'],
                'salle_id' => $_POST['salle_id'],
                'annee_academique' => $_POST['annee_academique'],
                'modifiable' => $_POST['modifiable'] ?? 1
            ];

            $conflicts = EmploiDuTemps::checkConflicts($data);

            if (empty($conflicts)) {
                EmploiDuTemps::save($data);
                header('Location: /emploi-du-temps?classe_id=' . $data['classe_id']);
                exit();
            } else {
                // Show the form again with error messages
                $entry = $data;
                $errors = $conflicts;
                $lycee_id = Auth::get('lycee_id');
                $matieres = Matiere::findAll();
                $professeurs = User::findByRole('enseignant', $lycee_id);
                $salles = Salle::findByLycee($lycee_id);
                require_once __DIR__ . '/../views/emploi_du_temps/edit.php';
            }
        }
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $entry = EmploiDuTemps::findById($id);
            EmploiDuTemps::delete($id);
            header('Location: /emploi-du-temps?classe_id=' . $entry['classe_id']);
            exit();
        }
        header('Location: /emploi-du-temps');
        exit();
    }

    public function index() {
        $this->checkAccess();

        $lycee_id = Auth::get('lycee_id');
        if (!$lycee_id) {
            // Or handle this case more gracefully
            die("Utilisateur non associé à un lycée.");
        }

        $settings = Settings::getByLyceeId($lycee_id);
        $annee_academique = $settings['annee_academique'] ?? date('Y') . '-' . (date('Y') + 1);

        $classe_id = $_GET['classe_id'] ?? null;

        if (!$classe_id) {
            // If no class is selected, show a list of classes to choose from
            $classes = Classe::findAll($lycee_id);
            require_once __DIR__ . '/../views/emploi_du_temps/select_class.php';
            return;
        }

        $timetable = EmploiDuTemps::findByClass($classe_id, $annee_academique);
        $classe = Classe::findById($classe_id);

        // Group timetable by day
        $grouped_timetable = [];
        foreach ($timetable as $entry) {
            $grouped_timetable[$entry['jour']][] = $entry;
        }

        require_once __DIR__ . '/../views/emploi_du_temps/index.php';
    }

    public function edit() {
        $this->checkAccess();

        $entry_id = $_GET['id'] ?? null;
        if (!$entry_id) {
            die("ID de l'entrée manquant.");
        }

        $entry = EmploiDuTemps::findById($entry_id);
        if (!$entry) {
            die("Entrée non trouvée.");
        }

        // Get data for the form
        $lycee_id = Auth::get('lycee_id');
        $settings = Settings::getByLyceeId($lycee_id);
        $annee_academique = $settings['annee_academique'] ?? date('Y') . '-' . (date('Y') + 1);
        $matieres = Matiere::findAll();
        $professeurs = User::findByRole('enseignant', $lycee_id);
        $salles = Salle::findByLycee($lycee_id);

        require_once __DIR__ . '/../views/emploi_du_temps/edit.php';
    }

    public function update() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'id' => $_POST['id'],
                'classe_id' => $_POST['classe_id'],
                'matiere_id' => $_POST['matiere_id'],
                'professeur_id' => $_POST['professeur_id'],
                'jour' => $_POST['jour'],
                'heure_debut' => $_POST['heure_debut'],
                'heure_fin' => $_POST['heure_fin'],
                'salle_id' => $_POST['salle_id'],
                'annee_academique' => $_POST['annee_academique'],
                'modifiable' => $_POST['modifiable'] ?? 1
            ];

            $conflicts = EmploiDuTemps::checkConflicts($data);

            if (empty($conflicts)) {
                EmploiDuTemps::save($data);
                header('Location: /emploi-du-temps?classe_id=' . $data['classe_id']);
                exit();
            } else {
                // Show the edit form again with error messages
                $entry = $data;
                $errors = $conflicts;
                $lycee_id = Auth::get('lycee_id');
                $matieres = Matiere::findAll();
                $professeurs = User::findByRole('enseignant', $lycee_id);
                $salles = Salle::findByLycee($lycee_id);
                require_once __DIR__ . '/../views/emploi_du_temps/edit.php';
            }
        }
    }
}
