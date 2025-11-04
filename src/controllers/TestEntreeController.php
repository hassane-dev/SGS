<?php

require_once __DIR__ . '/../models/TestEntree.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Classe.php';

class TestEntreeController {

    private function checkAccess() {
        if (!Auth::can('manage', 'tests_entree')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        $tests = TestEntree::findByEleveId($eleve_id);
        require_once __DIR__ . '/../views/tests_entree/index.php';
    }

    public function create() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        $lycee_id = !Auth::can('view_all_lycees', 'system') ? Auth::get('lycee_id') : null;
        $classes = Classe::findAll($lycee_id);
        require_once __DIR__ . '/../views/tests_entree/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            TestEntree::create($_POST);
            header('Location: /tests_entree?eleve_id=' . $_POST['eleve_id']);
            exit();
        }
        header('Location: /eleves');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        $eleve_id = $_POST['eleve_id'] ?? null;
        if ($id) {
            TestEntree::delete($id);
        }
        header('Location: /tests_entree?eleve_id=' . $eleve_id);
        exit();
    }
}
?>
