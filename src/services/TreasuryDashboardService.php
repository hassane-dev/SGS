<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../models/SessionCaisse.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';

class TreasuryDashboardService {

    /**
     * Centralized Single Source of Truth aggregation service for Caisse / Trésorerie Dashboard.
     * Operates strictly in read-only mode, respecting lycee_id multi-tenant boundaries and RBAC permissions.
     *
     * @param int $lyceeId
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public static function getDashboardData($lyceeId, $userId, array $filters = []) {
        $db = Database::getInstance();

        // 1. Resolve temporal range
        $period = $filters['period'] ?? 'today';
        $todayStr = date('Y-m-d');

        if ($period === '7days') {
            $dateDebut = date('Y-m-d', strtotime('-6 days'));
            $dateFin = $todayStr;
        } elseif ($period === '30days') {
            $dateDebut = date('Y-m-d', strtotime('-29 days'));
            $dateFin = $todayStr;
        } elseif ($period === 'custom' && !empty($filters['date_debut']) && !empty($filters['date_fin'])) {
            $dateDebut = $filters['date_debut'];
            $dateFin = $filters['date_fin'];
        } else {
            // Default: today
            $period = 'today';
            $dateDebut = $todayStr;
            $dateFin = $todayStr;
        }

        $compteIdFilter = !empty($filters['compte_id']) ? (int)$filters['compte_id'] : null;

        // Determine user RBAC scope
        $isValidator = Auth::can('validate', 'sessions_caisse') || Auth::can('manage', 'sessions_caisse');
        $isGlobalViewer = $isValidator || Auth::can('view', 'sessions_caisse') || Auth::can('view', 'comptes_financiers');

        // ---------------------------------------------------------------------
        // KPI 1: Solde courant total des caisses (comptes_financiers.solde_courant)
        // ---------------------------------------------------------------------
        $sqlSolde = "
            SELECT SUM(solde_courant)
            FROM comptes_financiers
            WHERE lycee_id = :lycee_id
              AND type_compte = 'caisse'
              AND est_coffre = 0
              AND statut = 'actif'
        ";
        $paramsSolde = ['lycee_id' => $lyceeId];
        if ($compteIdFilter) {
            $sqlSolde .= " AND id = :compte_id";
            $paramsSolde['compte_id'] = $compteIdFilter;
        }
        $stmtSolde = $db->prepare($sqlSolde);
        $stmtSolde->execute($paramsSolde);
        $soldeCaisses = (float)($stmtSolde->fetchColumn() ?? 0.00);

        // Solde Coffre Principal
        $sqlCoffre = "
            SELECT SUM(solde_courant)
            FROM comptes_financiers
            WHERE lycee_id = :lycee_id
              AND est_coffre = 1
              AND statut = 'actif'
        ";
        $stmtCoffre = $db->prepare($sqlCoffre);
        $stmtCoffre->execute(['lycee_id' => $lyceeId]);
        $soldeCoffre = (float)($stmtCoffre->fetchColumn() ?? 0.00);

        // ---------------------------------------------------------------------
        // KPI 2: Sessions ouvertes (statut = 'ouverte')
        // ---------------------------------------------------------------------
        $sqlOpenSess = "
            SELECT COUNT(*), SUM(solde_theorique)
            FROM sessions_caisse
            WHERE lycee_id = :lycee_id
              AND statut = 'ouverte'
        ";
        $paramsOpenSess = ['lycee_id' => $lyceeId];
        if (!$isGlobalViewer) {
            $sqlOpenSess .= " AND user_id = :user_id";
            $paramsOpenSess['user_id'] = $userId;
        }
        if ($compteIdFilter) {
            $sqlOpenSess .= " AND compte_id = :compte_id";
            $paramsOpenSess['compte_id'] = $compteIdFilter;
        }
        $stmtOpenSess = $db->prepare($sqlOpenSess);
        $stmtOpenSess->execute($paramsOpenSess);
        $rowOpenSess = $stmtOpenSess->fetch(PDO::FETCH_NUM);
        $openSessionsCount = (int)($rowOpenSess[0] ?? 0);
        $openSessionsSoldeTheorique = (float)($rowOpenSess[1] ?? 0.00);

        // ---------------------------------------------------------------------
        // KPI 3: Encaissements opérationnels nets de la période (mouvements_tresorerie)
        // ---------------------------------------------------------------------
        $sqlInflows = "
            SELECT SUM(
                CASE
                    WHEN evenement_type = 'encaissement' THEN montant
                    WHEN evenement_type IN ('annulation', 'remboursement') THEN -montant
                    ELSE 0
                END
            )
            FROM mouvements_tresorerie
            WHERE lycee_id = :lycee_id
              AND DATE(date_mouvement) >= :date_debut
              AND DATE(date_mouvement) <= :date_fin
        ";
        $paramsInflows = [
            'lycee_id' => $lyceeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
        if ($compteIdFilter) {
            $sqlInflows .= " AND compte_id = :compte_id";
            $paramsInflows['compte_id'] = $compteIdFilter;
        }
        $stmtInflows = $db->prepare($sqlInflows);
        $stmtInflows->execute($paramsInflows);
        $encaissementPeriode = (float)($stmtInflows->fetchColumn() ?? 0.00);

        // ---------------------------------------------------------------------
        // KPI 4: Décaissements opérationnels de la période (evenement_type = 'reglement_fournisseur')
        // ---------------------------------------------------------------------
        $sqlOutflows = "
            SELECT SUM(montant)
            FROM mouvements_tresorerie
            WHERE lycee_id = :lycee_id
              AND evenement_type = 'reglement_fournisseur'
              AND DATE(date_mouvement) >= :date_debut
              AND DATE(date_mouvement) <= :date_fin
        ";
        $paramsOutflows = [
            'lycee_id' => $lyceeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
        if ($compteIdFilter) {
            $sqlOutflows .= " AND compte_id = :compte_id";
            $paramsOutflows['compte_id'] = $compteIdFilter;
        }
        $stmtOutflows = $db->prepare($sqlOutflows);
        $stmtOutflows->execute($paramsOutflows);
        $decaissementPeriode = (float)($stmtOutflows->fetchColumn() ?? 0.00);

        // ---------------------------------------------------------------------
        // KPI 5: Sessions à valider / Écarts (statut = 'fermee_a_valider')
        // ---------------------------------------------------------------------
        $sqlPendingSess = "
            SELECT COUNT(*), SUM(ABS(ecart)), SUM(solde_reel), SUM(montant_remis)
            FROM sessions_caisse
            WHERE lycee_id = :lycee_id
              AND statut = 'fermee_a_valider'
        ";
        $paramsPending = ['lycee_id' => $lyceeId];
        if ($compteIdFilter) {
            $sqlPendingSess .= " AND compte_id = :compte_id";
            $paramsPending['compte_id'] = $compteIdFilter;
        }
        $stmtPending = $db->prepare($sqlPendingSess);
        $stmtPending->execute($paramsPending);
        $rowPending = $stmtPending->fetch(PDO::FETCH_NUM);
        $pendingSessionsCount = (int)($rowPending[0] ?? 0);
        $pendingEcartTotal = (float)($rowPending[1] ?? 0.00);
        $pendingSoldeReelTotal = (float)($rowPending[2] ?? 0.00);
        $pendingMontantRemisTotal = (float)($rowPending[3] ?? 0.00);

        // ---------------------------------------------------------------------
        // KPI 6: Remis au coffre validé sur la période (evenement_type = 'remise_coffre_entree')
        // ---------------------------------------------------------------------
        $sqlRemisCoffre = "
            SELECT SUM(montant)
            FROM mouvements_tresorerie
            WHERE lycee_id = :lycee_id
              AND evenement_type = 'remise_coffre_entree'
              AND DATE(date_mouvement) >= :date_debut
              AND DATE(date_mouvement) <= :date_fin
        ";
        $paramsRemis = [
            'lycee_id' => $lyceeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ];
        $stmtRemis = $db->prepare($sqlRemisCoffre);
        $stmtRemis->execute($paramsRemis);
        $remisCoffrePeriode = (float)($stmtRemis->fetchColumn() ?? 0.00);

        // ---------------------------------------------------------------------
        // SESSIONS BREAKDOWN GRID (Ouvertes, À valider, Validées)
        // ---------------------------------------------------------------------
        // 1. Pending sessions list
        $sqlPendingList = "
            SELECT s.*, c.nom_compte, u.nom as user_nom, u.prenom as user_prenom
            FROM sessions_caisse s
            JOIN comptes_financiers c ON s.compte_id = c.id
            JOIN utilisateurs u ON s.user_id = u.id_user
            WHERE s.lycee_id = :lycee_id AND s.statut = 'fermee_a_valider'
            ORDER BY s.date_fermeture DESC
        ";
        $stmtPendingList = $db->prepare($sqlPendingList);
        $stmtPendingList->execute(['lycee_id' => $lyceeId]);
        $pendingSessionsList = $stmtPendingList->fetchAll(PDO::FETCH_ASSOC);

        // 2. Open sessions list
        $sqlOpenList = "
            SELECT s.*, c.nom_compte, u.nom as user_nom, u.prenom as user_prenom
            FROM sessions_caisse s
            JOIN comptes_financiers c ON s.compte_id = c.id
            JOIN utilisateurs u ON s.user_id = u.id_user
            WHERE s.lycee_id = :lycee_id AND s.statut = 'ouverte'
            ORDER BY s.date_ouverture DESC
        ";
        $stmtOpenList = $db->prepare($sqlOpenList);
        $stmtOpenList->execute(['lycee_id' => $lyceeId]);
        $openSessionsList = $stmtOpenList->fetchAll(PDO::FETCH_ASSOC);

        // 3. Validated sessions on period
        $sqlValidatedList = "
            SELECT s.*, c.nom_compte, u.nom as user_nom, u.prenom as user_prenom, v.nom as valideur_nom, v.prenom as valideur_prenom
            FROM sessions_caisse s
            JOIN comptes_financiers c ON s.compte_id = c.id
            JOIN utilisateurs u ON s.user_id = u.id_user
            LEFT JOIN utilisateurs v ON s.valide_par = v.id_user
            WHERE s.lycee_id = :lycee_id AND s.statut = 'fermee_validee'
              AND DATE(s.valide_le) >= :date_debut AND DATE(s.valide_le) <= :date_fin
            ORDER BY s.valide_le DESC
        ";
        $stmtValidatedList = $db->prepare($sqlValidatedList);
        $stmtValidatedList->execute([
            'lycee_id' => $lyceeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ]);
        $validatedSessionsList = $stmtValidatedList->fetchAll(PDO::FETCH_ASSOC);

        $validatedSessionsCount = count($validatedSessionsList);
        $validatedSessionsTotalRemis = 0.00;
        foreach ($validatedSessionsList as $vs) {
            $validatedSessionsTotalRemis += (float)($vs['montant_remis'] ?? 0.00);
        }

        // ---------------------------------------------------------------------
        // TREND CHART SERIES (ApexCharts)
        // ---------------------------------------------------------------------
        $chartCategories = [];
        $chartInflows = [];
        $chartOutflows = [];

        if ($dateDebut === $dateFin) {
            // Hourly breakdown for single day
            for ($h = 0; $h < 24; $h++) {
                $hStr = sprintf('%02d', $h);
                $chartCategories[] = $hStr . 'h';
                $chartInflows[$hStr] = 0.00;
                $chartOutflows[$hStr] = 0.00;
            }

            $sqlHourly = "
                SELECT
                    SUBSTR(date_mouvement, 12, 2) as heure,
                    SUM(
                        CASE
                            WHEN evenement_type = 'encaissement' THEN montant
                            WHEN evenement_type IN ('annulation', 'remboursement') THEN -montant
                            ELSE 0
                        END
                    ) as entrees,
                    SUM(
                        CASE
                            WHEN evenement_type = 'reglement_fournisseur' THEN montant
                            ELSE 0
                        END
                    ) as sorties
                FROM mouvements_tresorerie
                WHERE lycee_id = :lycee_id
                  AND DATE(date_mouvement) = :date_ref
                GROUP BY SUBSTR(date_mouvement, 12, 2)
            ";
            $stmtHourly = $db->prepare($sqlHourly);
            $stmtHourly->execute(['lycee_id' => $lyceeId, 'date_ref' => $dateDebut]);
            while ($row = $stmtHourly->fetch(PDO::FETCH_ASSOC)) {
                $hKey = sprintf('%02d', (int)$row['heure']);
                if (isset($chartInflows[$hKey])) {
                    $chartInflows[$hKey] = (float)$row['entrees'];
                    $chartOutflows[$hKey] = (float)$row['sorties'];
                }
            }

            $chartInflowSeries = array_values($chartInflows);
            $chartOutflowSeries = array_values($chartOutflows);
        } else {
            // Continuous Daily breakdown between $dateDebut and $dateFin
            $startDt = new DateTime($dateDebut);
            $endDt = new DateTime($dateFin);
            $dailyMapInflows = [];
            $dailyMapOutflows = [];

            $curr = clone $startDt;
            while ($curr <= $endDt) {
                $dKey = $curr->format('Y-m-d');
                $chartCategories[] = $curr->format('d/m');
                $dailyMapInflows[$dKey] = 0.00;
                $dailyMapOutflows[$dKey] = 0.00;
                $curr->modify('+1 day');
            }

            $sqlDaily = "
                SELECT
                    DATE(date_mouvement) as d_mvt,
                    SUM(
                        CASE
                            WHEN evenement_type = 'encaissement' THEN montant
                            WHEN evenement_type IN ('annulation', 'remboursement') THEN -montant
                            ELSE 0
                        END
                    ) as entrees,
                    SUM(
                        CASE
                            WHEN evenement_type = 'reglement_fournisseur' THEN montant
                            ELSE 0
                        END
                    ) as sorties
                FROM mouvements_tresorerie
                WHERE lycee_id = :lycee_id
                  AND DATE(date_mouvement) >= :date_debut
                  AND DATE(date_mouvement) <= :date_fin
                GROUP BY DATE(date_mouvement)
            ";
            $stmtDaily = $db->prepare($sqlDaily);
            $stmtDaily->execute([
                'lycee_id' => $lyceeId,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ]);
            while ($row = $stmtDaily->fetch(PDO::FETCH_ASSOC)) {
                $dKey = $row['d_mvt'];
                if (isset($dailyMapInflows[$dKey])) {
                    $dailyMapInflows[$dKey] = (float)$row['entrees'];
                    $dailyMapOutflows[$dKey] = (float)$row['sorties'];
                }
            }

            $chartInflowSeries = array_values($dailyMapInflows);
            $chartOutflowSeries = array_values($dailyMapOutflows);
        }

        // ---------------------------------------------------------------------
        // TABLEAU COMPARATIF DES CAISSES (Single Query Grouped - No N+1)
        // ---------------------------------------------------------------------
        $sqlCaisses = "
            SELECT
                c.id as compte_id,
                c.nom_compte,
                c.solde_courant,
                c.responsable_id,
                resp.nom as resp_nom, resp.prenom as resp_prenom,
                s.id as active_session_id,
                s.user_id as session_user_id,
                s.statut as session_statut,
                s.ecart as session_ecart,
                sess_u.nom as sess_user_nom, sess_u.prenom as sess_user_prenom
            FROM comptes_financiers c
            LEFT JOIN utilisateurs resp ON c.responsable_id = resp.id_user
            LEFT JOIN sessions_caisse s ON s.compte_id = c.id AND s.statut IN ('ouverte', 'fermee_a_valider')
            LEFT JOIN utilisateurs sess_u ON s.user_id = sess_u.id_user
            WHERE c.lycee_id = :lycee_id
              AND c.type_compte = 'caisse'
              AND c.est_coffre = 0
              AND c.statut = 'actif'
            ORDER BY c.nom_compte ASC
        ";
        $stmtCaisses = $db->prepare($sqlCaisses);
        $stmtCaisses->execute(['lycee_id' => $lyceeId]);
        $caissesRows = $stmtCaisses->fetchAll(PDO::FETCH_ASSOC);

        // Fetch movements totals grouped by compte_id for the selected period
        $sqlCaissesMvts = "
            SELECT
                compte_id,
                SUM(
                    CASE
                        WHEN evenement_type = 'encaissement' THEN montant
                        WHEN evenement_type IN ('annulation', 'remboursement') THEN -montant
                        ELSE 0
                    END
                ) as total_entrees,
                SUM(
                    CASE
                        WHEN evenement_type = 'reglement_fournisseur' THEN montant
                        ELSE 0
                    END
                ) as total_sorties
            FROM mouvements_tresorerie
            WHERE lycee_id = :lycee_id
              AND DATE(date_mouvement) >= :date_debut
              AND DATE(date_mouvement) <= :date_fin
            GROUP BY compte_id
        ";
        $stmtCaissesMvts = $db->prepare($sqlCaissesMvts);
        $stmtCaissesMvts->execute([
            'lycee_id' => $lyceeId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ]);
        $caissesMvtsMap = [];
        while ($row = $stmtCaissesMvts->fetch(PDO::FETCH_ASSOC)) {
            $caissesMvtsMap[$row['compte_id']] = [
                'entrees' => (float)$row['total_entrees'],
                'sorties' => (float)$row['total_sorties']
            ];
        }

        $caissesComparison = [];
        foreach ($caissesRows as $cr) {
            $cId = $cr['compte_id'];

            // Rule 6: Cashier on active session takes priority over account manager
            if (!empty($cr['session_user_id']) && !empty($cr['sess_user_nom'])) {
                $personName = trim($cr['sess_user_prenom'] . ' ' . $cr['sess_user_nom']);
            } elseif (!empty($cr['responsable_id']) && !empty($cr['resp_nom'])) {
                $personName = trim($cr['resp_prenom'] . ' ' . $cr['resp_nom']);
            } else {
                $personName = 'Non assigné';
            }

            // DB status ENUM mapping
            $sessStatut = $cr['session_statut'] ?? 'aucune';

            $mvts = $caissesMvtsMap[$cId] ?? ['entrees' => 0.00, 'sorties' => 0.00];

            $caissesComparison[] = [
                'compte_id' => $cId,
                'nom_compte' => $cr['nom_compte'],
                'solde_courant' => (float)$cr['solde_courant'],
                'person_name' => $personName,
                'session_id' => $cr['active_session_id'] ? (int)$cr['active_session_id'] : null,
                'session_statut' => $sessStatut,
                'session_ecart' => $cr['session_ecart'] !== null ? (float)$cr['session_ecart'] : 0.00,
                'encaissements_periode' => $mvts['entrees'],
                'decaissements_periode' => $mvts['sorties']
            ];
        }

        // ---------------------------------------------------------------------
        // DYNAMIC CONTEXTUAL ACTIONS HUB
        // ---------------------------------------------------------------------
        // Check active session for current user
        $userActiveSession = SessionCaisse::findActiveByUser($userId, $lyceeId);

        $actionsHub = [
            'has_active_session' => $userActiveSession !== null,
            'active_session_id' => $userActiveSession ? (int)$userActiveSession['id'] : null,
            'pending_validations_count' => $pendingSessionsCount,
            'can_open_session' => Auth::can('create', 'sessions_caisse') && !$userActiveSession,
            'can_close_session' => Auth::can('edit', 'sessions_caisse') && $userActiveSession,
            'can_validate_session' => Auth::can('validate', 'sessions_caisse'),
            'can_view_movements' => Auth::can('view', 'mouvements_tresorerie') || Auth::can('view', 'paiement'),
            'can_pay_depense' => Auth::can('pay', 'depense'),
            'can_view_accounts' => Auth::can('view', 'comptes_financiers'),
        ];

        return [
            'period' => $period,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'compte_id_filter' => $compteIdFilter,
            'kpis' => [
                'solde_caisses' => $soldeCaisses,
                'solde_coffre' => $soldeCoffre,
                'sessions_ouvertes_count' => $openSessionsCount,
                'sessions_ouvertes_solde_theo' => $openSessionsSoldeTheorique,
                'encaissement_periode' => $encaissementPeriode,
                'decaissement_periode' => $decaissementPeriode,
                'sessions_a_valider_count' => $pendingSessionsCount,
                'sessions_a_valider_ecart_total' => $pendingEcartTotal,
                'sessions_a_valider_solde_reel' => $pendingSoldeReelTotal,
                'remis_coffre_periode' => $remisCoffrePeriode
            ],
            'sessions' => [
                'ouvertes' => [
                    'count' => $openSessionsCount,
                    'total_solde_theorique' => $openSessionsSoldeTheorique,
                    'list' => $openSessionsList
                ],
                'a_valider' => [
                    'count' => $pendingSessionsCount,
                    'total_ecart' => $pendingEcartTotal,
                    'total_solde_reel' => $pendingSoldeReelTotal,
                    'total_montant_remis' => $pendingMontantRemisTotal,
                    'list' => $pendingSessionsList
                ],
                'validees' => [
                    'count' => $validatedSessionsCount,
                    'total_remis' => $validatedSessionsTotalRemis,
                    'list' => $validatedSessionsList
                ]
            ],
            'chart' => [
                'categories' => $chartCategories,
                'inflows' => $chartInflowSeries,
                'outflows' => $chartOutflowSeries
            ],
            'caisses' => $caissesComparison,
            'actions_hub' => $actionsHub
        ];
    }

    /**
     * Lightweight summary KPIs method for Global Dashboard.
     */
    public static function getSummaryKpis(int $lyceeId, ?int $anneeId = null): array {
        $db = Database::getInstance();
        $todayStr = date('Y-m-d');

        // Cash Balance
        $stmtSolde = $db->prepare("
            SELECT SUM(solde_courant)
            FROM comptes_financiers
            WHERE lycee_id = :lycee_id AND type_compte = 'caisse' AND est_coffre = 0 AND statut = 'actif'
        ");
        $stmtSolde->execute(['lycee_id' => $lyceeId]);
        $soldeCaisses = (float)($stmtSolde->fetchColumn() ?? 0.00);

        // Vault Balance
        $stmtCoffre = $db->prepare("
            SELECT SUM(solde_courant)
            FROM comptes_financiers
            WHERE lycee_id = :lycee_id AND est_coffre = 1 AND statut = 'actif'
        ");
        $stmtCoffre->execute(['lycee_id' => $lyceeId]);
        $soldeCoffre = (float)($stmtCoffre->fetchColumn() ?? 0.00);

        // Today Inflows & Outflows
        $stmtInflows = $db->prepare("
            SELECT SUM(m.montant)
            FROM mouvements_tresorerie m
            JOIN comptes_financiers c ON m.compte_id = c.id
            WHERE c.lycee_id = :lycee_id
              AND DATE(m.date_mouvement) = :today
              AND m.evenement_type = 'encaissement'
        ");
        $stmtInflows->execute(['lycee_id' => $lyceeId, 'today' => $todayStr]);
        $encaissementsJour = (float)($stmtInflows->fetchColumn() ?? 0.00);

        $stmtOutflows = $db->prepare("
            SELECT SUM(m.montant)
            FROM mouvements_tresorerie m
            JOIN comptes_financiers c ON m.compte_id = c.id
            WHERE c.lycee_id = :lycee_id
              AND DATE(m.date_mouvement) = :today
              AND m.evenement_type = 'reglement_fournisseur'
        ");
        $stmtOutflows->execute(['lycee_id' => $lyceeId, 'today' => $todayStr]);
        $decaissementsJour = (float)($stmtOutflows->fetchColumn() ?? 0.00);

        // Global Recovery Rate
        require_once __DIR__ . '/KpiService.php';
        $tauxRecouvrement = (float)KpiService::computeKpi('taux_recouvrement', $lyceeId);

        return [
            'solde_caisses' => $soldeCaisses,
            'solde_coffre' => $soldeCoffre,
            'solde_total_liquidites' => $soldeCaisses + $soldeCoffre,
            'encaissements_jour' => $encaissementsJour,
            'decaissements_jour' => $decaissementsJour,
            'taux_recouvrement' => $tauxRecouvrement
        ];
    }
}
?>