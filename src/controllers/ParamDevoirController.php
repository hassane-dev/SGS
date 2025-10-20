<?php

require_once __DIR__ . '/../models/ParamDevoir.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class ParamDevoirController {

    private function checkAccess($permission = 'param_devoir:edit') {
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function edit() {
        $this->checkAccess();
        $params = ParamDevoir::findByCurrentSchoolAndYear();

        if (!$params) {
            View::render('errors/500', ['message' => 'Impossible de charger les paramètres des devoirs.']);
            exit();
        }

        View::render('param_devoir/edit', [
            'params' => $params,
            'title' => 'Paramètres des Devoirs'
        ]);
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = ParamDevoir::update($_POST);
            if ($success) {
                header('Location: /param-devoir/edit?success=1');
            } else {
                header('Location: /param-devoir/edit?error=1');
            }
            exit();
        }
    }
}
?>