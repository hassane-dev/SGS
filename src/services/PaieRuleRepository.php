<?php

require_once __DIR__ . '/../models/PaieRegleCalcul.php';
require_once __DIR__ . '/../models/PaieBaremeTranche.php';
require_once __DIR__ . '/../config/database.php';

class PaieRuleRepository {

    /**
     * Resolve the country jurisdiction code for a given establishment (lycee_id).
     */
    public static function resolveJurisdictionForLycee(?int $lyceeId = null): string {
        if (!$lyceeId) {
            return 'RCA';
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT devise_pays FROM param_general WHERE lycee_id = :lycee_id LIMIT 1");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $devisePays = $stmt->fetchColumn();

        if ($devisePays) {
            $code = strtoupper(trim($devisePays));
            if (in_array($code, ['XAF', 'FCFA', 'RCA', 'CENTRAFRIQUE', 'CENTRAFRICAINE'])) {
                return 'RCA';
            }
            if (in_array($code, ['CMR', 'CAMEROUN', 'CAMEROON'])) {
                return 'CMR';
            }
            if (in_array($code, ['GAB', 'GABON'])) {
                return 'GAB';
            }
            return $code;
        }

        return 'RCA';
    }

    /**
     * Get active calculation rules for a given jurisdiction/country and date, including their brackets/tiers.
     */
    public static function getActiveRulesWithTiers(string $juridictionCode = 'RCA', ?string $asOfDate = null): array {
        self::seedDefaultRulesIfNeeded();
        $rules = PaieRegleCalcul::findActiveRulesForJurisdiction($juridictionCode, $asOfDate);
        foreach ($rules as &$rule) {
            $rule['tiers'] = PaieBaremeTranche::findByRegleId($rule['id']);
        }
        return $rules;
    }

    /**
     * Ensure default calculation rules (e.g., CNSS, IUTS / Taxes) are seeded.
     */
    public static function seedDefaultRulesIfNeeded(): void {
        $existing = PaieRegleCalcul::findActiveRulesForJurisdiction('RCA');
        if (!empty($existing)) {
            return;
        }

        // Rule 1: Cotisation CNSS (Part Salariale 5.5%, Part Patronale 16.0%)
        $cnssSalId = PaieRegleCalcul::create([
            'juridiction_code' => 'RCA',
            'pays_code' => 'RCA',
            'code_regle' => 'CNSS_SALARIALE',
            'libelle' => 'Cotisation CNSS (Part Salariale)',
            'categorie' => 'cotisation_salariale',
            'mode_calcul' => 'pourcentage',
            'base_calcul_type' => 'brut_total',
            'taux_par_defaut' => 5.5000,
            'taux_patronal' => 0.0000,
            'ordre_application' => 100,
            'est_systeme' => 1,
            'actif' => 1,
            'date_debut_validite' => '2024-01-01'
        ]);

        $cnssPatId = PaieRegleCalcul::create([
            'juridiction_code' => 'RCA',
            'pays_code' => 'RCA',
            'code_regle' => 'CNSS_PATRONALE',
            'libelle' => 'Cotisation CNSS (Part Patronale)',
            'categorie' => 'cotisation_patronale',
            'mode_calcul' => 'pourcentage',
            'base_calcul_type' => 'brut_total',
            'taux_par_defaut' => 0.0000,
            'taux_patronal' => 16.0000,
            'ordre_application' => 300,
            'est_systeme' => 1,
            'actif' => 1,
            'date_debut_validite' => '2024-01-01'
        ]);

        // Rule 2: Impôt Général sur le Revenu / IUTS (Progressif par barème)
        $iutsId = PaieRegleCalcul::create([
            'juridiction_code' => 'RCA',
            'pays_code' => 'RCA',
            'code_regle' => 'IUTS_IMPOT',
            'libelle' => 'Impôt Général sur le Revenu (IUTS)',
            'categorie' => 'impot',
            'mode_calcul' => 'bareme_progressif',
            'base_calcul_type' => 'net_imposable_provisoire',
            'taux_par_defaut' => 0.0000,
            'ordre_application' => 200,
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
