<?php

require_once __DIR__ . '/../models/Paiement.php';
require_once __DIR__ . '/../models/Eleve.php';

class PaiementController {

    private function checkAdmin() {
        // For now, only admins can manage payments
        if (!Auth::check() || !in_array(Auth::get('role'), ['admin_local', 'super_admin_national'])) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAdmin();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        $paiements = Paiement::findByEleveId($eleve_id);
        require_once __DIR__ . '/../views/paiements/index.php';
    }

    public function create() {
        $this->checkAdmin();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        require_once __DIR__ . '/../views/paiements/create.php';
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Paiement::create($_POST);
            // Redirect to the payment list for that student
            header('Location: /paiements?eleve_id=' . $_POST['eleve_id']);
            exit();
        }
        // Fallback redirect
        header('Location: /eleves');
        exit();
    }
}
?>
