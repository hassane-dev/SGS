<?php

require_once __DIR__ . '/../models/Serie.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class SerieController {

    private function checkAccess() {
        if (!Auth::can('manage', 'series')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = Auth::getLyceeId();
        $series = Serie::findAll($lycee_id);
        View::render('series/index', [
            'series' => $series,
            'title' => 'Gestion des Séries'
        ]);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $data['lycee_id'] = Auth::getLyceeId();
            Serie::save($data);
        }
        header('Location: /series');
        exit();
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Serie::save($data);
        }
        header('Location: /series');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            Serie::delete($id);
        }
        header('Location: /series');
        exit();
    }
}
