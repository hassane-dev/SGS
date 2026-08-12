<?php
// src/models/AchatCommandeLigne.php

require_once __DIR__ . '/../config/database.php';

class AchatCommandeLigne {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_commande_lignes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByCommande($commandeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT cl.*, a.libelle as article_libelle, a.reference as article_ref, a.unite_mesure
            FROM achat_commande_lignes cl
            INNER JOIN achat_articles a ON cl.article_id = a.id
            WHERE cl.commande_id = :commande_id
            ORDER BY cl.id ASC
        ");
        $stmt->execute(['commande_id' => $commandeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_commande_lignes (commande_id, demande_ligne_id, article_id, quantite_commandee, prix_unitaire_negocie)
            VALUES (:commande_id, :demande_ligne_id, :article_id, :quantite_commandee, :prix_unitaire_negocie)
        ");
        $stmt->execute([
            'commande_id' => $data['commande_id'],
            'demande_ligne_id' => $data['demande_ligne_id'] ?? null,
            'article_id' => $data['article_id'],
            'quantite_commandee' => $data['quantite_commandee'],
            'prix_unitaire_negocie' => $data['prix_unitaire_negocie']
        ]);
        return $db->lastInsertId();
    }
}
?>