<?php

require_once __DIR__ . '/../config/database.php';

class PaieBaremeTranche {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_baremes_tranches
            (regle_id, tranche_numero, limite_inferieure, limite_superieure, taux, montant_fixe)
            VALUES (:regle_id, :tranche_numero, :limite_inferieure, :limite_superieure, :taux, :montant_fixe)
        ");
        $stmt->execute([
            'regle_id' => $data['regle_id'],
            'tranche_numero' => $data['tranche_numero'],
            'limite_inferieure' => $data['limite_inferieure'],
            'limite_superieure' => isset($data['limite_superieure']) && $data['limite_superieure'] !== '' ? (float)$data['limite_superieure'] : null,
            'taux' => $data['taux'],
            'montant_fixe' => $data['montant_fixe'] ?? 0.00
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByRegleId(int $regleId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_baremes_tranches WHERE regle_id = :regle_id ORDER BY tranche_numero ASC");
        $stmt->execute(['regle_id' => $regleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteByRegleId(int $regleId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM paie_baremes_tranches WHERE regle_id = :regle_id");
        return $stmt->execute(['regle_id' => $regleId]);
    }
}
