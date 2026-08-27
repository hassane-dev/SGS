<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../models/PaieBulletin.php';
require_once __DIR__ . '/../models/PaieBulletinLigne.php';
require_once __DIR__ . '/../models/PaieCahierTexteValidation.php';
require_once __DIR__ . '/../models/PaieBulletinHeure.php';
require_once __DIR__ . '/../models/PaieBulletinContratSnapshot.php';
require_once __DIR__ . '/../models/PaieBulletinFinancement.php';
require_once __DIR__ . '/../models/PaieBulletinRegleSnapshot.php';
require_once __DIR__ . '/../models/PaieBulletinRegleTrancheSnapshot.php';
require_once __DIR__ . '/../models/PaieReglement.php';
require_once __DIR__ . '/../models/PaieRegularisation.php';
require_once __DIR__ . '/../models/PaieRegularisationLigne.php';
require_once __DIR__ . '/../models/PaieAuditLog.php';
require_once __DIR__ . '/PaieCalculationEngine.php';
require_once __DIR__ . '/PaieAccountingAdapter.php';
require_once __DIR__ . '/PaieTreasuryAdapter.php';

class PaieWorkflowService {

    /**
     * V1 & V6: Acquire deterministic row locks in exact order:
     * paie_periodes -> comptabilite_periodes -> paie_bulletins
     */
    private static function acquireLocks(PDO $db, int $periodePaieId, ?int $bulletinId = null): array {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $lockClause = ($driver === 'sqlite') ? "" : " FOR UPDATE";

        // 1. Lock paie_periodes
        $stmtP = $db->prepare("SELECT * FROM paie_periodes WHERE id = :id {$lockClause}");
        $stmtP->execute(['id' => $periodePaieId]);
        $periode = $stmtP->fetch(PDO::FETCH_ASSOC);
        if (!$periode) {
            throw new InvalidArgumentException("Période de paie introuvable ID #{$periodePaieId}");
        }

        // 2. Lock comptabilite_periodes
        $stmtC = $db->prepare("SELECT * FROM comptabilite_periodes WHERE id = :id {$lockClause}");
        $stmtC->execute(['id' => $periode['periode_comptable_id']]);
        $comptaPeriode = $stmtC->fetch(PDO::FETCH_ASSOC);

        // V2 Lock Check: Verify if accounting period is closed
        if ($comptaPeriode && (!empty($comptaPeriode['est_cloturee']) || !empty($comptaPeriode['cloturee']))) {
            throw new LogicException("Impossible d'effectuer cette opération : la période comptable est clôturée.");
        }

        // 3. Lock active bulletin if provided
        $bulletin = null;
        if ($bulletinId) {
            $stmtB = $db->prepare("SELECT * FROM paie_bulletins WHERE id = :id {$lockClause}");
            $stmtB->execute(['id' => $bulletinId]);
            $bulletin = $stmtB->fetch(PDO::FETCH_ASSOC);
        }

        return [
            'periode' => $periode,
            'compta_periode' => $comptaPeriode,
            'bulletin' => $bulletin
        ];
    }

    /**
     * Retrieve eligible contracts for a period enriched with their exact Service Fait and calculation status.
     */
    public static function getEligibleContractsWithServiceFaitStatus(int $periodePaieId, ?int $lyceeId = null): array {
        $db = Database::getInstance();
        $stmtP = $db->prepare("SELECT * FROM paie_periodes WHERE id = :id");
        $stmtP->execute(['id' => $periodePaieId]);
        $periode = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$periode) {
            return [];
        }

        $contracts = PersonnelContractService::getEligibleContractsForPeriod($periodePaieId, $lyceeId);

        foreach ($contracts as &$c) {
            $personnelId = (int)$c['personnel_id'];
            $contratId = (int)$c['id'];
            $entiteJuridiqueId = (int)($c['entite_juridique_id'] ?? 1);
            $modeCalcul = strtolower($c['mode_calcul_principal'] ?? ($c['type_paiement'] ?? 'forfait_fixe'));

            // 1. Check existing active bulletin
            $existingB = PaieBulletin::findActiveForContractAndPeriod($personnelId, $entiteJuridiqueId, $contratId, $periodePaieId);

            // 2. Fetch validated hours
            $validatedSessions = PaieCahierTexteValidation::findValidatedForTeacherAndDates($personnelId, $periode['date_debut'], $periode['date_fin']);
            $heuresValideesCount = count($validatedSessions);
            $totalHeuresValidees = 0.0;
            $totalMontantHeuresValidees = 0.0;
            foreach ($validatedSessions as $vs) {
                $dh = (float)$vs['duree_heures'];
                $th = (float)$vs['taux_horaire'];
                $totalHeuresValidees += $dh;
                $totalMontantHeuresValidees += ($dh * $th);
            }

            // 3. Fetch unvalidated sessions in cahier_texte for period
            $stmtUnval = $db->prepare("
                SELECT COUNT(*) FROM cahier_texte
                WHERE personnel_id = :pid
                  AND date_cours BETWEEN :dstart AND :dend
                  AND NOT EXISTS (
                      SELECT 1 FROM paie_cahier_texte_validations v WHERE v.cahier_id = cahier_texte.cahier_id
                  )
            ");
            $stmtUnval->execute([
                'pid' => $personnelId,
                'dstart' => $periode['date_debut'],
                'dend' => $periode['date_fin']
            ]);
            $unvalidatedSessionsCount = (int)$stmtUnval->fetchColumn();

            // Determine status badge code and label
            if ($existingB) {
                $c['service_fait_code'] = 'bulletin_existant';
                $c['service_fait_label'] = _("Bulletin déjà généré");
                $c['status_badge_class'] = 'bg-light-info text-info';
                $c['est_calculable'] = false;
            } elseif ($heuresValideesCount > 0) {
                $c['service_fait_code'] = 'service_fait_valide';
                $c['service_fait_label'] = sprintf(_("Service fait validé (%s h)"), number_format($totalHeuresValidees, 1));
                $c['status_badge_class'] = 'bg-light-success text-success';
                $c['est_calculable'] = true;
            } elseif ($unvalidatedSessionsCount > 0) {
                $c['service_fait_code'] = 'service_fait_non_valide';
                $c['service_fait_label'] = sprintf(_("Service fait non validé (%d séance(s))"), $unvalidatedSessionsCount);
                $c['status_badge_class'] = 'bg-light-warning text-warning';
                $c['est_calculable'] = ($modeCalcul !== 'taux_horaire');
            } else {
                if ($modeCalcul === 'taux_horaire' || $modeCalcul === 'horaire') {
                    $c['service_fait_code'] = 'aucun_service_fait';
                    $c['service_fait_label'] = _("Contrat horaire — Aucun service fait");
                    $c['status_badge_class'] = 'bg-light-secondary text-secondary';
                    $c['est_calculable'] = false;
                } elseif ($modeCalcul === 'mixte') {
                    $c['service_fait_code'] = 'aucun_service_fait_mixte';
                    $c['service_fait_label'] = _("Contrat mixte — Fixe uniquement");
                    $c['status_badge_class'] = 'bg-light-primary text-primary';
                    $c['est_calculable'] = true;
                } else {
                    $c['service_fait_code'] = 'aucun_service_fait_fixe';
                    $c['service_fait_label'] = _("Contrat fixe — Calculable");
                    $c['status_badge_class'] = 'bg-light-success text-success';
                    $c['est_calculable'] = true;
                }
            }

            $c['heures_validees_count'] = $heuresValideesCount;
            $c['total_heures_validees'] = $totalHeuresValidees;
            $c['total_montant_heures_validees'] = $totalMontantHeuresValidees;
            $c['seances_non_validees_count'] = $unvalidatedSessionsCount;
        }

        return $contracts;
    }

    /**
     * Create a new payroll period.
     */
    public static function createPeriod(int $lyceeId, int $periodeComptableId, string $codePeriode, int $mois, int $annee, string $dateDebut, string $dateFin, int $userId): int {
        return PaiePeriode::create([
            'lycee_id' => $lyceeId,
            'periode_comptable_id' => $periodeComptableId,
            'code_periode' => $codePeriode,
            'mois' => $mois,
            'annee' => $annee,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'statut' => 'brouillon',
            'cree_par' => $userId
        ]);
    }

    /**
     * Generate a bulletin (V1) for a single employee contract.
     * Reused by both individual and global generation workflows.
     */
    public static function generateBulletinForEmployee(int $periodePaieId, int $personnelId, int $contratId, int $userId, ?string $idempotencyKey = null): int {
        $db = Database::getInstance();

        // 1. Lock Acquisition
        $locks = self::acquireLocks($db, $periodePaieId);
        $periode = $locks['periode'];

        if ($periode['statut'] === 'cloture') {
            throw new LogicException("Impossible de générer un bulletin : la période de paie est clôturée.");
        }

        // 2. Fetch contract and active check
        $stmtC = $db->prepare("SELECT * FROM personnel_contrats_historique WHERE id = :id");
        $stmtC->execute(['id' => $contratId]);
        $c = $stmtC->fetch(PDO::FETCH_ASSOC);

        if (!$c || $c['statut_contrat'] !== 'actif') {
            throw new InvalidArgumentException("Contrat introuvable ou non actif ID #{$contratId}");
        }

        $entiteJuridiqueId = (int)($c['entite_juridique_id'] ?? 1);

        // Check if active bulletin already exists
        $existing = PaieBulletin::findActiveForContractAndPeriod($personnelId, $entiteJuridiqueId, $contratId, $periodePaieId);
        if ($existing) {
            return (int)$existing['id'];
        }

        // 3. Fetch validated teacher hours for the period
        $cahierValidations = PaieCahierTexteValidation::findValidatedForTeacherAndDates($personnelId, $periode['date_debut'], $periode['date_fin']);

        // 4. Fetch contract components / allowances
        $stmtComp = $db->prepare("SELECT * FROM personnel_contrat_composants WHERE contrat_id = :id");
        $stmtComp->execute(['id' => $contratId]);
        $components = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

        // 5. Fetch pending regularisations
        $pendingRegularisations = PaieRegularisation::findPendingForEmployeeAndPeriod($personnelId, $periodePaieId);

        // 6. Compute Bulletin
        $computed = PaieCalculationEngine::computeBulletin($c, $cahierValidations, $components, 'DEFAULT', $periode['date_fin'], $pendingRegularisations);

        // 7. Create PaieBulletin V1
        $bulletinId = PaieBulletin::create([
            'periode_id' => $periodePaieId,
            'personnel_id' => $personnelId,
            'contrat_id' => $contratId,
            'version_num' => 1,
            'est_version_active' => 1,
            'entite_juridique_id' => $entiteJuridiqueId,
            'salaire_base' => $computed['salaire_base'],
            'total_brut' => $computed['total_brut'],
            'total_cotisations_salariales' => $computed['total_cotisations_salariales'],
            'total_impots' => $computed['total_impots'],
            'total_retenues' => $computed['total_retenues'],
            'net_imposable' => $computed['net_imposable'],
            'net_a_payer' => $computed['net_a_payer'],
            'total_cotisations_patronales' => $computed['total_cotisations_patronales'],
            'cout_total_employeur' => $computed['cout_total_employeur'],
            'devise' => $c['devise'] ?? 'FCFA',
            'statut_bulletin' => 'brouillon',
            'idempotency_key' => $idempotencyKey ? "{$idempotencyKey}-{$personnelId}-{$contratId}" : null
        ]);

        // 8. Create Rubrique Lines
        foreach ($computed['rubrique_lines'] as $rl) {
            $rl['bulletin_id'] = $bulletinId;
            PaieBulletinLigne::create($rl);
        }

        // 9. Create Teacher Hours Lines
        foreach ($computed['heures_lines'] as $hl) {
            $hl['bulletin_id'] = $bulletinId;
            PaieBulletinHeure::create($hl);
        }

        // 10. Log and Integrate Regularisations
        foreach ($pendingRegularisations as $reg) {
            PaieRegularisation::logIntegration(
                (int)$reg['id'],
                $bulletinId,
                (float)($reg['montant_brut_delta'] ?? 0.00),
                (float)($reg['montant_net_delta'] ?? 0.00)
            );
            PaieRegularisation::updateStatus((int)$reg['id'], 'integre', $userId);
        }

        // 11. Create Contract Snapshot
        PaieBulletinContratSnapshot::create([
            'bulletin_id' => $bulletinId,
            'contrat_id' => $contratId,
            'version_num' => $c['version_num'] ?? 1,
            'type_contrat' => $c['type_contrat_id'] ?? 'CDI',
            'mode_calcul_principal' => $c['mode_calcul_principal'] ?? 'forfait_fixe',
            'devise' => $c['devise'] ?? 'FCFA',
            'raw_json_snapshot' => $c
        ]);

        // 12. Create Rules Snapshots
        foreach ($computed['rules_snapshots'] as $rs) {
            $rs['bulletin_id'] = $bulletinId;
            $rsId = PaieBulletinRegleSnapshot::create($rs);
            foreach ($rs['tranches_snapshot'] as $ts) {
                $ts['regle_snapshot_id'] = $rsId;
                PaieBulletinRegleTrancheSnapshot::create($ts);
            }
        }

        PaieAuditLog::log('paie_bulletins', $bulletinId, 'create_v1', $userId, null, $computed);

        return $bulletinId;
    }

    /**
     * Dry-run calculation (Preview) for a period.
     * Guaranteed 100% read-only: no DB insertions, no regularisation state changes, no persistent locks.
     * Intersects requested $personnelIds with eligible contracts.
     */
    public static function previewBulletinsForPeriod(int $periodePaieId, ?array $personnelIds = null, ?int $lyceeId = null): array {
        $db = Database::getInstance();

        // Fetch period info (read-only)
        $stmtP = $db->prepare("SELECT * FROM paie_periodes WHERE id = :id");
        $stmtP->execute(['id' => $periodePaieId]);
        $periode = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$periode) {
            throw new InvalidArgumentException("Période de paie introuvable ID #{$periodePaieId}");
        }

        // Retrieve official eligible contracts
        $eligibleContracts = PersonnelContractService::getEligibleContractsForPeriod($periodePaieId, $lyceeId);

        // Filter by requested personnel IDs if provided (Intersection)
        if ($personnelIds !== null) {
            $requestedSet = array_map('intval', $personnelIds);
            $eligibleContracts = array_filter($eligibleContracts, function($c) use ($requestedSet) {
                return in_array((int)$c['personnel_id'], $requestedSet, true);
            });
        }

        $results = [];

        foreach ($eligibleContracts as $c) {
            $personnelId = (int)$c['personnel_id'];
            $contratId = (int)$c['id'];

            // Fetch validated hours for period
            $cahierValidations = PaieCahierTexteValidation::findValidatedForTeacherAndDates($personnelId, $periode['date_debut'], $periode['date_fin']);

            // Fetch components
            $components = PersonnelContractService::getComponentsForContract($contratId);

            // Fetch pending regularisations (read-only)
            $pendingRegs = PaieRegularisation::findPendingForEmployeeAndPeriod($personnelId, $periodePaieId);

            // Run dry-run computation
            $computed = PaieCalculationEngine::computeBulletin($c, $cahierValidations, $components, 'DEFAULT', $periode['date_fin'], $pendingRegs);

            $results[] = [
                'personnel_id' => $personnelId,
                'nom' => $c['nom'],
                'prenom' => $c['prenom'],
                'identifiant_public' => $c['identifiant_public'],
                'contrat_id' => $contratId,
                'contrat_libelle' => $c['contrat_libelle'] ?? 'Contrat',
                'salaire_base' => $computed['salaire_base'],
                'total_brut' => $computed['total_brut'],
                'total_cotisations_salariales' => $computed['total_cotisations_salariales'],
                'total_impots' => $computed['total_impots'],
                'total_retenues' => $computed['total_retenues'],
                'net_imposable' => $computed['net_imposable'],
                'net_a_payer' => $computed['net_a_payer'],
                'total_cotisations_patronales' => $computed['total_cotisations_patronales'],
                'cout_total_employeur' => $computed['cout_total_employeur'],
                'devise' => $c['devise'] ?? 'FCFA',
                'regularisations_count' => count($pendingRegs),
                'heures_validees_count' => count($cahierValidations),
                'status' => ($computed['total_brut'] <= 0) ? 'warning' : 'ok',
                'message' => ($computed['total_brut'] <= 0) ? _("Salaire brut égal à 0") : _("Prêt")
            ];
        }

        return [
            'periode' => $periode,
            'items' => $results
        ];
    }

    /**
     * Generate payroll bulletins (V1) for active staff contracts in a period.
     * Intersects provided $personnelIds with eligible contracts.
     * V5: Supports idempotency_key protection.
     */
    public static function generateBulletinsForPeriod(int $periodePaieId, int $userId, $personnelIds = null, ?string $idempotencyKey = null, ?int $lyceeId = null): array {
        // Support backward-compatible signature where 3rd param was $idempotencyKey
        if (is_string($personnelIds) && $idempotencyKey === null) {
            $idempotencyKey = $personnelIds;
            $personnelIds = null;
        } elseif (!is_array($personnelIds) && $personnelIds !== null) {
            $personnelIds = null;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $locks = self::acquireLocks($db, $periodePaieId);
            $periode = $locks['periode'];

            if ($periode['statut'] === 'cloture') {
                throw new LogicException("Impossible de calculer la paie : la période de paie est clôturée.");
            }

            // Check idempotency key if provided
            if (!empty($idempotencyKey)) {
                $stmtEx = $db->prepare("SELECT id FROM paie_bulletins WHERE periode_id = :p AND idempotency_key LIKE :k ORDER BY id ASC");
                $stmtEx->execute(['p' => $periodePaieId, 'k' => "{$idempotencyKey}%"]);
                $existingRows = $stmtEx->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($existingRows)) {
                    $db->commit();
                    return array_map('intval', $existingRows);
                }
            }

            // Find official eligible contracts for period
            $eligibleContracts = PersonnelContractService::getEligibleContractsForPeriod($periodePaieId, $lyceeId);

            // Filter by provided personnel IDs if selection specified (Intersection)
            if ($personnelIds !== null) {
                $requestedSet = array_map('intval', $personnelIds);
                $eligibleContracts = array_filter($eligibleContracts, function($c) use ($requestedSet) {
                    return in_array((int)$c['personnel_id'], $requestedSet, true);
                });
            }

            $createdBulletinIds = [];

            foreach ($eligibleContracts as $c) {
                $bulletinId = self::generateBulletinForEmployee(
                    $periodePaieId,
                    (int)$c['personnel_id'],
                    (int)$c['id'],
                    $userId,
                    $idempotencyKey
                );
                $createdBulletinIds[] = $bulletinId;
            }

            $db->commit();
            return $createdBulletinIds;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Re-draw a bulletin atomically from V1 to V2 (or VN to VN+1) with accounting counterpassation.
     */
    public static function redrawBulletin(int $bulletinId, int $userId, ?array $manualAdjustments = []): int {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Find current bulletin
            $stmtCur = $db->prepare("SELECT * FROM paie_bulletins WHERE id = :id");
            $stmtCur->execute(['id' => $bulletinId]);
            $currentB = $stmtCur->fetch(PDO::FETCH_ASSOC);

            if (!$currentB) {
                throw new InvalidArgumentException("Bulletin introuvable ID #{$bulletinId}");
            }

            $locks = self::acquireLocks($db, (int)$currentB['periode_id'], $bulletinId);

            if ($locks['periode']['statut'] === 'cloture') {
                throw new LogicException("Impossible de re-tirer le bulletin : la période de paie est clôturée.");
            }

            // Counterpass GL entry if posted
            if ($currentB['statut_comptabilisation'] === 'comptabilise') {
                PaieAccountingAdapter::reverseBulletinInGL($bulletinId, $userId, "Re-tirage bulletin vers V" . ($currentB['version_num'] + 1));
            }

            // Rollback regularisations linked to this old version so they can be re-consumed by V+1
            PaieRegularisation::rollbackIntegrationsForBulletin($bulletinId);

            // Mark old version as inactive
            PaieBulletin::update($bulletinId, [
                'est_version_active' => 0,
                'statut_bulletin' => 'annule'
            ]);

            // Create new version V+1
            $newVersionNum = (int)$currentB['version_num'] + 1;
            $newSalBase = (float)($manualAdjustments['salaire_base'] ?? $currentB['salaire_base']);

            // Re-fetch contract and calculate
            $stmtC = $db->prepare("SELECT * FROM personnel_contrats_historique WHERE id = :id");
            $stmtC->execute(['id' => $currentB['contrat_id']]);
            $contract = $stmtC->fetch(PDO::FETCH_ASSOC);
            $contract['salaire_base'] = $newSalBase;

            $stmtComp = $db->prepare("SELECT * FROM personnel_contrat_composants WHERE contrat_id = :id");
            $stmtComp->execute(['id' => $currentB['contrat_id']]);
            $components = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

            $cahierValidations = PaieCahierTexteValidation::findValidatedForTeacherAndDates((int)$currentB['personnel_id'], $locks['periode']['date_debut'], $locks['periode']['date_fin']);
            $pendingRegularisations = PaieRegularisation::findPendingForEmployeeAndPeriod((int)$currentB['personnel_id'], (int)$currentB['periode_id']);

            $computed = PaieCalculationEngine::computeBulletin($contract, $cahierValidations, $components, 'DEFAULT', $locks['periode']['date_fin'], $pendingRegularisations);

            $newBulletinId = PaieBulletin::create([
                'periode_id' => $currentB['periode_id'],
                'personnel_id' => $currentB['personnel_id'],
                'contrat_id' => $currentB['contrat_id'],
                'version_num' => $newVersionNum,
                'est_version_active' => 1,
                'entite_juridique_id' => $currentB['entite_juridique_id'],
                'cycle_id' => $currentB['cycle_id'],
                'salaire_base' => $computed['salaire_base'],
                'total_brut' => $computed['total_brut'],
                'total_cotisations_salariales' => $computed['total_cotisations_salariales'],
                'total_impots' => $computed['total_impots'],
                'total_retenues' => $computed['total_retenues'],
                'net_imposable' => $computed['net_imposable'],
                'net_a_payer' => $computed['net_a_payer'],
                'total_cotisations_patronales' => $computed['total_cotisations_patronales'],
                'cout_total_employeur' => $computed['cout_total_employeur'],
                'devise' => $currentB['devise'],
                'statut_bulletin' => 'brouillon'
            ]);

            // Save lines
            foreach ($computed['rubrique_lines'] as $rl) {
                $rl['bulletin_id'] = $newBulletinId;
                PaieBulletinLigne::create($rl);
            }

            // Save Teacher Hours
            foreach ($computed['heures_lines'] as $hl) {
                $hl['bulletin_id'] = $newBulletinId;
                PaieBulletinHeure::create($hl);
            }

            // Integrate regularisations into V+1
            foreach ($pendingRegularisations as $reg) {
                PaieRegularisation::logIntegration(
                    (int)$reg['id'],
                    $newBulletinId,
                    (float)($reg['montant_brut_delta'] ?? 0.00),
                    (float)($reg['montant_net_delta'] ?? 0.00)
                );
                PaieRegularisation::updateStatus((int)$reg['id'], 'integre', $userId);
            }

            PaieAuditLog::log('paie_bulletins', $newBulletinId, "redraw_v{$newVersionNum}", $userId, $currentB, $computed);

            $db->commit();
            return $newBulletinId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Post accounting entry for an active bulletin.
     */
    public static function postAccounting(int $bulletinId, int $userId): int {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmtB = $db->prepare("SELECT periode_id FROM paie_bulletins WHERE id = :id");
            $stmtB->execute(['id' => $bulletinId]);
            $periodeId = (int)$stmtB->fetchColumn();

            if (!$periodeId) {
                throw new InvalidArgumentException("Bulletin introuvable ID #{$bulletinId}");
            }

            $locks = self::acquireLocks($db, $periodeId, $bulletinId);
            $bulletin = $locks['bulletin'];

            if (!$bulletin || !$bulletin['est_version_active']) {
                throw new LogicException("Seul un bulletin actif peut être comptabilisé.");
            }

            if ($bulletin['statut_comptabilisation'] === 'comptabilise') {
                $db->commit();
                return 0; // Already posted
            }

            $lignes = PaieBulletinLigne::findByBulletinId($bulletinId);

            $pieceId = PaieAccountingAdapter::postBulletinToGL($bulletin, $lignes, $userId);

            PaieBulletin::update($bulletinId, [
                'statut_comptabilisation' => 'comptabilise',
                'statut_bulletin' => 'valide'
            ]);

            PaieAuditLog::log('paie_bulletins', $bulletinId, 'post_accounting', $userId, null, ['piece_id' => $pieceId]);

            $db->commit();
            return $pieceId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Settle payout for an active bulletin via TreasuryService.
     */
    public static function settlePayout(int $bulletinId, int $compteFinancierId, string $modeReglement, int $userId): int {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmtB = $db->prepare("SELECT periode_id FROM paie_bulletins WHERE id = :id");
            $stmtB->execute(['id' => $bulletinId]);
            $periodeId = (int)$stmtB->fetchColumn();

            if (!$periodeId) {
                throw new InvalidArgumentException("Bulletin introuvable ID #{$bulletinId}");
            }

            $locks = self::acquireLocks($db, $periodeId, $bulletinId);
            $bulletin = $locks['bulletin'];

            if (!$bulletin || !$bulletin['est_version_active']) {
                throw new LogicException("Seul un bulletin actif peut être réglé.");
            }

            if ($bulletin['statut_reglement'] === 'paye') {
                $db->commit();
                return 0; // Already paid
            }

            $mouvementId = PaieTreasuryAdapter::settleBulletin($bulletin, $compteFinancierId, $modeReglement, $userId);

            PaieReglement::create([
                'bulletin_id' => $bulletinId,
                'compte_financier_id' => $compteFinancierId,
                'mouvement_tresorerie_id' => $mouvementId,
                'mode_reglement' => $modeReglement,
                'montant' => $bulletin['net_a_payer'],
                'date_reglement' => date('Y-m-d'),
                'cree_par' => $userId
            ]);

            PaieBulletin::update($bulletinId, [
                'statut_reglement' => 'paye'
            ]);

            PaieAuditLog::log('paie_bulletins', $bulletinId, 'settle_payout', $userId, null, ['mouvement_id' => $mouvementId]);

            $db->commit();
            return $mouvementId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Create a regularization (N+1) supporting source_type ('bulletin', 'service_fait', 'autre').
     */
    public static function createRegularization(
        int $personnelId,
        int $destinationPeriodId,
        string $sourceType,
        ?int $periodeSourceId,
        ?int $bulletinSourceId,
        string $typeRegu,
        string $motif,
        float $brutDelta,
        float $netDelta,
        int $userId
    ): int {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Verify personnel exists
            $stmtUser = $db->prepare("SELECT id_user FROM utilisateurs WHERE id_user = :id");
            $stmtUser->execute(['id' => $personnelId]);
            if (!$stmtUser->fetchColumn()) {
                throw new InvalidArgumentException("Membre du personnel introuvable ID #{$personnelId}");
            }

            // Verify destination period is open
            $stmtP = $db->prepare("SELECT * FROM paie_periodes WHERE id = :id");
            $stmtP->execute(['id' => $destinationPeriodId]);
            $destP = $stmtP->fetch(PDO::FETCH_ASSOC);

            if (!$destP || $destP['statut'] === 'cloture') {
                throw new LogicException("La période de destination N+1 doit être ouverte.");
            }

            // Validate source_type rules
            if ($sourceType === 'bulletin') {
                if (!$bulletinSourceId) {
                    throw new InvalidArgumentException("Un bulletin source doit être sélectionné.");
                }
                $stmtB = $db->prepare("SELECT * FROM paie_bulletins WHERE id = :id");
                $stmtB->execute(['id' => $bulletinSourceId]);
                $srcB = $stmtB->fetch(PDO::FETCH_ASSOC);

                if (!$srcB) {
                    throw new InvalidArgumentException("Bulletin source introuvable ID #{$bulletinSourceId}");
                }
                if ((int)$srcB['personnel_id'] !== $personnelId) {
                    throw new InvalidArgumentException("Le bulletin source #{$bulletinSourceId} n'appartient pas au salarié sélectionné.");
                }

                $periodeSourceId = (int)$srcB['periode_id'];
                if ($periodeSourceId >= $destinationPeriodId) {
                    throw new InvalidArgumentException("La période source doit être antérieure à la période de destination.");
                }
            } elseif ($sourceType === 'service_fait') {
                if (!$periodeSourceId) {
                    throw new InvalidArgumentException("Une période source est obligatoire pour un régularisation liée au service fait.");
                }
                if ($periodeSourceId >= $destinationPeriodId) {
                    throw new InvalidArgumentException("La période source doit être antérieure à la période de destination.");
                }
            } elseif ($sourceType === 'autre') {
                if (strlen(trim($motif)) < 10) {
                    throw new InvalidArgumentException("Le motif de la régularisation doit contenir au moins 10 caractères.");
                }
            } else {
                throw new InvalidArgumentException("Type de source de régularisation invalide: {$sourceType}");
            }

            $reguId = PaieRegularisation::create([
                'personnel_id' => $personnelId,
                'source_type' => $sourceType,
                'periode_source_id' => $periodeSourceId,
                'bulletin_source_id' => $bulletinSourceId,
                'periode_destination_id' => $destinationPeriodId,
                'type_regularisation' => $typeRegu,
                'motif' => $motif,
                'montant_brut_delta' => $brutDelta,
                'montant_net_delta' => $netDelta,
                'statut' => 'valide',
                'cree_par' => $userId,
                'valide_par' => $userId
            ]);

            PaieAuditLog::log('paie_regularisations', $reguId, 'create_regularization', $userId, null, [
                'personnel_id' => $personnelId,
                'source_type' => $sourceType,
                'destination_periode_id' => $destinationPeriodId,
                'brut_delta' => $brutDelta,
                'net_delta' => $netDelta
            ]);

            $db->commit();
            return $reguId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Backward compatibility wrapper for legacy createRegularizationInN1
     */
    public static function createRegularizationInN1(int $sourceBulletinId, int $destinationPeriodId, string $typeRegu, string $motif, float $brutDelta, float $netDelta, int $userId): int {
        $db = Database::getInstance();
        $stmtB = $db->prepare("SELECT personnel_id, periode_id FROM paie_bulletins WHERE id = :id");
        $stmtB->execute(['id' => $sourceBulletinId]);
        $b = $stmtB->fetch(PDO::FETCH_ASSOC);

        if (!$b) {
            throw new InvalidArgumentException("Bulletin source introuvable ID #{$sourceBulletinId}");
        }

        return self::createRegularization(
            (int)$b['personnel_id'],
            $destinationPeriodId,
            'bulletin',
            (int)$b['periode_id'],
            $sourceBulletinId,
            $typeRegu,
            $motif,
            $brutDelta,
            $netDelta,
            $userId
        );
    }
}
