<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaieCahierTexteValidation.php';

class PaieCahierTexteController {

    public function index() {
        Auth::requirePermission('paie', 'view');
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT v.*, u.nom, u.prenom, c.date_cours, c.contenu_cours
            FROM paie_cahier_texte_validations v
            JOIN utilisateurs u ON v.enseignant_id = u.id_user
            JOIN cahier_texte c ON v.cahier_id = c.cahier_id
            ORDER BY v.id DESC LIMIT 100
        ");
        $validations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../views/paie/cahier_texte/index.php';
    }

    public function validate() {
        Auth::requirePermission('paie', 'validate');
        $cahierId = (int)($_POST['cahier_id'] ?? 0);
        $dureeHeures = (float)($_POST['duree_heures'] ?? 1.0);
        $tauxHoraire = (float)($_POST['taux_horaire'] ?? 5000.00);
        $enseignantId = (int)($_POST['enseignant_id'] ?? 0);
        $userId = Auth::getUserId();

        try {
            $existing = PaieCahierTexteValidation::findByCahierId($cahierId);
            if (!$existing) {
                PaieCahierTexteValidation::create([
                    'cahier_id' => $cahierId,
                    'enseignant_id' => $enseignantId,
                    'duree_heures' => $dureeHeures,
                    'taux_horaire' => $tauxHoraire,
                    'statut_validation' => 'valide',
                    'valide_par' => $userId,
                    'valide_le' => date('Y-m-d H:i:s')
                ]);
            }
            $_SESSION['success_message'] = _("Séance de cours validée pour la paie.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        header('Location: /paie/cahier-texte');
        exit();
    }
}
