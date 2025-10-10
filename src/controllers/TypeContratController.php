<?php

require_once __DIR__ . '/../models/TypeContrat.php';

class TypeContratController {


    public function index() {
        if (!Auth::can('user', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $lycee_id = !Auth::can('system', 'view_all_lycees') ? Auth::get('lycee_id') : null;
        $contrats = TypeContrat::findAll($lycee_id);
        require_once __DIR__ . '/../views/type_contrat/index.php';
    }

    public function create() {
        if (!Auth::can('user', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $lycees = Auth::can('system', 'view_all_lycees') ? Lycee::findAll() : [];
        $contrat = [];
        $is_edit = false;
        require_once __DIR__ . '/../views/type_contrat/create.php';
    }

    public function store() {
        if (!Auth::can('user', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::can('system', 'view_all_lycees')) {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            TypeContrat::save($_POST);
        }
        header('Location: /contrats');
        exit();
    }

    public function edit() {
        if (!Auth::can('user', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /contrats'); exit(); }

        $contrat = TypeContrat::findById($id);
        if (!$contrat) { header('Location: /contrats'); exit(); }

        if (!Auth::can('system', 'view_all_lycees') && $contrat['lycee_id'] != Auth::get('lycee_id')) {
            http_response_code(403); echo "Accès Interdit."; exit();
        }

        $lycees = Auth::can('system', 'view_all_lycees') ? Lycee::findAll() : [];
        $is_edit = true;
        require_once __DIR__ . '/../views/type_contrat/edit.php';
    }

    public function update() {
        if (!Auth::can('user', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::can('system', 'view_all_lycees')) {
                $_POST['lycee_id'] = Auth::get('lycee_id');
            }
            TypeContrat::save($_POST);
        }
        header('Location: /contrats');
        exit();
    }

    public function destroy() {
        if (!Auth::can('user', 'edit')) { http_response_code(403); echo "Accès Interdit."; exit(); }
        $id = $_POST['id'] ?? null;
        if ($id) {
            $contrat = TypeContrat::findById($id);
            if ($contrat && !Auth::can('system', 'view_all_lycees') && $contrat['lycee_id'] != Auth::get('lycee_id')) {
                 http_response_code(403); echo "Accès Interdit."; exit();
            }
            TypeContrat::delete($id);
        }
        header('Location: /contrats');
        exit();
    }
}
?>
