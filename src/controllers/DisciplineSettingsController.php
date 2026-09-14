<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../models/DisciplineTypeSanction.php';

class DisciplineSettingsController {

    public function index() {
        if (!Auth::can('view_config', 'discipline') && !Auth::can('manage_config', 'discipline')) {
            Auth::requirePermission('discipline', 'view_config');
        }

        $lyceeId = Auth::getLyceeId();
        if (!$lyceeId) {
            header('Location: /home?error=' . urlencode(_("Établissement non valide.")));
            exit;
        }

        $incidents = DisciplineTypeIncident::findAll($lyceeId);
        $sanctions = DisciplineTypeSanction::findAll($lyceeId);

        $activeTab = $_GET['tab'] ?? 'incidents';

        View::render('discipline/settings/index', [
            'incidents' => $incidents,
            'sanctions' => $sanctions,
            'activeTab' => $activeTab
        ]);
    }

    public function storeIncident() {
        Auth::requirePermission('discipline', 'manage_config');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/settings?tab=incidents');
            exit;
        }

        try {
            DisciplineTypeIncident::save($_POST);
            $_SESSION['flash_success'] = _("Type d'incident enregistré avec succès.");
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_error'] = _("Erreur lors de l'enregistrement du type d'incident.");
            error_log("DisciplineSettingsController::storeIncident error: " . $e->getMessage());
        }

        header('Location: /discipline/settings?tab=incidents');
        exit;
    }

    public function toggleIncident() {
        Auth::requirePermission('discipline', 'manage_config');

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $success = DisciplineTypeIncident::toggleActive($id);
            if ($success) {
                $_SESSION['flash_success'] = _("Statut du type d'incident modifié.");
            } else {
                $_SESSION['flash_error'] = _("Impossible de modifier le statut.");
            }
        }

        header('Location: /discipline/settings?tab=incidents');
        exit;
    }

    public function storeSanction() {
        Auth::requirePermission('discipline', 'manage_config');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/settings?tab=sanctions');
            exit;
        }

        try {
            DisciplineTypeSanction::save($_POST);
            $_SESSION['flash_success'] = _("Type de sanction enregistré avec succès.");
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        } catch (Exception $e) {
            $_SESSION['flash_error'] = _("Erreur lors de l'enregistrement du type de sanction.");
            error_log("DisciplineSettingsController::storeSanction error: " . $e->getMessage());
        }

        header('Location: /discipline/settings?tab=sanctions');
        exit;
    }

    public function toggleSanction() {
        Auth::requirePermission('discipline', 'manage_config');

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $success = DisciplineTypeSanction::toggleActive($id);
            if ($success) {
                $_SESSION['flash_success'] = _("Statut du type de sanction modifié.");
            } else {
                $_SESSION['flash_error'] = _("Impossible de modifier le statut.");
            }
        }

        header('Location: /discipline/settings?tab=sanctions');
        exit;
    }
}
?>