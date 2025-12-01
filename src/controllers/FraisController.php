<?php

require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class FraisController {

    private function checkAccess() {
        if (!Auth::can('manage', 'frais')) {
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = Auth::get('lycee_id');
        $activeYear = AnneeAcademique::getActive();

        $frais = [];
        if ($lycee_id && $activeYear) {
            $frais = Frais::findByLyceeAndYear($lycee_id, $activeYear['id']);
        }

        View::render('frais/index', [
            'title' => 'Grille Tarifaire',
            'frais' => $frais,
            'activeYear' => $activeYear
        ]);
    }

    public function create() {
        $this->checkAccess();
        $lycee_id = Auth::get('lycee_id');
        $activeYear = AnneeAcademique::getActive();

        View::render('frais/create', [
            'title' => 'Ajouter une Grille Tarifaire',
            'lycee_id' => $lycee_id,
            'activeYear' => $activeYear
        ]);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /frais');
            exit();
        }

        $data = Validator::sanitize($_POST);
        $lycee_id = Auth::get('lycee_id');
        $activeYear = AnneeAcademique::getActive();

        $data['lycee_id'] = $lycee_id;
        $data['annee_academique_id'] = $activeYear['id'];

        try {
            Frais::save($data);
            $_SESSION['success_message'] = "La grille tarifaire a été enregistrée avec succès.";
        } catch (InvalidArgumentException $e) {
            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['old_input'] = $data;
            header('Location: /frais/create');
            exit();
        }

        header('Location: /frais');
        exit();
    }

    // --- AJAX Endpoints ---

    /**
     * AJAX: Get all distinct levels for the current lycee.
     */
    public function getNiveaux() {
        if (!Auth::check()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }
        $lycee_id = Auth::get('lycee_id');
        $niveaux = Classe::getDistinctNiveaux($lycee_id);

        header('Content-Type: application/json');
        echo json_encode($niveaux);
        exit();
    }

    /**
     * AJAX: Get all distinct series for the current lycee.
     */
    public function getSeries() {
        if (!Auth::check()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }
        $lycee_id = Auth::get('lycee_id');
        $series = Classe::getDistinctSeries($lycee_id);

        header('Content-Type: application/json');
        echo json_encode($series);
        exit();
    }
}
?>