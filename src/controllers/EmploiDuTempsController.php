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

        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();
        $annee_academique_id = $active_year ? $active_year['id'] : null;

        $view_mode = $_GET['view_mode'] ?? 'classe'; // 'classe', 'professeur', 'salle'
        $cycle_id = !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null;
        $niveau = !empty($_GET['niveau']) ? $_GET['niveau'] : null;

        $view_classe_id = !empty($_GET['classe_id']) ? (int)$_GET['classe_id'] : null;
        $view_professeur_id = !empty($_GET['professeur_id']) ? (int)$_GET['professeur_id'] : null;
        $view_salle_id = !empty($_GET['salle_id']) ? (int)$_GET['salle_id'] : null;

        $classes = Classe::findAll($lycee_id);
        $professeurs = User::findAll($lycee_id);
        $salles = Salle::findAll($lycee_id);

        require_once __DIR__ . '/../models/Cycle.php';
        $cycles = Cycle::findAll($lycee_id);

        // Auto-select first class/prof/room if none specified
        if ($view_mode === 'classe' && !$view_classe_id && !empty($classes)) {
            $view_classe_id = (int)$classes[0]['id_classe'];
        } elseif ($view_mode === 'professeur' && !$view_professeur_id && !empty($professeurs)) {
            $view_professeur_id = (int)$professeurs[0]['id_user'];
        } elseif ($view_mode === 'salle' && !$view_salle_id && !empty($salles)) {
            $view_salle_id = (int)$salles[0]['id_salle'];
        }

        $timetable_entries = [];
        if ($annee_academique_id) {
            $timetable_entries = EmploiDuTemps::getByContext(
                $annee_academique_id,
                ($view_mode === 'classe') ? $view_classe_id : null,
                ($view_mode === 'professeur') ? $view_professeur_id : null,
                ($view_mode === 'salle') ? $view_salle_id : null,
                $lycee_id
            );
        }

        $timetable_grid = $this->buildGrid($timetable_entries);

        require_once __DIR__ . '/../views/emploi_du_temps/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            $_SESSION['error_message'] = "Erreur : Aucune année académique active n'a été trouvée.";
            header('Location: /emploi-du-temps');
            exit();
        }

        $data = [
            'classes' => Classe::findAll($lycee_id),
            'matieres' => Matiere::findAll(),
            'professeurs' => User::findAll($lycee_id),
            'salles' => Salle::findAll($lycee_id),
            'annee_academique_id' => $active_year['id'],
        ];
        require_once __DIR__ . '/../views/emploi_du_temps/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lycee_id = Auth::getLyceeId();
            if (EmploiDuTemps::save($_POST, $lycee_id)) {
                $_SESSION['success_message'] = "Le cours a été ajouté à l'emploi du temps avec succès.";
                header('Location: /emploi-du-temps');
                exit();
            } else {
                header('Location: /emploi-du-temps/create?error=conflict');
                exit();
            }
        }
        header('Location: /emploi-du-temps');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $lycee_id = Auth::getLyceeId();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /emploi-du-temps');
            exit();
        }

        $cours = EmploiDuTemps::findById($id, $lycee_id);
        if (!$cours) {
            $_SESSION['error_message'] = "Le cours demandé n'existe pas ou n'appartient pas à votre établissement.";
            header('Location: /emploi-du-temps');
            exit();
        }

        $data = [
            'cours' => $cours,
            'classes' => Classe::findAll($lycee_id),
            'matieres' => Matiere::findAll(),
            'professeurs' => User::findAll($lycee_id),
            'salles' => Salle::findAll($lycee_id),
            'annee_academique_id' => $cours['annee_academique_id'],
        ];
        require_once __DIR__ . '/../views/emploi_du_temps/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lycee_id = Auth::getLyceeId();
            $id = $_POST['id'] ?? null;
            if (!$id) {
                header('Location: /emploi-du-temps');
                exit();
            }

            if (EmploiDuTemps::save($_POST, $lycee_id)) {
                $_SESSION['success_message'] = "Le cours a été mis à jour avec succès.";
                header('Location: /emploi-du-temps');
                exit();
            } else {
                header('Location: /emploi-du-temps/edit?id=' . urlencode($id) . '&error=conflict');
                exit();
            }
        }
        header('Location: /emploi-du-temps');
        exit();
    }

    public function swap() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id1 = $_POST['id1'] ?? null;
            $id2 = $_POST['id2'] ?? null;
            $lycee_id = Auth::getLyceeId();

            if ($id1 && $id2) {
                if (EmploiDuTemps::swap($id1, $id2, $lycee_id)) {
                    $_SESSION['success_message'] = "La permutation des cours a été réalisée avec succès.";
                }
            } else {
                $_SESSION['error_message'] = "Veuillez sélectionner deux cours valides pour réaliser une permutation.";
            }
        }
        header('Location: /emploi-du-temps');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        $lycee_id = Auth::getLyceeId();
        if ($id) {
            if (EmploiDuTemps::delete($id, $lycee_id)) {
                $_SESSION['success_message'] = "Le cours a été supprimé de l'emploi du temps.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la suppression du cours.";
            }
        }
        header('Location: /emploi-du-temps');
        exit();
    }

    public function print() {
        $this->checkAccess();
        $lycee_id = Auth::getLyceeId();
        $active_year = AnneeAcademique::findActive();
        $annee_academique_id = $active_year ? $active_year['id'] : null;

        $view_mode = $_GET['view_mode'] ?? 'classe';
        $view_classe_id = !empty($_GET['classe_id']) ? (int)$_GET['classe_id'] : null;
        $view_professeur_id = !empty($_GET['professeur_id']) ? (int)$_GET['professeur_id'] : null;
        $view_salle_id = !empty($_GET['salle_id']) ? (int)$_GET['salle_id'] : null;

        $context_title = "Emploi du Temps";
        if ($view_mode === 'classe' && $view_classe_id) {
            $c = Classe::findById($view_classe_id);
            if ($c) $context_title = "Emploi du Temps — Classe " . Classe::getFormattedName($c);
        } elseif ($view_mode === 'professeur' && $view_professeur_id) {
            $p = User::findById($view_professeur_id);
            if ($p) $context_title = "Emploi du Temps — Enseignant " . $p['prenom'] . " " . $p['nom'];
        } elseif ($view_mode === 'salle' && $view_salle_id) {
            $s = Salle::findById($view_salle_id);
            if ($s) $context_title = "Emploi du Temps — Salle " . $s['nom_salle'];
        }

        $entries = [];
        if ($annee_academique_id) {
            $entries = EmploiDuTemps::getByContext(
                $annee_academique_id,
                ($view_mode === 'classe') ? $view_classe_id : null,
                ($view_mode === 'professeur') ? $view_professeur_id : null,
                ($view_mode === 'salle') ? $view_salle_id : null,
                $lycee_id
            );
        }

        $grid = $this->buildGrid($entries);
        require_once __DIR__ . '/../views/emploi_du_temps/print.php';
    }

    /**
     * Build dynamic timetable grid driven strictly by recorded start/end time intervals.
     * Guarantees zero hardcoded time slots and renders arbitrary start times/durations.
     */
    private function buildGrid($entries) {
        $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $intervalsMap = [];

        foreach ($entries as $e) {
            $start = substr($e['heure_debut'], 0, 5);
            $end = substr($e['heure_fin'], 0, 5);
            $key = $start . ' - ' . $end;
            if (!isset($intervalsMap[$key])) {
                $intervalsMap[$key] = [
                    'start' => $start,
                    'end' => $end,
                    'label' => $key,
                ];
            }
        }

        // Sort intervals chronologically
        usort($intervalsMap, function($a, $b) {
            return strcmp($a['start'], $b['start']) ?: strcmp($a['end'], $b['end']);
        });

        $grid = [];
        foreach ($intervalsMap as $slotKey => $slot) {
            foreach ($days as $day) {
                $grid[$slotKey][$day] = [];
            }
        }

        foreach ($entries as $e) {
            $start = substr($e['heure_debut'], 0, 5);
            $end = substr($e['heure_fin'], 0, 5);
            $slotKey = $start . ' - ' . $end;
            if (isset($grid[$slotKey][$e['jour']])) {
                $grid[$slotKey][$e['jour']][] = $e;
            }
        }

        return [
            'days' => $days,
            'intervals' => array_values($intervalsMap),
            'grid' => $grid,
            'raw_entries' => $entries,
        ];
    }
}
?>
