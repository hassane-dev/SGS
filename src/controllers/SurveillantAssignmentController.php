<?php

require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../services/SurveillantAssignmentService.php';
require_once __DIR__ . '/../models/SurveillantAffectation.php';
require_once __DIR__ . '/../models/SurveillantGroupe.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/CsrfService.php';

class SurveillantAssignmentController {

    private function checkAccess(string $action = 'view_surveillants') {
        $hasAccess = false;
        if ($action === 'view_surveillants') {
            $hasAccess = Auth::can('view_surveillants', 'pedagogy') ||
                         Auth::can('manage_surveillants', 'pedagogy') ||
                         Auth::can('view_all_lycees', 'lycee');
        } else {
            $hasAccess = Auth::can($action, 'pedagogy') ||
                         Auth::can('manage_surveillants', 'pedagogy') ||
                         Auth::can('view_all_lycees', 'lycee');
        }

        if (!$hasAccess) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit(); return false;
        }
        return true;
    }

    public function index() {
        if (!$this->checkAccess('view_surveillants')) return;

        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();
        $annee_id = $activeYear ? (int)$activeYear['id'] : 0;

        $affectations = SurveillantAffectation::findAllByLyceeAndAnnee($lycee_id, $annee_id);
        $groupes = SurveillantGroupe::findByLyceeAndAnnee($lycee_id, $annee_id);
        $classes = Classe::findAll($lycee_id);
        $surveillants = User::findAllByRoleName('surveillant', $lycee_id);

        View::render('surveillants/index', [
            'affectations' => $affectations,
            'groupes' => $groupes,
            'classes' => $classes,
            'surveillants' => $surveillants,
            'activeYear' => $activeYear
        ]);
    }

    public function repartition() {
        if (!$this->checkAccess('manage_surveillants')) return;

        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();
        $annee_id = $activeYear ? (int)$activeYear['id'] : 0;

        $classes = Classe::findAll($lycee_id);
        $surveillants = User::findAllByRoleName('surveillant', $lycee_id);

        View::render('surveillants/repartition', [
            'classes' => $classes,
            'surveillants' => $surveillants,
            'activeYear' => $activeYear
        ]);
    }

    public function proposeDistribution() {
        if (!$this->checkAccess('manage_surveillants')) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfService::requireValid();
            $data = Validator::sanitize($_POST);

            $lycee_id = Auth::getLyceeId();
            $activeYear = AnneeAcademique::findActive();
            $annee_id = $activeYear ? (int)$activeYear['id'] : 0;

            $selected_classe_ids = isset($_POST['classe_ids']) ? array_map('intval', $_POST['classe_ids']) : [];
            $selected_surveillant_ids = isset($_POST['surveillant_ids']) ? array_map('intval', $_POST['surveillant_ids']) : [];
            $mode = $data['mode'] ?? 'equitable';
            $max_per = !empty($data['max_per_surveillant']) ? (int)$data['max_per_surveillant'] : null;

            try {
                $proposal = SurveillantAssignmentService::proposeDistribution(
                    $lycee_id,
                    $annee_id,
                    $selected_classe_ids,
                    $selected_surveillant_ids,
                    $mode,
                    $max_per
                );

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'proposal' => $proposal]);
                if (!defined('TEST_MODE')) exit(); return;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                if (!defined('TEST_MODE')) exit(); return;
            }
        }
    }

    public function applyDistribution() {
        if (!$this->checkAccess('manage_surveillants')) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfService::requireValid();

            $json_raw = file_get_contents('php://input');
            $payload = json_decode($json_raw, true);

            if (!$payload) {
                $payload = $_POST;
                if (!empty($_POST['payload_json'])) {
                    $payload = json_decode($_POST['payload_json'], true) ?: $_POST;
                }
            }

            try {
                $current_user_id = Auth::getUserId();
                SurveillantAssignmentService::applyDistribution($payload, $current_user_id);

                $_SESSION['success_message'] = _("La répartition des surveillants a été enregistrée avec succès.");

                if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirect' => '/surveillants']);
                    if (!defined('TEST_MODE')) exit(); return;
                } else {
                    header('Location: /surveillants');
                    if (!defined('TEST_MODE')) exit(); return;
                }
            } catch (ConcurrentModificationException $e) {
                if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                    header('Content-Type: application/json');
                    http_response_code(409);
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    if (!defined('TEST_MODE')) exit(); return;
                } else {
                    $_SESSION['error_message'] = $e->getMessage();
                    header('Location: /surveillants/repartition');
                    if (!defined('TEST_MODE')) exit(); return;
                }
            } catch (Exception $e) {
                if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    if (!defined('TEST_MODE')) exit(); return;
                } else {
                    $_SESSION['error_message'] = $e->getMessage();
                    header('Location: /surveillants/repartition');
                    if (!defined('TEST_MODE')) exit(); return;
                }
            }
        }
    }

    public function storeAssignment() {
        if (!$this->checkAccess('manage_surveillants')) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfService::requireValid();
            $data = Validator::sanitize($_POST);

            $lycee_id = Auth::getLyceeId();
            $activeYear = AnneeAcademique::findActive();
            $annee_id = $activeYear ? (int)$activeYear['id'] : 0;

            $data['lycee_id'] = $lycee_id;
            $data['annee_academique_id'] = $annee_id;
            $data['created_by'] = Auth::getUserId();

            try {
                SurveillantAssignmentService::validateTenantHomogeneity($data);
                SurveillantAffectation::save($data);

                if (!empty($data['surveillant_id'])) {
                    AuthorizationScopeService::invalidateUserCache((int)$data['surveillant_id']);
                }

                $_SESSION['success_message'] = _("Affectation enregistrée avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /surveillants');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    public function storeGroup() {
        if (!$this->checkAccess('manage_surveillants')) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfService::requireValid();
            $data = Validator::sanitize($_POST);

            $lycee_id = Auth::getLyceeId();
            $activeYear = AnneeAcademique::findActive();
            $annee_id = $activeYear ? (int)$activeYear['id'] : 0;

            $data['lycee_id'] = $lycee_id;
            $data['annee_academique_id'] = $annee_id;
            $classe_ids = isset($_POST['classe_ids']) ? array_map('intval', $_POST['classe_ids']) : [];

            try {
                if (empty($data['nom_groupe'])) {
                    throw new InvalidArgumentException(_("Le nom du groupe est obligatoire."));
                }

                $groupe_id = SurveillantGroupe::save($data);
                SurveillantGroupe::syncClasses($groupe_id, $classe_ids);

                SurveillantAssignmentService::bumpConfigVersion($lycee_id);

                $_SESSION['success_message'] = _("Groupe de classes enregistré avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /surveillants');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    public function terminateAssignment() {
        if (!$this->checkAccess('manage_surveillants')) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfService::requireValid();
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id > 0) {
                $aff = SurveillantAffectation::findById($id);
                if ($aff) {
                    AuthorizationScopeService::assertAccessToObject($aff['lycee_id']);

                    $aff['statut'] = 'termine';
                    $aff['date_fin'] = date('Y-m-d');
                    SurveillantAffectation::save($aff);

                    if (!empty($aff['surveillant_id'])) {
                        AuthorizationScopeService::invalidateUserCache((int)$aff['surveillant_id']);
                    }

                    $_SESSION['success_message'] = _("Affectation clôturée avec succès.");
                }
            }

            header('Location: /surveillants');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    public function deleteGroup() {
        if (!$this->checkAccess('manage_surveillants')) return;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CsrfService::requireValid();
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($id > 0) {
                $grp = SurveillantGroupe::findById($id);
                if ($grp) {
                    AuthorizationScopeService::assertAccessToObject($grp['lycee_id']);
                    SurveillantGroupe::delete($id);
                    SurveillantAssignmentService::bumpConfigVersion((int)$grp['lycee_id']);

                    $_SESSION['success_message'] = _("Groupe de classes supprimé avec succès.");
                }
            }

            header('Location: /surveillants');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }
}
