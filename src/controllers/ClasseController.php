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

            // Verify unconfigured vs configured utilisation of series
            if ($targetCycle['utilise_series'] === null || $targetCycle['utilise_series'] === '') {
                $_SESSION['error_message'] = sprintf(_("Le cycle '%s' n'a pas encore été configuré concernant l'utilisation des séries. Veuillez le configurer dans Administration > Cycles avant de créer des classes."), $targetCycle['nom_cycle']);
                header('Location: /classes/create');
                exit();
            }

            if (!empty($serie) && (int)$targetCycle['utilise_series'] === 0) {
                $_SESSION['error_message'] = sprintf(_("Le cycle '%s' est configuré sans série. La série '%s' n'est pas autorisée."), $targetCycle['nom_cycle'], $serie);
                header('Location: /classes/create');
                exit();
            }

            // Validate level and level boundary against cycle definition
            $orderMap = Classe::getLevelOrderMap();
            $classLevelOrder = $orderMap[$niveau] ?? null;

            if ($classLevelOrder === null) {
                $_SESSION['error_message'] = sprintf(_("Le niveau '%s' n'est pas reconnu par le système."), $niveau);
                header('Location: /classes/create');
                exit();
            }

            if (!empty($targetCycle['niveau_debut']) && !empty($targetCycle['niveau_fin'])) {
                $startLevelOrder = Classe::resolveLevelOrder($targetCycle['niveau_debut']);
                $endLevelOrder = Classe::resolveLevelOrder($targetCycle['niveau_fin']);

                if ($startLevelOrder === null || $endLevelOrder === null || $startLevelOrder > $endLevelOrder) {
                    $_SESSION['error_message'] = sprintf(_("Les bornes de niveau (%s à %s) définies pour le cycle '%s' ne sont pas valides."), $targetCycle['niveau_debut'], $targetCycle['niveau_fin'], $targetCycle['nom_cycle']);
                    header('Location: /classes/create');
                    exit();
                }

                if ($classLevelOrder < $startLevelOrder || $classLevelOrder > $endLevelOrder) {
                    $_SESSION['error_message'] = sprintf(_("Le niveau '%s' est en dehors de la plage (%s à %s) définie pour le cycle '%s'."), $niveau, $targetCycle['niveau_debut'], $targetCycle['niveau_fin'], $targetCycle['nom_cycle']);
                    header('Location: /classes/create');
                    exit();
                }
            }

            // Validate that the submitted serie exists in the authorized school tenant series list if series is provided
            if (!empty($serie)) {
                $schoolSeries = array_column(Serie::findAll($lycee_id), 'nom_serie');
                if (!in_array($serie, $schoolSeries, true)) {
                    $_SESSION['error_message'] = sprintf(_("La série '%s' n'est pas répertoriée dans le référentiel des séries de l'établissement."), $serie);
                    header('Location: /classes/create');
                    exit();
                }

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
            if (!$classe) {
                header('Location: /classes');
                exit();
            }
            $this->checkOwnership($classe['lycee_id']);
            $data['lycee_id'] = $classe['lycee_id'];

            // Server-side validation of cycle and serie consistency
            $cycle_id = !empty($data['cycle_id']) ? (int)$data['cycle_id'] : (int)$classe['cycle_id'];
            $targetCycle = Cycle::findById($cycle_id);
            if (!$targetCycle) {
                $_SESSION['error_message'] = _("Le cycle sélectionné est inexistant.");
                header('Location: /classes/edit?id=' . $data['id_classe']);
                exit();
            }

            if (!empty($targetCycle['lycee_id']) && (int)$targetCycle['lycee_id'] !== (int)$classe['lycee_id']) {
                $_SESSION['error_message'] = _("Incohérence détectée : Le cycle sélectionné n'appartient pas à l'établissement de cette classe.");
                header('Location: /classes/edit?id=' . $data['id_classe']);
                exit();
            }

            require_once __DIR__ . '/../services/AuthorizationScopeService.php';
            if (!AuthorizationScopeService::canAccessLycee($classe['lycee_id']) || !AuthorizationScopeService::canAccessCycle($cycle_id)) {
                http_response_code(403);
                View::render('errors/403');
                exit();
            }
            $serie = !empty($data['serie']) ? strtoupper(trim($data['serie'])) : null;

            $niveau = trim((string)($data['niveau'] ?? $classe['niveau']));
            $orderMap = Classe::getLevelOrderMap();
            $classLevelOrder = $orderMap[$niveau] ?? null;

            if ($classLevelOrder === null) {
                $_SESSION['error_message'] = sprintf(_("Le niveau '%s' n'est pas reconnu par le système."), $niveau);
                header('Location: /classes/edit?id=' . $data['id_classe']);
                exit();
            }

            if (!empty($targetCycle['niveau_debut']) && !empty($targetCycle['niveau_fin'])) {
                $startLevelOrder = Classe::resolveLevelOrder($targetCycle['niveau_debut']);
                $endLevelOrder = Classe::resolveLevelOrder($targetCycle['niveau_fin']);

                if ($startLevelOrder === null || $endLevelOrder === null || $startLevelOrder > $endLevelOrder) {
                    $_SESSION['error_message'] = sprintf(_("Les bornes de niveau (%s à %s) définies pour le cycle '%s' ne sont pas valides."), $targetCycle['niveau_debut'], $targetCycle['niveau_fin'], $targetCycle['nom_cycle']);
                    header('Location: /classes/edit?id=' . $data['id_classe']);
                    exit();
                }

                if ($classLevelOrder < $startLevelOrder || $classLevelOrder > $endLevelOrder) {
                    $_SESSION['error_message'] = sprintf(_("Le niveau '%s' est en dehors de la plage (%s à %s) définie pour le cycle '%s'."), $niveau, $targetCycle['niveau_debut'], $targetCycle['niveau_fin'], $targetCycle['nom_cycle']);
                    header('Location: /classes/edit?id=' . $data['id_classe']);
                    exit();
                }
            }

            if ($targetCycle && ($targetCycle['utilise_series'] === null || $targetCycle['utilise_series'] === '')) {
                $_SESSION['error_message'] = sprintf(_("Le cycle '%s' n'a pas encore été configuré concernant l'utilisation des séries. Veuillez le configurer dans Administration > Cycles."), $targetCycle['nom_cycle']);
                header('Location: /classes/edit?id=' . $data['id_classe']);
                exit();
            }

            if (!empty($serie) && $targetCycle && (int)$targetCycle['utilise_series'] === 0) {
                $_SESSION['error_message'] = sprintf(_("Le cycle '%s' est configuré sans série. La série '%s' n'est pas autorisée."), $targetCycle['nom_cycle'], $serie);
                header('Location: /classes/edit?id=' . $data['id_classe']);
                exit();
            }

            if (!empty($serie)) {
                $schoolSeries = array_column(Serie::findAll($classe['lycee_id']), 'nom_serie');
                if (!in_array($serie, $schoolSeries, true)) {
                    $_SESSION['error_message'] = sprintf(_("La série '%s' n'est pas répertoriée dans le référentiel des séries de l'établissement."), $serie);
                    header('Location: /classes/edit?id=' . $data['id_classe']);
                    exit();
                }
            }

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

        $authClasses = AuthorizationScopeService::getAuthorizedClassIds($lycee_id);
        if ($authClasses === []) {
            echo json_encode([]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $niveaux = $cycle_id ? Classe::findDistinctNiveauxByCycle($cycle_id, $lycee_id, $authClasses) : Classe::getDistinctNiveaux($lycee_id, $authClasses);

        // Fallback for new cycle without created classes yet (unrestricted users only):
        if (empty($niveaux) && $cycle_id && $authClasses === null) {
            $cycle = Cycle::findById($cycle_id);
            if ($cycle && !empty($cycle['niveau_debut']) && !empty($cycle['niveau_fin'])) {
                $start = Classe::resolveLevelOrder($cycle['niveau_debut']);
                $end = Classe::resolveLevelOrder($cycle['niveau_fin']);

                if ($start !== null && $end !== null && $start <= $end) {
                    $canonicalLevels = Classe::getCanonicalLevelMap();

                    for ($i = $start; $i <= $end; $i++) {
                        if (isset($canonicalLevels[$i])) {
                            $niveaux[] = $canonicalLevels[$i];
                        }
                    }
                }
            }
        }

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

        // If a cycle is specified, verify if it uses series according to its configuration
        if ($cycle_id) {
            $cycle = Cycle::findById($cycle_id);
            if ($cycle && ($cycle['utilise_series'] === null || $cycle['utilise_series'] === '' || (int)$cycle['utilise_series'] === 0)) {
                echo json_encode([]);
                if (!defined('TEST_MODE')) exit(); return;
            }
        }

        $authClasses = AuthorizationScopeService::getAuthorizedClassIds($lycee_id);
        if ($authClasses === []) {
            echo json_encode([]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $series = Classe::findDistinctSeriesByNiveau($niveau, $lycee_id, $cycle_id, $authClasses);
        // If no classes exist for this level yet, fallback to all series for the school if cycle utilizes series (unrestricted users only)
        if (empty($series) && $cycle_id && $authClasses === null) {
            $cycle = Cycle::findById($cycle_id);
            if (!$cycle || (int)($cycle['utilise_series'] ?? 1) === 1) {
                $allSeriesObjects = Serie::findAll($lycee_id);
                $series = array_column($allSeriesObjects, 'nom_serie');
            }
        }

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

        $authClasses = AuthorizationScopeService::getAuthorizedClassIds($lycee_id);
        if ($authClasses === []) {
            echo json_encode([]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $numeros = Classe::findAvailableNumeros($niveau, $serie, $lycee_id, $cycle_id, $authClasses);
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

        $authClasses = AuthorizationScopeService::getAuthorizedClassIds($lycee_id);
        if ($authClasses === []) {
            echo json_encode(['id_classe' => null]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $id = Classe::findIdByDetails($lycee_id, $niveau, $serie, $numero, $cycle_id, $authClasses);
        echo json_encode(['id_classe' => $id]);
        if (!defined('TEST_MODE')) exit(); return;
    }
}
?>