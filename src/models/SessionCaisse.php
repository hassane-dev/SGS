<?php

require_once __DIR__ . '/../config/database.php';

class SessionCaisse {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM sessions_caisse WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findActiveByCompte($compteId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM sessions_caisse
            WHERE compte_id = :compte_id AND statut IN ('ouverte', 'fermee_a_valider')
            LIMIT 1
        ");
        $stmt->execute(['compte_id' => $compteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findActiveByUser($userId, $lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM sessions_caisse
            WHERE user_id = :user_id AND lycee_id = :lycee_id AND statut = 'ouverte'
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId, 'lycee_id' => $lyceeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function ouvrir($data) {
        $db = Database::getInstance();

        // Safety verification: No active session must exist on the account
        $active = self::findActiveByCompte($data['compte_id']);
        if ($active) {
            throw new Exception("Une session de caisse est déjà active sur ce compte financier.");
        }

        // Continuity logic: Find last validated session on this account
        $stmt_last = $db->prepare("
            SELECT fonds_caisse_conserve FROM sessions_caisse
            WHERE compte_id = :compte_id AND statut = 'fermee_validee'
            ORDER BY date_fermeture DESC, id DESC LIMIT 1
        ");
        $stmt_last->execute(['compte_id' => $data['compte_id']]);
        $row_last = $stmt_last->fetch(PDO::FETCH_ASSOC);

        if ($row_last !== false) {
            // A row was found. Check if the value is explicitly non-null (Phase 6 session)
            if ($row_last['fonds_caisse_conserve'] !== null) {
                $solde_ouverture = (float)$row_last['fonds_caisse_conserve'];
            } else {
                // Historic pre-Phase 6 validated session -> fallback to passed opening balance
                $solde_ouverture = $data['solde_ouverture'] ?? 0.00;
            }
        } else {
            // No previous session ever -> fallback to passed opening balance
            $solde_ouverture = $data['solde_ouverture'] ?? 0.00;
        }

        $stmt = $db->prepare("
            INSERT INTO sessions_caisse (lycee_id, user_id, compte_id, date_ouverture, solde_ouverture, solde_theorique, statut)
            VALUES (:lycee_id, :user_id, :compte_id, :date_ouverture, :solde_ouverture, :solde_theorique, 'ouverte')
        ");

        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'user_id' => $data['user_id'],
            'compte_id' => $data['compte_id'],
            'date_ouverture' => date('Y-m-d H:i:s'),
            'solde_ouverture' => $solde_ouverture,
            'solde_theorique' => $solde_ouverture
        ]);

        return $db->lastInsertId();
    }

    public static function cloturer($id, $solde_reel, $justificatif = '', $montant_remis = null, $fonds_caisse_conserve = null) {
        $db = Database::getInstance();
        $session = self::findById($id);
        if (!$session) {
            throw new Exception("Session de caisse introuvable.");
        }

        if ($montant_remis === null) {
            // Default rule: entire cash counted is remis to vault
            $montant_remis = $solde_reel;
            $fonds_caisse_conserve = 0.00;
        }

        // Strict verification of values and invariants
        $solde_reel = (float)$solde_reel;
        $montant_remis = (float)$montant_remis;
        $fonds_caisse_conserve = (float)$fonds_caisse_conserve;

        if ($solde_reel < 0 || $montant_remis < 0 || $fonds_caisse_conserve < 0) {
            throw new Exception("Les montants déclarés de clôture ne peuvent pas être négatifs.");
        }

        if (abs($solde_reel - ($montant_remis + $fonds_caisse_conserve)) > 0.01) {
            throw new Exception("Incohérence physique détectée : le solde réel (" . number_format($solde_reel, 2) . ") doit correspondre exactement à la somme du montant remis (" . number_format($montant_remis, 2) . ") et du fonds conservé (" . number_format($fonds_caisse_conserve, 2) . ").");
        }

        $theorique = (float)$session['solde_theorique'];
        $ecart = $solde_reel - $theorique;

        if ($ecart !== 0.00 && empty($justificatif)) {
            throw new Exception("Un motif de justification est obligatoire en cas d'écart de caisse.");
        }

        $stmt = $db->prepare("
            UPDATE sessions_caisse SET
                date_fermeture = :date_fermeture,
                solde_reel = :solde_reel,
                ecart = :ecart,
                justificatif_ecart = :justificatif,
                montant_remis = :montant_remis,
                fonds_caisse_conserve = :fonds_caisse_conserve,
                statut = 'fermee_a_valider'
            WHERE id = :id
        ");

        $stmt->execute([
            'date_fermeture' => date('Y-m-d H:i:s'),
            'solde_reel' => $solde_reel,
            'ecart' => $ecart,
            'justificatif' => $justificatif,
            'montant_remis' => $montant_remis,
            'fonds_caisse_conserve' => $fonds_caisse_conserve,
            'id' => $id
        ]);

        return $ecart;
    }

    public static function approuver($id, $valide_par, $motif_validation) {
        $db = Database::getInstance();
        $inTransaction = $db->inTransaction();

        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

            // 1. SELECT FOR UPDATE to acquire locks in strict sequential order
            // A. Lock session
            $sql = "SELECT * FROM sessions_caisse WHERE id = :id";
            if ($driver !== 'sqlite') { $sql .= " FOR UPDATE"; }
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception("Session de caisse introuvable.");
            }

            if ($session['statut'] !== 'fermee_a_valider') {
                throw new Exception("Seule une session à l'état 'En attente de validation' peut être approuvée.");
            }

            // B. Lock cash account of the session
            $sql_compte = "SELECT * FROM comptes_financiers WHERE id = :id";
            if ($driver !== 'sqlite') { $sql_compte .= " FOR UPDATE"; }
            $stmt_cf = $db->prepare($sql_compte);
            $stmt_cf->execute(['id' => $session['compte_id']]);
            $caisse = $stmt_cf->fetch(PDO::FETCH_ASSOC);

            if (!$caisse) {
                throw new Exception("Compte financier de la caisse introuvable.");
            }

            // C. Lock Main Vault of the Lycée
            require_once __DIR__ . '/CompteFinancier.php';
            $coffreId = CompteFinancier::findCoffreByLycee($session['lycee_id']);
            if (!$coffreId) {
                throw new Exception("Aucun Coffre Principal n'est configuré pour ce lycée. Veuillez configurer le Coffre dans les comptes financiers.");
            }

            $stmt_co = $db->prepare($sql_compte);
            $stmt_co->execute(['id' => $coffreId]);
            $coffre = $stmt_co->fetch(PDO::FETCH_ASSOC);

            if (!$coffre) {
                throw new Exception("Compte financier du Coffre Principal introuvable.");
            }

            // Check if there are multiple active vaults (sanity check)
            $stmt_all_coffres = $db->prepare("SELECT COUNT(*) FROM comptes_financiers WHERE lycee_id = :lycee_id AND est_coffre = 1 AND statut = 'actif'");
            $stmt_all_coffres->execute(['lycee_id' => $session['lycee_id']]);
            $coffresCount = (int)$stmt_all_coffres->fetchColumn();
            if ($coffresCount > 1) {
                throw new Exception("Plusieurs Coffres Principaux actifs ont été détectés pour ce lycée. Action bloquée.");
            }

            // Separation of duties check
            if ($session['user_id'] == $valide_par) {
                throw new Exception("Séparation des tâches : vous ne pouvez pas valider votre propre session de caisse.");
            }

            // FAIL-FAST validations for GL accounts
            $caisseGL = $caisse['compte_comptable_numero'] ?? '';
            $coffreGL = $coffre['compte_comptable_numero'] ?? '';

            if (empty($caisseGL)) {
                throw new Exception("Le compte comptable général associé à la caisse '" . $caisse['nom_compte'] . "' n'est pas configuré. Veuillez lier ce compte financier au plan comptable avant de valider.");
            }
            if (empty($coffreGL)) {
                throw new Exception("Le compte comptable général associé au Coffre Principal n'est pas configuré. Veuillez lier ce compte financier au plan comptable avant de valider.");
            }

            // 2. Traitement de l'Écart de Caisse
            $ecart = (float)$session['ecart'];
            if (abs($ecart) > 0.01) {
                $type_ecart = $ecart > 0 ? 'positif' : 'negatif';
                $montant_ecart = abs($ecart);
                $reference_audit = 'REG-SESSION-' . $session['id'] . '-' . time() . '-' . rand(1000, 9999);

                // Insert into regularisations_ecarts
                $stmt_reg = $db->prepare("
                    INSERT INTO regularisations_ecarts (
                        lycee_id, session_caisse_id, montant, type_ecart, motif, constate_par, approuve_par, reference_audit
                    ) VALUES (
                        :lycee_id, :session_caisse_id, :montant, :type_ecart, :motif, :constate_par, :approuve_par, :reference_audit
                    )
                ");

                $stmt_reg->execute([
                    'lycee_id' => $session['lycee_id'],
                    'session_caisse_id' => $session['id'],
                    'montant' => $montant_ecart,
                    'type_ecart' => $type_ecart,
                    'motif' => !empty($motif_validation) ? $motif_validation : ("Régularisation d'écart : " . ($session['justificatif_ecart'] ?? '')),
                    'constate_par' => $session['user_id'],
                    'approuve_par' => $valide_par,
                    'reference_audit' => $reference_audit
                ]);

                $regularisationId = $db->lastInsertId();

                // Insert into mouvements_tresorerie via TreasuryService
                require_once __DIR__ . '/TreasuryService.php';
                TreasuryService::registerMovement([
                    'lycee_id' => $session['lycee_id'],
                    'compte_id' => $session['compte_id'],
                    'session_caisse_id' => $session['id'],
                    'type_mouvement' => $ecart > 0 ? 'entree' : 'sortie',
                    'montant' => $montant_ecart,
                    'mode_paiement' => 'Espèces',
                    'reference_transaction' => $reference_audit,
                    'source_type' => 'regularisations_ecarts',
                    'source_id' => $regularisationId,
                    'evenement_type' => 'correction',
                    'motif' => "Régularisation d'écart de caisse (Session N°" . $session['id'] . ")",
                    'user_id' => $valide_par
                ]);

                // Generate General Ledger double entry in General Accounting
                require_once __DIR__ . '/../services/ComptabiliteService.php';
                ComptabiliteService::genererEcritureAutomatique(
                    $ecart > 0 ? 'ecart_positif' : 'ecart_negatif',
                    $montant_ecart,
                    $session['lycee_id'],
                    (string)$session['id'],
                    $valide_par,
                    'regularisations_ecarts',
                    $regularisationId,
                    date('Y-m-d')
                );
            }

            // 3. Traitement de la Remise au Coffre Principal
            $montantRemis = isset($session['montant_remis']) ? (float)$session['montant_remis'] : null;
            if ($montantRemis === null) {
                // If NULL (old historic session), default to 0.00 to bypass
                $montantRemis = 0.00;
            }

            if ($montantRemis > 0) {
                require_once __DIR__ . '/TreasuryService.php';

                // A. Register outgoing cash flow from Caisse
                TreasuryService::registerMovement([
                    'lycee_id' => $session['lycee_id'],
                    'compte_id' => $session['compte_id'],
                    'session_caisse_id' => $session['id'],
                    'type_mouvement' => 'sortie',
                    'montant' => $montantRemis,
                    'mode_paiement' => 'Espèces',
                    'reference_transaction' => 'REM-' . $session['id'],
                    'source_type' => 'sessions_caisse',
                    'source_id' => $session['id'],
                    'evenement_type' => 'remise_coffre_sortie',
                    'motif' => "Remise de fonds de caisse de la Session N°" . $session['id'] . " au Coffre Principal",
                    'user_id' => $valide_par
                ]);

                // B. Register incoming cash flow into Vault
                TreasuryService::registerMovement([
                    'lycee_id' => $session['lycee_id'],
                    'compte_id' => $coffreId,
                    'session_caisse_id' => $session['id'],
                    'type_mouvement' => 'entree',
                    'montant' => $montantRemis,
                    'mode_paiement' => 'Espèces',
                    'reference_transaction' => 'REM-' . $session['id'],
                    'source_type' => 'sessions_caisse',
                    'source_id' => $session['id'],
                    'evenement_type' => 'remise_coffre_entree',
                    'motif' => "Réception remise de fonds de la Session N°" . $session['id'],
                    'user_id' => $valide_par
                ]);

                // C. Generate Double Entry Accounting Piece
                require_once __DIR__ . '/../services/ComptabiliteService.php';
                ComptabiliteService::genererEcritureAutomatique(
                    'remise_coffre',
                    $montantRemis,
                    $session['lycee_id'],
                    (string)$session['id'],
                    $valide_par,
                    'sessions_caisse',
                    $session['id'],
                    date('Y-m-d'),
                    [
                        'compte_debit_numero' => $coffreGL,
                        'compte_credit_numero' => $caisseGL
                    ]
                );
            }

            // 4. Update sessions_caisse state
            $stmt_up = $db->prepare("
                UPDATE sessions_caisse SET
                    statut = 'fermee_validee',
                    valide_par = :valide_par,
                    valide_le = :valide_le
                WHERE id = :id
            ");

            $stmt_up->execute([
                'valide_par' => $valide_par,
                'valide_le' => date('Y-m-d H:i:s'),
                'id' => $id
            ]);

            if (!$inTransaction) {
                $db->commit();
            }
            return true;
        } catch (Exception $e) {
            if (!$inTransaction) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
?>