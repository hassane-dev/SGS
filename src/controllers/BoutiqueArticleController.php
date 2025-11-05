<?php

require_once __DIR__ . '/../models/BoutiqueArticle.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../core/Validator.php';

class BoutiqueArticleController {

    private function checkAccess() {
        if (!Auth::can('manage', 'boutique')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $user_role = Auth::get('role_name');
        $lycee_id = ($user_role === 'admin_local') ? Auth::get('lycee_id') : null;

        $articles = BoutiqueArticle::findAll($lycee_id);
        require_once __DIR__ . '/../views/boutique/articles/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycees = (Auth::can('view_all_lycees', 'lycee')) ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/boutique/articles/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (Auth::get('role_name') === 'admin_local') {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            BoutiqueArticle::save($data);
        }
        header('Location: /boutique/articles');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /boutique/articles');
            exit();
        }
        $article = BoutiqueArticle::findById($id);
        $lycees = (Auth::can('view_all_lycees', 'lycee')) ? Lycee::findAll() : [];
        require_once __DIR__ . '/../views/boutique/articles/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (Auth::get('role_name') === 'admin_local') {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            BoutiqueArticle::save($data);
        }
        header('Location: /boutique/articles');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            BoutiqueArticle::delete($id);
        }
        header('Location: /boutique/articles');
        exit();
    }
}
?>
