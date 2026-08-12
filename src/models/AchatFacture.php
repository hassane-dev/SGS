<?php
// src/models/AchatFacture.php

require_once __DIR__ . '/../config/database.php';

class AchatFacture {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_factures WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT f.*, fo.raison_sociale as fournisseur_nom,
                   c.numero_commande, r.numero_reception
            FROM achat_factures f
            INNER JOIN fournisseurs fo ON f.fournisseur_id = fo.id
            LEFT JOIN achat_commandes c ON f.commande_id = c.id
            LEFT JOIN achat_receptions r ON f.reception_id = r.id
            WHERE f.lycee_id = :lycee_id
            ORDER BY f.date_facture DESC, f.id DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_factures (lycee_id, fournisseur_id, commande_id, reception_id, piece_comptable_id, reference_facture, date_facture, date_echeance, montant_ht, montant_ttc, statut)
            VALUES (:lycee_id, :fournisseur_id, :commande_id, :reception_id, :piece_comptable_id, :reference_facture, :date_facture, :date_echeance, :montant_ht, :montant_ttc, :statut)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'fournisseur_id' => $data['fournisseur_id'],
            'commande_id' => $data['commande_id'] ?? null,
            'reception_id' => $data['reception_id'] ?? null,
            'piece_comptable_id' => $data['piece_comptable_id'] ?? null,
            'reference_facture' => $data['reference_facture'],
            'date_facture' => $data['date_facture'],
            'date_echeance' => $data['date_echeance'],
            'montant_ht' => $data['montant_ht'],
            'montant_ttc' => $data['montant_ttc'],
            'statut' => $data['statut'] ?? 'enregistree'
        ]);
        return $db->lastInsertId();
    }

    public static function updateStatus($id, $statut) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE achat_factures SET statut = :statut WHERE id = :id");
        return $stmt->execute([
            'statut' => $statut,
            'id' => $id
        ]);
    }

    public static function updatePieceComptable($id, $pieceComptableId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE achat_factures SET piece_comptable_id = :piece_comptable_id WHERE id = :id");
        return $stmt->execute([
            'piece_comptable_id' => $pieceComptableId,
            'id' => $id
        ]);
    }

    /**
     * Compute remaining balance (Reste à payer) dynamically
     * Reste à Payer = Montant TTC - (Règlements validés + Avoirs appliqués)
     */
    public static function getResteAPayer($id) {
        $db = Database::getInstance();
        $facture = self::findById($id);
        if (!$facture) {
            return 0.00;
        }

        // Sum regulations
        $stmt_reg = $db->prepare("SELECT SUM(montant_alloue) FROM achat_facture_reglements WHERE facture_id = :id");
        $stmt_reg->execute(['id' => $id]);
        $totalReglements = (float)$stmt_reg->fetchColumn();

        // Sum credit notes (avoirs)
        $stmt_av = $db->prepare("SELECT SUM(montant_ttc) FROM achat_avoirs_fournisseurs WHERE facture_id = :id AND statut = 'valide'");
        $stmt_av->execute(['id' => $id]);
        $totalAvoirs = (float)$stmt_av->fetchColumn();

        $reste = (float)$facture['montant_ttc'] - ($totalReglements + $totalAvoirs);
        return max(0.00, round($reste, 2));
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            return false;
        }

        // Financial records can never be physically deleted once they are locked/registered
        throw new Exception("Une facture fournisseur comptabilisée ne peut jamais être physiquement supprimée. Seule son annulation ou l'imputation d'un avoir est autorisée.");
    }
}
?>