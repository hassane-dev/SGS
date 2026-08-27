<?php

require_once __DIR__ . '/../models/TreasuryService.php';

class PaieTreasuryAdapter {

    /**
     * Settle a bulletin payout through TreasuryService and log movement in mouvements_tresorerie.
     */
    public static function settleBulletin(array $bulletin, int $compteFinancierId, string $modeReglement, int $userId, ?string $reference = null): int {
        $db = Database::getInstance();

        // Retrieve financial account to resolve lycee_id
        $stmtCf = $db->prepare("SELECT lycee_id FROM comptes_financiers WHERE id = :id");
        $stmtCf->execute(['id' => $compteFinancierId]);
        $lyceeId = (int)$stmtCf->fetchColumn();

        // Resolve active financial exercise for Lycée
        $stmtEx = $db->prepare("SELECT id FROM exercices_financiers WHERE lycee_id = :lycee_id AND est_actif = 1 LIMIT 1");
        $stmtEx->execute(['lycee_id' => $lyceeId]);
        $exerciceId = (int)$stmtEx->fetchColumn();

        if (!$exerciceId) {
            $stmtEx2 = $db->prepare("SELECT id FROM exercices_financiers WHERE lycee_id = :lycee_id LIMIT 1");
            $stmtEx2->execute(['lycee_id' => $lyceeId]);
            $exerciceId = (int)$stmtEx2->fetchColumn() ?: 1;
        }

        $amount = (float)$bulletin['net_a_payer'];

        $movementData = [
            'lycee_id' => $lyceeId,
            'compte_id' => $compteFinancierId,
            'exercice_financier_id' => $exerciceId,
            'type_mouvement' => 'sortie',
            'montant' => $amount,
            'mode_paiement' => $modeReglement,
            'reference_transaction' => $reference ?: ('PAIE-PAY-' . $bulletin['id']),
            'source_type' => 'paie_bulletins',
            'source_id' => (int)$bulletin['id'],
            'evenement_type' => 'reglement_fournisseur', // Standard movement event for disbursements
            'motif' => 'Règlement Salaire Bulletin #' . $bulletin['id'],
            'user_id' => $userId
        ];

        return TreasuryService::registerMovement($movementData);
    }
}
