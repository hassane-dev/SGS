<?php
// src/services/GrandLivreService.php

require_once __DIR__ . '/../config/database.php';

class GrandLivreService {

    /**
     * Récupère l'historique et le solde progressif d'un compte comptable
     */
    public static function getAccountDetails($compteId, $dateDebut = null, $dateFin = null) {
        $db = Database::getInstance();

        // 1. Fetch account details
        $stmt_c = $db->prepare("SELECT * FROM comptes_comptables WHERE id = :id");
        $stmt_c->execute(['id' => $compteId]);
        $account = $stmt_c->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            return null;
        }

        // 2. Compute opening balance before dateDebut if specified
        $solde_initial = 0.00;
        if ($dateDebut) {
            $stmt_init = $db->prepare("
                SELECT SUM(e.debit) as total_debit, SUM(e.credit) as total_credit
                FROM ecritures_comptables e
                JOIN pieces_comptables p ON e.piece_comptable_id = p.id
                WHERE e.compte_comptable_id = :compte_id
                AND p.date_piece < :date_debut
                AND p.statut <> 'brouillon'
            ");
            $stmt_init->execute([
                'compte_id' => $compteId,
                'date_debut' => $dateDebut
            ]);
            $init = $stmt_init->fetch(PDO::FETCH_ASSOC);
            $db_val = (float)($init['total_debit'] ?? 0.00);
            $cr_val = (float)($init['total_credit'] ?? 0.00);

            if ($account['nature'] === 'actif' || $account['nature'] === 'charge') {
                $solde_initial = $db_val - $cr_val;
            } else {
                $solde_initial = $cr_val - $db_val;
            }
        }

        // 3. Fetch entries within date range
        $sql = "
            SELECT e.*, p.numero_piece, p.date_piece, p.libelle_piece, j.code as journal_code
            FROM ecritures_comptables e
            JOIN pieces_comptables p ON e.piece_comptable_id = p.id
            JOIN journaux_comptables j ON p.journal_id = j.id
            WHERE e.compte_comptable_id = :compte_id
            AND p.statut <> 'brouillon'
        ";

        $params = ['compte_id' => $compteId];

        if ($dateDebut) {
            $sql .= " AND p.date_piece >= :date_debut";
            $params['date_debut'] = $dateDebut;
        }
        if ($dateFin) {
            $sql .= " AND p.date_piece <= :date_fin";
            $params['date_fin'] = $dateFin;
        }

        $sql .= " ORDER BY p.date_piece ASC, p.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Calculate cumulative balance
        $current_solde = $solde_initial;
        foreach ($entries as &$entry) {
            $deb = (float)$entry['debit'];
            $cred = (float)$entry['credit'];

            if ($account['nature'] === 'actif' || $account['nature'] === 'charge') {
                $current_solde += ($deb - $cred);
            } else {
                $current_solde += ($cred - $deb);
            }
            $entry['solde_progressif'] = $current_solde;
        }

        return [
            'compte' => $account,
            'solde_initial' => $solde_initial,
            'ecritures' => $entries,
            'solde_final' => $current_solde
        ];
    }
}
