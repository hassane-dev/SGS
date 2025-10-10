<?php

require_once __DIR__ . '/../config/database.php';

class AnneeAcademique {

    public static function findAll() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM annees_academiques ORDER BY date_debut DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM annees_academiques WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findActive() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['id']);
        $db = Database::getInstance();

        if ($isUpdate) {
            $sql = "UPDATE annees_academiques SET libelle = :libelle, date_debut = :date_debut, date_fin = :date_fin WHERE id = :id";
        } else {
            $sql = "INSERT INTO annees_academiques (libelle, date_debut, date_fin) VALUES (:libelle, :date_debut, :date_fin)";
        }

        $stmt = $db->prepare($sql);
        $params = [
            'libelle' => $data['libelle'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin']
        ];

        if ($isUpdate) {
            $params['id'] = $data['id'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM annees_academiques WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public static function setActive($id) {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Deactivate all other years
            $db->query("UPDATE annees_academiques SET est_active = 0");
            // Activate the selected year
            $stmt = $db->prepare("UPDATE annees_academiques SET est_active = 1 WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
}
?>