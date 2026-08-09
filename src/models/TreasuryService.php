<?php

require_once __DIR__ . '/CompteFinancier.php';
require_once __DIR__ . '/ExerciceFinancier.php';
require_once __DIR__ . '/SessionCaisse.php';

class TreasuryService {

    /**
     * Enregistre de façon sécurisée un mouvement de trésorerie dans le grand livre.
     * Cette méthode doit être appelée au sein d'une transaction PDO partagée.
     */
    public static function registerMovement($data) {
        $db = Database::getInstance();

        // 1. Validation de la non-négativité
        $montant = (float)$data['montant'];
        if ($montant <= 0) {
            throw new Exception("Le montant du mouvement financier doit être strictement positif.");
        }

        $lyceeId = $data['lycee_id'];
        $sourceType = $data['source_type'];
        $sourceId = $data['source_id'];
        $evenementType = $data['evenement_type'];
        $modePaiement = $data['mode_paiement'] ?? 'Espèces';

        // 2. Résolution défensive de l'exercice financier
        $exerciceId = $data['exercice_financier_id'] ?? null;
        if (!$exerciceId) {
            $exerciceActif = ExerciceFinancier::findActive($lyceeId);
            if (!$exerciceActif) {
                // Auto-génération d'un exercice actif par défaut si absent
                $currentYear = date('Y');
                $exerciceId = ExerciceFinancier::create([
                    'lycee_id' => $lyceeId,
                    'libelle' => "Exercice " . $currentYear,
                    'date_debut' => $currentYear . "-01-01",
                    'date_fin' => $currentYear . "-12-31",
                    'est_actif' => 1
                ]);
            } else {
                $exerciceId = $exerciceActif['id'];
            }
        }

        // 3. Résolution défensive du compte financier
        $compteId = $data['compte_id'] ?? null;
        if (!$compteId) {
            // Associer le type de compte au mode de paiement
            $typeCompte = 'caisse';
            if (in_array(strtolower($modePaiement), ['chèque', 'cheque', 'virement', 'banque'])) {
                $typeCompte = 'banque';
            } elseif (in_array(strtolower($modePaiement), ['mobile money', 'momo', 'paiement mobile', 'mobile'])) {
                $typeCompte = 'mobile_money';
            }

            // Si espèces et qu'une session de caisse est active, on utilise prioritairement son compte
            if ($typeCompte === 'caisse') {
                $activeSession = SessionCaisse::findActiveByUser($data['user_id'] ?? Auth::getUserId(), $lyceeId);
                if ($activeSession) {
                    $compteId = $activeSession['compte_id'];
                }
            }

            if (!$compteId) {
                // Chercher si un compte de ce type existe déjà
                $stmt = $db->prepare("
                    SELECT id FROM comptes_financiers
                    WHERE lycee_id = :lycee_id AND type_compte = :type_compte AND statut = 'actif'
                    LIMIT 1
                ");
                $stmt->execute(['lycee_id' => $lyceeId, 'type_compte' => $typeCompte]);
                $compteId = $stmt->fetchColumn();
            }

            if (!$compteId) {
                // Créer un compte par défaut de ce type
                $nomCompte = "Caisse Principale";
                if ($typeCompte === 'banque') {
                    $nomCompte = "Compte Courant Banque";
                } elseif ($typeCompte === 'mobile_money') {
                    $nomCompte = "Compte Mobile Money";
                }

                $compteId = CompteFinancier::create([
                    'lycee_id' => $lyceeId,
                    'nom_compte' => $nomCompte,
                    'type_compte' => $typeCompte,
                    'solde_courant' => 0.00
                ]);
            }
        }

        // 4. Vérification d'idempotence technique amont
        $stmt_check = $db->prepare("
            SELECT COUNT(*) FROM mouvements_tresorerie
            WHERE compte_id = :compte_id
              AND source_type = :source_type
              AND source_id = :source_id
              AND evenement_type = :evenement_type
        ");
        $stmt_check->execute([
            'compte_id' => $compteId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'evenement_type' => $evenementType
        ]);
        if ($stmt_check->fetchColumn() > 0) {
            return true;
        }

        // 5. Validation des règles métiers et de la cohérence financière amont
        self::validateBusinessRules($db, $data);

        $compte = CompteFinancier::findById($compteId);
        if (!$compte) {
            throw new Exception("Compte financier introuvable.");
        }
        if ($compte['statut'] !== 'actif') {
            throw new Exception("Ce compte financier est suspendu.");
        }

        // 6. Récupération de la session de caisse si applicable
        $sessionCaisseId = $data['session_caisse_id'] ?? null;
        $modeClean = str_replace(['é', 'è', 'ê', 'ë'], 'e', mb_strtolower(trim($modePaiement), 'UTF-8'));
        $isEspeces = in_array($modeClean, ['especes']);
        if (!$sessionCaisseId && $compte['type_compte'] === 'caisse' && $isEspeces) {
            $activeSession = SessionCaisse::findActiveByCompte($compteId);
            // S'il n'y a pas de session active en mode interactif, on n'impose pas le blocage en migration historique
            if (!$activeSession && empty($data['is_historical_migration'])) {
                // Créer automatiquement une session par défaut pour l'utilisateur en cours
                $sessionCaisseId = SessionCaisse::ouvrir([
                    'lycee_id' => $lyceeId,
                    'user_id' => $data['user_id'],
                    'compte_id' => $compteId,
                    'solde_ouverture' => (float)$compte['solde_courant']
                ]);
            } elseif ($activeSession) {
                $sessionCaisseId = $activeSession['id'];
            }
        }

        // 7. Détermination automatique du sens du mouvement
        $typeMouvement = $data['type_mouvement'] ?? null;
        if (!$typeMouvement) {
            if (in_array($evenementType, ['encaissement', 'correction'])) {
                $typeMouvement = 'entree';
            } else {
                $typeMouvement = 'sortie';
            }
        }

        // 8. Insertion physique dans le grand livre
        $stmt_ins = $db->prepare("
            INSERT INTO mouvements_tresorerie (
                lycee_id, compte_id, session_caisse_id, exercice_financier_id, transfert_id,
                type_mouvement, montant, mode_paiement, reference_transaction,
                source_type, source_id, evenement_type, motif, user_id,
                is_aggregate_data, date_reconstruite, is_historical_migration, mode_paiement_reconstruit
            ) VALUES (
                :lycee_id, :compte_id, :session_caisse_id, :exercice_financier_id, :transfert_id,
                :type_mouvement, :montant, :mode_paiement, :reference_transaction,
                :source_type, :source_id, :evenement_type, :motif, :user_id,
                :is_aggregate_data, :date_reconstruite, :is_historical_migration, :mode_paiement_reconstruit
            )
        ");

        $stmt_ins->execute([
            'lycee_id' => $lyceeId,
            'compte_id' => $compteId,
            'session_caisse_id' => $sessionCaisseId,
            'exercice_financier_id' => $exerciceId,
            'transfert_id' => $data['transfert_id'] ?? null,
            'type_mouvement' => $typeMouvement,
            'montant' => $montant,
            'mode_paiement' => $modePaiement,
            'reference_transaction' => $data['reference_transaction'] ?? null,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'evenement_type' => $evenementType,
            'motif' => $data['motif'],
            'user_id' => $data['user_id'],
            'is_aggregate_data' => !empty($data['is_aggregate_data']) ? 1 : 0,
            'date_reconstruite' => !empty($data['date_reconstruite']) ? 1 : 0,
            'is_historical_migration' => !empty($data['is_historical_migration']) ? 1 : 0,
            'mode_paiement_reconstruit' => !empty($data['mode_paiement_reconstruit']) ? 1 : 0
        ]);

        $movementId = $db->lastInsertId();

        // 9. Mise à jour synchrone et atomique du cache du solde et de la session de caisse
        CompteFinancier::updateSolde($compteId, $montant, $typeMouvement);

        if ($sessionCaisseId) {
            $session = SessionCaisse::findById($sessionCaisseId);
            if ($session) {
                // On n'incrémente pas le solde théorique opérationnel de la session pour les corrections/régularisations d'écarts de clôture
                if ($evenementType !== 'correction') {
                    $theorique = (float)$session['solde_theorique'];
                    if ($typeMouvement === 'entree') {
                        $theorique += $montant;
                    } else {
                        $theorique -= $montant;
                    }
                    $stmt_session = $db->prepare("UPDATE sessions_caisse SET solde_theorique = :theo WHERE id = :id");
                    $stmt_session->execute(['theo' => $theorique, 'id' => $sessionCaisseId]);
                }
            }
        }

        return $movementId;
    }

    /**
     * Valide de façon rigoureuse les règles métiers de l'opération demandée.
     */
    private static function validateBusinessRules($db, $data) {
        $sourceType = $data['source_type'];
        $sourceId = $data['source_id'];
        $evenementType = $data['evenement_type'];
        $montant = (float)$data['montant'];

        // Règle 1 : Incompatibilité et limite cumulée des remboursements (Modèle B)
        if ($evenementType === 'remboursement') {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM mouvements_tresorerie
                WHERE source_type = :source_type AND source_id = :source_id AND evenement_type = 'annulation'
            ");
            $stmt->execute(['source_type' => $sourceType, 'source_id' => $sourceId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("L'opération d'origine a été entièrement annulée. Tout remboursement est interdit.");
            }

            $stmt_enc = $db->prepare("
                SELECT SUM(montant) FROM mouvements_tresorerie
                WHERE source_type = :source_type AND source_id = :source_id AND evenement_type = 'encaissement'
            ");
            $stmt_enc->execute(['source_type' => $sourceType, 'source_id' => $sourceId]);
            $totalEncaisse = (float)$stmt_enc->fetchColumn() ?: 0.00;

            $stmt_rem = $db->prepare("
                SELECT SUM(montant) FROM mouvements_tresorerie
                WHERE source_type = :source_type AND source_id = :source_id AND evenement_type = 'remboursement'
            ");
            $stmt_rem->execute(['source_type' => $sourceType, 'source_id' => $sourceId]);
            $totalRembourse = (float)$stmt_rem->fetchColumn() ?: 0.00;

            if ($totalRembourse + $montant > $totalEncaisse + 0.01) {
                throw new Exception("Le cumul des remboursements dépasse le montant total réellement encaissé.");
            }
        }

        // Règle 2 : Une opération déjà annulée ne peut plus être annulée, ni remboursée
        if ($evenementType === 'annulation') {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM mouvements_tresorerie
                WHERE source_type = :source_type AND source_id = :source_id AND evenement_type = 'annulation'
            ");
            $stmt->execute(['source_type' => $sourceType, 'source_id' => $sourceId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("L'opération a déjà fait l'objet d'une annulation.");
            }

            $stmt_enc = $db->prepare("
                SELECT SUM(montant) FROM mouvements_tresorerie
                WHERE source_type = :source_type AND source_id = :source_id AND evenement_type = 'encaissement'
            ");
            $stmt_enc->execute(['source_type' => $sourceType, 'source_id' => $sourceId]);
            $totalEncaisse = (float)$stmt_enc->fetchColumn() ?: 0.00;

            $stmt_rem = $db->prepare("
                SELECT SUM(montant) FROM mouvements_tresorerie
                WHERE source_type = :source_type AND source_id = :source_id AND evenement_type = 'remboursement'
            ");
            $stmt_rem->execute(['source_type' => $sourceType, 'source_id' => $sourceId]);
            $totalRembourse = (float)$stmt_rem->fetchColumn() ?: 0.00;

            if ($totalRembourse + $montant > $totalEncaisse + 0.01) {
                throw new Exception("La somme des remboursements et de l'annulation dépasse l'encaissement d'origine.");
            }
        }
    }
}
?>