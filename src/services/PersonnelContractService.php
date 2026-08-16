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
    public static function resolveCurrency(?string $explicitCode, ?int $lycee_id = null): string {
        if (!empty($explicitCode)) {
            return $explicitCode;
        }
        if ($lycee_id) {
            $params = ParamGeneral::findByLyceeId($lycee_id);
            if (!empty($params['monnaie'])) {
                return $params['monnaie'];
            }
        }
        return 'FCFA';
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
              AND pch.statut_contrat = 'actif'
              AND pch.date_debut <= :date_ref1
              AND (pch.date_fin IS NULL OR pch.date_fin >= :date_ref2)
            ORDER BY pch.date_debut DESC
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

        $devise = self::resolveCurrency($data['devise'] ?? null, $lycee_id);

        if ($date_fin && $date_fin < $date_debut) {
            throw new InvalidArgumentException(_("La date de fin ne peut pas être antérieure à la date de début."));
        }

        // Anti-overlap validation for same personnel, same employer
        $sql_overlap = "
            SELECT id, date_debut, date_fin FROM personnel_contrats_historique
            WHERE personnel_id = :pid
              AND (entite_juridique_id IS NULL OR entite_juridique_id = :ejid)
              AND statut_contrat = 'actif'
              AND (:id IS NULL OR id != :id_check)";

        if (!$id && $statut_contrat === 'actif') {
            // When creating a new active contract/avenant, existing active contracts with date_debut < new date_debut will be closed automatically.
            // Conflict only if an active contract exists with date_debut >= new date_debut.
            $sql_overlap .= " AND date_debut >= :d_start";
            $stmt_overlap = $db->prepare($sql_overlap);
            $stmt_overlap->execute([
                'pid' => $personnel_id,
                'ejid' => $entite_juridique_id,
                'id' => $id,
                'id_check' => $id,
                'd_start' => $date_debut
            ]);
        } else {
            $sql_overlap .= " AND (
                (date_debut <= :d_start AND (date_fin IS NULL OR date_fin >= :d_start2)) OR
                (:d_end IS NOT NULL AND date_debut <= :d_end2 AND (date_fin IS NULL OR date_fin >= :d_end3))
              )";
            $stmt_overlap = $db->prepare($sql_overlap);
            $stmt_overlap->execute([
                'pid' => $personnel_id,
                'ejid' => $entite_juridique_id,
                'id' => $id,
                'id_check' => $id,
                'd_start' => $date_debut,
                'd_start2' => $date_debut,
                'd_end' => $date_fin,
                'd_end2' => $date_fin,
                'd_end3' => $date_fin
            ]);
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
                // Find existing active contract to branch as Avenant
                $stmt_prev = $db->prepare("
                    SELECT id, contrat_souche_id, version_num FROM personnel_contrats_historique
                    WHERE personnel_id = :personnel_id AND statut_contrat = 'actif'
                    ORDER BY id DESC LIMIT 1
                ");
                $stmt_prev->execute(['personnel_id' => $personnel_id]);
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
                    $stmt_souche = $db->prepare("UPDATE personnel_contrats_historique SET contrat_souche_id = :cid WHERE id = :cid");
                    $stmt_souche->execute(['cid' => $contrat_id]);
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

            // Process multi-funder lines
            if (isset($data['financements']) && is_array($data['financements'])) {
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
}
