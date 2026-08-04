<?php
// src/controllers/BudgetController.php

require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/BudgetLigne.php';
require_once __DIR__ . '/../models/BudgetAjustement.php';
require_once __DIR__ . '/../models/BudgetHistorique.php';
require_once __DIR__ . '/../models/ExerciceFinancier.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/DepenseCategorie.php';
require_once __DIR__ . '/../models/DepenseCentreCout.php';
require_once __DIR__ . '/../services/BudgetService.php';
require_once __DIR__ . '/../services/BudgetWorkflowService.php';
require_once __DIR__ . '/../services/BudgetControlService.php';
require_once __DIR__ . '/../services/BudgetAdjustmentService.php';
require_once __DIR__ . '/../services/BudgetReportingService.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

class BudgetController {

    private function checkAccess($action, $resource = 'budget') {
        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            $_SESSION['error_message'] = "Accès Refusé : Vous n'avez pas la permission '$action' sur '$resource'.";
            header('Location: /login');
            if (!defined('TEST_MODE')) {
                exit();
            }
            return false;
        }
        return true;
    }

    private function handleException(Exception $e, $redirectUrl = '/budgets') {
        $trace = $e->getTraceAsString();
        $truncatedTrace = substr($trace, 0, 400) . (strlen($trace) > 400 ? '...' : '');
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage() .
            " | Fichier: " . $e->getFile() . " (Ligne " . $e->getLine() . ")" .
            " | Trace: " . $truncatedTrace;
        header('Location: ' . $redirectUrl);
        if (!defined('TEST_MODE')) {
            exit();
        }
    }

    public function index() {
        if (!$this->checkAccess('view')) return;

        $lyceeId = Auth::getLyceeId();
        $budgets = Budget::findByLycee($lyceeId);

        require_once __DIR__ . '/../views/budgets/index.php';
    }

    public function create() {
        if (!$this->checkAccess('create')) return;

        $lyceeId = Auth::getLyceeId();
        // Retrieve open exercises
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM exercices_financiers WHERE lycee_id = :lycee_id AND cloture = 0");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $exercices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/budgets/create.php';
    }

    public function store() {
        if (!$this->checkAccess('create')) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /budgets');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        try {
            $lyceeId = Auth::getLyceeId();
            $userId = Auth::getUserId();
            $data = Validator::sanitize($_POST);

            if (empty($data['exercice_financier_id'])) {
                throw new Exception("L'exercice financier est obligatoire.");
            }
            if (empty($data['libelle'])) {
                throw new Exception("Le libellé du budget est obligatoire.");
            }

            $data['lycee_id'] = $lyceeId;
            $data['cree_par'] = $userId;
            $data['statut'] = 'brouillon';

            $budgetId = Budget::create($data);

            $_SESSION['success_message'] = "Budget créé avec succès à l'état de brouillon.";
            header('Location: /budgets/show/' . $budgetId);
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            $this->handleException($e, '/budgets/create');
        }
    }

    public function show($id) {
        if (!$this->checkAccess('view')) return;

        $budget = Budget::findById($id);
        if (!$budget) {
            $_SESSION['error_message'] = "Budget introuvable.";
            header('Location: /budgets');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        if ($budget['lycee_id'] !== Auth::getLyceeId()) {
            $_SESSION['error_message'] = "Accès non autorisé.";
            header('Location: /budgets');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $lines = BudgetReportingService::getBudgetLinesWithMetrics($id);
        $summary = BudgetReportingService::getBudgetSummary($id);

        $lyceeId = Auth::getLyceeId();
        $categories = DepenseCategorie::findActiveByLycee($lyceeId);
        $centres = DepenseCentreCout::findActiveByLycee($lyceeId);

        require_once __DIR__ . '/../views/budgets/show.php';
    }

    public function storeLine() {
        if (!$this->checkAccess('update')) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /budgets');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $budgetId = $_POST['budget_id'] ?? null;
        try {
            $data = Validator::sanitize($_POST);
            if (empty($data['categorie_id'])) {
                throw new Exception("La catégorie de dépenses est obligatoire.");
            }
            if (empty($data['allocation_initiale']) || (float)$data['allocation_initiale'] < 0) {
                throw new Exception("L'allocation initiale doit être supérieure ou égale à zéro.");
            }

            BudgetService::createBudgetLine($data);

            $_SESSION['success_message'] = "Ligne budgétaire ajoutée avec succès.";
            header('Location: /budgets/show/' . $budgetId);
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            $this->handleException($e, '/budgets/show/' . $budgetId);
        }
    }

    public function deleteLine() {
        if (!$this->checkAccess('update')) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /budgets');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $ligneId = $_POST['ligne_id'] ?? null;
        $budgetId = $_POST['budget_id'] ?? null;
        try {
            BudgetLigne::delete($ligneId);
            $_SESSION['success_message'] = "Ligne budgétaire supprimée avec succès.";
            header('Location: /budgets/show/' . $budgetId);
            if (!defined('TEST_MODE')) exit();
            return;
        } catch (Exception $e) {
            $this->handleException($e, '/budgets/show/' . $budgetId);
        }
    }

    public function submit($id) {
        if (!$this->checkAccess('update')) return;

        try {
            $userId = Auth::getUserId();
            BudgetWorkflowService::submit($id, $userId);
            $_SESSION['success_message'] = "Budget soumis pour validation avec succès.";
            header('Location: /budgets/show/' . $id);
            if (!defined('TEST_MODE')) exit();
            return;
        } catch (Exception $e) {
            $this->handleException($e, '/budgets/show/' . $id);
        }
    }

    public function approve($id) {
        if (!$this->checkAccess('activate')) return;

        try {
            $userId = Auth::getUserId();
            BudgetWorkflowService::validateBudget($id, $userId);
            BudgetWorkflowService::activate($id, $userId);
            $_SESSION['success_message'] = "Budget validé et activé officiellement.";
            header('Location: /budgets/show/' . $id);
            if (!defined('TEST_MODE')) exit();
            return;
        } catch (Exception $e) {
            $this->handleException($e, '/budgets/show/' . $id);
        }
    }

    public function close($id) {
        if (!$this->checkAccess('close')) return;

        try {
            $userId = Auth::getUserId();
            BudgetWorkflowService::close($id, $userId);
            $_SESSION['success_message'] = "Budget clôturé avec succès.";
            header('Location: /budgets/show/' . $id);
            if (!defined('TEST_MODE')) exit();
            return;
        } catch (Exception $e) {
            $this->handleException($e, '/budgets/show/' . $id);
        }
    }

    public function adjustment() {
        if (!$this->checkAccess('view')) return;

        $lyceeId = Auth::getLyceeId();

        // Find all active budget lines
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT bl.id, b.libelle as budget_libelle, cat.nom_categorie, cc.nom_centre
            FROM budget_lignes bl
            JOIN budgets b ON bl.budget_id = b.id
            JOIN depense_categories cat ON bl.categorie_id = cat.id
            LEFT JOIN depense_centres_couts cc ON bl.centre_cout_id = cc.id
            WHERE b.lycee_id = :lycee_id AND b.statut = 'actif'
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $adjustments = BudgetAjustement::findByLycee($lyceeId);

        require_once __DIR__ . '/../views/budgets/adjustment.php';
    }

    public function processAdjustment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /budgets/adjustment');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        try {
            $userId = Auth::getUserId();
            $type = $_POST['type_ajustement'] ?? '';
            $destId = $_POST['ligne_destination_id'] ?? null;
            $srcId = $_POST['ligne_source_id'] ?? null;
            $amount = $_POST['montant'] ?? 0;
            $reason = Validator::sanitize($_POST['motif'] ?? '');

            if ($type === 'transfert') {
                BudgetAdjustmentService::transferBudget($srcId, $destId, $amount, $userId, $reason);
                $_SESSION['success_message'] = "Transfert de crédits effectué avec succès.";
            } else {
                BudgetAdjustmentService::allocateExtra($destId, $amount, $userId, $reason);
                $_SESSION['success_message'] = "Dotation supplémentaire ajoutée avec succès.";
            }

            header('Location: /budgets/adjustment');
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            $this->handleException($e, '/budgets/adjustment');
        }
    }

    public function rebuild($id) {
        if (!$this->checkAccess('update')) return;

        try {
            BudgetService::rebuildBudget($id);
            $_SESSION['success_message'] = "Les montants du budget ont été reconstruits avec succès.";
            header('Location: /budgets/show/' . $id);
            if (!defined('TEST_MODE')) exit();
            return;
        } catch (Exception $e) {
            $this->handleException($e, '/budgets/show/' . $id);
        }
    }

    public function report($id) {
        if (!$this->checkAccess('report')) return;

        $budget = Budget::findById($id);
        if (!$budget) {
            $_SESSION['error_message'] = "Budget introuvable.";
            header('Location: /budgets');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $summary = BudgetReportingService::getBudgetSummary($id);
        $lines = BudgetReportingService::getBudgetLinesWithMetrics($id);

        require_once __DIR__ . '/../views/budgets/report.php';
    }
}
?>