<?php
// src/services/ReportingFinancierService.php

require_once __DIR__ . '/../config/database.php';

class ReportingFinancierService {

    /**
     * Calcule le compte de résultat simplifié : Produits (Classe 7) - Charges (Classe 6)
     */
    public static function getCompteResultat($lyceeId, $dateDebut = null, $dateFin = null) {
        $db = Database::getInstance();

        // 1. Fetch total of Classe 6 (Charges) grouped by account
        $sql_6 = "
            SELECT c.numero, c.libelle, SUM(e.debit - e.credit) as total
            FROM ecritures_comptables e
            JOIN pieces_comptables p ON e.piece_comptable_id = p.id
            JOIN comptes_comptables c ON e.compte_comptable_id = c.id
            WHERE p.lycee_id = :lycee_id
            AND c.classe = 6
            AND p.statut <> 'brouillon'
        ";
        $params = ['lycee_id' => $lyceeId];
        if ($dateDebut) {
            $sql_6 .= " AND p.date_piece >= :date_debut";
            $params['date_debut'] = $dateDebut;
        }
        if ($dateFin) {
            $sql_6 .= " AND p.date_piece <= :date_fin";
            $params['date_fin'] = $dateFin;
        }
        $sql_6 .= " GROUP BY c.id ORDER BY c.numero ASC";
        $stmt_6 = $db->prepare($sql_6);
        $stmt_6->execute($params);
        $charges = $stmt_6->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch total of Classe 7 (Produits) grouped by account
        $sql_7 = "
            SELECT c.numero, c.libelle, SUM(e.credit - e.debit) as total
            FROM ecritures_comptables e
            JOIN pieces_comptables p ON e.piece_comptable_id = p.id
            JOIN comptes_comptables c ON e.compte_comptable_id = c.id
            WHERE p.lycee_id = :lycee_id
            AND c.classe = 7
            AND p.statut <> 'brouillon'
        ";
        $params_7 = ['lycee_id' => $lyceeId];
        if ($dateDebut) {
            $sql_7 .= " AND p.date_piece >= :date_debut";
            $params_7['date_debut'] = $dateDebut;
        }
        if ($dateFin) {
            $sql_7 .= " AND p.date_piece <= :date_fin";
            $params_7['date_fin'] = $dateFin;
        }
        $sql_7 .= " GROUP BY c.id ORDER BY c.numero ASC";
        $stmt_7 = $db->prepare($sql_7);
        $stmt_7->execute($params_7);
        $produits = $stmt_7->fetchAll(PDO::FETCH_ASSOC);

        $total_charges = 0.00;
        foreach ($charges as $ch) {
            $total_charges += (float)$ch['total'];
        }

        $total_produits = 0.00;
        foreach ($produits as $pr) {
            $total_produits += (float)$pr['total'];
        }

        $resultat = $total_produits - $total_charges;

        return [
            'charges' => $charges,
            'produits' => $produits,
            'total_charges' => $total_charges,
            'total_produits' => $total_produits,
            'resultat_net' => $resultat,
            'type_resultat' => $resultat >= 0 ? 'excedent' : 'deficit'
        ];
    }

    /**
     * Calcule la situation de trésorerie consolidée en temps réel (Classe 5)
     */
    public static function getSituationTresorerie($lyceeId, $dateDebut = null, $dateFin = null) {
        $db = Database::getInstance();

        $sql_ref = "
            SELECT c.numero, c.libelle,
                   SUM(e.debit) as total_debit,
                   SUM(e.credit) as total_credit
            FROM ecritures_comptables e
            JOIN pieces_comptables p ON e.piece_comptable_id = p.id
            JOIN comptes_comptables c ON e.compte_comptable_id = c.id
            WHERE p.lycee_id = :lycee_id
            AND c.classe = 5
            AND p.statut <> 'brouillon'
        ";
        $params_ref = ['lycee_id' => $lyceeId];
        if ($dateDebut) {
            $sql_ref .= " AND p.date_piece >= :date_debut";
            $params_ref['date_debut'] = $dateDebut;
        }
        if ($dateFin) {
            $sql_ref .= " AND p.date_piece <= :date_fin";
            $params_ref['date_fin'] = $dateFin;
        }
        $sql_ref .= " GROUP BY c.id ORDER BY c.numero ASC";

        $stmt = $db->prepare($sql_ref);
        $stmt->execute($params_ref);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $treso = [];
        $total_debit = 0.00;
        $total_credit = 0.00;
        $total_solde = 0.00;

        foreach ($accounts as $ac) {
            $deb = (float)$ac['total_debit'];
            $cred = (float)$ac['total_credit'];
            $solde = $deb - $cred; // Classe 5 is active nature, debit - credit

            $treso[] = [
                'numero' => $ac['numero'],
                'libelle' => $ac['libelle'],
                'debit' => $deb,
                'credit' => $cred,
                'solde' => $solde
            ];

            $total_debit += $deb;
            $total_credit += $cred;
            $total_solde += $solde;
        }

        return [
            'comptes' => $treso,
            'total_debit' => $total_debit,
            'total_credit' => $total_credit,
            'total_solde' => $total_solde
        ];
    }
}
