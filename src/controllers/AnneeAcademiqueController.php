<?php

require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class AnneeAcademiqueController {

    public function index() {
        if (!Auth::can('manage', 'annee_academique') && !Auth::can('view_all_lycees', 'lycee')) {
            View::render('errors/403');
            exit();
        }
        $annees = AnneeAcademique::findAll();
        View::render('annees_academiques/index', ['annees' => $annees]);
    }

    public function create() {
        if (!Auth::can('manage', 'annee_academique') && !Auth::can('view_all_lycees', 'lycee')) {
            View::render('errors/403');
            exit();
        }
        View::render('annees_academiques/create');
    }

    public function store() {
        if (!Auth::can('manage', 'annee_academique') && !Auth::can('view_all_lycees', 'lycee')) {
            View::render('errors/403');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = Validator::sanitize($_POST);
                AnneeAcademique::save($data);
                $_SESSION['success_message'] = _('Année académique créée avec succès.');
            } catch (InvalidArgumentException $e) {
                $_SESSION['error_message'] = $e->getMessage();
                $_SESSION['old_post'] = $_POST;
                header('Location: /annees-academiques/create');
                exit();
            }
        }
        header('Location: /annees-academiques');
        exit();
    }

    /**
     * Clôture ou réouvre une année académique (Point 2)
     */
    public function toggleCloture() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $roleName = strtolower(Auth::get('role_name') ?? '');
        $isAuthorized = (strpos($roleName, 'admin') !== false || strpos($roleName, 'super_admin') !== false || (strpos($roleName, 'chef') !== false && strpos($roleName, 'compt') !== false));

        if (!$isAuthorized) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }

        $id = $_POST['id'] ?? null;
        if ($id) {
            $db = Database::getInstance();
            // Get current state
            $stmt = $db->prepare("SELECT cloturee FROM annees_academiques WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $current = $stmt->fetchColumn();

            // Toggle state
            $newState = $current ? 0 : 1;
            $stmt = $db->prepare("UPDATE annees_academiques SET cloturee = :state WHERE id = :id");
            $stmt->execute(['state' => $newState, 'id' => $id]);

            $_SESSION['success_message'] = $newState
                ? _("L'année académique a été clôturée avec succès. Les opérations comptables sont désormais verrouillées.")
                : _("L'année académique a été réouverte. Les opérations comptables sont à nouveau autorisées.");
        }

        header('Location: /annees-academiques');
        exit();
    }

    public function edit() {
        if (!Auth::can('manage', 'annee_academique') && !Auth::can('view_all_lycees', 'lycee')) {
            View::render('errors/403');
            exit();
        }
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /annees-academiques');
            exit();
        }
        $annee = AnneeAcademique::findById($id);
        if (!$annee) {
            $_SESSION['error_message'] = _("L'année académique demandée n'existe pas.");
            header('Location: /annees-academiques');
            exit();
        }
        View::render('annees_academiques/edit', ['annee' => $annee]);
    }

    public function update() {
        if (!Auth::can('manage', 'annee_academique') && !Auth::can('view_all_lycees', 'lycee')) {
            View::render('errors/403');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = Validator::sanitize($_POST);
                AnneeAcademique::save($data);
                $_SESSION['success_message'] = _('Année académique mise à jour avec succès.');
            } catch (InvalidArgumentException $e) {
                $_SESSION['error_message'] = $e->getMessage();
                $_SESSION['old_post'] = $_POST;
                header('Location: /annees-academiques/edit?id=' . $_POST['id']);
                exit();
            }
        }
        header('Location: /annees-academiques');
        exit();
    }

    public function destroy() {
        if (!Auth::can('manage', 'annee_academique') && !Auth::can('view_all_lycees', 'lycee')) {
            View::render('errors/403');
            exit();
        }
        $id = $_POST['id'] ?? null;
        if ($id) {
            // Add check to prevent deleting active year
            $annee = AnneeAcademique::findById($id);
            if ($annee && $annee['est_active']) {
                $_SESSION['error_message'] = _("Vous ne pouvez pas supprimer l'année académique active.");
            } else {
                AnneeAcademique::delete($id);
                $_SESSION['success_message'] = _('Année académique supprimée avec succès.');
            }
        }
        header('Location: /annees-academiques');
        exit();
    }

    public function activate() {
        if (!Auth::can('manage', 'annee_academique') && !Auth::can('view_all_lycees', 'lycee')) {
            View::render('errors/403');
            exit();
        }
        $id = $_POST['id'] ?? null;
        if ($id) {
            try {
                AnneeAcademique::setActive($id);
                $_SESSION['success_message'] = _('Année académique activée avec succès.');
            } catch (PDOException $e) {
                $_SESSION['error_message'] = _("Erreur lors de l'activation de l'année académique: ") . $e->getMessage();
            }
        }
        header('Location: /annees-academiques');
        exit();
    }
}
?>