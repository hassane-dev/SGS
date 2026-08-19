<?php

require_once __DIR__ . '/../config/database.php';

class PaiePeriode {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_periodes
            (lycee_id, periode_comptable_id, code_periode, mois, annee, date_debut, date_fin, statut, cree_par, created_at, updated_at)
            VALUES (:lycee_id, :periode_comptable_id, :code_periode, :mois, :annee, :date_debut, :date_fin, :statut, :cree_par, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'periode_comptable_id' => $data['periode_comptable_id'],
            'code_periode' => $data['code_periode'],
            'mois' => $data['mois'],
            'annee' => $data['annee'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'statut' => $data['statut'] ?? 'brouillon',
            'cree_par' => $data['cree_par']
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_periodes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findByLyceeAndMoisAnnee(int $lyceeId, int $annee, int $mois): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_periodes WHERE lycee_id = :lycee_id AND annee = :annee AND mois = :mois");
        $stmt->execute(['lycee_id' => $lyceeId, 'annee' => $annee, 'mois' => $mois]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findAllForLycee(int $lyceeId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_periodes WHERE lycee_id = :lycee_id ORDER BY annee DESC, mois DESC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateStatus(int $id, string $statut, ?int $userId = null): bool {
        $db = Database::getInstance();
        $extra = "";
        $params = ['id' => $id, 'statut' => $statut];
        if ($statut === 'valide' && $userId) {
            $extra = ", valide_par = :user_id";
            $params['user_id'] = $userId;
        } elseif ($statut === 'cloture' && $userId) {
            $extra = ", cloture_par = :user_id, closed_at = CURRENT_TIMESTAMP";
            $params['user_id'] = $userId;
        }
        $stmt = $db->prepare("UPDATE paie_periodes SET statut = :statut, updated_at = CURRENT_TIMESTAMP {$extra} WHERE id = :id");
        return $stmt->execute($params);
    }
}
