<?php
// src/services/BudgetReportingService.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../models/BudgetLigne.php';

class BudgetReportingService {

    /**
     * Get synthetic summary for a budget
     */
    public static function getBudgetSummary($budgetId) {
        $lines = BudgetLigne::findByBudget($budgetId);

        $initial = 0.00;
        $ajustements = 0.00;
        $engaged = 0.00;
        $consumed = 0.00;

        foreach ($lines as $l) {
            $initial += (float)$l['allocation_initiale'];
            $ajustements += (float)$l['montant_ajustements'];
            $engaged += (float)$l['montant_engage'];
            $consumed += (float)$l['montant_consomme'];
        }

        $total_credits = $initial + $ajustements;
        $total_utilise = $engaged + $consumed;
        $disponible = $total_credits - $total_utilise;

        $taux_consommation = $total_credits > 0 ? ($total_utilise / $total_credits) * 100 : 0;

        return [
            'allocation_initiale' => $initial,
            'montant_ajustements' => $ajustements,
            'credits_totaux' => $total_credits,
            'montant_engage' => $engaged,
            'montant_consomme' => $consumed,
            'total_utilise' => $total_utilise,
            'montant_disponible' => $disponible,
            'taux_consommation' => round($taux_consommation, 2)
        ];
    }

    /**
     * Get details for a specific budget line with helper metrics
     */
    public static function getLigneDetails($ligneId) {
        $l = BudgetLigne::findById($ligneId);
        if (!$l) return null;

        return self::formatLineMetrics($l);
    }

    /**
     * Get all lines for a given budget with styled indicator classes
     */
    public static function getBudgetLinesWithMetrics($budgetId) {
        $lines = BudgetLigne::findByBudget($budgetId);
        $formatted = [];
        foreach ($lines as $l) {
            $formatted[] = self::formatLineMetrics($l);
        }
        return $formatted;
    }

    /**
     * Helper to compute progress bar colors & ratios
     */
    private static function formatLineMetrics($l) {
        $initial = (float)$l['allocation_initiale'];
        $ajustements = (float)$l['montant_ajustements'];
        $engaged = (float)$l['montant_engage'];
        $consumed = (float)$l['montant_consomme'];

        $total_credits = $initial + $ajustements;
        $utilise = $engaged + $consumed;
        $disponible = $total_credits - $utilise;

        $pct = $total_credits > 0 ? ($utilise / $total_credits) * 100 : 0;

        // Visual alert indicators
        if ($pct >= 90) {
            $status_color = 'danger';
            $status_badge = 'bg-light-danger text-danger';
        } elseif ($pct >= 70) {
            $status_color = 'warning';
            $status_badge = 'bg-light-warning text-warning';
        } else {
            $status_color = 'success';
            $status_badge = 'bg-light-success text-success';
        }

        return array_merge($l, [
            'credits_totaux' => $total_credits,
            'total_utilise' => $utilise,
            'disponible' => $disponible,
            'pourcentage_consomme' => round($pct, 2),
            'status_color' => $status_color,
            'status_badge' => $status_badge
        ]);
    }
}
?>