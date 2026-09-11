<?php

require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../services/BulletinValidationService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class BulletinValidationController {

    private function checkAccess() {
        Auth::requirePermission('bulletin', 'validate');
    }

    /**
     * Renders the main Able Pro Report Card Validation dashboard.
     */
    public function index() {
        $this->checkAccess();

        $lycee_id = Auth::getLyceeId();

        $cycles = Cycle::findByLycee($lycee_id);
        $sequences = Sequence::findAll();
        $classes = Classe::findAll($lycee_id);

        // Extract distinct niveaux
        $niveaux = [];
        foreach ($classes as $c) {
            if (!empty($c['niveau']) && !in_array($c['niveau'], $niveaux)) {
                $niveaux[] = $c['niveau'];
            }
        }
        sort($niveaux);

        View::render('bulletins/validation', [
            'cycles' => $cycles,
            'niveaux' => $niveaux,
            'classes' => $classes,
            'sequences' => $sequences,
            'title' => 'Validation Institutionnelle des Bulletins'
        ]);
    }

    /**
     * AJAX endpoint returning summary report for target scope before validation.
     */
    public function summary() {
        $this->checkAccess();

        header('Content-Type: application/json');

        try {
            $lycee_id = Auth::getLyceeId();
            $sequence_id = (int)($_POST['sequence_id'] ?? 0);
            $scope_type = trim((string)($_POST['scope_type'] ?? ''));
            $scope_value = $_POST['scope_value'] ?? null;

            if (!$sequence_id || !$scope_type || $scope_value === null) {
                echo json_encode(['success' => false, 'message' => 'Paramètres incomplets (séquence et périmètre requis).']);
                exit();
            }

            $summary = BulletinValidationService::getValidationSummary($lycee_id, $sequence_id, $scope_type, $scope_value);
            echo json_encode($summary);
            exit();

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
    }

    /**
     * Executes bulk institutional validation.
     */
    public function execute() {
        $this->checkAccess();

        header('Content-Type: application/json');

        try {
            $lycee_id = Auth::getLyceeId();
            $user_id = Auth::getUserId();
            $sequence_id = (int)($_POST['sequence_id'] ?? 0);
            $scope_type = trim((string)($_POST['scope_type'] ?? ''));
            $scope_value = $_POST['scope_value'] ?? null;

            if (!$sequence_id || !$scope_type || $scope_value === null) {
                echo json_encode(['success' => false, 'message' => 'Paramètres incomplets.']);
                exit();
            }

            $result = BulletinValidationService::executeValidation($lycee_id, $sequence_id, $scope_type, $scope_value, $user_id);
            echo json_encode($result);
            exit();

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
    }
}
?>