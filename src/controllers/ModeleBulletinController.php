<?php

require_once __DIR__ . '/../models/ModeleBulletin.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class ModeleBulletinController {

    private function checkAccess($permission = 'bulletin_template:manage') {
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    // Show the editor interface
    public function edit() {
        $this->checkAccess();

        $template = ModeleBulletin::findByLyceeId();

        if (!$template) {
            // Handle case where a template could not be found or created
            View::render('errors/500', ['message' => "Impossible de charger le modèle de bulletin."]);
            exit();
        }

        View::render('modele_bulletin/edit', [
            'template' => $template,
            'title' => 'Éditeur de Modèle de Bulletin'
        ]);
    }

    // Save the new layout
    public function save() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $layout_order = $_POST['layout_order'] ?? [];
            $template_id = $_POST['template_id'] ?? null;

            if ($template_id && !empty($layout_order)) {
                ModeleBulletin::saveLayout($template_id, $layout_order);
                // Send a success response
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Mise en page enregistrée.']);
                exit();
            }
        }

        // Send an error response
        header('Content-Type: application/json', true, 400);
        echo json_encode(['status' => 'error', 'message' => 'Données invalides.']);
        exit();
    }
}
?>