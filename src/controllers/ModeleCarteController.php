<?php

// Force file recognition
require_once __DIR__ . '/../models/ModeleCarte.php';
require_once __DIR__ . '/../models/ParamLycee.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';


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
            $background_path = $_POST['current_background'] ?? null;

            // Handle background image upload
            if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/card_backgrounds/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $fileName = uniqid() . '-' . basename($_FILES['background_image']['name']);
                $targetFilePath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['background_image']['tmp_name'], $targetFilePath)) {
                    // Delete old background if it exists
                    if ($background_path && file_exists(__DIR__ . '/../../public' . $background_path)) {
                        unlink(__DIR__ . '/../../public' . $background_path);
                    }
                    $background_path = '/uploads/card_backgrounds/' . $fileName;
                }
            }

            $data = [
                'lycee_id' => $lycee_id,
                'nom_modele' => $_POST['nom_modele'] ?? 'Default Card Model',
                'layout_data' => $layout_data,
                'background' => $background_path
            ];

            ModeleCarte::save($data);
            header('Location: /modele-carte/edit?success=1');
            exit();
        }

        $modele = ModeleCarte::findByLyceeId($lycee_id);
        $params_lycee = ParamLycee::findByLyceeId($lycee_id);
        $annee_academique = AnneeAcademique::getActive();


        require_once __DIR__ . '/../views/modele_carte/edit.php';
    }
}
?>
