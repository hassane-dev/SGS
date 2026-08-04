<?php
// src/services/BudgetService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/BudgetLigne.php';
require_once __DIR__ . '/../models/BudgetEngagement.php';
require_once __DIR__ . '/../models/BudgetHistorique.php';
require_once __DIR__ . '/../models/Depense.php';

class BudgetService {

    public static function createBudget($data) {
        return Budget::create($data);
    }

    public static function createBudgetLine($data) {
        $budget = Budget::findById($data['budget_id']);
        if (!$budget) {
            throw new Exception("Budget introuvable.");
        }
        if ($budget['statut'] !== 'brouillon') {
            throw new Exception("Impossible d'ajouter des lignes budgétaires à un budget déjà approuvé, actif ou clos.");
        }

        return BudgetLigne::create($data);
    }

    /**
     * Reserve a budget amount for a given expense
     */
    public static function reserve($depenseId, $amount, $ligneId) {
        $db = Database::getInstance();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            // Lock budget line to prevent concurrent double reservation
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = "SELECT * FROM budget_lignes WHERE id = :id";
            if ($driver !== 'sqlite') {
                $lockSql .= " FOR UPDATE";
            }
            $stmt = $db->prepare($lockSql);
            $stmt->execute(['id' => $ligneId]);
            $ligne = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ligne) {
                throw new Exception("Ligne budgétaire introuvable.");
            }

            // Create Engagement record
            $engagementId = BudgetEngagement::create([
                'depense_id' => $depenseId,
                'budget_ligne_id' => $ligneId,
                'montant' => $amount,
                'statut' => 'reserve'
            ]);

            // Recompute / Update Ligne Balances
            self::recomputeLigneBalances($ligneId);

            BudgetHistorique::log(
                $db->query("SELECT lycee_id FROM budgets WHERE id = " . $ligne['budget_id'])->fetchColumn(),
                'BudgetExceeded', // Just general event or BudgetReserved
                "Réservation budgétaire de $amount pour la dépense ID: $depenseId sur la ligne ID: $ligneId.",
                $_SESSION['user']['id_user'] ?? 1
            );

            if (!$inTransaction) {
                $db->commit();
            }
            return $engagementId;
        } catch (Exception $e) {
            if (!$inTransaction) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Confirm/Promote the reservation to 'engage' state on approval
     */
    public static function engage($depenseId) {
        $db = Database::getInstance();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $engagement = BudgetEngagement::findByDepense($depenseId);
            if (!$engagement) {
                // If budget checking is bypassed/not set, just succeed silently
                if (!$inTransaction) { $db->commit(); }
                return true;
            }

            if ($engagement['statut'] !== 'reserve') {
                throw new Exception("L'engagement n'est pas à l'état 'reserve'. Statut actuel: " . $engagement['statut']);
            }

            BudgetEngagement::updateStatus($engagement['id'], 'engage');
            self::recomputeLigneBalances($engagement['budget_ligne_id']);

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

    /**
     * Consume the budget when an expense is paid
     */
    public static function consume($depenseId) {
        $db = Database::getInstance();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $engagement = BudgetEngagement::findByDepense($depenseId);
            if (!$engagement) {
                if (!$inTransaction) { $db->commit(); }
                return true;
            }

            if (!in_array($engagement['statut'], ['reserve', 'engage'])) {
                throw new Exception("L'engagement ne peut pas être consommé. Statut actuel: " . $engagement['statut']);
            }

            BudgetEngagement::updateStatus($engagement['id'], 'consomme');
            self::recomputeLigneBalances($engagement['budget_ligne_id']);

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

    /**
     * Release the budget reservation/engagement when an expense is rejected
     */
    public static function release($depenseId) {
        $db = Database::getInstance();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $engagement = BudgetEngagement::findByDepense($depenseId);
            if (!$engagement) {
                if (!$inTransaction) { $db->commit(); }
                return true;
            }

            if (!in_array($engagement['statut'], ['reserve', 'engage'])) {
                throw new Exception("L'engagement ne peut pas être libéré. Statut actuel: " . $engagement['statut']);
            }

            BudgetEngagement::updateStatus($engagement['id'], 'libere');
            self::recomputeLigneBalances($engagement['budget_ligne_id']);

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

    /**
     * Restore/revert consumption when a paid expense is cancelled (against-passation)
     */
    public static function restore($depenseId) {
        $db = Database::getInstance();
        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }

        try {
            $engagement = BudgetEngagement::findByDepense($depenseId);
            if (!$engagement) {
                if (!$inTransaction) { $db->commit(); }
                return true;
            }

            if ($engagement['statut'] !== 'consomme') {
                throw new Exception("L'engagement ne peut pas être restauré car il n'a pas été consommé.");
            }

            BudgetEngagement::updateStatus($engagement['id'], 'annule');
            self::recomputeLigneBalances($engagement['budget_ligne_id']);

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

    /**
     * Rebuild a budget by recomputing ALL line balances from active adjustments and engagements.
     */
    public static function rebuildBudget($budgetId) {
        $db = Database::getInstance();
        $lines = BudgetLigne::findByBudget($budgetId);

        $inTransaction = $db->inTransaction();
        if (!$inTransaction) {
            $db->beginTransaction();
        }
        try {
            foreach ($lines as $line) {
                self::recomputeLigneBalances($line['id']);
            }
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

    /**
     * Recompute and save balances for a specific budget line
     */
    private static function recomputeLigneBalances($ligneId) {
        $db = Database::getInstance();

        // 1. Calculate adjustments (dotation_supplementaire added to line, or transfer source vs destination)
        $stmt_adj_dest = $db->prepare("SELECT SUM(montant) FROM budget_ajustements WHERE ligne_destination_id = :id");
        $stmt_adj_dest->execute(['id' => $ligneId]);
        $destSum = (float)$stmt_adj_dest->fetchColumn();

        $stmt_adj_src = $db->prepare("SELECT SUM(montant) FROM budget_ajustements WHERE ligne_source_id = :id");
        $stmt_adj_src->execute(['id' => $ligneId]);
        $srcSum = (float)$stmt_adj_src->fetchColumn();

        $ajustements = $destSum - $srcSum;

        // 2. Calculate engaged (sum of 'reserve' and 'engage')
        $stmt_eng = $db->prepare("SELECT SUM(montant) FROM budget_engagements WHERE budget_ligne_id = :id AND statut IN ('reserve', 'engage')");
        $stmt_eng->execute(['id' => $ligneId]);
        $engaged = (float)$stmt_eng->fetchColumn();

        // 3. Calculate consumed ('consomme')
        $stmt_con = $db->prepare("SELECT SUM(montant) FROM budget_engagements WHERE budget_ligne_id = :id AND statut = 'consomme'");
        $stmt_con->execute(['id' => $ligneId]);
        $consumed = (float)$stmt_con->fetchColumn();

        BudgetLigne::updateBalances($ligneId, $ajustements, $engaged, $consumed);
    }
}
?>