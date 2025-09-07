<?php

require_once __DIR__ . '/../models/BoutiqueAchat.php';
require_once __DIR__ . '/../models/BoutiqueArticle.php';
require_once __DIR__ . '/../models/Eleve.php';

class BoutiqueAchatController {

    private function checkAccess() {
        if (!Auth::can('manage_boutique')) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);
        $achats = BoutiqueAchat::findByEleveId($eleve_id);
        require_once __DIR__ . '/../views/boutique/achats/index.php';
    }

    public function create() {
        $this->checkAccess();
        $eleve_id = $_GET['eleve_id'] ?? null;
        if (!$eleve_id) {
            header('Location: /eleves');
            exit();
        }
        $eleve = Eleve::findById($eleve_id);

        $lycee_id = Auth::get('role_name') === 'admin_local' ? Auth::get('lycee_id') : null;
        // This is a simplification. A super admin would need to know the student's lycee
        $articles = BoutiqueArticle::findAll($lycee_id);

        require_once __DIR__ . '/../views/boutique/achats/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            BoutiqueAchat::create($_POST);
            // Redirect to the purchase list for that student
            header('Location: /boutique/achats?eleve_id=' . $_POST['eleve_id']);
            exit();
        }
        header('Location: /eleves');
        exit();
    }
}
?>
