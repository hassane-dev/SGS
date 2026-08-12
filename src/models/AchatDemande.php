<?php
// src/models/AchatDemande.php

require_once __DIR__ . '/../config/database.php';

class AchatDemande {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM achat_demandes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT d.*, u.nom as demandeur_nom, u.prenom as demandeur_prenom,
                   a.nom as approbateur_nom, a.prenom as approbateur_prenom
            FROM achat_demandes d
            INNER JOIN utilisateurs u ON d.demandeur_id = u.id_user
            LEFT JOIN utilisateurs a ON d.approuve_par = a.id_user
            WHERE d.lycee_id = :lycee_id
            ORDER BY d.date_demande DESC, d.id DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO achat_demandes (lycee_id, demandeur_id, justification, date_demande, statut)
            VALUES (:lycee_id, :demandeur_id, :justification, :date_demande, :statut)
        ");
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'demandeur_id' => $data['demandeur_id'],
            'justification' => $data['justification'],
            'date_demande' => $data['date_demande'] ?? date('Y-m-d'),
            'statut' => $data['statut'] ?? 'brouillon'
        ]);
        return $db->lastInsertId();
    }

    public static function updateStatus($id, $statut, $approuvePar = null, $motif = null) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE achat_demandes
            SET statut = :statut,
                approuve_par = :approuve_par,
                date_approbation = :date_approbation,
                motif_statut = :motif_statut
            WHERE id = :id
        ");
        return $stmt->execute([
            'statut' => $statut,
            'approuve_par' => $approuvePar,
            'date_approbation' => $approuvePar ? date('Y-m-d H:i:s') : null,
            'motif_statut' => $motif,
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
            throw new Exception("Seules les demandes d'achat en brouillon peuvent être physiquement supprimées.");
        }

        $stmt = $db->prepare("DELETE FROM achat_demandes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>