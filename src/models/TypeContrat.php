<?php

require_once __DIR__ . '/../config/database.php';

class TypeContrat {

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        // Find global contract types (lycee_id IS NULL) and types for the specific lycee
        $sql = "SELECT * FROM type_contrat WHERE lycee_id IS NULL";
        if ($lycee_id !== null) {
            $sql .= " OR lycee_id = :lycee_id";
        }
        $sql .= " ORDER BY libelle ASC";

        $stmt = $db->prepare($sql);
        if ($lycee_id !== null) {
            $stmt->execute(['lycee_id' => $lycee_id]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM type_contrat WHERE id_contrat = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_contrat']);

        $sql = $isUpdate
            ? "UPDATE type_contrat SET libelle = :libelle, description = :description, type_paiement = :type_paiement, prise_en_charge = :prise_en_charge, lycee_id = :lycee_id WHERE id_contrat = :id_contrat"
            : "INSERT INTO type_contrat (libelle, description, type_paiement, prise_en_charge, lycee_id) VALUES (:libelle, :description, :type_paiement, :prise_en_charge, :lycee_id)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'libelle' => $data['libelle'],
            'description' => $data['description'] ?? null,
            'type_paiement' => $data['type_paiement'] ?? 'fixe',
            'prise_en_charge' => $data['prise_en_charge'] ?? 'Ecole',
            'lycee_id' => $data['lycee_id'] ?? null,
        ];

        if ($isUpdate) {
            $params['id_contrat'] = $data['id_contrat'];
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM type_contrat WHERE id_contrat = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
