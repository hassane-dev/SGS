<?php

require_once __DIR__ . '/../models/Settings.php';

class SettingsController {

    public function index() {
        // 1. Authentication Check
        if (!Auth::can('manage_own_lycee_settings')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }

        // For a multi-lycee app, the lycee_id would come from the user's session.
        // For now, we'll hardcode it to 1 for demonstration purposes.
        // In a real scenario, you'd create a Lycee first and assign it to the user.
        $lycee_id = Auth::get('lycee_id') ?? 1;

        $message = '';

        // 2. Handle POST request (form submission)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Settings::save($lycee_id, $_POST)) {
                $message = 'Paramètres enregistrés avec succès!';
            } else {
                $message = 'Erreur lors de l\'enregistrement des paramètres.';
            }
        }

        // 3. Fetch current settings for the view
        $settings = Settings::getByLyceeId($lycee_id);

        // 4. Display the view
        require_once __DIR__ . '/../views/settings/index.php';
    }
}
?>
