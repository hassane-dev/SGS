<?php
// src/controllers/PersonnelDocumentController.php

require_once __DIR__ . '/../services/PersonnelDocumentService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Validator.php';

class PersonnelDocumentController {

    private function checkAccess(string $action = 'manage_documents') {
        if (!Auth::can($action, 'drh')) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    /**
     * Uploads an HR document attachment.
     */
    public function store() {
        $this->checkAccess('manage_documents');

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
                $file = $_FILES['document_file'] ?? [];

                PersonnelDocumentService::saveDocument($data, $file, $author_id);

                $_SESSION['success_message'] = _("Document téléversé et archivé avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /drh/show?id=' . $personnel_id);
            if (!defined('TEST_MODE')) exit(); return;
        }
    }

    /**
     * Download or stream HR document safely with IDOR and sensitive data check.
     */
    public function download() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            header('Location: /drh');
            if (!defined('TEST_MODE')) exit(); return;
        }

        $doc = PersonnelDocumentService::getDocumentById($id);
        if (!$doc) {
            http_response_code(404);
            echo "Document non trouvé.";
            if (!defined('TEST_MODE')) exit(); return;
        }

        // Check view permissions
        if ($doc['confidentiel'] == 1 && !Auth::can('view_sensitive', 'drh')) {
            http_response_code(403);
            echo "Accès refusé au document confidentiel.";
            if (!defined('TEST_MODE')) exit(); return;
        }

        $personnel = User::findById($doc['personnel_id']);
        if ($personnel) {
            AuthorizationScopeService::assertAccessToObject($personnel['lycee_id']);
        }

        $filePath = __DIR__ . '/../../public' . $doc['chemin_fichier'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Fichier physique introuvable.";
            if (!defined('TEST_MODE')) exit(); return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($doc['nom_fichier']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        if (!defined('TEST_MODE')) exit(); return;
    }

    /**
     * Deletes a document.
     */
    public function delete() {
        $this->checkAccess('manage_documents');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $personnel_id = isset($_POST['personnel_id']) ? (int)$_POST['personnel_id'] : 0;

            if (!$id || !$personnel_id) {
                header('Location: /drh');
                if (!defined('TEST_MODE')) exit(); return;
            }

            $personnel = User::findById($personnel_id);
            if ($personnel) {
                AuthorizationScopeService::assertAccessToObject($personnel['lycee_id']);
            }

            try {
                $author_id = Auth::getUserId();
                PersonnelDocumentService::deleteDocument($id, $author_id);

                $_SESSION['success_message'] = _("Document supprimé avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }

            header('Location: /drh/show?id=' . $personnel_id);
            if (!defined('TEST_MODE')) exit(); return;
        }
    }
}
