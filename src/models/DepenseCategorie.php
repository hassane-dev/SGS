<?php
// src/models/DepenseCategorie.php

require_once __DIR__ . '/../config/database.php';

class DepenseCategorie {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_categories WHERE lycee_id = :lycee_id ORDER BY nom_categorie ASC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActiveByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM depense_categories WHERE lycee_id = :lycee_id AND statut = 'actif' ORDER BY nom_categorie ASC");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO depense_categories (lycee_id, nom_categorie, modifiable, statut, description)
            VALUES (:lycee_id, :nom_categorie, :modifiable, :statut, :description)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'nom_categorie' => $data['nom_categorie'],
            'modifiable' => isset($data['modifiable']) ? (int)$data['modifiable'] : 1,
            'statut' => $data['statut'] ?? 'actif',
            'description' => $data['description'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = Database::getInstance();

        $current = self::findById($id);
        if (!$current) {
            throw new Exception("Catégorie introuvable.");
        }

        // If not modifiable, check if trying to edit name
        if (!$current['modifiable'] && isset($data['nom_categorie']) && $data['nom_categorie'] !== $current['nom_categorie']) {
            throw new Exception("Cette catégorie est immuable et ne peut pas être renommée.");
        }

        $stmt = $db->prepare("
            UPDATE depense_categories
            SET nom_categorie = :nom_categorie,
                statut = :statut,
                description = :description
            WHERE id = :id
        ");
        return $stmt->execute([
            'nom_categorie' => $data['nom_categorie'] ?? $current['nom_categorie'],
            'statut' => $data['statut'] ?? $current['statut'],
            'description' => $data['description'] ?? $current['description'],
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            return false;
        }
        if (!$current['modifiable']) {
            throw new Exception("Cette catégorie est immuable et ne peut pas être supprimée.");
        }

        // Check if there are expenses using this category
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM depenses WHERE categorie_id = :id");
        $stmt_check->execute(['id' => $id]);
        if ($stmt_check->fetchColumn() > 0) {
            // Cannot physically delete if used. Toggle status to inactif
            $stmt_toggle = $db->prepare("UPDATE depense_categories SET statut = 'inactif' WHERE id = :id");
            return $stmt_toggle->execute(['id' => $id]);
        }

        $stmt = $db->prepare("DELETE FROM depense_categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>