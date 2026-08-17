<?php

require_once __DIR__ . '/../models/CompteComptable.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class CompteComptableController {

    private function checkAccess($action, $resource = 'comptes_comptables') {
        if (!Auth::can($action, $resource)) {
            if (PHP_SAPI === 'cli') {
                throw new Exception("FORBIDDEN");
            }
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    private function redirect($url) {
        if (PHP_SAPI === 'cli') {
            throw new Exception("REDIRECT: " . $url);
        }
        header('Location: ' . $url);
        exit();
    }

    public function index() {
        $this->checkAccess('view');

        $filters = [
            'classe' => $_GET['classe'] ?? '',
            'actif' => $_GET['actif'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        $comptes = CompteComptable::findAll($filters);
        $classes = CompteComptable::getClasses();

        View::render('comptabilite/comptes_comptables/index', [
            'comptes' => $comptes,
            'classes' => $classes,
            'filters' => $filters,
            'title' => _("Plan de Comptes Comptables General")
        ]);
    }

    public function create() {
        $this->checkAccess('create');

        $classes = CompteComptable::getClasses();
        $allComptes = CompteComptable::findAll(['actif' => 1]);

        View::render('comptabilite/comptes_comptables/create', [
            'classes' => $classes,
            'allComptes' => $allComptes,
            'title' => _("Créer un Compte Comptable")
        ]);
    }

    public function store() {
        $this->checkAccess('create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);

            try {
                CompteComptable::create([
                    'numero' => $data['numero'] ?? '',
                    'libelle' => $data['libelle'] ?? '',
                    'classe' => $data['classe'] ?? 0,
                    'nature' => $data['nature'] ?? 'actif',
                    'compte_parent_id' => !empty($data['compte_parent_id']) ? $data['compte_parent_id'] : null,
                    'autoriser_ecriture' => !empty($data['autoriser_ecriture']) ? 1 : 0,
                    'actif' => !empty($data['actif']) ? 1 : 0
                ]);

                $_SESSION['success_message'] = _("Compte comptable créé avec succès.");
                $this->redirect('/comptes-comptables');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                $this->redirect('/comptes-comptables/create');
            }
        }
        $this->redirect('/comptes-comptables');
    }

    public function edit($id = null) {
        $this->checkAccess('edit');

        if ($id === null) {
            $id = $_GET['id'] ?? null;
        }

        if (!$id) {
            $_SESSION['error_message'] = _("ID du compte comptable manquant.");
            $this->redirect('/comptes-comptables');
        }

        $compte = CompteComptable::findById($id);
        if (!$compte) {
            $_SESSION['error_message'] = _("Compte comptable introuvable.");
            $this->redirect('/comptes-comptables');
        }

        $classes = CompteComptable::getClasses();
        $allComptes = CompteComptable::findAll(['actif' => 1]);

        View::render('comptabilite/comptes_comptables/edit', [
            'compte' => $compte,
            'classes' => $classes,
            'allComptes' => $allComptes,
            'title' => _("Modifier le Compte Comptable")
        ]);
    }

    public function update() {
        $this->checkAccess('edit');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $id = $data['id'] ?? null;

            if (!$id) {
                $_SESSION['error_message'] = _("ID du compte comptable manquant.");
                $this->redirect('/comptes-comptables');
            }

            try {
                CompteComptable::update($id, [
                    'numero' => $data['numero'] ?? '',
                    'libelle' => $data['libelle'] ?? '',
                    'classe' => $data['classe'] ?? 0,
                    'nature' => $data['nature'] ?? 'actif',
                    'compte_parent_id' => !empty($data['compte_parent_id']) ? $data['compte_parent_id'] : null,
                    'autoriser_ecriture' => !empty($data['autoriser_ecriture']) ? 1 : 0,
                    'actif' => !empty($data['actif']) ? 1 : 0
                ]);

                $_SESSION['success_message'] = _("Compte comptable mis à jour avec succès.");
                $this->redirect('/comptes-comptables');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                $this->redirect('/comptes-comptables/edit/' . $id);
            }
        }
        $this->redirect('/comptes-comptables');
    }

    public function toggleActive($id = null) {
        $this->checkAccess('edit');

        if ($id === null) {
            $id = $_GET['id'] ?? null;
        }

        if (!$id) {
            $_SESSION['error_message'] = _("ID du compte comptable manquant.");
            $this->redirect('/comptes-comptables');
        }

        $compte = CompteComptable::findById($id);
        if (!$compte) {
            $_SESSION['error_message'] = _("Compte comptable introuvable.");
            $this->redirect('/comptes-comptables');
        }

        try {
            $newStatus = $compte['actif'] ? 0 : 1;
            CompteComptable::update($id, ['actif' => $newStatus]);
            $msg = $newStatus ? _("Compte comptable activé.") : _("Compte comptable désactivé.");
            $_SESSION['success_message'] = $msg;
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        $this->redirect('/comptes-comptables');
    }

    public function destroy($id = null) {
        $this->checkAccess('delete');

        if ($id === null) {
            $id = $_GET['id'] ?? null;
        }

        if (!$id) {
            $_SESSION['error_message'] = _("ID du compte comptable manquant.");
            $this->redirect('/comptes-comptables');
        }

        try {
            CompteComptable::delete($id);
            $_SESSION['success_message'] = _("Compte comptable supprimé avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        $this->redirect('/comptes-comptables');
    }
}
