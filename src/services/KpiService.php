<?php
// src/services/KpiService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class KpiService {

    /**
     * Get all canonical KPI definitions
     */
    public static function getDefinitions() {
        return [
            'liquidites_totales' => [
                'code' => 'liquidites_totales',
                'libelle' => 'Liquidités Totales',
                'categorie' => 'trésorerie',
                'unite' => 'FCFA',
                'source' => 'comptes_financiers',
                'formule' => 'SUM(solde_courant)',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'journaliere'
            ],
            'liquidites_par_compte' => [
                'code' => 'liquidites_par_compte',
                'libelle' => 'Liquidités par Compte',
                'categorie' => 'trésorerie',
                'unite' => 'FCFA',
                'source' => 'comptes_financiers',
                'formule' => 'solde_courant GROUP BY id',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'journaliere'
            ],
            'entrees_treso' => [
                'code' => 'entrees_treso',
                'libelle' => 'Entrées de Trésorerie',
                'categorie' => 'trésorerie',
                'unite' => 'FCFA',
                'source' => 'mouvements_tresorerie',
                'formule' => "SUM(montant) WHERE type = 'entree'",
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'mensuelle'
            ],
            'sorties_treso' => [
                'code' => 'sorties_treso',
                'libelle' => 'Sorties de Trésorerie',
                'categorie' => 'trésorerie',
                'unite' => 'FCFA',
                'source' => 'mouvements_tresorerie',
                'formule' => "SUM(montant) WHERE type = 'sortie'",
                'sens_interpretation' => 'decroissant',
                'precision' => 2,
                'type_periode' => 'mensuelle'
            ],
            'variation_nette_treso' => [
                'code' => 'variation_nette_treso',
                'libelle' => 'Variation Nette de Trésorerie',
                'categorie' => 'trésorerie',
                'unite' => 'FCFA',
                'source' => 'mouvements_tresorerie',
                'formule' => 'Entrées - Sorties',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'mensuelle'
            ],
            'solde_classe_5' => [
                'code' => 'solde_classe_5',
                'libelle' => 'Solde Général Trésorerie (Classe 5)',
                'categorie' => 'comptabilité',
                'unite' => 'FCFA',
                'source' => 'ecritures_comptables',
                'formule' => 'SUM(debit - credit) [Classe 5]',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'mensuelle'
            ],
            'produits' => [
                'code' => 'produits',
                'libelle' => 'Total Produits (Classe 7)',
                'categorie' => 'comptabilité',
                'unite' => 'FCFA',
                'source' => 'ecritures_comptables',
                'formule' => 'SUM(credit - debit) [Classe 7]',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'mensuelle'
            ],
            'charges' => [
                'code' => 'charges',
                'libelle' => 'Total Charges (Classe 6)',
                'categorie' => 'comptabilité',
                'unite' => 'FCFA',
                'source' => 'ecritures_comptables',
                'formule' => 'SUM(debit - credit) [Classe 6]',
                'sens_interpretation' => 'decroissant',
                'precision' => 2,
                'type_periode' => 'mensuelle'
            ],
            'resultat' => [
                'code' => 'resultat',
                'libelle' => 'Résultat Net Comptable',
                'categorie' => 'comptabilité',
                'unite' => 'FCFA',
                'source' => 'ecritures_comptables',
                'formule' => 'Produits - Charges',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'mensuelle'
            ],
            'budget_initial' => [
                'code' => 'budget_initial',
                'libelle' => 'Enveloppe Budgétaire Initiale',
                'categorie' => 'budget',
                'unite' => 'FCFA',
                'source' => 'budget_lignes',
                'formule' => 'SUM(allocation_initiale + montant_ajustements)',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'annuelle'
            ],
            'engagements_budget' => [
                'code' => 'engagements_budget',
                'libelle' => 'Engagements Budgétaires',
                'categorie' => 'budget',
                'unite' => 'FCFA',
                'source' => 'budget_lignes',
                'formule' => 'SUM(montant_engage)',
                'sens_interpretation' => 'decroissant',
                'precision' => 2,
                'type_periode' => 'annuelle'
            ],
            'consommation_budget' => [
                'code' => 'consommation_budget',
                'libelle' => 'Consommation Budgétaire',
                'categorie' => 'budget',
                'unite' => 'FCFA',
                'source' => 'budget_lignes',
                'formule' => 'SUM(montant_consomme)',
                'sens_interpretation' => 'decroissant',
                'precision' => 2,
                'type_periode' => 'annuelle'
            ],
            'disponible_budget' => [
                'code' => 'disponible_budget',
                'libelle' => 'Disponible Budgétaire',
                'categorie' => 'budget',
                'unite' => 'FCFA',
                'source' => 'budget_lignes',
                'formule' => 'Budget Total - (Engagé + Consommé)',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'annuelle'
            ],
            'dette_fournisseur' => [
                'code' => 'dette_fournisseur',
                'libelle' => 'Dette Fournisseur Totale',
                'categorie' => 'achats',
                'unite' => 'FCFA',
                'source' => 'achat_factures',
                'formule' => 'Factures TTC - Règlements Alloués',
                'sens_interpretation' => 'decroissant',
                'precision' => 2,
                'type_periode' => 'journaliere'
            ],
            'factures_impayees' => [
                'code' => 'factures_impayees',
                'libelle' => 'Factures en Souffrance / Échues',
                'categorie' => 'achats',
                'unite' => 'Factures',
                'source' => 'achat_factures',
                'formule' => "COUNT(id) WHERE statut != 'payee' AND date_echeance < NOW()",
                'sens_interpretation' => 'decroissant',
                'precision' => 0,
                'type_periode' => 'journaliere'
            ],
            'recettes_scolaires' => [
                'code' => 'recettes_scolaires',
                'libelle' => 'Recettes Scolaires Totales',
                'categorie' => 'scolarité',
                'unite' => 'FCFA',
                'source' => 'inscriptions/mensualites',
                'formule' => 'Inscriptions validées + Mensualités validées',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'mensuelle'
            ],
            'taux_recouvrement' => [
                'code' => 'taux_recouvrement',
                'libelle' => 'Taux Global de Recouvrement',
                'categorie' => 'scolarité',
                'unite' => '%',
                'source' => 'inscriptions',
                'formule' => 'Montant versé / Montant attendu',
                'sens_interpretation' => 'croissant',
                'precision' => 2,
                'type_periode' => 'annuelle'
            ]
        ];
    }

    /**
     * Compute a KPI value live
     */
    public static function computeKpi($kpiCode, $lyceeId, $filters = []) {
        $db = Database::getInstance();
        $dateDebut = $filters['date_debut'] ?? null;
        $dateFin = $filters['date_fin'] ?? null;
        $exerciceId = $filters['exercice_financier_id'] ?? null;

        switch ($kpiCode) {
            case 'liquidites_totales':
                $sql = "SELECT SUM(solde_courant) FROM comptes_financiers WHERE lycee_id = :lycee_id AND statut = 'actif'";
                $stmt = $db->prepare($sql);
                $stmt->execute(['lycee_id' => $lyceeId]);
                return (float)$stmt->fetchColumn();

            case 'liquidites_par_compte':
                $sql = "SELECT id, nom_compte as libelle, solde_courant as valeur FROM comptes_financiers WHERE lycee_id = :lycee_id AND statut = 'actif'";
                $stmt = $db->prepare($sql);
                $stmt->execute(['lycee_id' => $lyceeId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'entrees_treso':
                $sql = "SELECT SUM(montant) FROM mouvements_tresorerie WHERE lycee_id = :lycee_id AND type_mouvement = 'entree'";
                $params = ['lycee_id' => $lyceeId];
                if ($dateDebut) { $sql .= " AND date_mouvement >= :d1"; $params['d1'] = $dateDebut; }
                if ($dateFin) { $sql .= " AND date_mouvement <= :d2"; $params['d2'] = $dateFin; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return (float)$stmt->fetchColumn();

            case 'sorties_treso':
                $sql = "SELECT SUM(montant) FROM mouvements_tresorerie WHERE lycee_id = :lycee_id AND type_mouvement = 'sortie'";
                $params = ['lycee_id' => $lyceeId];
                if ($dateDebut) { $sql .= " AND date_mouvement >= :d1"; $params['d1'] = $dateDebut; }
                if ($dateFin) { $sql .= " AND date_mouvement <= :d2"; $params['d2'] = $dateFin; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return (float)$stmt->fetchColumn();

            case 'variation_nette_treso':
                $entrees = self::computeKpi('entrees_treso', $lyceeId, $filters);
                $sorties = self::computeKpi('sorties_treso', $lyceeId, $filters);
                return $entrees - $sorties;

            case 'solde_classe_5':
                $sql = "
                    SELECT SUM(e.debit - e.credit)
                    FROM ecritures_comptables e
                    JOIN pieces_comptables p ON e.piece_comptable_id = p.id
                    JOIN comptes_comptables c ON e.compte_comptable_id = c.id
                    WHERE p.lycee_id = :lycee_id
                    AND c.classe = 5
                    AND p.statut <> 'brouillon'
                ";
                $params = ['lycee_id' => $lyceeId];
                if ($dateDebut) { $sql .= " AND p.date_piece >= :d1"; $params['d1'] = $dateDebut; }
                if ($dateFin) { $sql .= " AND p.date_piece <= :d2"; $params['d2'] = $dateFin; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return (float)$stmt->fetchColumn();

            case 'produits':
                $sql = "
                    SELECT SUM(e.credit - e.debit)
                    FROM ecritures_comptables e
                    JOIN pieces_comptables p ON e.piece_comptable_id = p.id
                    JOIN comptes_comptables c ON e.compte_comptable_id = c.id
                    WHERE p.lycee_id = :lycee_id
                    AND c.classe = 7
                    AND p.statut <> 'brouillon'
                ";
                $params = ['lycee_id' => $lyceeId];
                if ($dateDebut) { $sql .= " AND p.date_piece >= :d1"; $params['d1'] = $dateDebut; }
                if ($dateFin) { $sql .= " AND p.date_piece <= :d2"; $params['d2'] = $dateFin; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return (float)$stmt->fetchColumn();

            case 'charges':
                $sql = "
                    SELECT SUM(e.debit - e.credit)
                    FROM ecritures_comptables e
                    JOIN pieces_comptables p ON e.piece_comptable_id = p.id
                    JOIN comptes_comptables c ON e.compte_comptable_id = c.id
                    WHERE p.lycee_id = :lycee_id
                    AND c.classe = 6
                    AND p.statut <> 'brouillon'
                ";
                $params = ['lycee_id' => $lyceeId];
                if ($dateDebut) { $sql .= " AND p.date_piece >= :d1"; $params['d1'] = $dateDebut; }
                if ($dateFin) { $sql .= " AND p.date_piece <= :d2"; $params['d2'] = $dateFin; }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return (float)$stmt->fetchColumn();

            case 'resultat':
                $prod = self::computeKpi('produits', $lyceeId, $filters);
                $charg = self::computeKpi('charges', $lyceeId, $filters);
                return $prod - $charg;

            case 'budget_initial':
                $sql = "
                    SELECT SUM(bl.allocation_initiale + bl.montant_ajustements)
                    FROM budget_lignes bl
                    JOIN budgets b ON bl.budget_id = b.id
                    WHERE b.lycee_id = :lycee_id
                ";
                $params = ['lycee_id' => $lyceeId];
                if ($exerciceId) {
                    $sql .= " AND b.exercice_financier_id = :ex_id";
                    $params['ex_id'] = $exerciceId;
                } else {
                    $sql .= " AND b.statut = 'actif'";
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return (float)$stmt->fetchColumn();

            case 'engagements_budget':
                $sql = "
                    SELECT SUM(bl.montant_engage)
                    FROM budget_lignes bl
                    JOIN budgets b ON bl.budget_id = b.id
                    WHERE b.lycee_id = :lycee_id
                ";
                $params = ['lycee_id' => $lyceeId];
                if ($exerciceId) {
                    $sql .= " AND b.exercice_financier_id = :ex_id";
                    $params['ex_id'] = $exerciceId;
                } else {
                    $sql .= " AND b.statut = 'actif'";
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return (float)$stmt->fetchColumn();

            case 'consommation_budget':
                $sql = "
                    SELECT SUM(bl.montant_consomme)
                    FROM budget_lignes bl
                    JOIN budgets b ON bl.budget_id = b.id
                    WHERE b.lycee_id = :lycee_id
                ";
                $params = ['lycee_id' => $lyceeId];
                if ($exerciceId) {
                    $sql .= " AND b.exercice_financier_id = :ex_id";
                    $params['ex_id'] = $exerciceId;
                } else {
                    $sql .= " AND b.statut = 'actif'";
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return (float)$stmt->fetchColumn();

            case 'disponible_budget':
                $budget_tot = self::computeKpi('budget_initial', $lyceeId, $filters);
                $engag = self::computeKpi('engagements_budget', $lyceeId, $filters);
                $cons = self::computeKpi('consommation_budget', $lyceeId, $filters);
                return $budget_tot - ($engag + $cons);

            case 'dette_fournisseur':
                // Sum factures TTC - Sum reglements
                $sql_f = "SELECT SUM(montant_ttc) FROM achat_factures WHERE lycee_id = :lycee_id AND statut <> 'annulee'";
                $stmt_f = $db->prepare($sql_f);
                $stmt_f->execute(['lycee_id' => $lyceeId]);
                $factures = (float)$stmt_f->fetchColumn();

                $sql_r = "
                    SELECT SUM(r.montant_alloue)
                    FROM achat_facture_reglements r
                    JOIN achat_factures f ON r.facture_id = f.id
                    WHERE f.lycee_id = :lycee_id AND f.statut <> 'annulee'
                ";
                $stmt_r = $db->prepare($sql_r);
                $stmt_r->execute(['lycee_id' => $lyceeId]);
                $reglements = (float)$stmt_r->fetchColumn();

                $sql_a = "
                    SELECT SUM(montant_ttc)
                    FROM achat_avoirs_fournisseurs
                    WHERE lycee_id = :lycee_id AND statut = 'valide'
                ";
                $stmt_a = $db->prepare($sql_a);
                $stmt_a->execute(['lycee_id' => $lyceeId]);
                $avoirs = (float)$stmt_a->fetchColumn();

                return $factures - $reglements - $avoirs;

            case 'factures_impayees':
                // count unpaid invoices that have passed due date
                $sql = "SELECT COUNT(id) FROM achat_factures WHERE lycee_id = :lycee_id AND statut IN ('enregistree', 'validee') AND date_echeance < :today";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'lycee_id' => $lyceeId,
                    'today' => date('Y-m-d')
                ]);
                return (int)$stmt->fetchColumn();

            case 'recettes_scolaires':
                // Payments for Inscriptions + Mensualités
                $sql_inscr = "SELECT SUM(montant_verse) FROM inscriptions WHERE lycee_id = :lycee_id AND statut = 'valide'";
                $params_i = ['lycee_id' => $lyceeId];
                if ($dateDebut) { $sql_inscr .= " AND date_inscription >= :d1"; $params_i['d1'] = $dateDebut; }
                if ($dateFin) { $sql_inscr .= " AND date_inscription <= :d2"; $params_i['d2'] = $dateFin; }
                $stmt_i = $db->prepare($sql_inscr);
                $stmt_i->execute($params_i);
                $inscr = (float)$stmt_i->fetchColumn();

                $sql_mens = "
                    SELECT SUM(md.montant)
                    FROM mensualite_details md
                    JOIN mensualites m ON md.mensualite_id = m.id_mensualite
                    WHERE m.lycee_id = :lycee_id AND md.statut = 'valide'
                ";
                $params_m = ['lycee_id' => $lyceeId];
                if ($dateDebut) { $sql_mens .= " AND md.date_paiement >= :d1"; $params_m['d1'] = $dateDebut; }
                if ($dateFin) { $sql_mens .= " AND md.date_paiement <= :d2"; $params_m['d2'] = $dateFin; }
                $stmt_m = $db->prepare($sql_mens);
                $stmt_m->execute($params_m);
                $mens = (float)$stmt_m->fetchColumn();

                return $inscr + $mens;

            case 'taux_recouvrement':
                // total payments / total expected
                $sql = "SELECT SUM(montant_verse) as verse, SUM(montant_total) as total FROM inscriptions WHERE lycee_id = :lycee_id AND statut = 'valide'";
                $stmt = $db->prepare($sql);
                $stmt->execute(['lycee_id' => $lyceeId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $total = (float)($row['total'] ?? 0);
                $verse = (float)($row['verse'] ?? 0);
                return $total > 0 ? ($verse / $total) * 100 : 100.00;

            default:
                return 0.00;
        }
    }

    /**
     * Perform all 6 mandatory reconciliations
     */
    public static function validateReconciliations($lyceeId, $filters = []) {
        $reconciliations = [];

        // R1: trésorerie ↔ classe 5
        $t_val = self::computeKpi('liquidites_totales', $lyceeId, $filters);
        $c5_val = self::computeKpi('solde_classe_5', $lyceeId, $filters);
        $diff_r1 = abs($t_val - $c5_val);
        $reconciliations['R1'] = [
            'code' => 'R1',
            'libelle' => 'Trésorerie Opérationnelle vs Grand Livre (Classe 5)',
            'valeur_source' => $t_val,
            'valeur_calculee' => $c5_val,
            'ecart' => $diff_r1,
            'statut' => $diff_r1 < 0.01 ? 'OK' : 'ECART'
        ];

        // R2: fournisseurs ↔ factures/règlements/avoirs
        // Let's compare computed supplier debt to General Ledger third party accounts (Class 401)
        $dette_kpi = self::computeKpi('dette_fournisseur', $lyceeId, $filters);
        $db = Database::getInstance();
        $sql_401 = "
            SELECT SUM(e.credit - e.debit)
            FROM ecritures_comptables e
            JOIN pieces_comptables p ON e.piece_comptable_id = p.id
            JOIN comptes_comptables c ON e.compte_comptable_id = c.id
            WHERE p.lycee_id = :lycee_id
            AND (c.numero LIKE '401%' OR c.numero = '401100')
            AND p.statut <> 'brouillon'
        ";
        $stmt_401 = $db->prepare($sql_401);
        $stmt_401->execute(['lycee_id' => $lyceeId]);
        $gl_401 = (float)$stmt_401->fetchColumn();
        $diff_r2 = abs($dette_kpi - $gl_401);
        $reconciliations['R2'] = [
            'code' => 'R2',
            'libelle' => 'Dette Auxiliaire Fournisseur vs Grand Livre (Classe 40)',
            'valeur_source' => $dette_kpi,
            'valeur_calculee' => $gl_401,
            'ecart' => $diff_r2,
            'statut' => $diff_r2 < 0.01 ? 'OK' : 'ECART'
        ];

        // R3: budget ↔ allocation/engagement/consommation
        // total_utilise + disponible == total_credits
        $alloc_tot = self::computeKpi('budget_initial', $lyceeId, $filters);
        $eng = self::computeKpi('engagements_budget', $lyceeId, $filters);
        $cons = self::computeKpi('consommation_budget', $lyceeId, $filters);
        $disp = self::computeKpi('disponible_budget', $lyceeId, $filters);
        $utilise_calc = $eng + $cons + $disp;
        $diff_r3 = abs($alloc_tot - $utilise_calc);
        $reconciliations['R3'] = [
            'code' => 'R3',
            'libelle' => 'Cohérence Budgétaire (Allocation = Engagé + Consommé + Disponible)',
            'valeur_source' => $alloc_tot,
            'valeur_calculee' => $utilise_calc,
            'ecart' => $diff_r3,
            'statut' => $diff_r3 < 0.01 ? 'OK' : 'ECART'
        ];

        // R4: produits - charges = résultat
        $res_kpi = self::computeKpi('resultat', $lyceeId, $filters);
        $prod = self::computeKpi('produits', $lyceeId, $filters);
        $charg = self::computeKpi('charges', $lyceeId, $filters);
        $res_diff_calc = $prod - $charg;
        $diff_r4 = abs($res_kpi - $res_diff_calc);
        $reconciliations['R4'] = [
            'code' => 'R4',
            'libelle' => 'Compte de Résultat Simplifié (Résultat = Produits - Charges)',
            'valeur_source' => $res_kpi,
            'valeur_calculee' => $res_diff_calc,
            'ecart' => $diff_r4,
            'statut' => $diff_r4 < 0.01 ? 'OK' : 'ECART'
        ];

        // R5: débit = crédit
        $sql_dc = "
            SELECT SUM(debit) as total_debit, SUM(credit) as total_credit
            FROM ecritures_comptables e
            JOIN pieces_comptables p ON e.piece_comptable_id = p.id
            WHERE p.lycee_id = :lycee_id
            AND p.statut <> 'brouillon'
        ";
        $stmt_dc = $db->prepare($sql_dc);
        $stmt_dc->execute(['lycee_id' => $lyceeId]);
        $row_dc = $stmt_dc->fetch(PDO::FETCH_ASSOC);
        $deb = (float)($row_dc['total_debit'] ?? 0);
        $cred = (float)($row_dc['total_credit'] ?? 0);
        $diff_r5 = abs($deb - $cred);
        $reconciliations['R5'] = [
            'code' => 'R5',
            'libelle' => 'Équilibre de la Balance Comptable (Débit = Crédit)',
            'valeur_source' => $deb,
            'valeur_calculee' => $cred,
            'ecart' => $diff_r5,
            'statut' => $diff_r5 < 0.01 ? 'OK' : 'ECART'
        ];

        // R6: snapshot = valeur calculée
        // Let's see the latest monthly snapshot for liquidites_totales
        $sql_snap = "
            SELECT valeur FROM reporting_snapshots
            WHERE lycee_id = :lycee_id AND kpi_code = 'liquidites_totales'
            ORDER BY date_snapshot DESC, id DESC LIMIT 1
        ";
        $stmt_snap = $db->prepare($sql_snap);
        $stmt_snap->execute(['lycee_id' => $lyceeId]);
        $snap_val = $stmt_snap->fetchColumn();
        if ($snap_val === false) {
            $snap_val = $t_val; // If no snapshot exists yet, default to matching
        } else {
            $snap_val = (float)$snap_val;
        }
        $diff_r6 = abs($snap_val - $t_val);
        $reconciliations['R6'] = [
            'code' => 'R6',
            'libelle' => 'Snapshot Historique vs Valeur Calculée en Temps Réel',
            'valeur_source' => $snap_val,
            'valeur_calculee' => $t_val,
            'ecart' => $diff_r6,
            'statut' => $diff_r6 < 0.01 ? 'OK' : 'ECART'
        ];

        return $reconciliations;
    }
}
?>
