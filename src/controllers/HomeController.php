<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Eleve.php'; // For stats
require_once __DIR__ . '/../models/Notification.php'; // For stats

class HomeController {

    public function index() {
        if (!Auth::isLoggedIn()) {
            header('Location: /login');
            exit();
        }

        $lycee_id = Auth::get('lycee_id');
        $user_id = Auth::get('id');

        // Example stats for the dashboard
        $pending_enrollments_count = count(Eleve::findByStatus('en_attente_paiement', $lycee_id));

        // Pass data to the view
        $data = [
            'pending_enrollments_count' => $pending_enrollments_count
        ];

        // This is a simple way to pass data to the view scope
        extract($data);

        require_once __DIR__ . '/../views/dashboard.php';
    }
}
?>