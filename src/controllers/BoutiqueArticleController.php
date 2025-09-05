<?php

require_once __DIR__ . '/../models/BoutiqueArticle.php';
require_once __DIR__ . '/../models/Lycee.php';

class BoutiqueArticleController {

    private function checkAdmin() {
        if (!Auth::check() || !in_array(Auth::get('role'), ['admin_local', 'super_admin_national'])) {
            http_response_code(403);
            echo "Accès Interdit.";
            exit();
        }
    }

    public function index() {
        $this->checkAdmin();
        $user_role = Auth::get('role');
        $lycee_id = ($user_role === 'admin_local') ? Auth::get('lycee_id') : null;

        $articles = BoutiqueArticle::findAll($lycee_id);
        require_once __DIR__ . '/../views/boutique/articles/index.php';
    }

    public function create() {
        $this->checkAdmin();
        $lycees = (Auth::get('role') === 'super_admin_national') ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/boutique/articles/create.php';
    }

    public function store() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Auth::get('role') === 'admin_local') {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            BoutiqueArticle::save($_POST);
        }
        header('Location: /boutique/articles');
        exit();
    }

    public function edit() {
        $this->checkAdmin();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /boutique/articles');
            exit();
        }
        $article = BoutiqueArticle::findById($id);
        $lycees = (Auth::get('role') === 'super_admin_national') ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/boutique/articles/edit.php';
    }

    public function update() {
        $this->checkAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Auth::get('role') === 'admin_local') {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            BoutiqueArticle::save($_POST);
        }
        header('Location: /boutique/articles');
        exit();
    }

    public function destroy() {
        $this->checkAdmin();
        $id = $_POST['id'] ?? null;
        if ($id) {
            BoutiqueArticle::delete($id);
        }
        header('Location: /boutique/articles');
        exit();
    }
}
?>
