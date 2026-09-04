<?php

require_once __DIR__ . '/../models/ParamTypeEvaluation.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class ParamTypeEvaluationController {

    private function checkAccess($permission = 'evaluation:manage_settings') {
        list($resource, $action) = explode(':', $permission);
        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $types = ParamTypeEvaluation::findAll();
        View::render('evaluations/types_index', [
            'types' => $types,
            'title' => 'Types d\'Évaluation'
        ]);
    }

    public function create() {
        $this->checkAccess();
        View::render('evaluations/types_form', [
            'type' => null,
            'isEdit' => false,
            'title' => 'Nouveau Type d\'Évaluation'
        ]);
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                ParamTypeEvaluation::save($_POST);
                header('Location: /evaluations/types?success=created');
                exit();
            } catch (Exception $e) {
                View::render('evaluations/types_form', [
                    'type' => $_POST,
                    'isEdit' => false,
                    'error' => $e->getMessage(),
                    'title' => 'Nouveau Type d\'Évaluation'
                ]);
                exit();
            }
        }
        header('Location: /evaluations/types');
        exit();
    }

    public function edit($id = null) {
        $this->checkAccess();
        $id = $id ?? $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) {
            header('Location: /evaluations/types?error=missing_id');
            exit();
        }
        $type = ParamTypeEvaluation::findById($id);
        if (!$type) {
            header('Location: /evaluations/types?error=not_found');
            exit();
        }
        View::render('evaluations/types_form', [
            'type' => $type,
            'isEdit' => true,
            'title' => 'Modifier le Type d\'Évaluation'
        ]);
    }

    public function update($id = null) {
        $this->checkAccess();
        $id = $id ?? $_POST['id'] ?? $_GET['id'] ?? null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $_POST['id'] = $id;
                ParamTypeEvaluation::save($_POST);
                header('Location: /evaluations/types?success=updated');
                exit();
            } catch (Exception $e) {
                View::render('evaluations/types_form', [
                    'type' => array_merge($_POST, ['id' => $id]),
                    'isEdit' => true,
                    'error' => $e->getMessage(),
                    'title' => 'Modifier le Type d\'Évaluation'
                ]);
                exit();
            }
        }
        header('Location: /evaluations/types');
        exit();
    }

    public function toggle($id = null) {
        $this->checkAccess();
        $id = $id ?? $_GET['id'] ?? $_POST['id'] ?? null;
        if ($id) {
            ParamTypeEvaluation::toggleActive($id);
        }
        header('Location: /evaluations/types?success=toggled');
        exit();
    }
}
?>