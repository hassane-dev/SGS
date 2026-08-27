<?php

require_once __DIR__ . '/../config/database.php';

class PaieRegularisation {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_regularisations
            (personnel_id, source_type, periode_source_id, bulletin_source_id, periode_destination_id, type_regularisation, motif, montant_brut_delta, montant_net_delta, statut, cree_par, valide_par, created_at)
            VALUES (:personnel_id, :source_type, :periode_source_id, :bulletin_source_id, :periode_destination_id, :type_regularisation, :motif, :montant_brut_delta, :montant_net_delta, :statut, :cree_par, :valide_par, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'personnel_id' => $data['personnel_id'],
            'source_type' => $data['source_type'] ?? 'bulletin',
            'periode_source_id' => $data['periode_source_id'] ?? null,
            'bulletin_source_id' => $data['bulletin_source_id'] ?? null,
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
        $stmt = $db->prepare("
            SELECT r.*, u.nom, u.prenom, u.identifiant_public
            FROM paie_regularisations r
            JOIN utilisateurs u ON r.personnel_id = u.id_user
            WHERE r.periode_destination_id = :periode_id
            ORDER BY r.id DESC
        ");
        $stmt->execute(['periode_id' => $periodeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findPendingForEmployeeAndPeriod(int $personnelId, int $periodeDestinationId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM paie_regularisations
            WHERE personnel_id = :personnel_id
              AND periode_destination_id = :periode_id
              AND statut = 'valide'
            ORDER BY id ASC
        ");
        $stmt->execute([
            'personnel_id' => $personnelId,
            'periode_id' => $periodeDestinationId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateStatus(int $id, string $statut, ?int $validePar = null): bool {
        $db = Database::getInstance();
        $sql = "UPDATE paie_regularisations SET statut = :statut";
        $params = ['id' => $id, 'statut' => $statut];
        if ($validePar !== null) {
            $sql .= ", valide_par = :valide_par";
            $params['valide_par'] = $validePar;
        }
        $sql .= " WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function logIntegration(int $regularisationId, int $bulletinId, float $montantBrut, float $montantNet): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_regularisation_integrations
            (regularisation_id, bulletin_id, montant_brut_integre, montant_net_integre, created_at)
            VALUES (:regularisation_id, :bulletin_id, :montant_brut_integre, :montant_net_integre, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'regularisation_id' => $regularisationId,
            'bulletin_id' => $bulletinId,
            'montant_brut_integre' => $montantBrut,
            'montant_net_integre' => $montantNet
        ]);
        return (int)$db->lastInsertId();
    }

    public static function rollbackIntegrationsForBulletin(int $bulletinId): void {
        $db = Database::getInstance();

        // 1. Find all regularisations linked to this bulletin via integrations
        $stmtFind = $db->prepare("SELECT regularisation_id FROM paie_regularisation_integrations WHERE bulletin_id = :bulletin_id");
        $stmtFind->execute(['bulletin_id' => $bulletinId]);
        $reguIds = $stmtFind->fetchAll(PDO::FETCH_COLUMN);

        // 2. Remove integration records
        $stmtDel = $db->prepare("DELETE FROM paie_regularisation_integrations WHERE bulletin_id = :bulletin_id");
        $stmtDel->execute(['bulletin_id' => $bulletinId]);

        // 3. Mark regularisations back to 'valide'
        if (!empty($reguIds)) {
            $inClause = implode(',', array_map('intval', $reguIds));
            $db->exec("UPDATE paie_regularisations SET statut = 'valide' WHERE id IN ({$inClause})");
        }
    }
}
