<?php

require_once __DIR__ . '/../config/database.php';

class PaieBulletinHeure {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_bulletin_heures
            (bulletin_id, cahier_validation_id, heures_effectuees, taux_horaire, montant_total)
            VALUES (:bulletin_id, :cahier_validation_id, :heures_effectuees, :taux_horaire, :montant_total)
        ");
        $stmt->execute([
            'bulletin_id' => $data['bulletin_id'],
            'cahier_validation_id' => $data['cahier_validation_id'],
            'heures_effectuees' => $data['heures_effectuees'],
            'taux_horaire' => $data['taux_horaire'],
            'montant_total' => $data['montant_total']
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByBulletinId(int $bulletinId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_bulletin_heures WHERE bulletin_id = :bulletin_id");
        $stmt->execute(['bulletin_id' => $bulletinId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
