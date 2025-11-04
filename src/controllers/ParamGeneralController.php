<?php

require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class ParamGeneralController {

    private function checkAccess($permission = 'param_general:edit') {
        list($resource, $action) = explode(':', $permission);
        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function edit() {
        $this->checkAccess();
        $params = ParamGeneral::findByAuthenticatedUser();

        if (!$params) {
            View::render('errors/500', ['message' => 'Impossible de charger les paramètres généraux.']);
            exit();
        }

        View::render('param_general/edit', [
            'params' => $params,
            'title' => 'Paramètres Généraux'
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $success = ParamGeneral::update($data);
            if ($success) {
                header('Location: /param-general/edit?success=1');
            } else {
                header('Location: /param-general/edit?error=1');
            }
            exit();
        }
    }
}
?>