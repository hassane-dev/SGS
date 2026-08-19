<?php

require_once __DIR__ . '/../config/database.php';

class PaieRegularisation {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_regularisations
            (bulletin_source_id, periode_destination_id, type_regularisation, motif, montant_brut_delta, montant_net_delta, statut, cree_par, valide_par, created_at)
            VALUES (:bulletin_source_id, :periode_destination_id, :type_regularisation, :motif, :montant_brut_delta, :montant_net_delta, :statut, :cree_par, :valide_par, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'bulletin_source_id' => $data['bulletin_source_id'],
            'periode_destination_id' => $data['periode_destination_id'],
            'type_regularisation' => $data['type_regularisation'],
            'motif' => $data['motif'],
            'montant_brut_delta' => $data['montant_brut_delta'] ?? 0.00,
            'montant_net_delta' => $data['montant_net_delta'] ?? 0.00,
            'statut' => $data['statut'] ?? 'brouillon',
            'cree_par' => $data['cree_par'],
            'valide_par' => $data['valide_par'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_regularisations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findByDestinationPeriod(int $periodeId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_regularisations WHERE periode_destination_id = :periode_id ORDER BY id DESC");
        $stmt->execute(['periode_id' => $periodeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
