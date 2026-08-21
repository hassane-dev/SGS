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
     * Generate payroll bulletins (V1) for all active staff contracts in a period.
     * V5: Supports idempotency_key protection.
     */
    public static function generateBulletinsForPeriod(int $periodePaieId, int $userId, ?string $idempotencyKey = null): array {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $locks = self::acquireLocks($db, $periodePaieId);
            $periode = $locks['periode'];

            if ($periode['statut'] === 'cloture') {
                throw new LogicException("Impossible de calculer la paie : la période de paie est clôturée.");
            }

            // Check idempotency key if provided
            if ($idempotencyKey) {
                $stmtEx = $db->prepare("SELECT id FROM paie_bulletins WHERE periode_id = :p AND idempotency_key LIKE :k ORDER BY id ASC");
                $stmtEx->execute(['p' => $periodePaieId, 'k' => "{$idempotencyKey}%"]);
                $existingRows = $stmtEx->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($existingRows)) {
                    $db->commit();
                    return array_map('intval', $existingRows);
                }
            }

            // Find all active personnel contracts
            $stmtContracts = $db->prepare("
                SELECT c.*, u.nom, u.prenom
                FROM personnel_contrats_historique c
                JOIN utilisateurs u ON c.personnel_id = u.id_user
                WHERE c.statut_contrat = 'actif'
            ");
            $stmtContracts->execute();
            $contracts = $stmtContracts->fetchAll(PDO::FETCH_ASSOC);

            $createdBulletinIds = [];

            foreach ($contracts as $c) {
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
