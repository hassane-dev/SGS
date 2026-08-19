<?php

require_once __DIR__ . '/../config/database.php';

class PaieBulletinContratSnapshot {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $rawJson = is_array($data['raw_json_snapshot']) ? json_encode($data['raw_json_snapshot']) : $data['raw_json_snapshot'];
        $stmt = $db->prepare("
            INSERT INTO paie_bulletin_contrat_snapshot
            (bulletin_id, contrat_id, version_num, type_contrat, mode_calcul_principal, devise, raw_json_snapshot, created_at)
            VALUES (:bulletin_id, :contrat_id, :version_num, :type_contrat, :mode_calcul_principal, :devise, :raw_json_snapshot, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'bulletin_id' => $data['bulletin_id'],
            'contrat_id' => $data['contrat_id'],
            'version_num' => $data['version_num'],
            'type_contrat' => $data['type_contrat'],
            'mode_calcul_principal' => $data['mode_calcul_principal'],
            'devise' => $data['devise'],
            'raw_json_snapshot' => $rawJson
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByBulletinId(int $bulletinId): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_bulletin_contrat_snapshot WHERE bulletin_id = :bulletin_id");
        $stmt->execute(['bulletin_id' => $bulletinId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }
}
