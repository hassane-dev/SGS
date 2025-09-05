<?php

require_once __DIR__ . '/../models/Bulletin.php';

class BulletinController {

    private function checkAuth() {
        // Allow teachers and admins to view bulletins
        if (!Auth::check() || !in_array(Auth::get('role'), ['enseignant', 'admin_local', 'super_admin_national'])) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function show() {
        $this->checkAuth();
        $etude_id = $_GET['etude_id'] ?? null;

        if (!$etude_id) {
            // Redirect somewhere sensible if no ID is provided
            header('Location: /');
            exit();
        }

        $bulletin_data = Bulletin::generateForEtude($etude_id);

        if (!$bulletin_data) {
            // Handle case where bulletin can't be generated
            echo "Erreur: Impossible de générer le bulletin pour l'inscription ID {$etude_id}.";
            exit();
        }

        require_once __DIR__ . '/../views/bulletins/show.php';
    }

}
?>
