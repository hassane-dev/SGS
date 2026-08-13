<?php
// src/services/ForecastService.php

require_once __DIR__ . '/../config/database.php';

class ForecastService {

    /**
     * Get monthly cash flows for the last N months to use as training history
     */
    public static function getMonthlyHistory($lyceeId, $limitMonths = 12) {
        $db = Database::getInstance();

        // Standardized query grouping cash receipts (entree) and outputs (sortie) by month
        // We select the year and month using database-agnostic PHP post-processing or standard SQL SUBSTR if SQLite/MySQL.
        // Let's use standard database-agnostic datetime formatting.
        $sql = "
            SELECT date_mouvement, type_mouvement, montant
            FROM mouvements_tresorerie
            WHERE lycee_id = :lycee_id
            ORDER BY date_mouvement ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(['lycee_id' => $lyceeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $monthlyData = [];
        foreach ($rows as $r) {
            $monthKey = date('Y-m', strtotime($r['date_mouvement']));
            if (!isset($monthlyData[$monthKey])) {
                $monthlyData[$monthKey] = ['entrees' => 0.00, 'sorties' => 0.00, 'net' => 0.00];
            }
            $amount = (float)$r['montant'];
            if ($r['type_mouvement'] === 'entree') {
                $monthlyData[$monthKey]['entrees'] += $amount;
            } else {
                $monthlyData[$monthKey]['sorties'] += $amount;
            }
            $monthlyData[$monthKey]['net'] = $monthlyData[$monthKey]['entrees'] - $monthlyData[$monthKey]['sorties'];
        }

        // Sort by month ascending and slice to limit
        ksort($monthlyData);
        $history = [];
        foreach ($monthlyData as $month => $metrics) {
            $history[] = [
                'mois' => $month,
                'entrees' => $metrics['entrees'],
                'sorties' => $metrics['sorties'],
                'net' => $metrics['net']
            ];
        }

        return array_slice($history, -$limitMonths);
    }

    /**
     * Execute prediction on a series of history points
     */
    public static function predict($lyceeId, $method = 'moving_average', $horizon = 3, $scenario = 'central', $hypotheses = []) {
        $history = self::getMonthlyHistory($lyceeId, 12);
        $count = count($history);

        // Fail-fast safeguard: if history is less than 3 months, reject projection cleanly
        if ($count < 3) {
            return [
                'valeur_prevue' => 0.00,
                'borne_basse' => 0.00,
                'borne_haute' => 0.00,
                'methode' => $method,
                'horizon' => $horizon,
                'historique_utilise' => $history,
                'date_calcul' => date('Y-m-d H:i:s'),
                'statut_qualite' => 'INSUFFISANT',
                'message' => 'Historique insuffisant pour générer des prévisions fiables (minimum 3 mois requis).'
            ];
        }

        $values = array_column($history, 'net');

        // Apply method
        $baseline = 0.00;
        switch ($method) {
            case 'baseline':
                // Simply take the last month's value
                $baseline = end($values);
                break;

            case 'moving_average':
                // Average of last 3 months
                $last3 = array_slice($values, -3);
                $baseline = array_sum($last3) / count($last3);
                break;

            case 'weighted_moving_average':
                // Weighted average of last 3 months: weights 0.5 (last), 0.3, 0.2
                $last3 = array_slice($values, -3);
                if (count($last3) === 3) {
                    $baseline = ($last3[2] * 0.5) + ($last3[1] * 0.3) + ($last3[0] * 0.2);
                } else {
                    $baseline = array_sum($last3) / count($last3);
                }
                break;

            case 'linear_trend':
                // Simple Linear Regression: y = mx + b
                $n = count($values);
                $sumX = 0;
                $sumY = 0;
                $sumXY = 0;
                $sumXX = 0;
                for ($i = 0; $i < $n; $i++) {
                    $x = $i + 1;
                    $y = $values[$i];
                    $sumX += $x;
                    $sumY += $y;
                    $sumXY += ($x * $y);
                    $sumXX += ($x * $x);
                }
                $denominator = ($n * $sumXX) - ($sumX * $sumX);
                if ($denominator != 0) {
                    $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
                    $intercept = ($sumY - ($slope * $sumX)) / $n;
                    // Predict for the next step (n + horizon)
                    $baseline = ($slope * ($n + 1)) + $intercept;
                } else {
                    $baseline = end($values);
                }
                break;

            case 'exponential_smoothing':
                // Simple Exponential Smoothing (alpha = 0.3)
                $alpha = 0.3;
                $s = $values[0];
                for ($i = 1; $i < $count; $i++) {
                    $s = ($alpha * $values[$i]) + ((1 - $alpha) * $s);
                }
                $baseline = $s;
                break;

            default:
                $baseline = array_sum($values) / $count;
                break;
        }

        // Apply Scenario Adjustments
        // Prudent: Receipts decrease or expenses increase -> reduces net cash by 15%
        // Optimiste: Receipts increase or expenses decrease -> increases net cash by 15%
        // Central: Baseline
        $predicted = $baseline;
        $variance = abs($baseline * 0.15);
        if ($variance < 1000) {
            $variance = 50000; // default margin
        }

        if ($scenario === 'prudent') {
            $predicted = $baseline - $variance;
        } elseif ($scenario === 'optimiste') {
            $predicted = $baseline + $variance;
        }

        $borne_basse = $predicted - $variance;
        $borne_haute = $predicted + $variance;

        // Statut de qualité
        $statut_qualite = 'EXCELLENT';
        if ($count < 6) {
            $statut_qualite = 'BON';
        }

        return [
            'valeur_prevue' => round($predicted, 2),
            'borne_basse' => round($borne_basse, 2),
            'borne_haute' => round($borne_haute, 2),
            'methode' => $method,
            'horizon' => $horizon,
            'historique_utilise' => $history,
            'date_calcul' => date('Y-m-d H:i:s'),
            'statut_qualite' => $statut_qualite,
            'scenarios' => [
                'prudent' => round($baseline - $variance, 2),
                'central' => round($baseline, 2),
                'optimiste' => round($baseline + $variance, 2)
            ]
        ];
    }
}
?>
