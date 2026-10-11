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
        require_once __DIR__ . '/../services/AcademicFilterService.php';
        $cascadeOptions = AcademicFilterService::getCascadeOptions($_GET);

        $baseSql = "SELECT c.*, cy.nom_cycle FROM classes c JOIN cycles cy ON c.cycle_id = cy.id_cycle";
        $applied = AcademicFilterService::applyToQuery($baseSql, [], $_GET, 'c');

        $db = Database::getInstance();
        $stmt = $db->prepare($applied['sql'] . " ORDER BY cy.nom_cycle ASC, c.niveau ASC, c.serie ASC, c.numero ASC");
        $stmt->execute($applied['params']);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('classes/index', [
            'classes' => $classes,
            'cascadeOptions' => $cascadeOptions,
            'filters' => $_GET,
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
            $lycee_id = Auth::can('view_all_lycees', 'lycee') ? ($data['lycee_id'] ?? Auth::getLyceeId()) : Auth::getLyceeId();
            $data['lycee_id'] = $lycee_id;

            $cycle_id = !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null;
            if (!$cycle_id) {
                if (defined('TEST_MODE')) {
                    throw new InvalidArgumentException("Le cycle est obligatoire.");
                }
                $_SESSION['error_message'] = "Le cycle est obligatoire.";
                header('Location: /classes/create');
                exit();
            }

            // Validate cycle existence and school tenancy
            $cycle = Cycle::findById($cycle_id);
            if (!$cycle || ($cycle['lycee_id'] !== null && (int)$cycle['lycee_id'] !== (int)$lycee_id)) {
                if (defined('TEST_MODE')) {
                    throw new InvalidArgumentException("Le cycle sélectionné est invalide ou n'appartient pas à l'établissement.");
                }
                $_SESSION['error_message'] = "Le cycle sélectionné est invalide ou n'appartient pas à l'établissement.";
                header('Location: /classes/create');
                exit();
            }

            // Assert scope authorization on lycee and cycle
            require_once __DIR__ . '/../services/AcademicFilterService.php';
            AcademicFilterService::assertAccess(['lycee_id' => $lycee_id, 'cycle_id' => $cycle_id]);

            $data['cycle_id'] = $cycle_id;

            // Set categorie based on serie
            if (!empty($data['serie'])) {
                $serie = strtoupper($data['serie']);
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
        require_once __DIR__ . '/../services/AcademicFilterService.php';
        $options = AcademicFilterService::getCascadeOptions($_GET);
        echo json_encode(array_values($options['niveaux']));
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function getSeriesForNiveau() {
        if (!defined('TEST_MODE')) { header('Content-Type: application/json'); }
        require_once __DIR__ . '/../services/AcademicFilterService.php';
        $options = AcademicFilterService::getCascadeOptions($_GET);
        echo json_encode(array_values($options['series']));
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function getNumerosForClasse() {
        if (!defined('TEST_MODE')) { header('Content-Type: application/json'); }
        require_once __DIR__ . '/../services/AcademicFilterService.php';
        $options = AcademicFilterService::getCascadeOptions($_GET);
        echo json_encode(array_values($options['numeros']));
        if (!defined('TEST_MODE')) exit(); return;
    }

    public function findClassId() {
        if (!defined('TEST_MODE')) { header('Content-Type: application/json'); }
        require_once __DIR__ . '/../services/AcademicFilterService.php';
        $validated = AcademicFilterService::sanitizeAndIntersectFilters($_GET);
        echo json_encode(['id_classe' => $validated['classe_id']]);
        if (!defined('TEST_MODE')) exit(); return;
    }
}
?>