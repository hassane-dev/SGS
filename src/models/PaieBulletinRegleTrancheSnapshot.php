<?php

require_once __DIR__ . '/../config/database.php';

class PaieBulletinRegleTrancheSnapshot {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_bulletin_regle_tranches_snapshot
            (regle_snapshot_id, tranche_numero, limite_inferieure, limite_superieure, taux, base_imposable_tranche, montant_calcule)
            VALUES (:regle_snapshot_id, :tranche_numero, :limite_inferieure, :limite_superieure, :taux, :base_imposable_tranche, :montant_calcule)
        ");
        $stmt->execute([
            'regle_snapshot_id' => $data['regle_snapshot_id'],
            'tranche_numero' => $data['tranche_numero'],
            'limite_inferieure' => $data['limite_inferieure'],
            'limite_superieure' => $data['limite_superieure'] ?? null,
            'taux' => $data['taux'],
            'base_imposable_tranche' => $data['base_imposable_tranche'],
            'montant_calcule' => $data['montant_calcule']
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByRegleSnapshotId(int $regleSnapshotId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_bulletin_regle_tranches_snapshot WHERE regle_snapshot_id = :regle_snapshot_id ORDER BY tranche_numero ASC");
        $stmt->execute(['regle_snapshot_id' => $regleSnapshotId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
