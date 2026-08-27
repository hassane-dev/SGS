<?php

require_once __DIR__ . '/../config/database.php';

class PaieBulletin {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_bulletins
            (periode_id, personnel_id, contrat_id, version_num, est_version_active, entite_juridique_id, cycle_id,
             salaire_base, total_brut, total_cotisations_salariales, total_impots, total_retenues, net_imposable, net_a_payer,
             total_cotisations_patronales, cout_total_employeur, devise, taux_change, statut_bulletin, statut_comptabilisation,
             statut_reglement, statut_cloture, est_reprise_legacy, idempotency_key, content_hash, created_at, updated_at)
            VALUES (:periode_id, :personnel_id, :contrat_id, :version_num, :est_version_active, :entite_juridique_id, :cycle_id,
             :salaire_base, :total_brut, :total_cotisations_salariales, :total_impots, :total_retenues, :net_imposable, :net_a_payer,
             :total_cotisations_patronales, :cout_total_employeur, :devise, :taux_change, :statut_bulletin, :statut_comptabilisation,
             :statut_reglement, :statut_cloture, :est_reprise_legacy, :idempotency_key, :content_hash, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'periode_id' => $data['periode_id'],
            'personnel_id' => $data['personnel_id'],
            'contrat_id' => $data['contrat_id'],
            'version_num' => $data['version_num'] ?? 1,
            'est_version_active' => $data['est_version_active'] ?? 1,
            'entite_juridique_id' => $data['entite_juridique_id'],
            'cycle_id' => $data['cycle_id'] ?? null,
            'salaire_base' => $data['salaire_base'] ?? 0.00,
            'total_brut' => $data['total_brut'] ?? 0.00,
            'total_cotisations_salariales' => $data['total_cotisations_salariales'] ?? 0.00,
            'total_impots' => $data['total_impots'] ?? 0.00,
            'total_retenues' => $data['total_retenues'] ?? 0.00,
            'net_imposable' => $data['net_imposable'] ?? 0.00,
            'net_a_payer' => $data['net_a_payer'] ?? 0.00,
            'total_cotisations_patronales' => $data['total_cotisations_patronales'] ?? 0.00,
            'cout_total_employeur' => $data['cout_total_employeur'] ?? 0.00,
            'devise' => $data['devise'] ?? 'FCFA',
            'taux_change' => $data['taux_change'] ?? 1.000000,
            'statut_bulletin' => $data['statut_bulletin'] ?? 'brouillon',
            'statut_comptabilisation' => $data['statut_comptabilisation'] ?? 'non_comptabilise',
            'statut_reglement' => $data['statut_reglement'] ?? 'non_paye',
            'statut_cloture' => $data['statut_cloture'] ?? 'ouvert',
            'est_reprise_legacy' => $data['est_reprise_legacy'] ?? 0,
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'content_hash' => $data['content_hash'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_bulletins WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findActiveForContractAndPeriod(int $personnelId, int $entiteJuridiqueId, int $contratId, int $periodeId): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM paie_bulletins
            WHERE personnel_id = :personnel_id
              AND entite_juridique_id = :entite_juridique_id
              AND contrat_id = :contrat_id
              AND periode_id = :periode_id
              AND est_version_active = 1
        ");
        $stmt->execute([
            'personnel_id' => $personnelId,
            'entite_juridique_id' => $entiteJuridiqueId,
            'contrat_id' => $contratId,
            'periode_id' => $periodeId
        ]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findByPeriod(int $periodeId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT b.*, u.nom, u.prenom, u.identifiant_public
            FROM paie_bulletins b
            JOIN utilisateurs u ON b.personnel_id = u.id_user
            WHERE b.periode_id = :periode_id
            ORDER BY u.nom ASC, u.prenom ASC, b.version_num ASC
        ");
        $stmt->execute(['periode_id' => $periodeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByIdempotencyKey(int $periodeId, string $idempotencyKey): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_bulletins WHERE periode_id = :periode_id AND idempotency_key = :key");
        $stmt->execute(['periode_id' => $periodeId, 'key' => $idempotencyKey]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getInstance();
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $col => $val) {
            $fields[] = "{$col} = :{$col}";
            $params[$col] = $val;
        }
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE paie_bulletins SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}
