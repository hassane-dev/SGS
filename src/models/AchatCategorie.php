<?php
// src/models/AchatCategorie.php

require_once __DIR__ . '/../config/database.php';

class AchatCategorie {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findAll() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM achat_categories ORDER BY libelle ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActive() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM achat_categories WHERE actif = 1 ORDER BY libelle ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_categories (libelle, compte_comptable_charge, actif)
            VALUES (:libelle, :compte_comptable_charge, :actif)
        ");
        $stmt->execute([
            'libelle' => $data['libelle'],
            'compte_comptable_charge' => $data['compte_comptable_charge'],
            'actif' => isset($data['actif']) ? (int)$data['actif'] : 1
        ]);
        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            throw new Exception("Catégorie introuvable.");
        }

        $stmt = $db->prepare("
            UPDATE achat_categories
            SET libelle = :libelle,
                compte_comptable_charge = :compte_comptable_charge,
                actif = :actif
            WHERE id = :id
        ");
        return $stmt->execute([
            'libelle' => $data['libelle'] ?? $current['libelle'],
            'compte_comptable_charge' => $data['compte_comptable_charge'] ?? $current['compte_comptable_charge'],
            'actif' => isset($data['actif']) ? (int)$data['actif'] : $current['actif'],
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();

        // Enforce physical deletion block if articles exist
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM achat_articles WHERE categorie_id = :id");
        $stmt_check->execute(['id' => $id]);
        if ($stmt_check->fetchColumn() > 0) {
            // Cannot physically delete; toggle actif to 0
            $stmt_toggle = $db->prepare("UPDATE achat_categories SET actif = 0 WHERE id = :id");
            return $stmt_toggle->execute(['id' => $id]);
        }

        $stmt = $db->prepare("DELETE FROM achat_categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>