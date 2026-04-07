<?php

require_once __DIR__ . '/../models/TypeContrat.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/View.php';

class TypeContratController {

    private function checkAccess() {
        if (!Auth::can('manage', 'user')) { // Reuse this permission
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::get('lycee_id') : null;
        $contrats = TypeContrat::findAll($lycee_id);
        require_once __DIR__ . '/../views/type_contrat/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];
        $contrat = [];
        $is_edit = false;
        require_once __DIR__ . '/../views/type_contrat/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            TypeContrat::save($data);
        }
        header('Location: /contrats');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /contrats'); exit(); }

        $contrat = TypeContrat::findById($id);
        if (!$contrat) { header('Location: /contrats'); exit(); }

        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];
        $is_edit = true;
        require_once __DIR__ . '/../views/type_contrat/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            TypeContrat::save($data);
        }
        header('Location: /contrats');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            TypeContrat::delete($id);
        }
        header('Location: /contrats');
        exit();
    }
}
?>
