<?php
// src/controllers/PersonnelContractController.php

require_once __DIR__ . '/../services/PersonnelContractService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Validator.php';

class PersonnelContractController {

    private function checkAccess() {
        if (!Auth::can('manage_contrats', 'drh')) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    /**
     * Stores or updates a contract record.
     */
    public function store() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $personnel_id = isset($data['personnel_id']) ? (int)$data['personnel_id'] : 0;

            if (!$personnel_id) {
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            $personnel = User::findById($personnel_id);
            if (!$personnel) {
                $_SESSION['error_message'] = _("Personnel introuvable.");
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            AuthorizationScopeService::assertAccessToObject($personnel['lycee_id']);

            try {
                $author_id = Auth::getUserId();
                PersonnelContractService::saveContract($data, $author_id);

                $_SESSION['success_message'] = _("Contrat du personnel enregistré avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /drh/show?id=' . $personnel_id);
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    /**
     * Cancels an existing contract version with a required motif.
     */
    public function cancel() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $contract_id = isset($data['contract_id']) ? (int)$data['contract_id'] : 0;
            $personnel_id = isset($data['personnel_id']) ? (int)$data['personnel_id'] : 0;
            $motif = isset($data['motif_annulation']) ? trim($data['motif_annulation']) : '';

            if (!$personnel_id || !$contract_id) {
                $_SESSION['error_message'] = _("Identifiants de contrat ou personnel invalides.");
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            $personnel = User::findById($personnel_id);
            if (!$personnel) {
                $_SESSION['error_message'] = _("Personnel introuvable.");
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            AuthorizationScopeService::assertAccessToObject($personnel['lycee_id']);

            try {
                $author_id = Auth::getUserId();
                PersonnelContractService::cancelContract($contract_id, $personnel_id, $motif, $author_id);
                $_SESSION['success_message'] = _("Le contrat a été annulé avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /drh/show?id=' . $personnel_id);
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    /**
     * Returns contract details as JSON.
     */
    public function details() {
        if (!Auth::can('manage_contrats', 'drh') && !Auth::can('view_contracts', 'drh')) {
            http_response_code(403);
            echo json_encode(['error' => _("Accès refusé.")]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => _("ID invalide.")]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $details = PersonnelContractService::getContractDetails($id);
        if (!$details) {
            http_response_code(404);
            echo json_encode(['error' => _("Contrat introuvable.")]);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $personnel = User::findById((int)$details['personnel_id']);
        if ($personnel) {
            AuthorizationScopeService::assertAccessToObject($personnel['lycee_id']);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'contract' => $details]);
        if (!defined('TEST_MODE')) exit(); return;
    }
}
