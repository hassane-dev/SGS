<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ModePaiement.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../models/SessionCaisse.php';

class PaymentRoutingService {

    /**
     * Resolves the target financial account and session requirement for a given mode and input dataset.
     */
    public static function resolveDestination($lyceeId, $modePaiementIdOrCode, $userId, array $options = []) {
        $db = Database::getInstance();

        // 1. Resolve ModePaiement entity
        if (is_numeric($modePaiementIdOrCode)) {
            $mode = ModePaiement::findById($modePaiementIdOrCode);
        } else {
            $mode = ModePaiement::findByCode($lyceeId, (string)$modePaiementIdOrCode);
            if (!$mode) {
                // Map legacy mode strings
                ModePaiement::seedDefaultsForLycee($lyceeId);
                $codeMap = [
                    'especes' => 'ESPECES',
                    'mobile money' => 'MOMO_ORANGE',
                    'orange money' => 'MOMO_ORANGE',
                    'mtn mobile money' => 'MOMO_MTN',
                    'cheque' => 'CHEQUE',
                    'virement' => 'VIREMENT'
                ];
                $cleanKey = str_replace(['é', 'è', 'ê', 'ë'], 'e', mb_strtolower(trim($modePaiementIdOrCode), 'UTF-8'));
                $targetCode = $codeMap[$cleanKey] ?? 'ESPECES';
                $mode = ModePaiement::findByCode($lyceeId, $targetCode);
            }
        }

        if (!$mode || (int)$mode['lycee_id'] !== (int)$lyceeId) {
            throw new Exception("Mode de paiement invalide pour cet établissement.");
        }

        if (empty($mode['actif'])) {
            throw new Exception("Le mode de paiement '" . $mode['libelle'] . "' est actuellement désactivé.");
        }

        $typeCanal = $mode['type_canal'];
        $targetCompteId = null;
        $sessionCaisseId = null;

        // 2. Channel-based Routing Logic
        if ($typeCanal === 'especes') {
            // A. Espèces -> Must be routed to active physical Cash Desk session
            $providedCompteId = $options['compte_id'] ?? null;
            if ($providedCompteId) {
                $compteObj = CompteFinancier::findById($providedCompteId);
                if ($compteObj && (int)$compteObj['lycee_id'] === (int)$lyceeId && empty($compteObj['est_coffre']) && $compteObj['type_compte'] === 'caisse') {
                    $openSession = SessionCaisse::findOpenByCompte($providedCompteId);
                    if ($openSession) {
                        $targetCompteId = (int)$providedCompteId;
                        $sessionCaisseId = (int)$openSession['id'];
                    }
                }
            }

            if (!$targetCompteId) {
                // Find active session for user
                $userSession = SessionCaisse::findActiveByUser($userId, $lyceeId);
                if ($userSession) {
                    $targetCompteId = (int)$userSession['compte_id'];
                    $sessionCaisseId = (int)$userSession['id'];
                }
            }

            if (!$targetCompteId && empty($options['is_historical_migration']) && empty($options['is_technical_closure'])) {
                throw new Exception("Veuillez ouvrir votre session de caisse journalière avant d'encaisser un paiement en espèces.");
            }

            // Fallback for historical migration
            if (!$targetCompteId) {
                $stmtCaisseDef = $db->prepare("
                    SELECT id FROM comptes_financiers
                    WHERE lycee_id = :lycee_id AND type_compte = 'caisse' AND est_coffre = 0 AND statut = 'actif'
                    LIMIT 1
                ");
                $stmtCaisseDef->execute(['lycee_id' => $lyceeId]);
                $targetCompteId = (int)$stmtCaisseDef->fetchColumn();
            }

        } else {
            // B. Virtual Channels (Mobile Money, Banque, Autre) -> Routed to virtual account, session_caisse_id = NULL
            $sessionCaisseId = null;

            // 1. Account specified directly on the mode
            if (!empty($mode['compte_financier_id'])) {
                $targetCompteId = (int)$mode['compte_financier_id'];
            }

            // 2. Account explicitly provided in options
            if (!$targetCompteId && !empty($options['compte_id'])) {
                $targetCompteId = (int)$options['compte_id'];
            }

            // 3. Fallback: Find active account matching channel type or create default virtual account
            if (!$targetCompteId) {
                $stmtVirtual = $db->prepare("
                    SELECT id FROM comptes_financiers
                    WHERE lycee_id = :lycee_id AND type_compte = :type_canal AND statut = 'actif'
                    LIMIT 1
                ");
                $stmtVirtual->execute(['lycee_id' => $lyceeId, 'type_canal' => $typeCanal]);
                $targetCompteId = $stmtVirtual->fetchColumn();

                if (!$targetCompteId) {
                    // Fallback to any non-coffre active account or default
                    $stmtDefVirtual = $db->prepare("
                        SELECT id FROM comptes_financiers
                        WHERE lycee_id = :lycee_id AND est_coffre = 0 AND statut = 'actif'
                        ORDER BY (CASE WHEN type_compte = :type_canal THEN 1 ELSE 2 END)
                        LIMIT 1
                    ");
                    $stmtDefVirtual->execute(['lycee_id' => $lyceeId, 'type_canal' => $typeCanal]);
                    $targetCompteId = (int)$stmtDefVirtual->fetchColumn();
                }
            }
        }

        if (!$targetCompteId) {
            throw new Exception("Aucun compte financier destinataire n'a pu être résolu pour le mode '" . $mode['libelle'] . "'.");
        }

        // Verify Vault Safety Invariant
        $targetCompte = CompteFinancier::findById($targetCompteId);
        if (!empty($targetCompte['est_coffre'])) {
            throw new Exception("Sécurité Trésorerie : Un paiement ordinaire ne peut pas être crédité directement sur le Coffre Principal.");
        }

        return [
            'mode' => $mode,
            'compte_id' => (int)$targetCompteId,
            'session_caisse_id' => $sessionCaisseId
        ];
    }
}
