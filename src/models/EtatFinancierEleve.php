<?php

require_once __DIR__ . '/../config/database.php';

class EtatFinancierEleve {

    public static function findByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM etats_financiers_eleves WHERE eleve_id = :eleve_id");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $db = Database::getInstance();
        $existing = self::findByEleveId($data['eleve_id']);

        $params = [
            'eleve_id' => $data['eleve_id'],
            'inscription_statut' => $data['inscription_statut'] ?? 'Non payée',
            'mensualite_statut' => $data['mensualite_statut'] ?? 'À jour',
            'notes_consultation' => $data['notes_consultation'] ?? 'Interdite',
            'bulletin_impression' => $data['bulletin_impression'] ?? 'Interdite'
        ];

        if ($existing) {
            $stmt = $db->prepare("UPDATE etats_financiers_eleves SET
                inscription_statut = :inscription_statut,
                mensualite_statut = :mensualite_statut,
                notes_consultation = :notes_consultation,
                bulletin_impression = :bulletin_impression
                WHERE eleve_id = :eleve_id");
            return $stmt->execute($params);
        } else {
            $stmt = $db->prepare("INSERT INTO etats_financiers_eleves (eleve_id, inscription_statut, mensualite_statut, notes_consultation, bulletin_impression)
                VALUES (:eleve_id, :inscription_statut, :mensualite_statut, :notes_consultation, :bulletin_impression)");
            return $stmt->execute($params);
        }
    }

    /**
     * Backward-compatible delegate to FinanceService.
     */
    public static function recalculateState($eleveId) {
        require_once __DIR__ . '/FinanceService.php';
        return FinanceService::updateFinancialState($eleveId);
    }
}
