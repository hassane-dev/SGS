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
     * Generate payroll bulletins (V1) for all active staff contracts in a period.
     * V5: Supports idempotency_key protection.
     */
    public static function generateBulletinsForPeriod(int $periodePaieId, int $userId, ?string $idempotencyKey = null): array {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $locks = self::acquireLocks($db, $periodePaieId);
            $periode = $locks['periode'];

            // V5 Check idempotency key if provided
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
                $personnelId = (int)$c['personnel_id'];
                $contratId = (int)$c['id'];
                $entiteJuridiqueId = (int)($c['entite_juridique_id'] ?? 1);

                // Fetch validated teacher hours for the period
                $cahierValidations = PaieCahierTexteValidation::findValidatedForTeacherAndDates($personnelId, $periode['date_debut'], $periode['date_fin']);

                // Fetch contract components / allowances
                $stmtComp = $db->prepare("SELECT * FROM personnel_contrat_composants WHERE contrat_id = :id");
                $stmtComp->execute(['id' => $contratId]);
                $components = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

                // Compute Bulletin
                $computed = PaieCalculationEngine::computeBulletin($c, $cahierValidations, $components, 'DEFAULT', $periode['date_fin']);

                // Create PaieBulletin V1
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

                // Create Lines
                foreach ($computed['rubrique_lines'] as $rl) {
                    $rl['bulletin_id'] = $bulletinId;
                    PaieBulletinLigne::create($rl);
                }

                // Create Teacher Hours Lines
                foreach ($computed['heures_lines'] as $hl) {
                    $hl['bulletin_id'] = $bulletinId;
                    PaieBulletinHeure::create($hl);
                }

                // Create Contract Snapshot
                PaieBulletinContratSnapshot::create([
                    'bulletin_id' => $bulletinId,
                    'contrat_id' => $contratId,
                    'version_num' => $c['version_num'] ?? 1,
                    'type_contrat' => $c['type_contrat_id'] ?? 'CDI',
                    'mode_calcul_principal' => $c['mode_calcul_principal'] ?? 'forfait_fixe',
                    'devise' => $c['devise'] ?? 'FCFA',
                    'raw_json_snapshot' => $c
                ]);

                // Create Rules Snapshots
                foreach ($computed['rules_snapshots'] as $rs) {
                    $rs['bulletin_id'] = $bulletinId;
                    $rsId = PaieBulletinRegleSnapshot::create($rs);
                    foreach ($rs['tranches_snapshot'] as $ts) {
                        $ts['regle_snapshot_id'] = $rsId;
                        PaieBulletinRegleTrancheSnapshot::create($ts);
                    }
                }

                PaieAuditLog::log('paie_bulletins', $bulletinId, 'create_v1', $userId, null, $computed);
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

            // Counterpass GL entry if posted
            if ($currentB['statut_comptabilisation'] === 'comptabilise') {
                PaieAccountingAdapter::reverseBulletinInGL($bulletinId, $userId, "Re-tirage bulletin vers V" . ($currentB['version_num'] + 1));
            }

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
            $computed = PaieCalculationEngine::computeBulletin($contract, $cahierValidations, $components, 'DEFAULT', $locks['periode']['date_fin']);

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

            // Lock acquisition V6: paie_periodes -> comptabilite_periodes -> paie_bulletins
            $locks = self::acquireLocks($db, $periodeId, $bulletinId);
            $bulletin = $locks['bulletin'];

            if (!$bulletin || !$bulletin['est_version_active']) {
                throw new LogicException("Seul un bulletin actif peut être comptabilisé.");
            }

            // V3: Check unique accounting piece protection
            if ($bulletin['statut_comptabilisation'] === 'comptabilise') {
                $db->commit();
                return 0; // Already posted idempotently
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

            // Lock acquisition V6: paie_periodes -> comptabilite_periodes -> paie_bulletins
            $locks = self::acquireLocks($db, $periodeId, $bulletinId);
            $bulletin = $locks['bulletin'];

            if (!$bulletin || !$bulletin['est_version_active']) {
                throw new LogicException("Seul un bulletin actif peut être réglé.");
            }

            if ($bulletin['statut_reglement'] === 'paye') {
                $db->commit();
                return 0; // Already paid idempotently
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
     * Create an N+1 regularization when a closed period needs adjustment.
     */
    public static function createRegularizationInN1(int $sourceBulletinId, int $destinationPeriodId, string $typeRegu, string $motif, float $brutDelta, float $netDelta, int $userId): int {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Verify source bulletin exists
            $stmtB = $db->prepare("SELECT * FROM paie_bulletins WHERE id = :id");
            $stmtB->execute(['id' => $sourceBulletinId]);
            $sourceB = $stmtB->fetch(PDO::FETCH_ASSOC);

            if (!$sourceB) {
                throw new InvalidArgumentException("Bulletin source introuvable ID #{$sourceBulletinId}");
            }

            // Verify destination period is open
            $stmtP = $db->prepare("SELECT * FROM paie_periodes WHERE id = :id");
            $stmtP->execute(['id' => $destinationPeriodId]);
            $destP = $stmtP->fetch(PDO::FETCH_ASSOC);

            if (!$destP || $destP['statut'] === 'cloture') {
                throw new LogicException("La période de destination N+1 doit être ouverte.");
            }

            $reguId = PaieRegularisation::create([
                'bulletin_source_id' => $sourceBulletinId,
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
                'source_bulletin_id' => $sourceBulletinId,
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
}
