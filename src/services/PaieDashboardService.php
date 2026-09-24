<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../models/PaieBulletin.php';
require_once __DIR__ . '/PaieWorkflowService.php';

class PaieDashboardService {

    /**
     * Get centralized aggregated dashboard metrics for HR & Paie.
     */
    public static function getDashboardData(int $lyceeId, ?int $periodeId = null): array {
        $db = Database::getInstance();

        // 1. Resolve selected / active period for school
        $periodes = PaiePeriode::findAllForLycee($lyceeId);
        $selectedPeriode = null;

        if ($periodeId > 0) {
            $selectedPeriode = PaiePeriode::findById($periodeId);
            if (!$selectedPeriode || (int)$selectedPeriode['lycee_id'] !== $lyceeId) {
                $selectedPeriode = null;
            }
        }

        if (!$selectedPeriode && !empty($periodes)) {
            // Find latest open period first, or latest period
            foreach ($periodes as $p) {
                if ($p['statut'] !== 'cloture') {
                    $selectedPeriode = $p;
                    break;
                }
            }
            if (!$selectedPeriode) {
                $selectedPeriode = $periodes[0];
            }
        }

        $activePeriodeId = $selectedPeriode ? (int)$selectedPeriode['id'] : 0;
        $dateStart = $selectedPeriode['date_debut'] ?? date('Y-m-01');
        $dateEnd = $selectedPeriode['date_fin'] ?? date('Y-m-t');

        // 2. Effectif RH Actif (Distinct physical persons with active date-effective contract)
        $stmtRh = $db->prepare("
            SELECT COUNT(DISTINCT u.id_user)
            FROM utilisateurs u
            JOIN personnel_contrats_historique c ON u.id_user = c.personnel_id
            WHERE u.lycee_id = :lycee_id
              AND u.actif = 1
              AND c.statut_contrat = 'actif'
              AND c.date_debut <= CURDATE()
              AND (c.date_fin IS NULL OR c.date_fin >= CURDATE())
        ");
        $stmtRh->execute(['lycee_id' => $lyceeId]);
        $effectifRhActif = (int)$stmtRh->fetchColumn();

        // Detailed role breakdown of active HR workforce
        $stmtRhRoles = $db->prepare("
            SELECT r.nom_role, COUNT(DISTINCT u.id_user) as total
            FROM utilisateurs u
            JOIN personnel_contrats_historique c ON u.id_user = c.personnel_id
            LEFT JOIN roles r ON u.role_id = r.id_role
            WHERE u.lycee_id = :lycee_id
              AND u.actif = 1
              AND c.statut_contrat = 'actif'
              AND c.date_debut <= CURDATE()
              AND (c.date_fin IS NULL OR c.date_fin >= CURDATE())
            GROUP BY r.nom_role
        ");
        $stmtRhRoles->execute(['lycee_id' => $lyceeId]);
        $effectifByRole = $stmtRhRoles->fetchAll(PDO::FETCH_KEY_PAIR);

        // 3. Salariés Éligibles à la Paie pour la période
        $eligibleContracts = $activePeriodeId ? PaieWorkflowService::getEligibleContractsWithServiceFaitStatus($activePeriodeId, $lyceeId) : [];
        $totalEligibles = count($eligibleContracts);
        $calculablesCount = 0;
        $serviceFaitValideCount = 0;
        $serviceFaitNonValideCount = 0;
        $aucunServiceFaitCount = 0;
        $bulletinsExistantCount = 0;

        foreach ($eligibleContracts as $ec) {
            if ($ec['service_fait_code'] === 'bulletin_existant') {
                $bulletinsExistantCount++;
            } elseif ($ec['service_fait_code'] === 'service_fait_valide') {
                $serviceFaitValideCount++;
            } elseif ($ec['service_fait_code'] === 'service_fait_non_valide') {
                $serviceFaitNonValideCount++;
            } else {
                $aucunServiceFaitCount++;
            }

            if (!empty($ec['est_calculable'])) {
                $calculablesCount++;
            }
        }

        // 4. Financial KPIs for active period (Brut, Net à Payer, Coût Employeur, Décaissement Réel)
        $financialSummary = [
            'total_brut' => 0.00,
            'total_cotisations_salariales' => 0.00,
            'total_impots' => 0.00,
            'total_retenues' => 0.00,
            'net_a_payer' => 0.00,
            'total_cotisations_patronales' => 0.00,
            'cout_total_employeur' => 0.00,
            'total_bulletins' => 0,
            'bulletins_brouillon' => 0,
            'bulletins_valides' => 0,
            'bulletins_payes' => 0,
            'bulletins_non_payes' => 0,
            'montant_effectivement_regle' => 0.00,
            'reste_a_regler' => 0.00
        ];

        if ($activePeriodeId > 0) {
            $stmtFin = $db->prepare("
                SELECT
                    COUNT(id) as total_bulletins,
                    SUM(CASE WHEN statut_bulletin = 'brouillon' THEN 1 ELSE 0 END) as bulletins_brouillon,
                    SUM(CASE WHEN statut_bulletin = 'valide' THEN 1 ELSE 0 END) as bulletins_valides,
                    SUM(CASE WHEN statut_reglement = 'paye' THEN 1 ELSE 0 END) as bulletins_payes,
                    SUM(CASE WHEN statut_reglement != 'paye' THEN 1 ELSE 0 END) as bulletins_non_payes,
                    COALESCE(SUM(salaire_base), 0) as salaire_base_sum,
                    COALESCE(SUM(total_brut), 0) as total_brut_sum,
                    COALESCE(SUM(total_cotisations_salariales), 0) as total_cotisations_salariales_sum,
                    COALESCE(SUM(total_impots), 0) as total_impots_sum,
                    COALESCE(SUM(total_retenues), 0) as total_retenues_sum,
                    COALESCE(SUM(net_a_payer), 0) as net_a_payer_sum,
                    COALESCE(SUM(total_cotisations_patronales), 0) as total_cotisations_patronales_sum,
                    COALESCE(SUM(cout_total_employeur), 0) as cout_total_employeur_sum
                FROM paie_bulletins
                WHERE periode_id = :periode_id
                  AND est_version_active = 1
            ");
            $stmtFin->execute(['periode_id' => $activePeriodeId]);
            $rowFin = $stmtFin->fetch(PDO::FETCH_ASSOC);

            if ($rowFin) {
                $financialSummary['total_bulletins'] = (int)$rowFin['total_bulletins'];
                $financialSummary['bulletins_brouillon'] = (int)$rowFin['bulletins_brouillon'];
                $financialSummary['bulletins_valides'] = (int)$rowFin['bulletins_valides'];
                $financialSummary['bulletins_payes'] = (int)$rowFin['bulletins_payes'];
                $financialSummary['bulletins_non_payes'] = (int)$rowFin['bulletins_non_payes'];
                $financialSummary['salaire_base'] = (float)$rowFin['salaire_base_sum'];
                $financialSummary['total_brut'] = (float)$rowFin['total_brut_sum'];
                $financialSummary['total_cotisations_salariales'] = (float)$rowFin['total_cotisations_salariales_sum'];
                $financialSummary['total_impots'] = (float)$rowFin['total_impots_sum'];
                $financialSummary['total_retenues'] = (float)$rowFin['total_retenues_sum'];
                $financialSummary['net_a_payer'] = (float)$rowFin['net_a_payer_sum'];
                $financialSummary['total_cotisations_patronales'] = (float)$rowFin['total_cotisations_patronales_sum'];
                $financialSummary['cout_total_employeur'] = (float)$rowFin['cout_total_employeur_sum'];
            }

            // Actual Treasury Disbursements from paie_reglements
            $stmtPaid = $db->prepare("
                SELECT COALESCE(SUM(pr.montant), 0)
                FROM paie_reglements pr
                JOIN paie_bulletins b ON pr.bulletin_id = b.id
                WHERE b.periode_id = :periode_id
                  AND b.est_version_active = 1
            ");
            $stmtPaid->execute(['periode_id' => $activePeriodeId]);
            $montantRegle = (float)$stmtPaid->fetchColumn();
            $financialSummary['montant_effectivement_regle'] = $montantRegle;
            $financialSummary['reste_a_regler'] = max(0.00, $financialSummary['net_a_payer'] - $montantRegle);
        }

        // 5. G1: Historical 6-Month Financial Trend
        $stmtG1 = $db->prepare("
            SELECT
                p.id as periode_id, p.code_periode, p.mois, p.annee, p.statut,
                COALESCE(SUM(b.total_brut), 0) as total_brut,
                COALESCE(SUM(b.net_a_payer), 0) as net_a_payer,
                COALESCE(SUM(b.total_cotisations_salariales + b.total_impots), 0) as cotis_impots_salariales,
                COALESCE(SUM(b.total_cotisations_patronales), 0) as cotis_patronales,
                COALESCE(SUM(b.cout_total_employeur), 0) as cout_total_employeur,
                COALESCE((
                    SELECT SUM(pr.montant)
                    FROM paie_reglements pr
                    JOIN paie_bulletins b2 ON pr.bulletin_id = b2.id
                    WHERE b2.periode_id = p.id AND b2.est_version_active = 1
                ), 0) as montant_effectivement_regle
            FROM paie_periodes p
            LEFT JOIN paie_bulletins b ON p.id = b.periode_id AND b.est_version_active = 1
            WHERE p.lycee_id = :lycee_id
            GROUP BY p.id, p.code_periode, p.mois, p.annee, p.statut, p.date_debut
            ORDER BY p.annee DESC, p.mois DESC, p.id DESC
            LIMIT 6
        ");
        $stmtG1->execute(['lycee_id' => $lyceeId]);
        $g1Raw = array_reverse($stmtG1->fetchAll(PDO::FETCH_ASSOC));

        // 6. G2: Additive Employer Cost Breakdown and Salary Deductions Breakdown
        $g2EmployerCost = [
            'salaire_base' => $financialSummary['salaire_base'] ?? 0.00,
            'primes_indemnites_heures' => max(0.00, ($financialSummary['total_brut'] ?? 0.00) - ($financialSummary['salaire_base'] ?? 0.00)),
            'cotisations_patronales' => $financialSummary['total_cotisations_patronales'] ?? 0.00,
            'cout_total_employeur' => $financialSummary['cout_total_employeur'] ?? 0.00
        ];

        $g2DeductionsBreakdown = [
            'total_brut' => $financialSummary['total_brut'] ?? 0.00,
            'cotisations_salariales' => $financialSummary['total_cotisations_salariales'] ?? 0.00,
            'impots' => $financialSummary['total_impots'] ?? 0.00,
            'autres_retenues' => $financialSummary['total_retenues'] ?? 0.00,
            'net_a_payer' => $financialSummary['net_a_payer'] ?? 0.00
        ];

        // 7. G3: Service Fait Pipeline (Heures Réalisées -> Validées -> Intégrées aux Bulletins)
        // a. Heures Réalisées dans cahier_texte
        $stmtCt = $db->prepare("
            SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(ct.heure_fin, ct.heure_debut)) / 3600.0), 0)
            FROM cahier_texte ct
            JOIN utilisateurs u ON ct.personnel_id = u.id_user
            WHERE u.lycee_id = :lycee_id
              AND ct.date_cours BETWEEN :dstart AND :dend
        ");
        $stmtCt->execute(['lycee_id' => $lyceeId, 'dstart' => $dateStart, 'dend' => $dateEnd]);
        $heuresRealisees = (float)$stmtCt->fetchColumn();

        // b. Heures Validées dans paie_cahier_texte_validations
        $stmtVal = $db->prepare("
            SELECT COALESCE(SUM(v.duree_heures), 0)
            FROM paie_cahier_texte_validations v
            JOIN cahier_texte ct ON v.cahier_id = ct.cahier_id
            JOIN utilisateurs u ON v.enseignant_id = u.id_user
            WHERE u.lycee_id = :lycee_id
              AND ct.date_cours BETWEEN :dstart AND :dend
        ");
        $stmtVal->execute(['lycee_id' => $lyceeId, 'dstart' => $dateStart, 'dend' => $dateEnd]);
        $heuresValidees = (float)$stmtVal->fetchColumn();

        // c. Heures Intégrées aux Bulletins
        $heuresIntegrees = 0.00;
        if ($activePeriodeId > 0) {
            $stmtInt = $db->prepare("
                SELECT COALESCE(SUM(bh.heures_effectuees), 0)
                FROM paie_bulletin_heures bh
                JOIN paie_bulletins b ON bh.bulletin_id = b.id
                WHERE b.periode_id = :periode_id
                  AND b.est_version_active = 1
            ");
            $stmtInt->execute(['periode_id' => $activePeriodeId]);
            $heuresIntegrees = (float)$stmtInt->fetchColumn();
        }

        $g3ServiceFaitPipeline = [
            'heures_realisees' => round($heuresRealisees, 1),
            'heures_validees' => round($heuresValidees, 1),
            'heures_integrees' => round($heuresIntegrees, 1),
            'heures_a_valider' => max(0.0, round($heuresRealisees - $heuresValidees, 1)),
            'heures_a_integrer' => max(0.0, round($heuresValidees - $heuresIntegrees, 1))
        ];

        // 8. G4: Échéancier des Contrats CDD (Populations Strictement Séparées)
        // a. Expired contracts
        $stmtExp = $db->prepare("
            SELECT c.id, c.personnel_id, c.date_debut, c.date_fin, u.nom, u.prenom, u.identifiant_public, tc.libelle AS contrat_libelle
            FROM personnel_contrats_historique c
            JOIN utilisateurs u ON c.personnel_id = u.id_user
            LEFT JOIN type_contrat tc ON c.type_contrat_id = tc.id_contrat
            WHERE u.lycee_id = :lycee_id
              AND c.statut_contrat = 'actif'
              AND c.date_fin IS NOT NULL
              AND c.date_fin < CURDATE()
            ORDER BY c.date_fin ASC
        ");
        $stmtExp->execute(['lycee_id' => $lyceeId]);
        $contratsExpires = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

        // b. Expiring in <= 30 days
        $stmtExp30 = $db->prepare("
            SELECT c.id, c.personnel_id, c.date_debut, c.date_fin, u.nom, u.prenom, u.identifiant_public, tc.libelle AS contrat_libelle
            FROM personnel_contrats_historique c
            JOIN utilisateurs u ON c.personnel_id = u.id_user
            LEFT JOIN type_contrat tc ON c.type_contrat_id = tc.id_contrat
            WHERE u.lycee_id = :lycee_id
              AND c.statut_contrat = 'actif'
              AND c.date_fin IS NOT NULL
              AND c.date_fin >= CURDATE()
              AND c.date_fin <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY c.date_fin ASC
        ");
        $stmtExp30->execute(['lycee_id' => $lyceeId]);
        $contratsExp30 = $stmtExp30->fetchAll(PDO::FETCH_ASSOC);

        // c. Expiring in 31 to 60 days
        $stmtExp60 = $db->prepare("
            SELECT c.id, c.personnel_id, c.date_debut, c.date_fin, u.nom, u.prenom, u.identifiant_public, tc.libelle AS contrat_libelle
            FROM personnel_contrats_historique c
            JOIN utilisateurs u ON c.personnel_id = u.id_user
            LEFT JOIN type_contrat tc ON c.type_contrat_id = tc.id_contrat
            WHERE u.lycee_id = :lycee_id
              AND c.statut_contrat = 'actif'
              AND c.date_fin IS NOT NULL
              AND c.date_fin > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              AND c.date_fin <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
            ORDER BY c.date_fin ASC
        ");
        $stmtExp60->execute(['lycee_id' => $lyceeId]);
        $contratsExp60 = $stmtExp60->fetchAll(PDO::FETCH_ASSOC);

        $g4ContractDeadlines = [
            'expires_count' => count($contratsExpires),
            'exp30_count' => count($contratsExp30),
            'exp60_count' => count($contratsExp60),
            'expires_list' => array_slice($contratsExpires, 0, 5),
            'exp30_list' => array_slice($contratsExp30, 0, 5),
            'exp60_list' => array_slice($contratsExp60, 0, 5)
        ];

        return [
            'selected_periode' => $selectedPeriode,
            'periodes_options' => $periodes,
            'effectif_rh_actif' => $effectifRhActif,
            'effectif_by_role' => $effectifByRole,
            'eligibilite_paie' => [
                'total_eligibles' => $totalEligibles,
                'calculables' => $calculablesCount,
                'service_fait_valide' => $serviceFaitValideCount,
                'service_fait_non_valide' => $serviceFaitNonValideCount,
                'aucun_service_fait' => $aucunServiceFaitCount,
                'bulletin_existant' => $bulletinsExistantCount
            ],
            'financial_summary' => $financialSummary,
            'g1_trend_6m' => $g1Raw,
            'g2_employer_cost' => $g2EmployerCost,
            'g2_deductions_breakdown' => $g2DeductionsBreakdown,
            'g3_service_fait_pipeline' => $g3ServiceFaitPipeline,
            'g4_contract_deadlines' => $g4ContractDeadlines
        ];
    }
}
