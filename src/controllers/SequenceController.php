<?php

require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class SequenceController {

    private function checkAccess($permission = 'sequence:manage') {
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $sequences = Sequence::findAll();
        View::render('sequences/index', [
            'sequences' => $sequences,
            'title' => 'Gestion des Séquences'
        ]);
    }

    public function create() {
        $this->checkAccess();
        View::render('sequences/create', [
            'title' => 'Nouvelle Séquence'
        ]);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Sequence::save($data);
        }
        header('Location: /sequences');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /sequences');
            exit();
        }
        $sequence = Sequence::findById($id);
        if (!$sequence) {
            http_response_code(404);
            View::render('errors/404');
            exit();
        }
        View::render('sequences/edit', [
            'sequence' => $sequence,
            'title' => 'Modifier la Séquence'
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            Sequence::save($data);
        }
        header('Location: /sequences');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $success = Sequence::delete($id);
            if (!$success) {
                header('Location: /sequences?error=delete_failed');
                exit();
            }
        }
        header('Location: /sequences');
        exit();
    }
}
?>