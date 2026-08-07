<?php
// src/services/BalanceService.php

require_once __DIR__ . '/../config/database.php';

class BalanceService {

    /**
     * Calcule la balance des comptes en 6 colonnes
     */
    public static function getBalanceData($lyceeId, $dateDebut = null, $dateFin = null) {
        $db = Database::getInstance();

        // 1. Fetch all active accounts
        $stmt_c = $db->query("SELECT * FROM comptes_comptables WHERE actif = 1 ORDER BY numero ASC");
        $comptes = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

        $balance = [];
        $total_debit_init = 0.00;
        $total_credit_init = 0.00;
        $total_debit_mvt = 0.00;
        $total_credit_mvt = 0.00;
        $total_debit_fin = 0.00;
        $total_credit_fin = 0.00;

        foreach ($comptes as $c) {
            $compteId = $c['id'];

            // a. Solde Initial (Mouvements avant dateDebut)
            $db_init = 0.00;
            $cr_init = 0.00;
            if ($dateDebut) {
                $stmt_init = $db->prepare("
                    SELECT SUM(e.debit) as total_debit, SUM(e.credit) as total_credit
                    FROM ecritures_comptables e
                    JOIN pieces_comptables p ON e.piece_comptable_id = p.id
                    WHERE e.compte_comptable_id = :compte_id
                    AND p.lycee_id = :lycee_id
                    AND p.date_piece < :date_debut
                    AND p.statut <> 'brouillon'
                ");
                $stmt_init->execute([
                    'compte_id' => $compteId,
                    'lycee_id' => $lyceeId,
                    'date_debut' => $dateDebut
                ]);
                $res = $stmt_init->fetch(PDO::FETCH_ASSOC);
                $db_init = (float)($res['total_debit'] ?? 0.00);
                $cr_init = (float)($res['total_credit'] ?? 0.00);
            }

            // Convert to debit/credit opening balances depending on account nature
            $solde_init_debit = 0.00;
            $solde_init_credit = 0.00;
            if ($c['nature'] === 'actif' || $c['nature'] === 'charge') {
                $diff = $db_init - $cr_init;
                if ($diff > 0) $solde_init_debit = $diff;
                else $solde_init_credit = abs($diff);
            } else {
                $diff = $cr_init - $db_init;
                if ($diff > 0) $solde_init_credit = $diff;
                else $solde_init_debit = abs($diff);
            }

            // b. Mouvements sur la période
            $sql_mvt = "
                SELECT SUM(e.debit) as total_debit, SUM(e.credit) as total_credit
                FROM ecritures_comptables e
                JOIN pieces_comptables p ON e.piece_comptable_id = p.id
                WHERE e.compte_comptable_id = :compte_id
                AND p.lycee_id = :lycee_id
                AND p.statut <> 'brouillon'
            ";
            $params = ['compte_id' => $compteId, 'lycee_id' => $lyceeId];

            if ($dateDebut) {
                $sql_mvt .= " AND p.date_piece >= :date_debut";
                $params['date_debut'] = $dateDebut;
            }
            if ($dateFin) {
                $sql_mvt .= " AND p.date_piece <= :date_fin";
                $params['date_fin'] = $dateFin;
            }

            $stmt_mvt = $db->prepare($sql_mvt);
            $stmt_mvt->execute($params);
            $res_mvt = $stmt_mvt->fetch(PDO::FETCH_ASSOC);
            $mvt_debit = (float)($res_mvt['total_debit'] ?? 0.00);
            $mvt_credit = (float)($res_mvt['total_credit'] ?? 0.00);

            // Skip accounts with zero movements and zero starting balance
            if ($solde_init_debit == 0 && $solde_init_credit == 0 && $mvt_debit == 0 && $mvt_credit == 0) {
                continue;
            }

            // c. Solde Final Cumulé
            $total_deb_cumul = $db_init + $mvt_debit;
            $total_cred_cumul = $cr_init + $mvt_credit;

            $solde_fin_debit = 0.00;
            $solde_fin_credit = 0.00;

            if ($c['nature'] === 'actif' || $c['nature'] === 'charge') {
                $diff = $total_deb_cumul - $total_cred_cumul;
                if ($diff > 0) $solde_fin_debit = $diff;
                else $solde_fin_credit = abs($diff);
            } else {
                $diff = $total_cred_cumul - $total_deb_cumul;
                if ($diff > 0) $solde_fin_credit = $diff;
                else $solde_fin_debit = abs($diff);
            }

            $balance[] = [
                'compte_id' => $c['id'],
                'numero' => $c['numero'],
                'libelle' => $c['libelle'],
                'nature' => $c['nature'],
                'solde_initial_debit' => $solde_init_debit,
                'solde_initial_credit' => $solde_init_credit,
                'mouvement_debit' => $mvt_debit,
                'mouvement_credit' => $mvt_credit,
                'solde_final_debit' => $solde_fin_debit,
                'solde_final_credit' => $solde_fin_credit
            ];

            // Aggregates
            $total_debit_init += $solde_init_debit;
            $total_credit_init += $solde_init_credit;
            $total_debit_mvt += $mvt_debit;
            $total_credit_mvt += $mvt_credit;
            $total_debit_fin += $solde_fin_debit;
            $total_credit_fin += $solde_fin_credit;
        }

        return [
            'lignes' => $balance,
            'totaux' => [
                'initial_debit' => $total_debit_init,
                'initial_credit' => $total_credit_init,
                'mouvement_debit' => $total_debit_mvt,
                'mouvement_credit' => $total_credit_mvt,
                'final_debit' => $total_debit_fin,
                'final_credit' => $total_credit_fin
            ]
        ];
    }
}
