<?php
// src/models/AchatReceptionLigne.php

require_once __DIR__ . '/../config/database.php';

class AchatReceptionLigne {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_reception_lignes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByReception($receptionId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT rl.*, a.libelle as article_libelle, a.reference as article_ref, a.unite_mesure,
                   cl.quantite_commandee, cl.prix_unitaire_negocie
            FROM achat_reception_lignes rl
            INNER JOIN achat_commande_lignes cl ON rl.commande_ligne_id = cl.id
            INNER JOIN achat_articles a ON cl.article_id = a.id
            WHERE rl.reception_id = :reception_id
            ORDER BY rl.id ASC
        ");
        $stmt->execute(['reception_id' => $receptionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_reception_lignes (reception_id, commande_ligne_id, quantite_receptionnee, quantite_refusee, motif_refus)
            VALUES (:reception_id, :commande_ligne_id, :quantite_receptionnee, :quantite_refusee, :motif_refus)
        ");
        $stmt->execute([
            'reception_id' => $data['reception_id'],
            'commande_ligne_id' => $data['commande_ligne_id'],
            'quantite_receptionnee' => $data['quantite_receptionnee'],
            'quantite_refusee' => $data['quantite_refusee'] ?? 0.0000,
            'motif_refus' => $data['motif_refus'] ?? null
        ]);
        return $db->lastInsertId();
    }
}
?>