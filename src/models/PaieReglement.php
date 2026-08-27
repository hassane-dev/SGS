<?php

require_once __DIR__ . '/../config/database.php';

class PaieReglement {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_reglements
            (bulletin_id, compte_financier_id, mouvement_tresorerie_id, mode_reglement, montant, reference_transaction, date_reglement, statut, cree_par, created_at)
            VALUES (:bulletin_id, :compte_financier_id, :mouvement_tresorerie_id, :mode_reglement, :montant, :reference_transaction, :date_reglement, :statut, :cree_par, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'bulletin_id' => $data['bulletin_id'],
            'compte_financier_id' => $data['compte_financier_id'],
            'mouvement_tresorerie_id' => $data['mouvement_tresorerie_id'] ?? null,
            'mode_reglement' => $data['mode_reglement'],
            'montant' => $data['montant'],
            'reference_transaction' => $data['reference_transaction'] ?? null,
            'date_reglement' => $data['date_reglement'] ?? date('Y-m-d'),
            'statut' => $data['statut'] ?? 'valide',
            'cree_par' => $data['cree_par']
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByBulletinId(int $bulletinId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_reglements WHERE bulletin_id = :bulletin_id ORDER BY id ASC");
        $stmt->execute(['bulletin_id' => $bulletinId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
