<?php
// src/services/BudgetControlService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/BudgetLigne.php';
require_once __DIR__ . '/../models/BudgetHistorique.php';
require_once __DIR__ . '/../core/Auth.php';

class BudgetControlService {

    /**
     * Check if a budget is active and has sufficient funds for a given line.
     * Returns associative array with status & message.
     */
    public static function checkAvailability($ligneId, $amount) {
        $ligne = BudgetLigne::findById($ligneId);
        if (!$ligne) {
            return [
                'disponible' => false,
                'solde_restant' => 0.00,
                'message' => "Ligne budgétaire introuvable."
            ];
        }

        $budget = Budget::findById($ligne['budget_id']);
        if (!$budget || $budget['statut'] !== 'actif') {
            return [
                'disponible' => false,
                'solde_restant' => 0.00,
                'message' => "Le budget n'est pas actif pour cette ligne."
            ];
        }

        // Available budget calculation:
        // Available = allocation_initiale + ajustements - engage - consomme
        $disponible = (float)$ligne['allocation_initiale'] + (float)$ligne['montant_ajustements'] - (float)$ligne['montant_engage'] - (float)$ligne['montant_consomme'];

        if ($disponible >= (float)$amount) {
            return [
                'disponible' => true,
                'solde_restant' => $disponible - (float)$amount,
                'message' => "Budget disponible."
            ];
        } else {
            return [
                'disponible' => false,
                'solde_restant' => $disponible,
                'message' => "Fonds insuffisants sur la ligne budgétaire (Requis: $amount, Disponible: $disponible)."
            ];
        }
    }

    /**
     * Verify whether an operation is authorized (e.g. at submission or payment).
     * Supports exceptional override if the user has 'budget.override' permission.
     */
    public static function authorizeSpending($lyceeId, $exerciceId, $categorieId, $centreCoutId, $amount, $userId) {
        // Find if a budget exists for this exercise
        $budget = Budget::findByLyceeAndExercice($lyceeId, $exerciceId);
        if (!$budget) {
            // If no budget is configured, we bypass and allow it (backward compatibility / progressive activation)
            return [
                'authorized' => true,
                'bypass' => true,
                'ligne_id' => null,
                'message' => "Aucun budget configuré pour cet exercice financier."
            ];
        }

        if ($budget['statut'] !== 'actif') {
            throw new Exception("Le budget de l'exercice financier n'est pas actif (Statut: " . $budget['statut'] . ").");
        }

        $ligne = BudgetLigne::findByUnique($budget['id'], $categorieId, $centreCoutId);
        if (!$ligne) {
            throw new Exception("Aucune ligne budgétaire configurée pour cette catégorie de dépenses et ce centre de coûts.");
        }

        $check = self::checkAvailability($ligne['id'], $amount);
        if ($check['disponible']) {
            return [
                'authorized' => true,
                'bypass' => false,
                'ligne_id' => $ligne['id'],
                'message' => "Autorisé."
            ];
        }

        // If insufficient, check for budget override permission
        if (Auth::can('override', 'budget') || (isset($_SESSION['user']['permissions']['budget']) && in_array('override', $_SESSION['user']['permissions']['budget']))) {
            // Log the exceptional override
            BudgetHistorique::log(
                $lyceeId,
                'BudgetExceeded',
                "DÉPASSEMENT BUDGÉTAIRE AUTORISÉ : Utilisateur ID $userId a forcé une dépense de $amount sur la ligne ID " . $ligne['id'] . " (Disponible: " . $check['solde_restant'] . ").",
                $userId
            );
            return [
                'authorized' => true,
                'bypass' => false,
                'ligne_id' => $ligne['id'],
                'message' => "Autorisé de manière exceptionnelle par dépassement de budget."
            ];
        }

        return [
            'authorized' => false,
            'bypass' => false,
            'ligne_id' => $ligne['id'],
            'message' => $check['message']
        ];
    }
}
?>