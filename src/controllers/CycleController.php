<?php

require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../core/Validator.php';

class CycleController {

    private function checkAccess() {
        if (!Auth::can('manage', 'cycle')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $cycles = Cycle::findAll();
        // Pass a potential error message from delete action
        $error = $_GET['error'] ?? null;
        require_once __DIR__ . '/../views/cycles/index.php';
    }

    public function create() {
        $this->checkAccess();
        require_once __DIR__ . '/../views/cycles/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);

            $hasDebut = !empty($data['niveau_debut']);
            $hasFin = !empty($data['niveau_fin']);

            if ($hasDebut !== $hasFin) {
                $_SESSION['error_message'] = _("Veuillez renseigner à la fois le niveau de début et le niveau de fin, ou laisser les deux champs vides.");
                if (!headers_sent()) { header('Location: /cycles/create'); exit(); } return;
            }

            if ($hasDebut && $hasFin) {
                $start = Classe::resolveLevelOrder($data['niveau_debut']);
                $end = Classe::resolveLevelOrder($data['niveau_fin']);

                if ($start === null || $end === null || $start > $end) {
                    $_SESSION['error_message'] = sprintf(_("Les bornes de niveau saisies ('%s' à '%s') ne sont pas valides ou reconnues par le système."), $data['niveau_debut'], $data['niveau_fin']);
                    if (!headers_sent()) { header('Location: /cycles/create'); exit(); } return;
                }
            }

            Cycle::save($data);
        }
        header('Location: /cycles');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /cycles');
            exit();
        }
        $cycle = Cycle::findById($id);
        require_once __DIR__ . '/../views/cycles/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $id = $data['id_cycle'] ?? '';

            $hasDebut = !empty($data['niveau_debut']);
            $hasFin = !empty($data['niveau_fin']);

            if ($hasDebut !== $hasFin) {
                $_SESSION['error_message'] = _("Veuillez renseigner à la fois le niveau de début et le niveau de fin, ou laisser les deux champs vides.");
                if (!headers_sent()) { header('Location: /cycles/edit?id=' . $id); exit(); } return;
            }

            if ($hasDebut && $hasFin) {
                $start = Classe::resolveLevelOrder($data['niveau_debut']);
                $end = Classe::resolveLevelOrder($data['niveau_fin']);

                if ($start === null || $end === null || $start > $end) {
                    $_SESSION['error_message'] = sprintf(_("Les bornes de niveau saisies ('%s' à '%s') ne sont pas valides ou reconnues par le système."), $data['niveau_debut'], $data['niveau_fin']);
                    if (!headers_sent()) { header('Location: /cycles/edit?id=' . $id); exit(); } return;
                }
            }

            Cycle::save($data);
        }
        header('Location: /cycles');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $success = Cycle::delete($id);
            if (!$success) {
                // Redirect with an error message if delete fails (e.g., foreign key constraint)
                header('Location: /cycles?error=delete_failed');
                exit();
            }
        }
        header('Location: /cycles');
        exit();
    }
}
?>
