<?php

// Force file recognition
require_once __DIR__ . '/../models/ModeleCarte.php';

class ModeleCarteController {

    private function checkAccess() {
        // For now, let's assume local admins can manage their school's template
        if (!Auth::can('edit', 'param_lycee')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    /**
     * Show the editor for the card template or save it.
     */
    public function edit() {
        $this->checkAccess();

        // A local admin can only edit the template for their own lycee.
        // A super admin would need a way to select which lycee to edit.
        // For simplicity, we'll focus on the local admin case.
        $lycee_id = Auth::get('lycee_id');
        if (!$lycee_id) {
            // Or redirect to a page where they can select a lycee to manage
            die("Super admins must select a school to manage its template.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $layout_data = $_POST['layout_data'] ?? '{}';
            // Basic validation/sanitization should be done here in a real app
            $data = [
                'lycee_id' => $lycee_id,
                'nom_modele' => $_POST['nom_modele'] ?? 'Default Card Model',
                'layout_data' => $layout_data
                // Add other fields like background, fonts etc. later
            ];
            ModeleCarte::save($data);
            // Redirect back to the editor with a success message
            header('Location: /modele-carte/edit?success=1');
            exit();
        }

        $modele = ModeleCarte::findByLyceeId($lycee_id);
        require_once __DIR__ . '/../views/modele_carte/edit.php';
    }
}
?>
