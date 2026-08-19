<?php

require_once __DIR__ . '/PaieRuleRepository.php';

class PaieCalculationEngine {

    /**
     * Compute a full bulletin breakdown given contract data, teacher hourly validations, components, rules, and jurisdiction.
     */
    public static function computeBulletin(array $contract, array $cahierValidations = [], array $components = [], string $juridictionCode = 'DEFAULT', ?string $asOfDate = null): array {
        PaieRuleRepository::seedDefaultRulesIfNeeded();
        $rules = PaieRuleRepository::getActiveRulesWithTiers($juridictionCode, $asOfDate);

        // Sort rules into deterministic calculation phases:
        // Phase 1: Cotisations salariales
        // Phase 2: Impôts / IUTS
        // Phase 3: Cotisations patronales
        usort($rules, function($a, $b) {
            $orderMap = [
                'cotisation_salariale' => 1,
                'impot' => 2,
                'cotisation_patronale' => 3
            ];
            $orderA = $orderMap[$a['categorie']] ?? 4;
            $orderB = $orderMap[$b['categorie']] ?? 4;
            return $orderA <=> $orderB;
        });

        // 1. Calculate Base Salary & Pedagogical Hours
        $salaireBase = (float)($contract['salaire_base'] ?? 0.00);
        $totalHeuresMontant = 0.00;
        $heuresLines = [];

        foreach ($cahierValidations as $v) {
            $hEffectuees = (float)$v['duree_heures'];
            $tHoraire = (float)$v['taux_horaire'];
            $mTotal = round($hEffectuees * $tHoraire, 4);
            $totalHeuresMontant += $mTotal;
            $heuresLines[] = [
                'cahier_validation_id' => $v['id'],
                'heures_effectuees' => $hEffectuees,
                'taux_horaire' => $tHoraire,
                'montant_total' => $mTotal
            ];
        }

        // 2. Allowances & Bonuses from Contract Components
        $totalPrimes = 0.00;
        $totalIndemnites = 0.00;
        $componentLines = [];

        foreach ($components as $comp) {
            $val = (float)($comp['valeur_numerique'] ?? 0.00);
            $nature = strtolower($comp['nature_composant'] ?? 'prime');
            if ($nature === 'prime') {
                $totalPrimes += $val;
            } else {
                $totalIndemnites += $val;
            }
            $componentLines[] = [
                'code_rubrique' => $comp['code_composant'],
                'libelle' => $comp['libelle'],
                'categorie' => $nature,
                'base_calcul' => $val,
                'taux' => 100.00,
                'montant_salarial' => $val,
                'montant_patronal' => 0.00,
                'est_imposable' => 1,
                'est_cotisable' => 1
            ];
        }

        // 3. Gross Total
        $totalBrut = round($salaireBase + $totalHeuresMontant + $totalPrimes + $totalIndemnites, 4);

        // 4. Cotisations & Taxes Breakdown
        $totalCotisationsSalariales = 0.00;
        $totalCotisationsPatronales = 0.00;
        $totalImpots = 0.00;
        $totalRetenues = 0.00;

        $rubriqueLines = [];
        $rulesSnapshots = [];

        // Always include Base Salary line
        $rubriqueLines[] = [
            'code_rubrique' => 'SALAIRE_BASE',
            'libelle' => 'Salaire de Base Contractuel',
            'categorie' => 'gain_base',
            'base_calcul' => $salaireBase,
            'taux' => 100.00,
            'montant_salarial' => $salaireBase,
            'montant_patronal' => 0.00,
            'ordre_affichage' => 10,
            'est_imposable' => 1,
            'est_cotisable' => 1
        ];

        if ($totalHeuresMontant > 0) {
            $rubriqueLines[] = [
                'code_rubrique' => 'HEURES_PEDAGOGIQUES',
                'libelle' => 'Rémunération Heures Pédagogiques Validées',
                'categorie' => 'gain_heures',
                'base_calcul' => $totalHeuresMontant,
                'taux' => 100.00,
                'montant_salarial' => $totalHeuresMontant,
                'montant_patronal' => 0.00,
                'ordre_affichage' => 20,
                'est_imposable' => 1,
                'est_cotisable' => 1
            ];
        }

        // Add component lines to rubriques
        $ordre = 30;
        foreach ($componentLines as $cline) {
            $cline['ordre_affichage'] = $ordre;
            $rubriqueLines[] = $cline;
            $ordre += 10;
        }

        // Apply Jurisdiction Rules
        foreach ($rules as $rule) {
            $code = $rule['code_regle'];
            $cat = $rule['categorie'];
            $mode = $rule['mode_calcul'];
            $mSal = 0.00;
            $mPat = 0.00;
            $ruleSnapshot = [
                'regle_id' => $rule['id'],
                'code_regle' => $code,
                'libelle' => $rule['libelle'],
                'categorie' => $cat,
                'mode_calcul' => $mode,
                'taux_applique' => (float)$rule['taux_par_defaut'],
                'tranches_snapshot' => [],
                'raw_json_snapshot' => $rule
            ];

            if ($mode === 'pourcentage') {
                $taux = (float)$rule['taux_par_defaut'];
                if ($cat === 'cotisation_salariale') {
                    $mSal = round(($totalBrut * $taux) / 100.0, 4);
                    $totalCotisationsSalariales += $mSal;
                } elseif ($cat === 'cotisation_patronale') {
                    $mPat = round(($totalBrut * $taux) / 100.0, 4);
                    $totalCotisationsPatronales += $mPat;
                }
                $rubriqueLines[] = [
                    'code_rubrique' => $code,
                    'libelle' => $rule['libelle'],
                    'categorie' => $cat,
                    'base_calcul' => $totalBrut,
                    'taux' => $taux,
                    'montant_salarial' => $mSal,
                    'montant_patronal' => $mPat,
                    'ordre_affichage' => 100 + $ordre,
                    'est_imposable' => 0,
                    'est_cotisable' => 0
                ];
            } elseif ($mode === 'bareme_progressif') {
                $netImposableProvisoire = max(0.00, $totalBrut - $totalCotisationsSalariales);
                $cumulImpot = 0.00;
                $tiers = $rule['tiers'] ?? [];

                foreach ($tiers as $t) {
                    $limInf = (float)$t['limite_inferieure'];
                    $limSup = $t['limite_superieure'] !== null ? (float)$t['limite_superieure'] : null;
                    $tauxT = (float)$t['taux'];

                    if ($netImposableProvisoire > $limInf) {
                        $assietteTranche = ($limSup !== null) ? min($netImposableProvisoire - $limInf, $limSup - $limInf) : ($netImposableProvisoire - $limInf);
                        $montantTranche = round(($assietteTranche * $tauxT) / 100.0, 4);
                        $cumulImpot += $montantTranche;

                        $ruleSnapshot['tranches_snapshot'][] = [
                            'tranche_numero' => $t['tranche_numero'],
                            'limite_inferieure' => $limInf,
                            'limite_superieure' => $limSup,
                            'taux' => $tauxT,
                            'base_imposable_tranche' => $assietteTranche,
                            'montant_calcule' => $montantTranche
                        ];
                    }
                }

                $mSal = round($cumulImpot, 4);
                $totalImpots += $mSal;
                $rubriqueLines[] = [
                    'code_rubrique' => $code,
                    'libelle' => $rule['libelle'],
                    'categorie' => $cat,
                    'base_calcul' => $netImposableProvisoire,
                    'taux' => 0.00,
                    'montant_salarial' => $mSal,
                    'montant_patronal' => 0.00,
                    'ordre_affichage' => 200 + $ordre,
                    'est_imposable' => 0,
                    'est_cotisable' => 0
                ];
            }

            $rulesSnapshots[] = $ruleSnapshot;
            $ordre += 10;
        }

        // Net Computations
        $netImposable = max(0.00, $totalBrut - $totalCotisationsSalariales);
        $netAPayer = max(0.00, $totalBrut - $totalCotisationsSalariales - $totalImpots - $totalRetenues);
        $coutTotalEmployeur = round($totalBrut + $totalCotisationsPatronales, 4);

        return [
            'salaire_base' => $salaireBase,
            'total_brut' => $totalBrut,
            'total_cotisations_salariales' => $totalCotisationsSalariales,
            'total_impots' => $totalImpots,
            'total_retenues' => $totalRetenues,
            'net_imposable' => $netImposable,
            'net_a_payer' => $netAPayer,
            'total_cotisations_patronales' => $totalCotisationsPatronales,
            'cout_total_employeur' => $coutTotalEmployeur,
            'heures_lines' => $heuresLines,
            'rubrique_lines' => $rubriqueLines,
            'rules_snapshots' => $rulesSnapshots
        ];
    }
}
