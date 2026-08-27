<?php

require_once __DIR__ . '/../config/database.php';

class PaieBulletinLigne {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_bulletin_lignes
            (bulletin_id, code_rubrique, libelle, categorie, base_calcul, taux, montant_salarial, montant_patronal, ordre_affichage, est_imposable, est_cotisable)
            VALUES (:bulletin_id, :code_rubrique, :libelle, :categorie, :base_calcul, :taux, :montant_salarial, :montant_patronal, :ordre_affichage, :est_imposable, :est_cotisable)
        ");
        $stmt->execute([
            'bulletin_id' => $data['bulletin_id'],
            'code_rubrique' => $data['code_rubrique'],
            'libelle' => $data['libelle'],
            'categorie' => $data['categorie'],
            'base_calcul' => $data['base_calcul'] ?? 0.00,
            'taux' => $data['taux'] ?? 0.00,
            'montant_salarial' => $data['montant_salarial'] ?? 0.00,
            'montant_patronal' => $data['montant_patronal'] ?? 0.00,
            'ordre_affichage' => $data['ordre_affichage'] ?? 0,
            'est_imposable' => $data['est_imposable'] ?? 1,
            'est_cotisable' => $data['est_cotisable'] ?? 1
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByBulletinId(int $bulletinId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_bulletin_lignes WHERE bulletin_id = :bulletin_id ORDER BY ordre_affichage ASC, id ASC");
        $stmt->execute(['bulletin_id' => $bulletinId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
