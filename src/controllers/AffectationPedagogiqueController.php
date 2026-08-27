<?php

require_once __DIR__ . '/../services/AffectationPedagogiqueService.php';
require_once __DIR__ . '/../models/AffectationPedagogique.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class AffectationPedagogiqueController {

    private function checkManageAccess() {
        if (!Auth::can('manage_affectations', 'pedagogy')) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    private function checkViewAccess() {
        if (!Auth::can('view_affectations', 'pedagogy') && !Auth::can('view_my_affectations', 'pedagogy') && !Auth::can('manage_affectations', 'pedagogy')) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    public function index() {
        $this->checkViewAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::getLyceeId() : null;
        $user_id = Auth::getUserId();

        $filters = [
            'lycee_id' => $lycee_id,
            'classe_id' => $_GET['classe_id'] ?? null,
            'enseignant_id' => $_GET['enseignant_id'] ?? null,
            'statut' => $_GET['statut'] ?? null,
        ];

        // If user is teacher only (view_my_affectations), constrain to their own user_id
        if (!Auth::can('view_affectations', 'pedagogy') && !Auth::can('manage_affectations', 'pedagogy') && Auth::can('view_my_affectations', 'pedagogy')) {
            $filters['enseignant_id'] = $user_id;
        }

        $active_year = AnneeAcademique::findActive();
        if ($active_year) {
            $filters['annee_id'] = $active_year['id'];
        }

        $affectations = AffectationPedagogique::findAll($filters);
        $classes = Classe::findAll($lycee_id);
        $enseignants = User::findTeachers($lycee_id);

        View::render('affectations_pedagogiques/index', [
            'affectations' => $affectations,
            'classes' => $classes,
            'enseignants' => $enseignants,
            'active_year' => $active_year,
            'filters' => $filters,
            'title' => _('Affectations Pédagogiques')
        ]);
    }

    public function create() {
        $this->checkManageAccess();
        $lycee_id = Auth::getLyceeId();

        $selected_classe_id = $_GET['classe_id'] ?? null;
        $selected_matiere_id = $_GET['matiere_id'] ?? null;

        $classes = Classe::findAll($lycee_id);
        $enseignants = User::findTeachers($lycee_id);
        $matieres = [];

        if ($selected_classe_id) {
            $matieres = Matiere::findByClassId($selected_classe_id);
        }

        $active_year = AnneeAcademique::findActive();

        View::render('affectations_pedagogiques/create', [
            'classes' => $classes,
            'enseignants' => $enseignants,
            'matieres' => $matieres,
            'active_year' => $active_year,
            'selected_classe_id' => $selected_classe_id,
            'selected_matiere_id' => $selected_matiere_id,
            'title' => _('Nouvelle Affectation Pédagogique')
        ]);
    }

    public function store() {
        $this->checkManageAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $author_id = Auth::getUserId();

            try {
                AffectationPedagogiqueService::createAssignment($data, $author_id);
                $_SESSION['success_message'] = _("Affectation pédagogique enregistrée avec succès.");
                header('Location: /affectations-pedagogiques' . (!empty($data['classe_id']) ? '?classe_id=' . $data['classe_id'] : ''));
                if (!defined('TEST_MODE')) exit(); return;
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /affectations-pedagogiques/create?classe_id=' . ($data['classe_id'] ?? '') . '&matiere_id=' . ($data['matiere_id'] ?? ''));
                if (!defined('TEST_MODE')) exit(); return;
            }
        }
    }

    public function suspend() {
        $this->checkManageAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $motif = $_POST['motif'] ?? 'Suspension administrative';
            $author_id = Auth::getUserId();

            try {
                AffectationPedagogiqueService::updateStatus($id, 'suspendu', $motif, null, $author_id);
                $_SESSION['success_message'] = _("Affectation suspendue avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /affectations-pedagogiques');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    public function terminate() {
        $this->checkManageAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $motif = $_POST['motif'] ?? 'Fin de contrat / Clôture';
            $date_fin = $_POST['date_fin'] ?? date('Y-m-d');
            $author_id = Auth::getUserId();

            try {
                AffectationPedagogiqueService::updateStatus($id, 'termine', $motif, $date_fin, $author_id);
                $_SESSION['success_message'] = _("Affectation clôturée avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /affectations-pedagogiques');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    public function history() {
        $this->checkViewAccess();
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::getLyceeId() : null;
        $user_id = Auth::getUserId();

        $filters = [
            'lycee_id' => $lycee_id,
            'classe_id' => $_GET['classe_id'] ?? null,
            'enseignant_id' => $_GET['enseignant_id'] ?? null,
        ];

        // If user is teacher only (view_my_affectations), constrain to their own user_id
        if (!Auth::can('view_affectations', 'pedagogy') && !Auth::can('manage_affectations', 'pedagogy') && Auth::can('view_my_affectations', 'pedagogy')) {
            $filters['enseignant_id'] = $user_id;
        }

        $affectations = AffectationPedagogique::findAll($filters);
        $classes = Classe::findAll($lycee_id);
        $enseignants = User::findTeachers($lycee_id);

        View::render('affectations_pedagogiques/history', [
            'affectations' => $affectations,
            'classes' => $classes,
            'enseignants' => $enseignants,
            'filters' => $filters,
            'title' => _('Historique des Affectations Pédagogiques')
        ]);
    }
}
