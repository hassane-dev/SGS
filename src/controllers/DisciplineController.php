<?php

require_once __DIR__ . '/../models/Discipline.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class DisciplineController {

    public function create() {
        if (Auth::getRoleName() !== 'surveillant' && !Auth::can('manage', 'discipline')) {
            View::render('errors/403');
            exit();
        }

        $lycee_id = Auth::getLyceeId();
        // For simplicity, we get all students. This could be filtered for supervisors.
        $eleves = Eleve::findAll($lycee_id);

        View::render('discipline/create', [
            'eleves' => $eleves,
            'title' => 'Signaler un Incident'
        ]);
    }

    public function store() {
        if (Auth::getRoleName() !== 'surveillant' && !Auth::can('manage', 'discipline') || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('errors/403');
            exit();
        }

        $active_year = AnneeAcademique::findActive();
        if (!$active_year) {
            header('Location: /discipline/create?error=no_active_year');
            exit();
        }

        $data = [
            'eleve_id' => $_POST['eleve_id'],
            'rapporteur_id' => Auth::getUserId(),
            'annee_academique_id' => $active_year['id'],
            'type' => $_POST['type'],
            'description' => $_POST['description'],
            'lycee_id' => Auth::getLyceeId()
        ];

        if (Discipline::save($data)) {
            header('Location: /?success=discipline_saved'); // Redirect to dashboard or a list view
        } else {
            header('Location: /discipline/create?error=1');
        }
        exit();
    }
}
