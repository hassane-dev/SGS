<?php
// src/models/AchatCommande.php

require_once __DIR__ . '/../config/database.php';

class AchatCommande {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_commandes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, f.raison_sociale as fournisseur_nom,
                   u.nom as cree_par_nom, u.prenom as cree_par_prenom,
                   v.nom as valide_par_nom, v.prenom as valide_par_prenom
            FROM achat_commandes c
            INNER JOIN fournisseurs f ON c.fournisseur_id = f.id
            INNER JOIN utilisateurs u ON c.cree_par = u.id_user
            LEFT JOIN utilisateurs v ON c.valide_par = v.id_user
            WHERE c.lycee_id = :lycee_id
            ORDER BY c.date_commande DESC, c.id DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_commandes (lycee_id, demande_id, fournisseur_id, numero_commande, date_commande, statut, cree_par)
            VALUES (:lycee_id, :demande_id, :fournisseur_id, :numero_commande, :date_commande, :statut, :cree_par)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'demande_id' => $data['demande_id'] ?? null,
            'fournisseur_id' => $data['fournisseur_id'],
            'numero_commande' => $data['numero_commande'],
            'date_commande' => $data['date_commande'] ?? date('Y-m-d'),
            'statut' => $data['statut'] ?? 'brouillon',
            'cree_par' => $data['cree_par']
        ]);
        return $db->lastInsertId();
    }

    public static function updateStatus($id, $statut, $validePar = null) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE achat_commandes
            SET statut = :statut,
                valide_par = :valide_par
            WHERE id = :id
        ");
        return $stmt->execute([
            'statut' => $statut,
            'valide_par' => $validePar,
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            return false;
        }

        // Only brouillons can be physically deleted
        if ($current['statut'] !== 'brouillon') {
            throw new Exception("Seuls les bons de commande en brouillon peuvent être physiquement supprimés.");
        }

        $stmt = $db->prepare("DELETE FROM achat_commandes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>