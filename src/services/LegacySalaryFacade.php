<?php

require_once __DIR__ . '/../models/Salaire.php';
require_once __DIR__ . '/../models/PaieBulletin.php';

class LegacySalaryFacade {

    /**
     * Import historical legacy salary records into the new Paie bulletin structure with est_reprise_legacy = 1.
     */
    public static function importLegacySalairesToPaie(int $periodePaieId, int $userId): int {
        $db = Database::getInstance();

        // Find legacy salaires records
        $stmt = $db->query("SELECT * FROM salaires WHERE 1=1");
        $salaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $importedCount = 0;

        foreach ($salaires as $s) {
            $personnelId = (int)($s['personnel_id'] ?? $s['user_id'] ?? 0);
            $montantNet = (float)($s['montant_net'] ?? $s['montant'] ?? 0.00);

            // Find staff active contract or legal entity
            $stmtC = $db->prepare("
                SELECT id, entite_juridique_id, devise
                FROM personnel_contrats_historique
                WHERE personnel_id = :personnel_id AND statut_contrat = 'actif'
                ORDER BY version_num DESC LIMIT 1
            ");
            $stmtC->execute(['personnel_id' => $personnelId]);
            $contrat = $stmtC->fetch(PDO::FETCH_ASSOC);

            $contratId = $contrat ? (int)$contrat['id'] : 1;
            $entiteJuridiqueId = ($contrat && $contrat['entite_juridique_id']) ? (int)$contrat['entite_juridique_id'] : 1;
            $devise = ($contrat && $contrat['devise']) ? $contrat['devise'] : 'FCFA';

            // Check if legacy bulletin already exists
            $existing = PaieBulletin::findActiveForContractAndPeriod($personnelId, $entiteJuridiqueId, $contratId, $periodePaieId);
            if (!$existing) {
                PaieBulletin::create([
                    'periode_id' => $periodePaieId,
                    'personnel_id' => $personnelId,
                    'contrat_id' => $contratId,
                    'version_num' => 1,
                    'est_version_active' => 1,
                    'entite_juridique_id' => $entiteJuridiqueId,
                    'salaire_base' => $montantNet,
                    'total_brut' => $montantNet,
                    'total_cotisations_salariales' => 0.00,
                    'total_impots' => 0.00,
                    'total_retenues' => 0.00,
                    'net_imposable' => $montantNet,
                    'net_a_payer' => $montantNet,
                    'total_cotisations_patronales' => 0.00,
                    'cout_total_employeur' => $montantNet,
                    'devise' => $devise,
                    'statut_bulletin' => 'valide',
                    'statut_comptabilisation' => 'non_comptabilise',
                    'statut_reglement' => 'non_paye',
                    'statut_cloture' => 'ouvert',
                    'est_reprise_legacy' => 1
                ]);
                $importedCount++;
            }
        }

        return $importedCount;
    }
}
