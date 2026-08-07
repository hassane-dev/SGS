<?php
// src/services/JournalService.php

require_once __DIR__ . '/../config/database.php';

class JournalService {

    /**
     * Récupère toutes les pièces comptables filtrées
     */
    public static function getJournalEntries($lyceeId, $filters = []) {
        $db = Database::getInstance();

        $sql = "
            SELECT p.*, j.code as journal_code, j.libelle as journal_libelle, u.nom as user_nom, u.prenom as user_prenom
            FROM pieces_comptables p
            JOIN journaux_comptables j ON p.journal_id = j.id
            JOIN utilisateurs u ON p.cree_par = u.id_user
            WHERE p.lycee_id = :lycee_id
        ";

        $params = ['lycee_id' => $lyceeId];

        if (!empty($filters['journal_id'])) {
            $sql .= " AND p.journal_id = :journal_id";
            $params['journal_id'] = $filters['journal_id'];
        }
        if (!empty($filters['exercice_id'])) {
            $sql .= " AND p.exercice_financier_id = :exercice_id";
            $params['exercice_id'] = $filters['exercice_id'];
        }
        if (!empty($filters['date_debut'])) {
            $sql .= " AND p.date_piece >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND p.date_piece <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }
        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut = :statut";
            $params['statut'] = $filters['statut'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (p.numero_piece LIKE :s OR p.libelle_piece LIKE :s)";
            $params['s'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY p.date_piece DESC, p.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch entries lines for each piece
        foreach ($pieces as &$p) {
            $stmt_l = $db->prepare("
                SELECT e.*, c.numero as compte_numero, c.libelle as compte_libelle
                FROM ecritures_comptables e
                JOIN comptes_comptables c ON e.compte_comptable_id = c.id
                WHERE e.piece_comptable_id = :piece_id
            ");
            $stmt_l->execute(['piece_id' => $p['id']]);
            $p['lignes'] = $stmt_l->fetchAll(PDO::FETCH_ASSOC);
        }

        return $pieces;
    }
}
