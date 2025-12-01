<?php

require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class SequenceController {

    public function index() {
        if (!Auth::can('manage', 'sequence')) {
            View::render('errors/403');
            exit();
        }
        $sequences = Sequence::findAll();
        View::render('sequences/index', [
            'sequences' => $sequences,
            'title' => 'Gestion des Séquences'
        ]);
    }

    public function create() {
        if (!Auth::can('manage', 'sequence')) {
            View::render('errors/403');
            exit();
        }
        $data = $_SESSION['form_data'] ?? [];
        $error = $_SESSION['error_message'] ?? null;
        unset($_SESSION['form_data'], $_SESSION['error_message']);

        View::render('sequences/create', [
            'title' => 'Nouvelle Séquence',
            'sequence' => $data,
            'error' => $error
        ]);
    }

    public function store() {
        if (!Auth::can('manage', 'sequence')) {
            View::render('errors/403');
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            try {
                Sequence::save($data);
                $_SESSION['success_message'] = "La séquence a été créée avec succès.";
                header('Location: /sequences');
                exit();
            } catch (InvalidArgumentException $e) {
                $_SESSION['error_message'] = $e->getMessage();
                $_SESSION['form_data'] = $data;
                header('Location: /sequences/create');
                exit();
            }
        }
        header('Location: /sequences');
        exit();
    }

    public function edit() {
        if (!Auth::can('manage', 'sequence')) {
            View::render('errors/403');
            exit();
        }
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            header('Location: /sequences');
            exit();
        }

        $sequence = $_SESSION['form_data'] ?? Sequence::findById($id);
        $error = $_SESSION['error_message'] ?? null;
        unset($_SESSION['form_data'], $_SESSION['error_message']);

        if (!$sequence) {
            View::render('errors/404');
            exit();
        }
        View::render('sequences/edit', [
            'sequence' => $sequence,
            'title' => 'Modifier la Séquence',
            'error' => $error
        ]);
    }

    public function update() {
        if (!Auth::can('manage', 'sequence')) {
            View::render('errors/403');
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $id = $data['id'] ?? null;

            try {
                Sequence::save($data);
                $_SESSION['success_message'] = "La séquence a été mise à jour avec succès.";
                header('Location: /sequences');
                exit();
            } catch (InvalidArgumentException $e) {
                $_SESSION['error_message'] = $e->getMessage();
                $_SESSION['form_data'] = $data;
                header('Location: /sequences/edit?id=' . $id);
                exit();
            }
        }
        header('Location: /sequences');
        exit();
    }

    public function destroy() {
        if (!Auth::can('manage', 'sequence')) {
            View::render('errors/403');
            exit();
        }
        $id = $_POST['id'] ?? null;
        if ($id) {
            $success = Sequence::delete($id);
            if ($success) {
                $_SESSION['success_message'] = "La séquence a été supprimée avec succès.";
            } else {
                $_SESSION['error_message'] = "La suppression a échoué. La séquence est peut-être utilisée.";
            }
        }
        header('Location: /sequences');
        exit();
    }
}
?>
