<?php

require_once __DIR__ . '/PaieRuleRepository.php';

class PaieCalculationEngine {

    /**
     * Compute a full bulletin breakdown given contract data, teacher hourly validations, components, rules, jurisdiction, and regularisations.
     */
    public static function computeBulletin(
        array $contract,
        array $cahierValidations = [],
        array $components = [],
        string $juridictionCode = 'RCA',
        ?string $asOfDate = null,
        array $regularisations = []
    ): array {
        $rules = PaieRuleRepository::getActiveRulesWithTiers($juridictionCode, $asOfDate);

        // Sort rules dynamically by explicit order_application first, then id
        usort($rules, function($a, $b) {
            $ordA = (int)($a['ordre_application'] ?? 100);
            $ordB = (int)($b['ordre_application'] ?? 100);
            if ($ordA === $ordB) {
                return (int)$a['id'] <=> (int)$b['id'];
            }
            return $ordA <=> $ordB;
        });

        // 1. Calculate Base Salary & Pedagogical Hours respecting Contract Mode
        $modeCalcul = strtolower($contract['mode_calcul_principal'] ?? ($contract['type_paiement'] ?? 'forfait_fixe'));
        if ($modeCalcul === 'taux_horaire') {
            $salaireBase = 0.00;
        } else {
            $salaireBase = (float)($contract['salaire_base'] ?? 0.00);
        }

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
            $code = strtoupper($comp['code_composant'] ?? '');

            // Skip rate reference components (such as TAUX_HORAIRE) from being counted as monthly allowances
            if ($nature === 'taux_horaire' || $code === 'TAUX_HORAIRE') {
                continue;
            }

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

        // 3. Process Regularisations
        $regulBrutDelta = 0.00;
        $regulNetDelta = 0.00;
        $regulLines = [];

        foreach ($regularisations as $reg) {
            $bDelta = (float)($reg['montant_brut_delta'] ?? 0.00);
            $nDelta = (float)($reg['montant_net_delta'] ?? 0.00);
            $motif = $reg['motif'] ?? 'Régularisation';

            if ($bDelta > 0) {
                $regulBrutDelta += $bDelta;
                $regulLines[] = [
                    'code_rubrique' => 'REGUL_BRUT',
                    'libelle' => "Rappel Brut: {$motif}",
                    'categorie' => 'gain_regularisation',
                    'base_calcul' => $bDelta,
                    'taux' => 100.00,
                    'montant_salarial' => $bDelta,
                    'montant_patronal' => 0.00,
                    'est_imposable' => 1,
                    'est_cotisable' => 1,
                    'regularisation_id' => $reg['id'] ?? null
                ];
            } elseif ($bDelta < 0) {
                $regulBrutDelta += $bDelta;
                $regulLines[] = [
                    'code_rubrique' => 'REGUL_RETENUE',
                    'libelle' => "Retenue Trop-Perçu: {$motif}",
                    'categorie' => 'retenue_regularisation',
                    'base_calcul' => abs($bDelta),
                    'taux' => 100.00,
                    'montant_salarial' => $bDelta,
                    'montant_patronal' => 0.00,
                    'est_imposable' => 1,
                    'est_cotisable' => 1,
                    'regularisation_id' => $reg['id'] ?? null
                ];
            }

            if ($nDelta != 0.00) {
                $regulNetDelta += $nDelta;
                $regulLines[] = [
                    'code_rubrique' => 'REGUL_NET',
                    'libelle' => "Ajustement Net: {$motif}",
                    'categorie' => 'ajustement_net',
                    'base_calcul' => abs($nDelta),
                    'taux' => 100.00,
                    'montant_salarial' => $nDelta,
                    'montant_patronal' => 0.00,
                    'est_imposable' => 0,
                    'est_cotisable' => 0,
                    'regularisation_id' => $reg['id'] ?? null
                ];
            }
        }

        // 4. Gross Total (incorporating REGUL_BRUT / REGUL_RETENUE)
        $totalBrut = max(0.00, round($salaireBase + $totalHeuresMontant + $totalPrimes + $totalIndemnites + $regulBrutDelta, 4));

        // 5. Initialise Accumulators for Dynamic Breakdown
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

        $ordre = 30;
        foreach ($componentLines as $cline) {
            $cline['ordre_affichage'] = $ordre;
            $rubriqueLines[] = $cline;
            $ordre += 10;
        }

        foreach ($regulLines as $rline) {
            $rline['ordre_affichage'] = $ordre;
            $rubriqueLines[] = $rline;
            $ordre += 10;
        }

        // Filter rules by Contract Type if specified on rule
        $contractTypeId = (int)($contract['type_contrat_id'] ?? 0);

        // 6. Execute Dynamic Rules Engine
        foreach ($rules as $rule) {
            if (!empty($rule['type_contrat_id']) && (int)$rule['type_contrat_id'] !== $contractTypeId) {
                continue; // Skip rules limited to a different contract type
            }

            $code = $rule['code_regle'];
            $cat = $rule['categorie'];
            $mode = $rule['mode_calcul'];
            $baseType = strtolower($rule['base_calcul_type'] ?? 'brut_total');

            // Determine raw calculation base
            if ($baseType === 'salaire_base') {
                $rawAssiette = $salaireBase;
            } elseif ($baseType === 'net_imposable' || $baseType === 'net_imposable_provisoire') {
                $rawAssiette = max(0.00, $totalBrut - $totalCotisationsSalariales);
            } elseif ($baseType === 'heures_validees') {
                $rawAssiette = $totalHeuresMontant;
            } else { // 'brut_total' or default
                $rawAssiette = $totalBrut;
            }

            // Apply Abattements if configured
            $abattementForfaitaire = (float)($rule['abattement_forfaitaire'] ?? 0.00);
            $abattementPourcentage = (float)($rule['abattement_pourcentage'] ?? 0.00);

            $assietteApresAbattement = max(0.00, $rawAssiette - $abattementForfaitaire);
            if ($abattementPourcentage > 0) {
                $assietteApresAbattement = max(0.00, $assietteApresAbattement * (1.0 - ($abattementPourcentage / 100.0)));
            }

            // Apply Seuil Minimum and Plafond Maximum
            $seuilMin = isset($rule['seuil_minimum']) && $rule['seuil_minimum'] !== null ? (float)$rule['seuil_minimum'] : null;
            $plafondMax = isset($rule['plafond_maximum']) && $rule['plafond_maximum'] !== null ? (float)$rule['plafond_maximum'] : null;

            if ($seuilMin !== null && $assietteApresAbattement < $seuilMin) {
                $assietteCalcul = 0.00;
            } else {
                $assietteCalcul = $assietteApresAbattement;
                if ($plafondMax !== null && $assietteCalcul > $plafondMax) {
                    $assietteCalcul = $plafondMax;
                }
            }

            $mSal = 0.00;
            $mPat = 0.00;
            $tauxSal = (float)($rule['taux_par_defaut'] ?? 0.00);
            $tauxPat = (float)($rule['taux_patronal'] ?? 0.00);
            $fixedSal = (float)($rule['montant_fixe_salarial'] ?? 0.00);
            $fixedPat = (float)($rule['montant_fixe_patronal'] ?? 0.00);

            $ruleSnapshot = [
                'regle_id' => $rule['id'],
                'code_regle' => $code,
                'libelle' => $rule['libelle'],
                'categorie' => $cat,
                'mode_calcul' => $mode,
                'taux_applique' => $tauxSal,
                'tranches_snapshot' => [],
                'raw_json_snapshot' => $rule
            ];

            if ($mode === 'pourcentage' || $mode === 'montant_fixe') {
                if ($assietteCalcul > 0 || $fixedSal > 0 || $fixedPat > 0) {
                    if ($tauxSal > 0) {
                        $mSal += round(($assietteCalcul * $tauxSal) / 100.0, 4);
                    }
                    $mSal += $fixedSal;

                    if ($tauxPat > 0) {
                        $mPat += round(($assietteCalcul * $tauxPat) / 100.0, 4);
                    }
                    $mPat += $fixedPat;
                }

                if ($cat === 'cotisation_salariale') {
                    $totalCotisationsSalariales += $mSal;
                } elseif ($cat === 'cotisation_patronale') {
                    $totalCotisationsPatronales += $mPat;
                } elseif ($cat === 'impot') {
                    $totalImpots += $mSal;
                } elseif ($cat === 'retenue') {
                    $totalRetenues += $mSal;
                }

                $rubriqueLines[] = [
                    'code_rubrique' => $code,
                    'libelle' => $rule['libelle'],
                    'categorie' => $cat,
                    'base_calcul' => $assietteCalcul,
                    'taux' => $tauxSal,
                    'montant_salarial' => $mSal,
                    'montant_patronal' => $mPat,
                    'ordre_affichage' => (int)($rule['ordre_application'] ?? (100 + $ordre)),
                    'est_imposable' => 0,
                    'est_cotisable' => 0
                ];
            } elseif ($mode === 'bareme_progressif') {
                $cumulImpot = 0.00;
                $tiers = $rule['tiers'] ?? [];

                foreach ($tiers as $t) {
                    $limInf = (float)$t['limite_inferieure'];
                    $limSup = $t['limite_superieure'] !== null ? (float)$t['limite_superieure'] : null;
                    $tauxT = (float)$t['taux'];
                    $fixedT = (float)($t['montant_fixe'] ?? 0.00);

                    if ($assietteCalcul > $limInf) {
                        $assietteTranche = ($limSup !== null) ? min($assietteCalcul - $limInf, $limSup - $limInf) : ($assietteCalcul - $limInf);
                        $montantTranche = round(($assietteTranche * $tauxT) / 100.0, 4) + $fixedT;
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

                if ($cat === 'impot') {
                    $totalImpots += $mSal;
                } elseif ($cat === 'cotisation_salariale') {
                    $totalCotisationsSalariales += $mSal;
                } elseif ($cat === 'cotisation_patronale') {
                    $totalCotisationsPatronales += $mSal;
                } else {
                    $totalRetenues += $mSal;
                }

                $rubriqueLines[] = [
                    'code_rubrique' => $code,
                    'libelle' => $rule['libelle'],
                    'categorie' => $cat,
                    'base_calcul' => $assietteCalcul,
                    'taux' => 0.00,
                    'montant_salarial' => $mSal,
                    'montant_patronal' => 0.00,
                    'ordre_affichage' => (int)($rule['ordre_application'] ?? (200 + $ordre)),
                    'est_imposable' => 0,
                    'est_cotisable' => 0
                ];
            }

            $rulesSnapshots[] = $ruleSnapshot;
            $ordre += 10;
        }

        // Net Computations
        $netImposable = max(0.00, $totalBrut - $totalCotisationsSalariales);
        $netAPayer = max(0.00, round($totalBrut - $totalCotisationsSalariales - $totalImpots - $totalRetenues + $regulNetDelta, 4));
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
            'rules_snapshots' => $rulesSnapshots,
            'regularisations_consumed' => $regularisations
        ];
    }
}
