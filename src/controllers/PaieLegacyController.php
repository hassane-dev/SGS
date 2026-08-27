<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../services/LegacySalaryFacade.php';
require_once __DIR__ . '/../models/PaiePeriode.php';

class PaieLegacyController {

    public function conflits() {
        if (!Auth::can('create', 'paie') && !Auth::can('audit', 'paie')) {
            Auth::requirePermission('paie', 'audit');
        }

        $lyceeId = Auth::getLyceeId() ?: 1;
        $db = Database::getInstance();

        // Fetch legacy salaires records
        $stmt = $db->query("
            SELECT s.*, u.nom, u.prenom, u.identifiant_public
            FROM salaires s
            LEFT JOIN utilisateurs u ON s.personnel_id = u.id_user
            ORDER BY s.id_salaire DESC
        ");
        $legacySalaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch open paie periods for target import
        $periodes = PaiePeriode::findAllForLycee($lyceeId);

        include __DIR__ . '/../views/paie/legacy/conflits.php';
    }

    public function import() {
        Auth::requirePermission('paie', 'create');
        $periodeId = (int)($_POST['periode_id'] ?? 0);
        $userId = Auth::getUserId();

        try {
            $count = LegacySalaryFacade::importLegacySalairesToPaie($periodeId, $userId);
            $_SESSION['success_message'] = sprintf(_("%d enregistrements de salaires historiques importés dans la paie."), $count);
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/periodes/' . $periodeId);
        exit();
    }
}
