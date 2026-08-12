<?php
// src/models/AchatDemandeLigne.php

require_once __DIR__ . '/../config/database.php';

class AchatDemandeLigne {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_demande_lignes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByDemande($demandeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT dl.*, a.libelle as article_libelle, a.reference as article_ref, a.unite_mesure
            FROM achat_demande_lignes dl
            INNER JOIN achat_articles a ON dl.article_id = a.id
            WHERE dl.demande_id = :demande_id
            ORDER BY dl.id ASC
        ");
        $stmt->execute(['demande_id' => $demandeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_demande_lignes (demande_id, article_id, quantite_demandee, prix_unitaire_estime, budget_ligne_id)
            VALUES (:demande_id, :article_id, :quantite_demandee, :prix_unitaire_estime, :budget_ligne_id)
        ");
        $stmt->execute([
            'demande_id' => $data['demande_id'],
            'article_id' => $data['article_id'],
            'quantite_demandee' => $data['quantite_demandee'],
            'prix_unitaire_estime' => $data['prix_unitaire_estime'],
            'budget_ligne_id' => $data['budget_ligne_id'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function deleteByDemande($demandeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM achat_demande_lignes WHERE demande_id = :demande_id");
        return $stmt->execute(['demande_id' => $demandeId]);
    }
}
?>