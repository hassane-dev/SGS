<?php

require_once __DIR__ . '/../models/Presence.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class PresenceController {

    private function checkAccess($action = 'manage') {
        if (!Auth::can($action, 'presence')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function gerer($classe_id) {
        $this->checkAccess();
        $date = $_GET['date'] ?? date('Y-m-d');

        $classe = Classe::findById($classe_id);
        if (!$classe) {
            header('Location: /');
            exit();
        }

        $eleves = Eleve::findByClass($classe_id);
        $existing_presences = Presence::findByClassAndDate($classe_id, $date);

        // Index existing presences by eleve_id for easier lookup in view
        $presences_map = [];
        foreach ($existing_presences as $p) {
            $presences_map[$p['eleve_id']] = $p;
        }

        View::render('presences/gerer', [
            'classe' => $classe,
            'eleves' => $eleves,
            'date' => $date,
            'presences_map' => $presences_map,
            'title' => 'Gestion des Présences'
        ]);
    }

    public function store() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'];
            $date = $_POST['date_presence'];
            $active_year = AnneeAcademique::findActive();

            $data = [
                'classe_id' => $classe_id,
                'date_presence' => $date,
                'enseignant_id' => Auth::getUserId(),
                'annee_academique_id' => $active_year['id'],
                'lycee_id' => Auth::getLyceeId(),
                'presences' => $_POST['presences'] ?? []
            ];

            Presence::saveAll($data);

            header("Location: /presences/gerer/$classe_id?date=$date&success=1");
            exit();
        }
    }
}
