<?php
// src/models/AchatFactureReglement.php

require_once __DIR__ . '/../config/database.php';

class AchatFactureReglement {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_facture_reglements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByFacture($factureId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT fr.*, mt.date_mouvement, mt.mode_paiement, mt.reference_transaction, mt.motif
            FROM achat_facture_reglements fr
            INNER JOIN mouvements_tresorerie mt ON fr.mouvement_tresorerie_id = mt.id
            WHERE fr.facture_id = :facture_id
            ORDER BY fr.id ASC
        ");
        $stmt->execute(['facture_id' => $factureId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_facture_reglements (facture_id, mouvement_tresorerie_id, montant_alloue, idempotency_key)
            VALUES (:facture_id, :mouvement_tresorerie_id, :montant_alloue, :idempotency_key)
        ");
        $stmt->execute([
            'facture_id' => $data['facture_id'],
            'mouvement_tresorerie_id' => $data['mouvement_tresorerie_id'],
            'montant_alloue' => $data['montant_alloue'],
            'idempotency_key' => $data['idempotency_key']
        ]);
        return $db->lastInsertId();
    }
}
?>