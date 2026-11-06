<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/CsrfService.php';
require_once __DIR__ . '/../models/ParamPromotionSeuil.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Serie.php';
require_once __DIR__ . '/../services/PromotionThresholdService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class PromotionThresholdController {

    private function checkAccess(string $action = 'view'): void {
        if (!Auth::check()) {
            if (defined('TEST_MODE')) {
                throw new Exception("Authentification requise.");
            }
            if (!headers_sent()) {
                header('Location: /login');
            }
            exit();
        }

        if (!Auth::can($action, 'promotion_threshold')) {
            if (defined('TEST_MODE')) {
                throw new Exception("Accès Interdit (Permission promotion_threshold:{$action} requise).");
            }
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    /**
     * Main dashboard displaying hierarchical promotion thresholds matrix.
     * Route: GET /settings/promotion-thresholds
     */
    public function index(): void {
        $this->checkAccess('view');
        $lyceeId = Auth::getLyceeId();

        $matrix = PromotionThresholdService::getEffectiveMatrix($lyceeId);
        $cycles = Cycle::findByLycee($lyceeId);
        $series = Serie::findAll($lyceeId);

        // Map distinct levels per cycle
        $cycleNiveaux = [];
        foreach ($cycles as $cy) {
            $cId = (int)$cy['id_cycle'];
            $niveaux = Classe::findDistinctNiveauxByCycle($cId, $lyceeId);
            if (empty($niveaux) && !empty($cy['niveau_debut']) && !empty($cy['niveau_fin'])) {
                // Generate canonical fallback range if no classes created yet
                $startOrder = Classe::resolveLevelOrder($cy['niveau_debut']);
                $endOrder = Classe::resolveLevelOrder($cy['niveau_fin']);
                $canonMap = Classe::getCanonicalLevelMap();
                if ($startOrder !== null && $endOrder !== null && $startOrder <= $endOrder) {
                    for ($o = $startOrder; $o <= $endOrder; $o++) {
                        if (isset($canonMap[$o])) {
                            $niveaux[] = $canonMap[$o];
                        }
                    }
                }
            }
            $cycleNiveaux[$cId] = $niveaux;
        }

        View::render('promotion_thresholds/index', [
            'title' => _("Seuils de Promotion & Délibération"),
            'matrix' => $matrix,
            'cycles' => $cycles,
            'series' => $series,
            'cycleNiveaux' => $cycleNiveaux,
            'lyceeId' => $lyceeId,
            'canManage' => Auth::can('manage', 'promotion_threshold')
        ]);
    }

    /**
     * Store or update a threshold rule.
     * Route: POST /settings/promotion-thresholds/store
     */
    public function store(): void {
        $this->checkAccess('manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /settings/promotion-thresholds');
            exit();
        }

        CsrfService::requireValid();

        $lyceeId = Auth::getLyceeId();
        $data = Validator::sanitize($_POST);
        $data['lycee_id'] = $lyceeId;
        $data['cree_par'] = Auth::getUserId();

        // Handle empty string to null normalization
        $data['cycle_id'] = !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null;
        $data['niveau'] = !empty($data['niveau']) ? trim($data['niveau']) : null;
        $data['serie'] = !empty($data['serie']) ? trim($data['serie']) : null;

        // Exclusion handling
        if (empty($data['activer_exclusion'])) {
            $data['seuil_exclusion_max'] = null;
        }

        try {
            PromotionThresholdService::saveThreshold($data);
            $_SESSION['success_message'] = _("La règle de seuils a été enregistrée avec succès.");
        } catch (InvalidArgumentException $e) {
            $_SESSION['error_message'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
        } catch (Exception $e) {
            error_log("Error in PromotionThresholdController::store: " . $e->getMessage());
            $_SESSION['error_message'] = _("Une erreur inattendue est survenue lors de l'enregistrement.");
        }

        header('Location: /settings/promotion-thresholds');
        if (defined('TEST_MODE')) return;
        exit();
    }

    /**
     * Delete a threshold rule.
     * Route: POST /settings/promotion-thresholds/delete
     */
    public function delete(): void {
        $this->checkAccess('manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /settings/promotion-thresholds');
            exit();
        }

        CsrfService::requireValid();

        $lyceeId = Auth::getLyceeId();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $_SESSION['error_message'] = _("Identifiant de règle invalide.");
            header('Location: /settings/promotion-thresholds');
            exit();
        }

        try {
            $rule = ParamPromotionSeuil::findById($id);
            if (!$rule || (int)$rule['lycee_id'] !== $lyceeId) {
                throw new InvalidArgumentException(_("Règle introuvable ou non autorisée."));
            }

            PromotionThresholdService::deleteThreshold($id, $lyceeId);
            $_SESSION['success_message'] = _("La règle de seuils a été supprimée avec succès.");
        } catch (Exception $e) {
            error_log("Error in PromotionThresholdController::delete: " . $e->getMessage());
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /settings/promotion-thresholds');
        if (defined('TEST_MODE')) return;
        exit();
    }

    /**
     * AJAX endpoint to test/simulate threshold resolution for a student context.
     * Route: GET /settings/promotion-thresholds/resolve-preview
     */
    public function resolvePreview(): void {
        $this->checkAccess('view');
        header('Content-Type: application/json');

        $lyceeId = Auth::getLyceeId();
        $cycleId = filter_input(INPUT_GET, 'cycle_id', FILTER_VALIDATE_INT);
        $niveau = filter_input(INPUT_GET, 'niveau', FILTER_DEFAULT);
        $serie = filter_input(INPUT_GET, 'serie', FILTER_DEFAULT);

        if (!$cycleId || !$niveau) {
            echo json_encode([
                'success' => false,
                'message' => _("Cycle et Niveau sont obligatoires pour tester la résolution.")
            ]);
            if (defined('TEST_MODE')) return;
            exit();
        }

        $result = PromotionThresholdService::resolveThresholds($lyceeId, (int)$cycleId, (string)$niveau, $serie ?: null);
        echo json_encode([
            'success' => true,
            'result' => $result
        ]);
        if (defined('TEST_MODE')) return;
        exit();
    }
}
