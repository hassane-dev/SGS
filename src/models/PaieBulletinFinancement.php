<?php

require_once __DIR__ . '/../config/database.php';

class PaieBulletinFinancement {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_bulletin_financements
            (bulletin_id, financeur_nom, type_financeur, pourcentage_prise_en_charge, montant_prise_en_charge)
            VALUES (:bulletin_id, :financeur_nom, :type_financeur, :pourcentage_prise_en_charge, :montant_prise_en_charge)
        ");
        $stmt->execute([
            'bulletin_id' => $data['bulletin_id'],
            'financeur_nom' => $data['financeur_nom'],
            'type_financeur' => $data['type_financeur'],
            'pourcentage_prise_en_charge' => $data['pourcentage_prise_en_charge'],
            'montant_prise_en_charge' => $data['montant_prise_en_charge']
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByBulletinId(int $bulletinId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_bulletin_financements WHERE bulletin_id = :bulletin_id");
        $stmt->execute(['bulletin_id' => $bulletinId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
