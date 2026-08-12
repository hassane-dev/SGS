<?php
// src/models/AchatReception.php

require_once __DIR__ . '/../config/database.php';

class AchatReception {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_receptions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT r.*, c.numero_commande, f.raison_sociale as fournisseur_nom,
                   u.nom as receptionne_par_nom, u.prenom as receptionne_par_prenom
            FROM achat_receptions r
            INNER JOIN achat_commandes c ON r.commande_id = c.id
            INNER JOIN fournisseurs f ON c.fournisseur_id = f.id
            INNER JOIN utilisateurs u ON r.receptionne_par = u.id_user
            WHERE r.lycee_id = :lycee_id
            ORDER BY r.date_reception DESC, r.id DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_receptions (lycee_id, commande_id, numero_reception, date_reception, statut, receptionne_par, details)
            VALUES (:lycee_id, :commande_id, :numero_reception, :date_reception, :statut, :receptionne_par, :details)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'commande_id' => $data['commande_id'],
            'numero_reception' => $data['numero_reception'],
            'date_reception' => $data['date_reception'] ?? date('Y-m-d'),
            'statut' => $data['statut'] ?? 'brouillon',
            'receptionne_par' => $data['receptionne_par'],
            'details' => $data['details'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function updateStatus($id, $statut) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE achat_receptions SET statut = :statut WHERE id = :id");
        return $stmt->execute([
            'statut' => $statut,
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $current = self::findById($id);
        if (!$current) {
            return false;
        }

        // Only brouillons can be deleted physically
        if ($current['statut'] !== 'brouillon') {
            throw new Exception("Seuls les bons de réception en brouillon peuvent être physiquement supprimés.");
        }

        $stmt = $db->prepare("DELETE FROM achat_receptions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>