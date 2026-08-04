<?php
// src/services/BudgetWorkflowService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/BudgetHistorique.php';

class BudgetWorkflowService {

    /**
     * Submit budget for validation
     */
    public static function submit($budgetId, $userId) {
        self::checkPermission('update', 'budget');
        return self::transitionState($budgetId, 'soumis', $userId, "Soumission du budget pour validation");
    }

    /**
     * Approve / Validate budget
     */
    public static function validateBudget($budgetId, $userId) {
        self::checkPermission('activate', 'budget');
        return self::transitionState($budgetId, 'valide', $userId, "Validation officielle du budget");
    }

    /**
     * Activate budget
     */
    public static function activate($budgetId, $userId) {
        self::checkPermission('activate', 'budget');

        $db = Database::getInstance();
        $budget = Budget::findById($budgetId);
        if (!$budget) {
            throw new Exception("Budget introuvable.");
        }

        // Deactivate any currently active budget for this lycee and academic year if needed,
        // although UNIQUE (lycee_id, exercice_financier_id) restricts having multiple budgets for the same year anyway.

        return self::transitionState($budgetId, 'actif', $userId, "Activation du budget annuel");
    }

    /**
     * Close budget
     */
    public static function close($budgetId, $userId) {
        self::checkPermission('close', 'budget');
        return self::transitionState($budgetId, 'clos', $userId, "Clôture définitive du budget annuel");
    }

    /**
     * Transition state helper
     */
    private static function transitionState($budgetId, $newStatus, $userId, $motif = "") {
        $db = Database::getInstance();
        $budget = Budget::findById($budgetId);
        if (!$budget) {
            throw new Exception("Budget introuvable.");
        }

        // Lycee isolation check
        $userLyceeId = Auth::getLyceeId();
        if ($userLyceeId !== null && $budget['lycee_id'] !== $userLyceeId) {
            throw new Exception("FORBIDDEN : Accès refusé pour cet établissement.");
        }

        self::validateTransition($budget['statut'], $newStatus);

        $db->beginTransaction();
        try {
            Budget::updateStatus($budgetId, $newStatus);

            // Map event names
            $evtMap = [
                'soumis' => 'BudgetSubmitted',
                'valide' => 'BudgetApproved',
                'actif' => 'BudgetActivated',
                'clos' => 'BudgetClosed'
            ];
            $evtName = $evtMap[$newStatus] ?? 'BudgetStatusChanged';

            BudgetHistorique::log(
                $budget['lycee_id'],
                $evtName,
                "Budget '{$budget['libelle']}' (ID: $budgetId) transité de '{$budget['statut']}' vers '$newStatus'. Motif: $motif",
                $userId
            );

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function validateTransition($oldStatus, $newStatus) {
        $allowed = [
            'brouillon' => ['soumis'],
            'soumis' => ['valide', 'brouillon'],
            'valide' => ['actif'],
            'actif' => ['clos'],
            'clos' => []
        ];

        if (!isset($allowed[$oldStatus]) || !in_array($newStatus, $allowed[$oldStatus])) {
            throw new Exception("Transition budgétaire non autorisée de '$oldStatus' vers '$newStatus'.");
        }
    }

    private static function checkPermission($action, $resource) {
        if (!Auth::can($action, $resource)) {
            if (isset($_SESSION['user']['permissions'][$resource]) && in_array($action, $_SESSION['user']['permissions'][$resource])) {
                return true;
            }
            throw new Exception("FORBIDDEN : Action non autorisée pour votre profil utilisateur.");
        }
    }
}
?>