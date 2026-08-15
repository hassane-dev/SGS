<?php
// src/controllers/PersonnelAssignmentController.php

require_once __DIR__ . '/../services/PersonnelAssignmentService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Validator.php';

class PersonnelAssignmentController {

    private function checkAccess() {
        if (!Auth::can('manage_affectations', 'drh')) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    /**
     * Stores or updates a cycle assignment with strict overlap validation.
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

            // Server-side object assertion
            $personnel = User::findById($personnel_id);
            if (!$personnel) {
                $_SESSION['error_message'] = _("Personnel introuvable.");
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            AuthorizationScopeService::assertAccessToObject($personnel['lycee_id']);

            try {
                $author_id = Auth::getUserId();
                PersonnelAssignmentService::saveAssignment($data, $author_id);

                $_SESSION['success_message'] = _("Affectation de cycle enregistrée avec succès. Les accès de l'utilisateur ont été immédiatement synchronisés.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /drh/show?id=' . $personnel_id);
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    /**
     * Removes an assignment and updates scope cache.
     */
    public function delete() {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $personnel_id = isset($_POST['personnel_id']) ? (int)$_POST['personnel_id'] : 0;

            if (!$id || !$personnel_id) {
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
                PersonnelAssignmentService::deleteAssignment($id, $author_id);

                $_SESSION['success_message'] = _("Affectation de cycle retirée avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /drh/show?id=' . $personnel_id);
            if (!defined('TEST_MODE')) exit(); return;
        }
    }
}
