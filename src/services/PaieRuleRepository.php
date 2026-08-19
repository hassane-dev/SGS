<?php

require_once __DIR__ . '/../models/PaieRegleCalcul.php';
require_once __DIR__ . '/../models/PaieBaremeTranche.php';

class PaieRuleRepository {
    /**
     * Get active calculation rules for a given jurisdiction and date, including their brackets/tiers.
     */
    public static function getActiveRulesWithTiers(string $juridictionCode = 'DEFAULT', ?string $asOfDate = null): array {
        $rules = PaieRegleCalcul::findActiveRulesForJurisdiction($juridictionCode, $asOfDate);
        foreach ($rules as &$rule) {
            $rule['tiers'] = PaieBaremeTranche::findByRegleId($rule['id']);
        }
        return $rules;
    }

    /**
     * Ensure default calculation rules (e.g., CNSS, IUTS / Taxes, Retenues) are seeded.
     */
    public static function seedDefaultRulesIfNeeded(): void {
        $existing = PaieRegleCalcul::findActiveRulesForJurisdiction('DEFAULT');
        if (!empty($existing)) {
            return;
        }

        // Rule 1: Cotisation CNSS Salariale (5.5%)
        $cnssSalId = PaieRegleCalcul::create([
            'juridiction_code' => 'DEFAULT',
            'code_regle' => 'CNSS_SALARIALE',
            'libelle' => 'Cotisation CNSS (Part Salariale)',
            'categorie' => 'cotisation_salariale',
            'mode_calcul' => 'pourcentage',
            'taux_par_defaut' => 5.5000,
            'est_systeme' => 1,
            'actif' => 1,
            'date_debut_validite' => '2024-01-01'
        ]);

        // Rule 2: Cotisation CNSS Patronale (16.0%)
        $cnssPatId = PaieRegleCalcul::create([
            'juridiction_code' => 'DEFAULT',
            'code_regle' => 'CNSS_PATRONALE',
            'libelle' => 'Cotisation CNSS (Part Patronale)',
            'categorie' => 'cotisation_patronale',
            'mode_calcul' => 'pourcentage',
            'taux_par_defaut' => 16.0000,
            'est_systeme' => 1,
            'actif' => 1,
            'date_debut_validite' => '2024-01-01'
        ]);

        // Rule 3: Impôt sur le Revenu / IUTS (Progressif par barème)
        $iutsId = PaieRegleCalcul::create([
            'juridiction_code' => 'DEFAULT',
            'code_regle' => 'IUTS_IMPOT',
            'libelle' => 'Impôt Général sur le Revenu (IUTS)',
            'categorie' => 'impot',
            'mode_calcul' => 'bareme_progressif',
            'taux_par_defaut' => 0.0000,
            'est_systeme' => 1,
            'actif' => 1,
            'date_debut_validite' => '2024-01-01'
        ]);

        // Seed IUTS brackets
        PaieBaremeTranche::create(['regle_id' => $iutsId, 'tranche_numero' => 1, 'limite_inferieure' => 0.00, 'limite_superieure' => 50000.00, 'taux' => 0.0000, 'montant_fixe' => 0.00]);
        PaieBaremeTranche::create(['regle_id' => $iutsId, 'tranche_numero' => 2, 'limite_inferieure' => 50000.00, 'limite_superieure' => 150000.00, 'taux' => 10.0000, 'montant_fixe' => 0.00]);
        PaieBaremeTranche::create(['regle_id' => $iutsId, 'tranche_numero' => 3, 'limite_inferieure' => 150000.00, 'limite_superieure' => 300000.00, 'taux' => 15.0000, 'montant_fixe' => 0.00]);
        PaieBaremeTranche::create(['regle_id' => $iutsId, 'tranche_numero' => 4, 'limite_inferieure' => 300000.00, 'limite_superieure' => null, 'taux' => 20.0000, 'montant_fixe' => 0.00]);
    }
}
