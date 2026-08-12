<?php
// src/models/AchatAvoirFournisseur.php

require_once __DIR__ . '/../config/database.php';

class AchatAvoirFournisseur {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_avoirs_fournisseurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT a.*, fo.raison_sociale as fournisseur_nom, f.reference_facture
            FROM achat_avoirs_fournisseurs a
            INNER JOIN fournisseurs fo ON a.fournisseur_id = fo.id
            INNER JOIN achat_factures f ON a.facture_id = f.id
            WHERE a.lycee_id = :lycee_id
            ORDER BY a.date_avoir DESC, a.id DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_avoirs_fournisseurs (lycee_id, fournisseur_id, facture_id, reference_avoir, date_avoir, montant_ht, montant_ttc, statut, piece_comptable_id)
            VALUES (:lycee_id, :fournisseur_id, :facture_id, :reference_avoir, :date_avoir, :montant_ht, :montant_ttc, :statut, :piece_comptable_id)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'fournisseur_id' => $data['fournisseur_id'],
            'facture_id' => $data['facture_id'],
            'reference_avoir' => $data['reference_avoir'],
            'date_avoir' => $data['date_avoir'],
            'montant_ht' => $data['montant_ht'],
            'montant_ttc' => $data['montant_ttc'],
            'statut' => $data['statut'] ?? 'enregistre',
            'piece_comptable_id' => $data['piece_comptable_id'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function updateStatus($id, $statut) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE achat_avoirs_fournisseurs SET statut = :statut WHERE id = :id");
        return $stmt->execute([
            'statut' => $statut,
            'id' => $id
        ]);
    }

    public static function updatePieceComptable($id, $pieceComptableId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE achat_avoirs_fournisseurs SET piece_comptable_id = :piece_comptable_id WHERE id = :id");
        return $stmt->execute([
            'piece_comptable_id' => $pieceComptableId,
            'id' => $id
        ]);
    }

    public static function delete($id) {
        throw new Exception("Un avoir fournisseur validé ou enregistré ne peut jamais être physiquement supprimé de la base.");
    }
}
?>