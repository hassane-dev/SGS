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
}
