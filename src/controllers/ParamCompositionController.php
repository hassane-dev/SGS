<?php

require_once __DIR__ . '/../models/ParamComposition.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class ParamCompositionController {

    private function checkAccess($permission = 'param_composition:edit') {
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function edit() {
        $this->checkAccess();
        $params = ParamComposition::findByCurrentSchoolAndYear();

        if (!$params) {
            View::render('errors/500', ['message' => 'Impossible de charger les paramètres des compositions.']);
            exit();
        }

        View::render('param_composition/edit', [
            'params' => $params,
            'title' => 'Paramètres des Compositions'
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $success = ParamComposition::update($data);
            if ($success) {
                header('Location: /param-composition/edit?success=1');
            } else {
                header('Location: /param-composition/edit?error=1');
            }
            exit();
        }
    }
}
?>