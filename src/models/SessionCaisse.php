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

        $stmt = $db->prepare("
            INSERT INTO sessions_caisse (lycee_id, user_id, compte_id, date_ouverture, solde_ouverture, solde_theorique, statut)
            VALUES (:lycee_id, :user_id, :compte_id, :date_ouverture, :solde_ouverture, :solde_theorique, 'ouverte')
        ");

        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'user_id' => $data['user_id'],
            'compte_id' => $data['compte_id'],
            'date_ouverture' => date('Y-m-d H:i:s'),
            'solde_ouverture' => $data['solde_ouverture'] ?? 0.00,
            'solde_theorique' => $data['solde_ouverture'] ?? 0.00
        ]);

        return $db->lastInsertId();
    }

    public static function cloturer($id, $solde_reel, $justificatif = '') {
        $db = Database::getInstance();
        $session = self::findById($id);
        if (!$session) {
            throw new Exception("Session de caisse introuvable.");
        }

        $theorique = (float)$session['solde_theorique'];
        $ecart = (float)$solde_reel - $theorique;

        $stmt = $db->prepare("
            UPDATE sessions_caisse SET
                date_fermeture = :date_fermeture,
                solde_reel = :solde_reel,
                ecart = :ecart,
                justificatif_ecart = :justificatif,
                statut = 'fermee_a_valider'
            WHERE id = :id
        ");

        $stmt->execute([
            'date_fermeture' => date('Y-m-d H:i:s'),
            'solde_reel' => $solde_reel,
            'ecart' => $ecart,
            'justificatif' => $justificatif,
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
            // Lock session for update (MySQL only, SQLite has dynamic lock fallback)
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $sql = "SELECT * FROM sessions_caisse WHERE id = :id";
            if ($driver !== 'sqlite') {
                $sql .= " FOR UPDATE";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception("Session de caisse introuvable.");
            }

            if ($session['statut'] !== 'fermee_a_valider') {
                throw new Exception("Seule une session à l'état 'En attente de validation' peut être approuvée.");
            }

            // Separation of duties check
            if ($session['user_id'] == $valide_par) {
                throw new Exception("Séparation des tâches : vous ne pouvez pas valider votre propre session de caisse.");
            }

            $ecart = (float)$session['ecart'];

            if (abs($ecart) > 0.01) {
                $type_ecart = $ecart > 0 ? 'positif' : 'negatif';
                $montant_ecart = abs($ecart);
                $reference_audit = 'REG-SESSION-' . $session['id'] . '-' . time() . '-' . rand(1000, 9999);

                // 1. Insert into regularisations_ecarts
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

                // 2. Insert into mouvements_tresorerie via TreasuryService
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

                // 3. Generate General Ledger double entry in General Accounting (Phase 5)
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