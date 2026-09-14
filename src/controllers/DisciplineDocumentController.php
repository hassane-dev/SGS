<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/DisciplineDocument.php';
require_once __DIR__ . '/../models/DisciplineNotification.php';
require_once __DIR__ . '/../models/DisciplineHistorique.php';
require_once __DIR__ . '/../models/DisciplineIncident.php';
require_once __DIR__ . '/../models/DisciplineSanction.php';
require_once __DIR__ . '/../models/User.php';

class DisciplineDocumentController {

    private function getTeacherScopeInfo($lyceeId) {
        $userId = Auth::getUserId();
        $canManage = Auth::can('manage_incident', 'discipline');

        if (!$canManage) {
            $assignments = User::getTeacherAssignments($userId);
            $allowedClassIds = array_unique(array_filter(array_column($assignments, 'id_classe')));
            return [
                'isTeacherScoped' => true,
                'userId' => $userId,
                'allowedClassIds' => array_values($allowedClassIds)
            ];
        }

        return [
            'isTeacherScoped' => false,
            'userId' => $userId,
            'allowedClassIds' => []
        ];
    }

    public function upload() {
        Auth::requirePermission('discipline', 'manage_documents');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/incidents');
            exit;
        }

        $lyceeId = Auth::getLyceeId();
        $incidentId = !empty($_POST['incident_id']) ? (int)$_POST['incident_id'] : null;
        $sanctionId = !empty($_POST['sanction_id']) ? (int)$_POST['sanction_id'] : null;
        $eleveId = !empty($_POST['eleve_id']) ? (int)$_POST['eleve_id'] : null;

        $redirectUrl = $incidentId ? '/discipline/incidents/show?id=' . $incidentId : ($sanctionId ? '/discipline/sanctions/show?id=' . $sanctionId : '/discipline/incidents');

        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = _("Fichier inexistant ou erreur lors du téléversement.");
            header('Location: ' . $redirectUrl);
            exit;
        }

        $file = $_FILES['document'];
        $maxSize = 10 * 1024 * 1024; // 10 MB limit
        if ($file['size'] > $maxSize) {
            $_SESSION['flash_error'] = _("Le fichier dépasse la taille maximale autorisée (10 Mo).");
            header('Location: ' . $redirectUrl);
            exit;
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
            $_SESSION['flash_error'] = _("Format de fichier non autorisé. Formats acceptés : PDF, DOC, DOCX, JPG, PNG.");
            header('Location: ' . $redirectUrl);
            exit;
        }

        $uploadDir = UPLOAD_BASE_DIR . '/discipline_documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $safeStorageName = 'disc_doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $targetPath = $uploadDir . $safeStorageName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['flash_error'] = _("Impossible d'enregistrer le fichier sur le serveur.");
            header('Location: ' . $redirectUrl);
            exit;
        }

        try {
            $docId = DisciplineDocument::create([
                'incident_id' => $incidentId,
                'sanction_id' => $sanctionId,
                'eleve_id' => $eleveId,
                'nom_original' => $originalName,
                'nom_stockage' => $safeStorageName,
                'chemin_interne' => $targetPath,
                'mime_type' => $file['type'],
                'taille' => $file['size']
            ]);

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'user_id' => Auth::getUserId(),
                'incident_id' => $incidentId,
                'sanction_id' => $sanctionId,
                'eleve_id' => $eleveId,
                'action' => 'UPLOAD_DOCUMENT',
                'description' => "Pièce jointe '{$originalName}' téléversée (#{$docId})"
            ]);

            $_SESSION['flash_success'] = _("Pièce jointe ajoutée avec succès.");
        } catch (Exception $e) {
            @unlink($targetPath);
            $_SESSION['flash_error'] = _("Erreur lors de l'enregistrement de la pièce jointe.");
            error_log("DisciplineDocumentController::upload error: " . $e->getMessage());
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    public function download() {
        Auth::requirePermission('discipline', 'manage_documents');

        $id = (int)($_GET['id'] ?? 0);
        $lyceeId = Auth::getLyceeId();

        $doc = DisciplineDocument::findById($id, $lyceeId);
        if (!$doc) {
            http_response_code(404);
            echo "Document introuvable ou non autorisé.";
            exit;
        }

        $scopeInfo = $this->getTeacherScopeInfo($lyceeId);
        if ($scopeInfo['isTeacherScoped']) {
            $authorized = false;

            if ($doc['incident_id']) {
                $incident = DisciplineIncident::findById($doc['incident_id'], $lyceeId);
                if ($incident) {
                    if ((int)$incident['signale_par_user_id'] === (int)$scopeInfo['userId']) {
                        $authorized = true;
                    } else {
                        foreach ($incident['eleves'] as $e) {
                            if (in_array((int)$e['classe_id'], $scopeInfo['allowedClassIds'], true)) {
                                $authorized = true;
                                break;
                            }
                        }
                    }
                }
            } elseif ($doc['sanction_id']) {
                $sanction = DisciplineSanction::findById($doc['sanction_id'], $lyceeId);
                if ($sanction && in_array((int)$sanction['classe_id'], $scopeInfo['allowedClassIds'], true)) {
                    $authorized = true;
                }
            }

            if (!$authorized) {
                http_response_code(403);
                echo "Accès non autorisé à cette pièce jointe.";
                exit;
            }
        }

        if (!file_exists($doc['chemin_interne'])) {
            http_response_code(404);
            echo "Fichier physique introuvable sur le serveur.";
            exit;
        }

        // Stream file securely
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . addslashes($doc['nom_original']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($doc['chemin_interne']));
        readfile($doc['chemin_interne']);
        exit;
    }

    public function delete() {
        Auth::requirePermission('discipline', 'manage_documents');

        $id = (int)($_GET['id'] ?? 0);
        $lyceeId = Auth::getLyceeId();

        $doc = DisciplineDocument::findById($id, $lyceeId);
        if ($doc) {
            $redirectUrl = $doc['incident_id'] ? '/discipline/incidents/show?id=' . $doc['incident_id'] : ($doc['sanction_id'] ? '/discipline/sanctions/show?id=' . $doc['sanction_id'] : '/discipline/incidents');
            DisciplineDocument::delete($id, $lyceeId);

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'user_id' => Auth::getUserId(),
                'incident_id' => $doc['incident_id'],
                'sanction_id' => $doc['sanction_id'],
                'eleve_id' => $doc['eleve_id'],
                'action' => 'SUPPRESSION_DOCUMENT',
                'description' => "Pièce jointe '{$doc['nom_original']}' supprimée"
            ]);

            $_SESSION['flash_success'] = _("Pièce jointe supprimée.");
            header('Location: ' . $redirectUrl);
            exit;
        }

        header('Location: /discipline/incidents');
        exit;
    }

    public function storeNotification() {
        Auth::requirePermission('discipline', 'manage_notifications');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/incidents');
            exit;
        }

        $lyceeId = Auth::getLyceeId();
        $incidentId = !empty($_POST['incident_id']) ? (int)$_POST['incident_id'] : null;
        $sanctionId = !empty($_POST['sanction_id']) ? (int)$_POST['sanction_id'] : null;

        $redirectUrl = $incidentId ? '/discipline/incidents/show?id=' . $incidentId : ($sanctionId ? '/discipline/sanctions/show?id=' . $sanctionId : '/discipline/incidents');

        try {
            $notifId = DisciplineNotification::create($_POST);

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'user_id' => Auth::getUserId(),
                'incident_id' => $incidentId,
                'sanction_id' => $sanctionId,
                'eleve_id' => $_POST['eleve_id'] ?? null,
                'action' => 'NOTIFICATION_PARENT',
                'description' => "Notification consignée à {$_POST['destinataire_nom']} via {$_POST['mode_notification']} (#{$notifId})"
            ]);

            $_SESSION['flash_success'] = _("Notification consignée avec succès.");
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_error'] = _("Erreur lors de la consignation de la notification.");
            error_log("DisciplineDocumentController::storeNotification error: " . $e->getMessage());
        }

        header('Location: ' . $redirectUrl);
        exit;
    }
}
?>