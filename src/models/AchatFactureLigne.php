<?php
// src/models/AchatFactureLigne.php

require_once __DIR__ . '/../config/database.php';

class AchatFactureLigne {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_facture_lignes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByFacture($factureId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT fl.*, a.libelle as article_libelle, a.reference as article_ref, a.unite_mesure,
                   c.compte_comptable_charge
            FROM achat_facture_lignes fl
            INNER JOIN achat_reception_lignes rl ON fl.reception_ligne_id = rl.id
            INNER JOIN achat_commande_lignes cl ON rl.commande_ligne_id = cl.id
            INNER JOIN achat_articles a ON cl.article_id = a.id
            INNER JOIN achat_categories c ON a.categorie_id = c.id
            WHERE fl.facture_id = :facture_id
            ORDER BY fl.id ASC
        ");
        $stmt->execute(['facture_id' => $factureId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_facture_lignes (facture_id, reception_ligne_id, quantite_facturee, prix_unitaire_facture, taux_tva_facture, montant_ht_ligne, montant_ttc_ligne)
            VALUES (:facture_id, :reception_ligne_id, :quantite_facturee, :prix_unitaire_facture, :taux_tva_facture, :montant_ht_ligne, :montant_ttc_ligne)
        ");
        $stmt->execute([
            'facture_id' => $data['facture_id'],
            'reception_ligne_id' => $data['reception_ligne_id'],
            'quantite_facturee' => $data['quantite_facturee'],
            'prix_unitaire_facture' => $data['prix_unitaire_facture'],
            'taux_tva_facture' => $data['taux_tva_facture'] ?? 0.0000,
            'montant_ht_ligne' => $data['montant_ht_ligne'],
            'montant_ttc_ligne' => $data['montant_ttc_ligne']
        ]);
        return $db->lastInsertId();
    }
}
?>