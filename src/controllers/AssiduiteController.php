<?php

require_once __DIR__ . '/../models/Assiduite.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class AssiduiteController {

    public function index() {
        $user_role = Auth::getRoleName();
        if ($user_role !== 'surveillant' && !Auth::can('view_all', 'assiduite')) { // 'assiduite:view_all' for censeur
            View::render('errors/403');
            exit();
        }

        $lycee_id = Auth::getLyceeId();
        $filters = [
            'classe_id' => $_GET['classe_id'] ?? null,
            'date_cours' => $_GET['date_cours'] ?? date('Y-m-d'),
            'statut' => $_GET['statut'] ?? 'Absent', // Default to showing absences
        ];

        $assiduite_records = Assiduite::findAllForLycee($lycee_id, $filters);

        // Data for filters
        $classes = Classe::findAll($lycee_id);

        View::render('assiduite/index', [
            'assiduite_records' => $assiduite_records,
            'classes' => $classes,
            'filters' => $filters,
            'title' => 'Tableau de Bord de l\'Assiduité'
        ]);
    }

    public function faireAppel() {
        if (Auth::getRoleName() !== 'enseignant') {
            View::render('errors/403');
            exit();
        }

        $enseignant_id = Auth::getUserId();
        // For simplicity, we'll let the teacher choose from all classes they are assigned to.
        // A better approach would be to link this to the emploi_du_temps (timetable).
        $classes_enseignees = User::findSubjectsTaughtByTeacher($enseignant_id);

        // If a class is selected, show the student list for attendance.
        if (isset($_GET['classe_id'])) {
            $classe_id = $_GET['classe_id'];

            // Security Check: Make sure the teacher is actually assigned to this class.
            $is_authorized = false;
            foreach ($classes_enseignees as $classe) {
                if ($classe['classe_id'] == $classe_id) {
                    $is_authorized = true;
                    break;
                }
            }
            if (!$is_authorized) {
                View::render('errors/403');
                exit();
            }

            $eleves = Eleve::findAllByClassIds([$classe_id]);
            View::render('assiduite/appel', [
                'eleves' => $eleves,
                'classe' => Classe::findById($classe_id),
                'title' => 'Faire l\'appel'
            ]);
        } else {
            // Otherwise, show a selection form.
            View::render('assiduite/select_classe', [
                'classes' => $classes_enseignees,
                'title' => 'Choisir une classe'
            ]);
        }
    }

    public function store() {
        if (Auth::getRoleName() !== 'enseignant' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('errors/403');
            exit();
        }

        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            // Handle error: no active year
            header('Location: /assiduite/faire-appel?error=no_active_year');
            exit();
        }

        $data = [
            'classe_id' => $_POST['classe_id'],
            'annee_academique_id' => $active_year['id'],
            'enseignant_id' => Auth::getUserId(),
            'date_cours' => $_POST['date_cours'],
            'heure_debut' => $_POST['heure_debut'],
            'heure_fin' => $_POST['heure_fin'],
            'lycee_id' => Auth::getLyceeId(),
            'presences' => $_POST['presences']
        ];

        if (Assiduite::saveBatch($data)) {
            // Redirect with success message
            header('Location: /assiduite/faire-appel?success=1');
        } else {
            // Redirect with error message
            header('Location: /assiduite/faire-appel?classe_id=' . $_POST['classe_id'] . '&error=1');
        }
        exit();
    }
}
