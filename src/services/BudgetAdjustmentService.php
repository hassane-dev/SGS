<?php
// src/services/BudgetAdjustmentService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/BudgetLigne.php';
require_once __DIR__ . '/../models/BudgetAjustement.php';
require_once __DIR__ . '/../models/BudgetHistorique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/BudgetService.php';

class BudgetAdjustmentService {

    /**
     * Transfer budget credits from one line to another
     */
    public static function transferBudget($sourceLineId, $destLineId, $amount, $userId, $reason) {
        if (!Auth::can('transfer', 'budget')) {
            if (!isset($_SESSION['user']['permissions']['budget']) || !in_array('transfer', $_SESSION['user']['permissions']['budget'])) {
                throw new Exception("FORBIDDEN : Action de transfert budgétaire non autorisée.");
            }
        }

        if (empty($reason)) {
            throw new Exception("La justification/motif du virement budgétaire est obligatoire.");
        }

        $amount = (float)$amount;
        if ($amount <= 0) {
            throw new Exception("Le montant du virement doit être supérieur à zéro.");
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $srcLine = BudgetLigne::findById($sourceLineId);
            $dstLine = BudgetLigne::findById($destLineId);

            if (!$srcLine || !$dstLine) {
                throw new Exception("Ligne budgétaire source ou destination introuvable.");
            }

            if ($srcLine['budget_id'] !== $dstLine['budget_id']) {
                throw new Exception("Les lignes budgétaires doivent appartenir au même exercice budgétaire.");
            }

            // Check remaining available credits on source line to avoid negative budget
            $disponibleSrc = (float)$srcLine['allocation_initiale'] + (float)$srcLine['montant_ajustements'] - (float)$srcLine['montant_engage'] - (float)$srcLine['montant_consomme'];
            if ($disponibleSrc < $amount) {
                throw new Exception("Solde insuffisant sur la ligne source pour effectuer ce transfert (Disponible: $disponibleSrc, Requis: $amount).");
            }

            $budget = Budget::findById($srcLine['budget_id']);
            if (!$budget) {
                throw new Exception("Budget introuvable.");
            }

            // Enforce Lycée Isolation check
            $userLyceeId = Auth::getLyceeId();
            if ($userLyceeId !== null && $budget['lycee_id'] !== $userLyceeId) {
                throw new Exception("FORBIDDEN : Accès refusé pour cet établissement.");
            }

            // Create Adjustment record
            BudgetAjustement::create([
                'lycee_id' => $budget['lycee_id'],
                'type_ajustement' => 'transfert',
                'ligne_source_id' => $sourceLineId,
                'ligne_destination_id' => $destLineId,
                'montant' => $amount,
                'motif' => $reason,
                'execute_par' => $userId
            ]);

            // Recompute balances for both lines
            BudgetService::rebuildBudget($budget['id']);

            BudgetHistorique::log(
                $budget['lycee_id'],
                'BudgetAdjusted',
                "VIREMENT DE CRÉDITS : Transfert de $amount de la ligne ID $sourceLineId vers la ligne ID $destLineId. Justification: $reason",
                $userId
            );

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Allocate additional emergency budget credits directly to a line
     */
    public static function allocateExtra($ligneId, $amount, $userId, $reason) {
        if (!Auth::can('adjust', 'budget')) {
            if (!isset($_SESSION['user']['permissions']['budget']) || !in_array('adjust', $_SESSION['user']['permissions']['budget'])) {
                throw new Exception("FORBIDDEN : Action de dotation budgétaire exceptionnelle non autorisée.");
            }
        }

        if (empty($reason)) {
            throw new Exception("La justification/motif de la dotation supplémentaire est obligatoire.");
        }

        $amount = (float)$amount;
        if ($amount <= 0) {
            throw new Exception("Le montant de la dotation supplémentaire doit être supérieur à zéro.");
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $line = BudgetLigne::findById($ligneId);
            if (!$line) {
                throw new Exception("Ligne budgétaire introuvable.");
            }

            $budget = Budget::findById($line['budget_id']);
            if (!$budget) {
                throw new Exception("Budget introuvable.");
            }

            // Enforce Lycée Isolation check
            $userLyceeId = Auth::getLyceeId();
            if ($userLyceeId !== null && $budget['lycee_id'] !== $userLyceeId) {
                throw new Exception("FORBIDDEN : Accès refusé pour cet établissement.");
            }

            // Create Adjustment record
            BudgetAjustement::create([
                'lycee_id' => $budget['lycee_id'],
                'type_ajustement' => 'dotation_supplementaire',
                'ligne_source_id' => null,
                'ligne_destination_id' => $ligneId,
                'montant' => $amount,
                'motif' => $reason,
                'execute_par' => $userId
            ]);

            // Recompute balances for the budget
            BudgetService::rebuildBudget($budget['id']);

            BudgetHistorique::log(
                $budget['lycee_id'],
                'BudgetAdjusted',
                "DOTATION CRÉDIT EXCEPTIONNELLE : Ajout de $amount sur la ligne ID $ligneId. Justification: $reason",
                $userId
            );

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
?>