<?php

require_once __DIR__ . '/../config/database.php';

class ExerciceFinancier {

    public static function findActive($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM exercices_financiers
            WHERE lycee_id = :lycee_id AND est_actif = 1 AND cloture = 0
            LIMIT 1
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findAll($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM exercices_financiers
            WHERE lycee_id = :lycee_id
            ORDER BY date_debut DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM exercices_financiers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO exercices_financiers (lycee_id, libelle, date_debut, date_fin, est_actif, type_exercice)
            VALUES (:lycee_id, :libelle, :date_debut, :date_fin, :est_actif, :type_exercice)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'libelle' => $data['libelle'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'est_actif' => !empty($data['est_actif']) ? 1 : 0,
            'type_exercice' => $data['type_exercice'] ?? 'normal'
        ]);
        return $db->lastInsertId();
    }
}
?>