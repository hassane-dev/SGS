<?php

require_once __DIR__ . '/../config/database.php';

class PaieBulletinRegleSnapshot {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $rawJson = is_array($data['raw_json_snapshot']) ? json_encode($data['raw_json_snapshot']) : $data['raw_json_snapshot'];
        $stmt = $db->prepare("
            INSERT INTO paie_bulletin_regles_snapshot
            (bulletin_id, regle_id, code_regle, libelle, categorie, mode_calcul, taux_applique, raw_json_snapshot)
            VALUES (:bulletin_id, :regle_id, :code_regle, :libelle, :categorie, :mode_calcul, :taux_applique, :raw_json_snapshot)
        ");
        $stmt->execute([
            'bulletin_id' => $data['bulletin_id'],
            'regle_id' => $data['regle_id'],
            'code_regle' => $data['code_regle'],
            'libelle' => $data['libelle'],
            'categorie' => $data['categorie'],
            'mode_calcul' => $data['mode_calcul'],
            'taux_applique' => $data['taux_applique'],
            'raw_json_snapshot' => $rawJson
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByBulletinId(int $bulletinId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_bulletin_regles_snapshot WHERE bulletin_id = :bulletin_id");
        $stmt->execute(['bulletin_id' => $bulletinId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
