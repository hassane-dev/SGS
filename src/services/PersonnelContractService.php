<?php
// src/services/PersonnelContractService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/PersonnelHistoryService.php';

class PersonnelContractService {

    /**
     * Resolve currency according to hierarchy:
     * 1. Explicit currency code
     * 2. School param_general monnaie
     * 3. Default fallback 'FCFA'
     */
    public static function resolveCurrency(?string $explicitCode, ?int $lycee_id = null, ?string $contractCurrency = null): string {
        if (!empty($explicitCode)) {
            return $explicitCode;
        }
        if (!empty($contractCurrency)) {
            return $contractCurrency;
        }
        if ($lycee_id) {
            $params = ParamGeneral::findByLyceeId($lycee_id);
            if (!empty($params['monnaie'])) {
                return $params['monnaie'];
            }
        }
        throw new DomainException(_("Configuration monétaire incomplète : la monnaie générale de l'établissement n'est pas configurée dans les paramètres généraux."));
    }

    /**
     * Check if an idempotency key has already been processed.
     */
    public static function checkIdempotency(string $idempotencyKey): ?array {
        if (empty($idempotencyKey)) return null;
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT response_payload FROM sys_idempotency_keys WHERE idempotency_key = :key");
        $stmt->execute(['key' => $idempotencyKey]);
        $payload = $stmt->fetchColumn();
        return $payload ? json_decode($payload, true) : null;
    }

    /**
     * Store idempotency response.
     */
    public static function storeIdempotency(string $idempotencyKey, string $route, ?int $userId, array $response): void {
        if (empty($idempotencyKey)) return;
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO sys_idempotency_keys (idempotency_key, route, user_id, response_payload)
            VALUES (:key, :route, :user, :payload)
        ");
        $stmt->execute([
            'key' => $idempotencyKey,
            'route' => $route,
            'user' => $userId,
            'payload' => json_encode($response)
        ]);
    }

    /**
     * Finds all contract records for a specific personnel.
     */
    public static function getContractsForPersonnel(int $personnel_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT pch.*, tc.libelle AS contrat_libelle, tc.type_paiement, tc.prise_en_charge,
                   pej.raison_sociale AS employeur_nom, pej.sigle AS employeur_sigle
            FROM personnel_contrats_historique pch
            LEFT JOIN type_contrat tc ON pch.type_contrat_id = tc.id_contrat
            LEFT JOIN paie_entites_juridiques pej ON pch.entite_juridique_id = pej.id
            WHERE pch.personnel_id = :personnel_id
            ORDER BY pch.date_debut DESC, pch.id DESC
        ");
        $stmt->execute(['personnel_id' => $personnel_id]);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attach components and financing
        foreach ($contracts as &$c) {
            $c['composants'] = self::getComponentsForContract((int)$c['id']);
            $c['financements'] = self::getFinancingForContract((int)$c['id']);
        }
        return $contracts;
    }

    /**
     * Get components for a specific contract version.
     */
    public static function getComponentsForContract(int $contrat_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM personnel_contrat_composants WHERE contrat_id = :cid ORDER BY id ASC");
        $stmt->execute(['cid' => $contrat_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get financing lines for a specific contract version.
     */
    public static function getFinancingForContract(int $contrat_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM personnel_contrat_financements WHERE contrat_id = :cid ORDER BY id ASC");
        $stmt->execute(['cid' => $contrat_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all active contracts for personnel eligible for payroll in a specific period.
     * Serves as the authoritative source of truth for payroll scope.
     */
    public static function getEligibleContractsForPeriod(int $periodePaieId, ?int $lyceeId = null): array {
        $db = Database::getInstance();

        $sql = "
            SELECT c.*, u.nom, u.prenom, u.identifiant_public, u.email, tc.libelle AS contrat_libelle
            FROM paie_periodes p
            JOIN personnel_contrats_historique c ON (
                c.statut_contrat = 'actif'
                AND c.date_debut <= p.date_fin
                AND (c.date_fin IS NULL OR c.date_fin >= p.date_debut)
            )
            JOIN utilisateurs u ON c.personnel_id = u.id_user
            LEFT JOIN type_contrat tc ON c.type_contrat_id = tc.id_contrat
            WHERE p.id = :periode_id
        ";
        $params = ['periode_id' => $periodePaieId];

        if ($lyceeId) {
            $sql .= " AND p.lycee_id = :lycee_id_p AND u.lycee_id = :lycee_id_u";
            $params['lycee_id_p'] = $lyceeId;
            $params['lycee_id_u'] = $lyceeId;
        }

        $sql .= " ORDER BY u.nom ASC, u.prenom ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($contracts as &$c) {
            $c['composants'] = self::getComponentsForContract((int)$c['id']);
            $c['financements'] = self::getFinancingForContract((int)$c['id']);
        }

        return $contracts;
    }

    /**
     * Gets the active current contract for a personnel.
     */
    public static function getActiveContract(int $personnel_id, ?string $dateRef = null): ?array {
        $db = Database::getInstance();
        $targetDate = $dateRef ?: date('Y-m-d');
        $stmt = $db->prepare("
            SELECT pch.*, tc.libelle AS contrat_libelle, tc.type_paiement, tc.prise_en_charge,
                   pej.raison_sociale AS employeur_nom, pej.sigle AS employeur_sigle
            FROM personnel_contrats_historique pch
            LEFT JOIN type_contrat tc ON pch.type_contrat_id = tc.id_contrat
            LEFT JOIN paie_entites_juridiques pej ON pch.entite_juridique_id = pej.id
            WHERE pch.personnel_id = :personnel_id
              AND pch.statut_contrat IN ('actif', 'avenant_remplace')
              AND pch.date_debut <= :date_ref1
              AND (pch.date_fin IS NULL OR pch.date_fin >= :date_ref2)
            ORDER BY pch.date_debut DESC, pch.version_num DESC
            LIMIT 1
        ");
        $stmt->execute([
            'personnel_id' => $personnel_id,
            'date_ref1' => $targetDate,
            'date_ref2' => $targetDate
        ]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res) {
            $res['composants'] = self::getComponentsForContract((int)$res['id']);
            $res['financements'] = self::getFinancingForContract((int)$res['id']);
        }
        return $res ?: null;
    }

    /**
     * Saves or creates an avenant for a contract record while preserving historical integrity.
     */
    public static function saveContract(array $data, int $author_id): bool {
        if (empty($data['personnel_id']) || empty($data['type_contrat_id']) || empty($data['date_debut'])) {
            throw new InvalidArgumentException(_("Les champs Personnel, Type de contrat et Date de début sont obligatoires."));
        }

        $idempotencyKey = $data['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $cached = self::checkIdempotency($idempotencyKey);
            if ($cached) return true;
        }

        $personnel_id = (int)$data['personnel_id'];
        $type_contrat_id = (int)$data['type_contrat_id'];
        $entite_juridique_id = !empty($data['entite_juridique_id']) ? (int)$data['entite_juridique_id'] : null;
        $date_debut = $data['date_debut'];
        $date_fin = !empty($data['date_fin']) ? $data['date_fin'] : null;
        $salaire_base = (float)($data['salaire_base'] ?? 0.00);
        $volume_horaire = !empty($data['volume_horaire_mensuel']) ? (float)$data['volume_horaire_mensuel'] : null;
        $statut_contrat = $data['statut_contrat'] ?? 'actif';
        $commentaire = $data['commentaire'] ?? null;
        $mode_calcul = $data['mode_calcul_principal'] ?? 'forfait_fixe';
        $unite_rem = $data['unite_remuneration'] ?? 'mois';
        $periodicite = $data['periodicite_paiement'] ?? 'mensuel';
        $periode_essai = (int)($data['periode_essai_jours'] ?? 0);
        $statut_essai = $data['statut_essai'] ?? ($periode_essai > 0 ? 'en_cours' : 'non_applicable');
        $type_avenant = $data['type_avenant'] ?? null;
        $avenant_numero = $data['avenant_numero'] ?? null;
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        // Fetch user lycee_id for currency resolution
        $db = Database::getInstance();
        $stmt_u_lycee = $db->prepare("SELECT lycee_id FROM utilisateurs WHERE id_user = :uid");
        $stmt_u_lycee->execute(['uid' => $personnel_id]);
        $lycee_id = $stmt_u_lycee->fetchColumn() ?: null;

        $devise = !empty($data['devise']) ? trim($data['devise']) : null;
        // Verify currency resolution doesn't fail due to unconfigured school monnaie
        self::resolveCurrency($devise, $lycee_id);

        if ($date_fin && $date_fin < $date_debut) {
            throw new InvalidArgumentException(_("La date de fin ne peut pas être antérieure à la date de début."));
        }

        // Anti-overlap validation for same personnel, same employer
        if ($entite_juridique_id !== null) {
            $sql_overlap = "
                SELECT id, date_debut, date_fin FROM personnel_contrats_historique
                WHERE personnel_id = :pid
                  AND (entite_juridique_id = :ejid OR entite_juridique_id IS NULL)
                  AND statut_contrat = 'actif'
                  AND (:id_null IS NULL OR id != :id_check)";
        } else {
            $sql_overlap = "
                SELECT id, date_debut, date_fin FROM personnel_contrats_historique
                WHERE personnel_id = :pid
                  AND entite_juridique_id IS NULL
                  AND statut_contrat = 'actif'
                  AND (:id_null IS NULL OR id != :id_check)";
        }

        if (!$id && $statut_contrat === 'actif') {
            // When creating a new active contract/avenant, existing active contracts with date_debut < new date_debut will be closed automatically.
            // Conflict only if an active contract exists with date_debut >= new date_debut.
            $sql_overlap .= " AND date_debut >= :d_start";
            $stmt_overlap = $db->prepare($sql_overlap);
            $params = [
                'pid' => $personnel_id,
                'id_null' => $id,
                'id_check' => $id,
                'd_start' => $date_debut
            ];
            if ($entite_juridique_id !== null) {
                $params['ejid'] = $entite_juridique_id;
            }
            $stmt_overlap->execute($params);
        } else {
            $sql_overlap .= " AND (
                (date_debut <= :d_start AND (date_fin IS NULL OR date_fin >= :d_start2)) OR
                (:d_end IS NOT NULL AND date_debut <= :d_end2 AND (date_fin IS NULL OR date_fin >= :d_end3))
              )";
            $stmt_overlap = $db->prepare($sql_overlap);
            $params = [
                'pid' => $personnel_id,
                'id_null' => $id,
                'id_check' => $id,
                'd_start' => $date_debut,
                'd_start2' => $date_debut,
                'd_end' => $date_fin,
                'd_end2' => $date_fin,
                'd_end3' => $date_fin
            ];
            if ($entite_juridique_id !== null) {
                $params['ejid'] = $entite_juridique_id;
            }
            $stmt_overlap->execute($params);
        }

        if ($stmt_overlap->fetch()) {
            throw new InvalidArgumentException(_("Un contrat actif existe déjà pour le même employeur sur cette période. Veuillez clore ou modifier le contrat précédent."));
        }

        $inTx = $db->inTransaction();
        if (!$inTx) {
            $db->beginTransaction();
        }

        try {
            $version_num = 1;
            $contrat_souche_id = null;

            // Handle Avenant creation / Terminate previous active versions
            if ($statut_contrat === 'actif' && !$id) {
                // Find existing active contract for same employer to branch as Avenant
                $sql_prev = "
                    SELECT id, contrat_souche_id, version_num FROM personnel_contrats_historique
                    WHERE personnel_id = :personnel_id AND statut_contrat = 'actif'
                ";
                if ($entite_juridique_id !== null) {
                    $sql_prev .= " AND (entite_juridique_id = :ejid OR entite_juridique_id IS NULL)";
                } else {
                    $sql_prev .= " AND entite_juridique_id IS NULL";
                }
                $sql_prev .= " ORDER BY id DESC LIMIT 1";

                $stmt_prev = $db->prepare($sql_prev);
                $params_prev = ['personnel_id' => $personnel_id];
                if ($entite_juridique_id !== null) {
                    $params_prev['ejid'] = $entite_juridique_id;
                }
                $stmt_prev->execute($params_prev);
                $prev = $stmt_prev->fetch(PDO::FETCH_ASSOC);

                if ($prev) {
                    $contrat_souche_id = $prev['contrat_souche_id'] ?: $prev['id'];
                    $version_num = (int)$prev['version_num'] + 1;
                    $yesterday = date('Y-m-d', strtotime($date_debut . ' -1 day'));

                    // Close previous active version
                    $stmt_term = $db->prepare("
                        UPDATE personnel_contrats_historique
                        SET statut_contrat = 'avenant_remplace', date_fin = :date_fin_prev
                        WHERE id = :prev_id
                    ");
                    $stmt_term->execute([
                        'date_fin_prev' => $yesterday,
                        'prev_id' => $prev['id']
                    ]);
                }
            }

            $old_contract = null;
            if ($id) {
                $stmt_old = $db->prepare("SELECT * FROM personnel_contrats_historique WHERE id = :id");
                $stmt_old->execute(['id' => $id]);
                $old_contract = $stmt_old->fetch(PDO::FETCH_ASSOC);

                if (!$old_contract) {
                    throw new InvalidArgumentException(_("Contrat introuvable."));
                }

                if ($old_contract['statut_contrat'] !== 'actif') {
                    throw new InvalidArgumentException(_("Un contrat historique immuable ne peut pas être modifié directement. Veuillez créer un avenant."));
                }

                $contrat_souche_id = $old_contract['contrat_souche_id'] ?: $id;
                $version_num = $old_contract['version_num'] ?: 1;

                $sql = "UPDATE personnel_contrats_historique
                        SET type_contrat_id = :type_contrat_id, entite_juridique_id = :entite_juridique_id,
                            date_debut = :date_debut, date_fin = :date_fin, salaire_base = :salaire_base,
                            devise = :devise, volume_horaire_mensuel = :volume_horaire_mensuel,
                            mode_calcul_principal = :mode_calcul, unite_remuneration = :unite_rem,
                            periodicite_paiement = :periodicite, periode_essai_jours = :periode_essai,
                            statut_essai = :statut_essai, statut_contrat = :statut_contrat,
                            commentaire = :commentaire, type_avenant = :type_avenant, avenant_numero = :avenant_numero
                        WHERE id = :id AND personnel_id = :personnel_id";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'type_contrat_id' => $type_contrat_id,
                    'entite_juridique_id' => $entite_juridique_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'salaire_base' => $salaire_base,
                    'devise' => $devise,
                    'volume_horaire_mensuel' => $volume_horaire,
                    'mode_calcul' => $mode_calcul,
                    'unite_rem' => $unite_rem,
                    'periodicite' => $periodicite,
                    'periode_essai' => $periode_essai,
                    'statut_essai' => $statut_essai,
                    'statut_contrat' => $statut_contrat,
                    'commentaire' => $commentaire,
                    'type_avenant' => $type_avenant,
                    'avenant_numero' => $avenant_numero,
                    'id' => $id,
                    'personnel_id' => $personnel_id
                ]);
                $contrat_id = $id;
            } else {
                $sql = "INSERT INTO personnel_contrats_historique (
                            personnel_id, contrat_souche_id, type_contrat_id, entite_juridique_id, date_debut, date_fin,
                            salaire_base, devise, volume_horaire_mensuel, mode_calcul_principal, unite_remuneration,
                            periodicite_paiement, periode_essai_jours, statut_essai, version_num, statut_contrat,
                            commentaire, type_avenant, avenant_numero, cree_par
                        ) VALUES (
                            :personnel_id, :contrat_souche_id, :type_contrat_id, :entite_juridique_id, :date_debut, :date_fin,
                            :salaire_base, :devise, :volume_horaire_mensuel, :mode_calcul, :unite_rem,
                            :periodicite, :periode_essai, :statut_essai, :version_num, :statut_contrat,
                            :commentaire, :type_avenant, :avenant_numero, :cree_par
                        )";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    'personnel_id' => $personnel_id,
                    'contrat_souche_id' => $contrat_souche_id,
                    'type_contrat_id' => $type_contrat_id,
                    'entite_juridique_id' => $entite_juridique_id,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'salaire_base' => $salaire_base,
                    'devise' => $devise,
                    'volume_horaire_mensuel' => $volume_horaire,
                    'mode_calcul' => $mode_calcul,
                    'unite_rem' => $unite_rem,
                    'periodicite' => $periodicite,
                    'periode_essai' => $periode_essai,
                    'statut_essai' => $statut_essai,
                    'version_num' => $version_num,
                    'statut_contrat' => $statut_contrat,
                    'commentaire' => $commentaire,
                    'type_avenant' => $type_avenant,
                    'avenant_numero' => $avenant_numero,
                    'cree_par' => $author_id
                ]);
                $contrat_id = (int)$db->lastInsertId();

                if (!$contrat_souche_id) {
                    $stmt_souche = $db->prepare("UPDATE personnel_contrats_historique SET contrat_souche_id = :souche_id WHERE id = :target_id");
                    $stmt_souche->execute(['souche_id' => $contrat_id, 'target_id' => $contrat_id]);
                }
            }

            // Process contract components
            if (isset($data['composants']) && is_array($data['composants'])) {
                $db->prepare("DELETE FROM personnel_contrat_composants WHERE contrat_id = :cid")->execute(['cid' => $contrat_id]);
                $stmt_comp = $db->prepare("
                    INSERT INTO personnel_contrat_composants (
                        contrat_id, code_composant, libelle, nature_composant, mode_calcul,
                        valeur_numerique, unite_remuneration, periodicite_paiement, devise_code, date_debut
                    ) VALUES (
                        :cid, :code, :lib, :nature, :mode, :val, :unite, :period, :devise, :dstart
                    )
                ");
                foreach ($data['composants'] as $comp) {
                    if (empty($comp['libelle'])) continue;
                    $stmt_comp->execute([
                        'cid' => $contrat_id,
                        'code' => $comp['code_composant'] ?? 'COMP_GENERIC',
                        'lib' => $comp['libelle'],
                        'nature' => $comp['nature_composant'] ?? 'prime',
                        'mode' => $comp['mode_calcul'] ?? 'forfait_fixe',
                        'val' => (float)($comp['valeur_numerique'] ?? 0),
                        'unite' => $comp['unite_remuneration'] ?? 'mois',
                        'period' => $comp['periodicite_paiement'] ?? 'mensuel',
                        'devise' => !empty($comp['devise_code']) ? $comp['devise_code'] : null,
                        'dstart' => $date_debut
                    ]);
                }
            }

        // Validate multi-funder percentage sum <= 100%
            if (isset($data['financements']) && is_array($data['financements'])) {
            $totalPct = 0.0;
            foreach ($data['financements'] as $fin) {
                if (!empty($fin['financeur_nom'])) {
                    $totalPct += (float)($fin['pourcentage_prise_en_charge'] ?? 0);
                }
            }
            if (round($totalPct, 2) > 100.00) {
                throw new InvalidArgumentException(_("Le total des pourcentages de prise en charge ne peut pas dépasser 100%."));
            }

                $db->prepare("DELETE FROM personnel_contrat_financements WHERE contrat_id = :cid")->execute(['cid' => $contrat_id]);
                $stmt_fin = $db->prepare("
                    INSERT INTO personnel_contrat_financements (
                        contrat_id, financeur_nom, type_financeur, pourcentage_prise_en_charge, montant_plafone, date_debut
                    ) VALUES (
                        :cid, :nom, :type, :pct, :plaf, :dstart
                    )
                ");
                foreach ($data['financements'] as $fin) {
                    if (empty($fin['financeur_nom'])) continue;
                    $stmt_fin->execute([
                        'cid' => $contrat_id,
                        'nom' => $fin['financeur_nom'],
                        'type' => $fin['type_financeur'] ?? 'etablissement',
                        'pct' => (float)($fin['pourcentage_prise_en_charge'] ?? 100),
                        'plaf' => !empty($fin['montant_plafone']) ? (float)$fin['montant_plafone'] : null,
                        'dstart' => $date_debut
                    ]);
                }
            }

            // Sync legacy contrat_id column in utilisateurs for backward compatibility
            $stmt_u = $db->prepare("UPDATE utilisateurs SET contrat_id = :contrat_id WHERE id_user = :id");
            $stmt_u->execute(['contrat_id' => $type_contrat_id, 'id' => $personnel_id]);

            // Audit
            PersonnelHistoryService::logMovement([
                'personnel_id' => $personnel_id,
                'type_mouvement' => $id ? 'changement_contrat' : ($type_avenant ? 'avenant_contrat' : 'nouveau_contrat'),
                'motif' => $commentaire ?? ($id ? "Mise à jour du contrat" : "Création d'un nouveau contrat"),
                'auteur_id' => $author_id,
                'ancien_etat' => $old_contract,
                'nouvel_etat' => [
                    'contrat_id' => $contrat_id,
                    'type_contrat_id' => $type_contrat_id,
                    'entite_juridique_id' => $entite_juridique_id,
                    'date_debut' => $date_debut,
                    'salaire_base' => $salaire_base,
                    'statut_contrat' => $statut_contrat
                ]
            ]);

            if ($idempotencyKey) {
                self::storeIdempotency($idempotencyKey, '/drh/contracts/store', $author_id, ['success' => true, 'contrat_id' => $contrat_id]);
            }

            if (!$inTx) {
                $db->commit();
            }
            return true;
        } catch (Exception $e) {
            if (!$inTx && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get full detailed contract record including components, financing, and creator info.
     */
    public static function getContractDetails(int $contract_id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT pch.*, tc.libelle AS contrat_libelle, tc.type_paiement, tc.prise_en_charge,
                   pej.raison_sociale AS employeur_nom, pej.sigle AS employeur_sigle,
                   u.nom AS cree_par_nom, u.prenom AS cree_par_prenom
            FROM personnel_contrats_historique pch
            LEFT JOIN type_contrat tc ON pch.type_contrat_id = tc.id_contrat
            LEFT JOIN paie_entites_juridiques pej ON pch.entite_juridique_id = pej.id
            LEFT JOIN utilisateurs u ON pch.cree_par = u.id_user
            WHERE pch.id = :id
        ");
        $stmt->execute(['id' => $contract_id]);
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contract) return null;

        $contract['composants'] = self::getComponentsForContract($contract_id);
        $contract['financements'] = self::getFinancingForContract($contract_id);

        return $contract;
    }

    /**
     * Cancels an existing contract version with a required motif while retaining full history.
     */
    public static function cancelContract(int $contract_id, int $personnel_id, string $motif, int $author_id): bool {
        if (empty(trim($motif))) {
            throw new InvalidArgumentException(_("Un motif explicite est obligatoire pour annuler un contrat."));
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM personnel_contrats_historique WHERE id = :id AND personnel_id = :pid");
        $stmt->execute(['id' => $contract_id, 'pid' => $personnel_id]);
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contract) {
            throw new InvalidArgumentException(_("Contrat introuvable."));
        }

        if ($contract['statut_contrat'] === 'annule') {
            throw new InvalidArgumentException(_("Ce contrat est déjà annulé."));
        }

        $inTx = $db->inTransaction();
        if (!$inTx) $db->beginTransaction();

        try {
            $dateNow = date('Y-m-d H:i:s');
            $newComment = trim(($contract['commentaire'] ?? '') . "\n[Annulé le {$dateNow} par ID {$author_id}] Motif: " . $motif);

            $stmt_upd = $db->prepare("
                UPDATE personnel_contrats_historique
                SET statut_contrat = 'annule', commentaire = :comment
                WHERE id = :id
            ");
            $stmt_upd->execute(['comment' => $newComment, 'id' => $contract_id]);

            PersonnelHistoryService::logMovement([
                'personnel_id' => $personnel_id,
                'type_mouvement' => 'annulation_contrat',
                'motif' => "Annulation du contrat version {$contract['version_num']} : {$motif}",
                'auteur_id' => $author_id,
                'ancien_etat' => $contract,
                'nouvel_etat' => ['statut_contrat' => 'annule', 'motif_annulation' => $motif]
            ]);

            if (!$inTx) $db->commit();
            return true;
        } catch (Exception $e) {
            if (!$inTx && $db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Physical deletion is strictly forbidden.
     */
    public static function deleteContract(int $contract_id): void {
        throw new LogicException(_("La suppression physique d'un contrat historique est strictement interdite pour des raisons de traçabilité et de valeur légale."));
    }

    /**
     * Migrates legacy user contract associations (`utilisateurs.contrat_id`)
     * into `personnel_contrats_historique` for users that do not yet have a record in `personnel_contrats_historique`.
     */
    public static function migrateLegacyContracts(): int {
        $db = Database::getInstance();

        $sql = "
            SELECT u.id_user, u.contrat_id, u.date_embauche, u.lycee_id, u.nom, u.prenom, tc.libelle AS tc_libelle
            FROM utilisateurs u
            JOIN type_contrat tc ON u.contrat_id = tc.id_contrat
            WHERE u.contrat_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1 FROM personnel_contrats_historique pch WHERE pch.personnel_id = u.id_user
              )
        ";
        $stmt = $db->query($sql);
        $legacyUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $migratedCount = 0;
        foreach ($legacyUsers as $u) {
            $personnel_id = (int)$u['id_user'];
            $type_contrat_id = (int)$u['contrat_id'];
            $date_debut = !empty($u['date_embauche']) ? date('Y-m-d', strtotime($u['date_embauche'])) : '2024-01-01';

            $salaire_base = 0.00;
            try {
                $stmt_sal = $db->prepare("SELECT net_a_payer FROM salaires WHERE id_personnel = :pid ORDER BY id_salaire DESC LIMIT 1");
                $stmt_sal->execute(['pid' => $personnel_id]);
                $net = $stmt_sal->fetchColumn();
                if ($net) {
                    $salaire_base = (float)$net;
                }
            } catch (Exception $e) {
                // Ignore if salaires table missing
            }

            $insSql = "
                INSERT INTO personnel_contrats_historique (
                    personnel_id, contrat_souche_id, type_contrat_id, date_debut, date_fin,
                    salaire_base, devise, mode_calcul_principal, unite_remuneration,
                    periodicite_paiement, version_num, statut_contrat, commentaire, cree_par
                ) VALUES (
                    :pid, NULL, :tcid, :dstart, NULL,
                    :sal, 'FCFA', 'forfait_fixe', 'mois',
                    'mensuel', 1, 'actif', :comment, 1
                )
            ";
            $stmt_ins = $db->prepare($insSql);
            $stmt_ins->execute([
                'pid' => $personnel_id,
                'tcid' => $type_contrat_id,
                'dstart' => $date_debut,
                'sal' => $salaire_base,
                'comment' => 'Migré automatiquement depuis l\'ancien gestionnaire (utilisateurs.contrat_id)'
            ]);
            $newId = (int)$db->lastInsertId();

            $stmt_souche = $db->prepare("UPDATE personnel_contrats_historique SET contrat_souche_id = :souche_id WHERE id = :target_id");
            $stmt_souche->execute(['souche_id' => $newId, 'target_id' => $newId]);

            $migratedCount++;
        }

        return $migratedCount;
    }
}
