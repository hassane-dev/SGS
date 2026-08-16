<?php
// src/controllers/PersonnelController.php

require_once __DIR__ . '/../services/PersonnelService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/TypeContrat.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/View.php';

class PersonnelController {

    private function checkAccess(string $action) {
        if (!Auth::can($action, 'drh')) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    /**
     * DRH Cockpit Dashboard
     */
    public function dashboard() {
        $this->checkAccess('view_all');

        $filters = Validator::sanitize($_GET);
        $personnelList = PersonnelService::searchPersonnel($filters);

        // Compute metrics respecting scope
        $total = count($personnelList);
        $actifs = 0;
        $suspendus = 0;
        $conges = 0;

        foreach ($personnelList as $p) {
            $st = $p['statut_rh'] ?? 'en_activite';
            if ($st === 'en_activite') $actifs++;
            elseif ($st === 'suspendu') $suspendus++;
            elseif ($st === 'en_conge') $conges++;
        }

        $cycles = AuthorizationScopeService::getAuthorizedCycleIds();
        $authorized_lycee_ids = AuthorizationScopeService::getAuthorizedLyceeIds();

        View::render('drh/dashboard', [
            'title' => _('Tableau de Bord DRH'),
            'total' => $total,
            'actifs' => $actifs,
            'suspendus' => $suspendus,
            'conges' => $conges,
            'personnelList' => array_slice($personnelList, 0, 10), // latest 10
            'filters' => $filters
        ]);
    }

    /**
     * DRH Personnel Directory / List
     */
    public function index() {
        $this->checkAccess('view_all');

        $filters = Validator::sanitize($_GET);
        $personnels = PersonnelService::searchPersonnel($filters);

        $lycee_id = Auth::getLyceeId();
        $roles = Role::findAll($lycee_id);
        $contrats = TypeContrat::findAll($lycee_id);
        $cycles = Cycle::findByLycee($lycee_id);

        View::render('drh/index', [
            'title' => _('Annuaire du Personnel - DRH'),
            'personnels' => $personnels,
            'roles' => $roles,
            'contrats' => $contrats,
            'cycles' => $cycles,
            'filters' => $filters
        ]);
    }

    /**
     * DRH 360° Personnel File View
     */
    public function show() {
        $this->checkAccess('view_one');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            header('Location: /drh');
            if (!defined('TEST_MODE')) exit(); return;
        }

        $can_view_sensitive = Auth::can('view_sensitive', 'drh');
        $details = PersonnelService::get360Details($id, $can_view_sensitive);

        if (!$details) {
            $_SESSION['error_message'] = _("Membre du personnel non trouvé ou accès non autorisé.");
            header('Location: /drh');
            if (!defined('TEST_MODE')) exit(); return;
        }

        require_once __DIR__ . '/../models/PaieEntiteJuridique.php';
        $lycee_id = $details['personnel']['lycee_id'];
        $cycles = Cycle::findByLycee($lycee_id);
        $typeContrats = TypeContrat::findAll($lycee_id);
        $fonctions = PersonnelService::getFonctions($lycee_id);
        $entitesJuridiques = PaieEntiteJuridique::findAll();

        View::render('drh/show', [
            'title' => _('Dossier Personnel 360° : ') . $details['personnel']['prenom'] . ' ' . $details['personnel']['nom'],
            'p' => $details['personnel'],
            'assignments' => $details['assignments'],
            'contracts' => $details['contracts'],
            'active_contract' => $details['active_contract'],
            'documents' => $details['documents'],
            'history' => $details['history'],
            'cycles' => $cycles,
            'typeContrats' => $typeContrats,
            'fonctions' => $fonctions,
            'entitesJuridiques' => $entitesJuridiques,
            'can_view_sensitive' => $can_view_sensitive
        ]);
    }

    /**
     * Create Form
     */
    public function create() {
        $this->checkAccess('create');

        $lycee_id = Auth::getLyceeId();
        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];
        $roles = Role::findAll($lycee_id);
        $contrats = TypeContrat::findAll($lycee_id);
        $fonctions = PersonnelService::getFonctions($lycee_id);

        View::render('drh/create', [
            'title' => _('Nouveau Membre du Personnel - DRH'),
            'lycees' => $lycees,
            'roles' => $roles,
            'contrats' => $contrats,
            'fonctions' => $fonctions
        ]);
    }

    /**
     * Store Form Submission
     */
    public function store() {
        $this->checkAccess('create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);

            // Force tenant scope if not global super admin
            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = Auth::getLyceeId();
            }

            try {
                $author_id = Auth::getUserId();
                $personnel_id = PersonnelService::savePersonnel($data, $author_id);

                $_SESSION['success_message'] = _("Membre du personnel enregistré avec succès.");
                header('Location: /drh/show?id=' . $personnel_id);
                if (!defined('TEST_MODE')) exit(); return;
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /drh/create');
                if (!defined('TEST_MODE')) exit(); return;
            }
        }
    }

    /**
     * Edit Form
     */
    public function edit() {
        $this->checkAccess('edit');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            header('Location: /drh');
            if (!defined('TEST_MODE')) exit(); return;
        }

        $details = PersonnelService::get360Details($id, Auth::can('view_sensitive', 'drh'));
        if (!$details) {
            $_SESSION['error_message'] = _("Personnel introuvable.");
            header('Location: /drh');
            if (!defined('TEST_MODE')) exit(); return;
        }

        $lycee_id = $details['personnel']['lycee_id'];
        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];
        $roles = Role::findAll($lycee_id);
        $contrats = TypeContrat::findAll($lycee_id);
        $fonctions = PersonnelService::getFonctions($lycee_id);

        View::render('drh/edit', [
            'title' => _('Modifier Membre du Personnel - DRH'),
            'p' => $details['personnel'],
            'lycees' => $lycees,
            'roles' => $roles,
            'contrats' => $contrats,
            'fonctions' => $fonctions
        ]);
    }

    /**
     * Update Submission
     */
    public function update() {
        $this->checkAccess('edit');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $id = isset($data['id_user']) ? (int)$data['id_user'] : 0;

            if (!$id) {
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            // Assert target object access server-side
            $target = User::findById($id);
            if (!$target) {
                $_SESSION['error_message'] = _("Personnel introuvable.");
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            AuthorizationScopeService::assertAccessToObject($target['lycee_id']);

            // Prevent changing lycee_id via POST unless global super admin
            if (!Auth::can('view_all_lycees', 'lycee')) {
                $data['lycee_id'] = $target['lycee_id'];
            }

            try {
                $author_id = Auth::getUserId();
                PersonnelService::savePersonnel($data, $author_id);

                $_SESSION['success_message'] = _("Dossier du personnel mis à jour avec succès.");
                header('Location: /drh/show?id=' . $id);
                if (!defined('TEST_MODE')) exit(); return;
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /drh/edit?id=' . $id);
                if (!defined('TEST_MODE')) exit(); return;
            }
        }
    }

    /**
     * Update HR Status (e.g. Leave, Suspension, Exit)
     */
    public function updateStatus() {
        $this->checkAccess('manage_statut');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $personnel_id = isset($data['personnel_id']) ? (int)$data['personnel_id'] : 0;
            $statut_rh = $data['statut_rh'] ?? '';
            $motif = $data['motif_sortie'] ?? null;
            $date_sortie = !empty($data['date_sortie']) ? $data['date_sortie'] : null;

            if (!$personnel_id || empty($statut_rh)) {
                $_SESSION['error_message'] = _("Paramètres invalides pour la mise à jour du statut.");
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            $target = User::findById($personnel_id);
            if (!$target) {
                $_SESSION['error_message'] = _("Personnel introuvable.");
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            AuthorizationScopeService::assertAccessToObject($target['lycee_id']);

            try {
                $author_id = Auth::getUserId();
                PersonnelService::updateStatus($personnel_id, $statut_rh, $motif, $date_sortie, $author_id);

                $_SESSION['success_message'] = _("Statut RH mis à jour avec succès.");
                header('Location: /drh/show?id=' . $personnel_id);
                if (!defined('TEST_MODE')) exit(); return;
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /drh/show?id=' . $personnel_id);
                if (!defined('TEST_MODE')) exit(); return;
            }
        }
    }

    /**
     * CSV Export respecting scope & sensitive filters
     */
    public function export() {
        $this->checkAccess('export');

        $filters = Validator::sanitize($_GET);
        $personnels = PersonnelService::searchPersonnel($filters);
        $can_view_sensitive = Auth::can('view_sensitive', 'drh');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="registre_drh_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");

        $headers = ['Matricule', 'Nom', 'Prénom', 'Sexe', 'Email', 'Téléphone', 'Fonction RH', 'Rôle Applicatif', 'Établissement', 'Statut RH'];
        if ($can_view_sensitive) {
            $headers[] = 'N° CNSS';
            $headers[] = 'Contrat Actuel';
        }

        fputcsv($output, $headers, ';');

        foreach ($personnels as $p) {
            $row = [
                $p['identifiant_public'] ?? 'N/A',
                $p['nom'],
                $p['prenom'],
                $p['sexe'] ?? '',
                $p['email'],
                $p['telephone'] ?? '',
                $p['fonction'] ?? '',
                $p['nom_role'] ?? '',
                $p['nom_lycee'] ?? '',
                strtoupper($p['statut_rh'] ?? 'en_activite')
            ];
            if ($can_view_sensitive) {
                $row[] = $p['num_cnss'] ?? '';
                $row[] = $p['contrat_actuel'] ?? '';
            }
            fputcsv($output, $row, ';');
        }

        fclose($output);
        if (!defined('TEST_MODE')) exit(); return;
    }
}
