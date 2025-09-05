<?php

require_once __DIR__ . '/../config/database.php';

class Licence {

    public static function findAll() {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT l.*, ly.nom_lycee
            FROM licences l
            JOIN lycees ly ON l.lycee_id = ly.id_lycee
            ORDER BY l.date_fin DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM licences WHERE id_licence = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_licence']);

        // Calculate date_fin based on date_debut and duree_mois
        $date_debut = new DateTime($data['date_debut']);
        $date_fin = clone $date_debut;
        $date_fin->add(new DateInterval("P{$data['duree_mois']}M"));

        $sql = $isUpdate
            ? "UPDATE licences SET lycee_id = :lycee_id, duree_mois = :duree_mois, date_debut = :date_debut, date_fin = :date_fin, actif = :actif WHERE id_licence = :id_licence"
            : "INSERT INTO licences (lycee_id, duree_mois, date_debut, date_fin, actif) VALUES (:lycee_id, :duree_mois, :date_debut, :date_fin, :actif)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'lycee_id' => $data['lycee_id'],
            'duree_mois' => $data['duree_mois'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $date_fin->format('Y-m-d'),
            'actif' => $data['actif'] ?? 0,
        ];

        if ($isUpdate) {
            $params['id_licence'] = $data['id_licence'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM licences WHERE id_licence = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
