<?php
// src/models/AchatArticle.php

require_once __DIR__ . '/../config/database.php';

class AchatArticle {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findAll() {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT a.*, c.libelle as nom_categorie, c.compte_comptable_charge
            FROM achat_articles a
            INNER JOIN achat_categories c ON a.categorie_id = c.id
            ORDER BY a.libelle ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActive() {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT a.*, c.libelle as nom_categorie, c.compte_comptable_charge
            FROM achat_articles a
            INNER JOIN achat_categories c ON a.categorie_id = c.id
            WHERE a.actif = 1 AND c.actif = 1
            ORDER BY a.libelle ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_articles (categorie_id, libelle, reference, unite_mesure, prix_unitaire_estime, is_service, actif)
            VALUES (:categorie_id, :libelle, :reference, :unite_mesure, :prix_unitaire_estime, :is_service, :actif)
        ");
        $stmt->execute([
            'categorie_id' => $data['categorie_id'],
            'libelle' => $data['libelle'],
            'reference' => $data['reference'],
            'unite_mesure' => $data['unite_mesure'],
            'prix_unitaire_estime' => $data['prix_unitaire_estime'] ?? 0.0000,
            'is_service' => isset($data['is_service']) ? (int)$data['is_service'] : 0,
            'actif' => isset($data['actif']) ? (int)$data['actif'] : 1
        ]);
        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            throw new Exception("Article introuvable.");
        }

        $stmt = $db->prepare("
            UPDATE achat_articles
            SET categorie_id = :categorie_id,
                libelle = :libelle,
                unite_mesure = :unite_mesure,
                prix_unitaire_estime = :prix_unitaire_estime,
                is_service = :is_service,
                actif = :actif
            WHERE id = :id
        ");
        return $stmt->execute([
            'categorie_id' => $data['categorie_id'] ?? $current['categorie_id'],
            'libelle' => $data['libelle'] ?? $current['libelle'],
            'unite_mesure' => $data['unite_mesure'] ?? $current['unite_mesure'],
            'prix_unitaire_estime' => $data['prix_unitaire_estime'] ?? $current['prix_unitaire_estime'],
            'is_service' => isset($data['is_service']) ? (int)$data['is_service'] : $current['is_service'],
            'actif' => isset($data['actif']) ? (int)$data['actif'] : $current['actif'],
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();

        // Check if referenced in requests
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM achat_demande_lignes WHERE article_id = :id");
        $stmt_check->execute(['id' => $id]);
        if ($stmt_check->fetchColumn() > 0) {
            // Cannot delete physically; toggle active to 0
            $stmt_toggle = $db->prepare("UPDATE achat_articles SET actif = 0 WHERE id = :id");
            return $stmt_toggle->execute(['id' => $id]);
        }

        $stmt = $db->prepare("DELETE FROM achat_articles WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>