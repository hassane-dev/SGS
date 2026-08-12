<?php
// src/models/AchatAvoirFournisseurLigne.php

require_once __DIR__ . '/../config/database.php';

class AchatAvoirFournisseurLigne {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_avoir_fournisseur_lignes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByAvoir($avoirId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT al.*, a.libelle as article_libelle, a.reference as article_ref
            FROM achat_avoir_fournisseur_lignes al
            INNER JOIN achat_facture_lignes fl ON al.facture_ligne_id = fl.id
            INNER JOIN achat_reception_lignes rl ON fl.reception_ligne_id = rl.id
            INNER JOIN achat_commande_lignes cl ON rl.commande_ligne_id = cl.id
            INNER JOIN achat_articles a ON cl.article_id = a.id
            WHERE al.avoir_id = :avoir_id
            ORDER BY al.id ASC
        ");
        $stmt->execute(['avoir_id' => $avoirId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_avoir_fournisseur_lignes (avoir_id, facture_ligne_id, quantite_avoir, prix_unitaire_avoir, montant_ht_ligne, montant_ttc_ligne)
            VALUES (:avoir_id, :facture_ligne_id, :quantite_avoir, :prix_unitaire_avoir, :montant_ht_ligne, :montant_ttc_ligne)
        ");
        $stmt->execute([
            'avoir_id' => $data['avoir_id'],
            'facture_ligne_id' => $data['facture_ligne_id'],
            'quantite_avoir' => $data['quantite_avoir'],
            'prix_unitaire_avoir' => $data['prix_unitaire_avoir'],
            'montant_ht_ligne' => $data['montant_ht_ligne'],
            'montant_ttc_ligne' => $data['montant_ttc_ligne']
        ]);
        return $db->lastInsertId();
    }
}
?>