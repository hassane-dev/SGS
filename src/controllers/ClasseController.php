<?php

require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Serie.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AffectationPedagogique.php';
require_once __DIR__ . '/../models/ClasseParametre.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class ClasseController {

    private function checkAccess($permission) {
        list($resource, $action) = explode(':', $permission);
        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function updateParams() {
        $this->checkAccess('class:edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);

            // Check ownership
            $classe = Classe::findById($data['classe_id']);
            if (!$classe) {
                http_response_code(404);
                View::render('errors/404');
                exit();
            }
            $this->checkOwnership($classe['lycee_id']);

            try {
                ClasseParametre::save($data);
                $_SESSION['success_message'] = "Paramètres de la classe mis à jour pour l'année en cours.";
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur lors de la mise à jour des paramètres : " . $e->getMessage();
            }
        }
        header('Location: /classes/show?id=' . $data['classe_id']);
        exit();
    }

    public function index() {
        $this->checkAccess('class:view');
        $lycee_id = !Auth::can('view_all_lycees', 'lycee') ? Auth::getLyceeId() : null;
        $classes = Classe::findAll($lycee_id);
        View::render('classes/index', [
            'classes' => $classes,
            'title' => 'Gestion des Classes'
        ]);
    }

    public function show() {
        $this->checkAccess('class:view');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /classes');
            exit();
        }

        $classe = Classe::findById($id);
        if (!$classe) {
            http_response_code(404);
            View::render('errors/404');
            exit();
        }

        $this->checkOwnership($classe['lycee_id']);

        $active_year = AnneeAcademique::findActive();
        $params_annuels = [];
        if ($active_year) {
            $params_annuels = ClasseParametre::findByClasseAndAnnee($id, $active_year['id']);
        }

        $assigned_matieres = Matiere::findByClassId($id);
        $all_matieres = Matiere::findAll($classe['lycee_id']);
        $enseignants = User::findTeachers($classe['lycee_id']);
        $teacher_assignments = AffectationPedagogique::findAssignmentsForClass($id);

        // Add formatted name to the classe array
        $classe['nom_complet'] = Classe::getFormattedName($classe);

        View::render('classes/show', [
            'classe' => $classe,
            'params_annuels' => $params_annuels,
            'active_year' => $active_year,
            'assigned_matieres' => $assigned_matieres,
            'all_matieres' => $all_matieres,
            'enseignants' => $enseignants,
            'teacher_assignments' => $teacher_assignments,
            'title' => 'Détails de la Classe'
        ]);
    }

    public function create() {
        $this->checkAccess('class:create');
        $cycles = Cycle::findAll();
        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];

        $lycee_id = Auth::getLyceeId();
        $params = ParamGeneral::findByLyceeId($lycee_id);
        $mode_cycle = $params['mode_cycle'] ?? 'separe_ceg_lycee'; // Default value
        $series = Serie::findAll($lycee_id);

        View::render('classes/create', [
            'cycles' => $cycles,
            'lycees' => $lycees,
            'series' => $series,
            'mode_cycle' => $mode_cycle,
            'title' => 'Nouvelle Classe'
        ]);
    }

    public function store() {
        $this->checkAccess('class:create');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $lycee_id = Auth::can('view_all_lycees', 'lycee') && !empty($data['lycee_id']) ? (int)$data['lycee_id'] : Auth::getLyceeId();
            $data['lycee_id'] = $lycee_id;

            // 1. Cycle ID validation
            $cycle_id = !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null;
            if (!$cycle_id) {
                $_SESSION['error_message'] = _("Veuillez sélectionner un cycle valide.");
                header('Location: /classes/create');
                exit();
            }

            // 2. Cycle existence and school ownership check
            $targetCycle = Cycle::findById($cycle_id);
            if (!$targetCycle) {
                $_SESSION['error_message'] = _("Le cycle sélectionné est inexistant.");
                header('Location: /classes/create');
                exit();
            }

            if (!empty($targetCycle['lycee_id']) && (int)$targetCycle['lycee_id'] !== (int)$lycee_id) {
                $_SESSION['error_message'] = _("Le cycle sélectionné n'appartient pas à l'établissement choisi.");
                header('Location: /classes/create');
                exit();
            }

            // 3. RBAC & Cycle Scope authorization
            require_once __DIR__ . '/../services/AuthorizationScopeService.php';
            if (!AuthorizationScopeService::canAccessLycee($lycee_id) || !AuthorizationScopeService::canAccessCycle($cycle_id)) {
                http_response_code(403);
                View::render('errors/403');
                exit();
            }

            $data['cycle_id'] = $cycle_id;

            // Level and series consistency validation with cycle
            $niveau = trim((string)($data['niveau'] ?? ''));
            $serie = !empty($data['serie']) ? strtoupper(trim($data['serie'])) : null;

            if (empty($niveau)) {
                $_SESSION['error_message'] = _("Le niveau est obligatoire.");
                header('Location: /classes/create');
                exit();
            }

            // CEG/Collège cycle levels (6e, 5e, 4e, 3e) should not have a series
            $cegNiveaux = ['6e', '5e', '4e', '3e', '6ème', '5ème', '4ème', '3ème'];
            if (in_array(strtolower($niveau), $cegNiveaux, true) && !empty($serie)) {
                $_SESSION['error_message'] = _("Les classes du Premier Cycle (CEG/Collège) ne comportent pas de série.");
                header('Location: /classes/create');
                exit();
            }

            // Validate level boundary against cycle definition if cycle defines niveau_debut / niveau_fin
            if (!empty($targetCycle['niveau_debut']) && !empty($targetCycle['niveau_fin'])) {
                $orderMap = Classe::getLevelOrderMap();
                $classLevelOrder = $orderMap[$niveau] ?? null;
                $startLevelOrder = $orderMap[$targetCycle['niveau_debut']] ?? null;
                $endLevelOrder = $orderMap[$targetCycle['niveau_fin']] ?? null;

                if ($classLevelOrder !== null && $startLevelOrder !== null && $endLevelOrder !== null) {
                    if ($classLevelOrder < $startLevelOrder || $classLevelOrder > $endLevelOrder) {
                        $_SESSION['error_message'] = sprintf(_("Le niveau '%s' est en dehors de la plage (%s à %s) définie pour le cycle '%s'."), $niveau, $targetCycle['niveau_debut'], $targetCycle['niveau_fin'], $targetCycle['nom_cycle']);
                        header('Location: /classes/create');
                        exit();
                    }
                }
            }

            // Set categorie based on serie
            if (!empty($serie)) {
                if (in_array($serie, ['A', 'A4', 'L'])) {
                    $data['categorie'] = 'Littéraire';
                } elseif (in_array($serie, ['C', 'D', 'E', 'S'])) {
                    $data['categorie'] = 'Scientifique';
                }
            }

            Classe::save($data);
        }
        header('Location: /classes');
        exit();
    }

    public function edit() {
        $this->checkAccess('class:edit');
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /classes'); exit(); }

        $classe = Classe::findById($id);
        $this->checkOwnership($classe['lycee_id']);

        $cycles = Cycle::findAll();
        $lycees = Auth::can('view_all_lycees', 'lycee') ? Lycee::findAll() : [];
        $series = Serie::findAll($classe['lycee_id']);

        // Add formatted name to the classe array
        $classe['nom_complet'] = Classe::getFormattedName($classe);

        View::render('classes/edit', [
            'classe' => $classe,
            'cycles' => $cycles,
            'lycees' => $lycees,
            'series' => $series,
            'title' => 'Modifier la Classe'
        ]);
    }

    public function update() {
        $this->checkAccess('class:edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $classe = Classe::findById($data['id_classe']);
            $this->checkOwnership($classe['lycee_id']);
            $data['lycee_id'] = $classe['lycee_id'];
            Classe::save($data);
        }
        header('Location: /classes');
        exit();
    }

    public function destroy() {
        $this->checkAccess('class:delete');
        $id = $_POST['id'] ?? null;
        if ($id) {
            $classe = Classe::findById($id);
            if ($classe) {
                $this->checkOwnership($classe['lycee_id']);
                Classe::delete($id, $classe['lycee_id']);
            }
        }
        header('Location: /classes');
        exit();
    }

    public function assignMatiere() {
        $this->checkAccess('class:edit');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $classe_id = $_POST['classe_id'];
            $classe = Classe::findById($classe_id);
            $this->checkOwnership($classe['lycee_id']);

            Matiere::assignToClass($_POST);
        }
        header('Location: /classes/show?id=' . $classe_id);
        exit();
    }

    public function removeMatiere() {
        $this->checkAccess('class:edit');
        $classe_id = $_GET['classe_id'];
        $classe_matiere_id = $_GET['id'];

        $classe = Classe::findById($classe_id);
        $this->checkOwnership($classe['lycee_id']);

        Matiere::removeFromClass($classe_matiere_id);

        header('Location: /classes/show?id=' . $classe_id);
        exit();
    }


    private function checkOwnership($resource_lycee_id) {
        if (!Auth::can('view_all_lycees', 'lycee') && $resource_lycee_id != Auth::getLyceeId()) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    // --- API Methods for AJAX calls ---

    public function getNiveauxForCycle() {
        if (!defined('TEST_MODE')) { header('Content-Type: application/json'); }
        $cycle_id = !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null;
        $lycee_id = !empty($_GET['lycee_id']) ? (int)$_GET['lycee_id'] : Auth::getLyceeId();

        require_once __DIR__ . '/../services/AuthorizationScopeService.php';
        if (!AuthorizationScopeService::canAccessLycee($lycee_id) || ($cycle_id && !AuthorizationScopeService::canAccessCycle($cycle_id))) {
            echo json_encode([]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $niveaux = $cycle_id ? Classe::findDistinctNiveauxByCycle($cycle_id, $lycee_id) : Classe::getDistinctNiveaux($lycee_id);
        echo json_encode(array_values($niveaux));
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function getSeriesForNiveau() {
        if (!defined('TEST_MODE')) { header('Content-Type: application/json'); }
        $niveau = $_GET['niveau'] ?? null;
        $cycle_id = !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null;
        $lycee_id = !empty($_GET['lycee_id']) ? (int)$_GET['lycee_id'] : Auth::getLyceeId();

        require_once __DIR__ . '/../services/AuthorizationScopeService.php';
        if (!AuthorizationScopeService::canAccessLycee($lycee_id) || ($cycle_id && !AuthorizationScopeService::canAccessCycle($cycle_id))) {
            echo json_encode([]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $series = Classe::findDistinctSeriesByNiveau($niveau, $lycee_id, $cycle_id);
        echo json_encode(array_values($series));
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function getNumerosForClasse() {
        if (!defined('TEST_MODE')) { header('Content-Type: application/json'); }
        $niveau = $_GET['niveau'] ?? null;
        $serie = $_GET['serie'] ?? null;
        $cycle_id = !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null;
        $lycee_id = !empty($_GET['lycee_id']) ? (int)$_GET['lycee_id'] : Auth::getLyceeId();

        require_once __DIR__ . '/../services/AuthorizationScopeService.php';
        if (!AuthorizationScopeService::canAccessLycee($lycee_id) || ($cycle_id && !AuthorizationScopeService::canAccessCycle($cycle_id))) {
            echo json_encode([]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $numeros = Classe::findAvailableNumeros($niveau, $serie, $lycee_id, $cycle_id);
        echo json_encode(array_values($numeros));
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function findClassId() {
        if (!defined('TEST_MODE')) { header('Content-Type: application/json'); }
        $lycee_id = !empty($_GET['lycee_id']) ? (int)$_GET['lycee_id'] : Auth::getLyceeId();
        $niveau = $_GET['niveau'] ?? null;
        $serie = $_GET['serie'] ?? null;
        $numero = $_GET['numero'] ?? null;
        $cycle_id = !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null;

        require_once __DIR__ . '/../services/AuthorizationScopeService.php';
        if (!AuthorizationScopeService::canAccessLycee($lycee_id) || ($cycle_id && !AuthorizationScopeService::canAccessCycle($cycle_id))) {
            echo json_encode(['id_classe' => null]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $id = Classe::findIdByDetails($lycee_id, $niveau, $serie, $numero, $cycle_id);
        echo json_encode(['id_classe' => $id]);
        if (!defined('TEST_MODE')) exit(); return;
    }
}
?>