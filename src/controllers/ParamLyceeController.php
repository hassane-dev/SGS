<?php

require_once __DIR__ . '/../models/ParamLycee.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class ParamLyceeController {

    private function checkAccess($permission = 'param_lycee:edit') {
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function edit() {
        $this->checkAccess();
        $params = ParamLycee::findByAuthenticatedUser();

        if (!$params) {
            // Should not happen for a logged-in user of a school
            View::render('errors/500', ['message' => 'Impossible de charger les paramètres du lycée.']);
            exit();
        }

        View::render('param_lycee/edit', [
            'params' => $params,
            'title' => 'Paramètres du Lycée'
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = ParamLycee::update($_POST);
            if ($success) {
                header('Location: /param-lycee/edit?success=1');
            } else {
                header('Location: /param-lycee/edit?error=1');
            }
            exit();
        }
    }
}
?>