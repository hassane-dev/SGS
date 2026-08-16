<?php

require_once __DIR__ . '/../models/TypeContrat.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/View.php';

class TypeContratController {

    private function checkAccess() {
        if (!Auth::can('manage_contrats', 'drh') && !Auth::can('manage', 'user')) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::get('lycee_id') : null;
        $contrats = TypeContrat::findAll($lycee_id);
        require_once __DIR__ . '/../views/type_contrat/index.php';
    }

    public function create() {
        $this->checkAccess();
        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];
        $contrat = [];
        $is_edit = false;
        require_once __DIR__ . '/../views/type_contrat/create.php';
    }

    public function store() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            TypeContrat::save($data);
        }
        header('Location: /contrats');
        exit();
    }

    public function edit() {
        $this->checkAccess();
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /contrats'); exit(); }

        $contrat = TypeContrat::findById($id);
        if (!$contrat) { header('Location: /contrats'); exit(); }

        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];
        $is_edit = true;
        require_once __DIR__ . '/../views/type_contrat/edit.php';
    }

    public function update() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::get('lycee_id');
            }
            TypeContrat::save($data);
        }
        header('Location: /contrats');
        exit();
    }

    public function destroy() {
        $this->checkAccess();
        $id = $_POST['id'] ?? null;
        if ($id) {
            $db = Database::getInstance();
            $stmt1 = $db->prepare("SELECT COUNT(*) FROM personnel_contrats_historique WHERE type_contrat_id = :id");
            $stmt1->execute(['id' => $id]);
            $count1 = (int)$stmt1->fetchColumn();

            $stmt2 = $db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE contrat_id = :id");
            $stmt2->execute(['id' => $id]);
            $count2 = (int)$stmt2->fetchColumn();

            if ($count1 > 0 || $count2 > 0) {
                $_SESSION['error_message'] = _("Impossible de supprimer ce type de contrat car il est actuellement associé à des membres du personnel.");
            } else {
                TypeContrat::delete($id);
                $_SESSION['success_message'] = _("Type de contrat supprimé avec succès.");
            }
        }
        header('Location: /contrats');
        if (!defined('TEST_MODE')) exit(); return;
    }
}
?>
