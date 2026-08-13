<?php
// src/services/ReportingService.php

require_once __DIR__ . '/../config/database.php';

class ReportingService {

    /**
     * Generate or update a KPI snapshot
     */
    public static function generateSnapshot($lyceeId, $exerciceId, $periodeId, $dateSnapshot, $frequence, $kpiCode, $valeur, $devise = 'FCFA', $sourceVersion = '1.0') {
        $db = Database::getInstance();

        // Check if snapshot already exists
        $sql_check = "
            SELECT id FROM reporting_snapshots
            WHERE lycee_id = :lycee_id
            AND exercice_financier_id = :ex_id
            AND date_snapshot = :dt
            AND frequence = :freq
            AND kpi_code = :kpi
        ";
        $stmt_check = $db->prepare($sql_check);
        $stmt_check->execute([
            'lycee_id' => $lyceeId,
            'ex_id' => $exerciceId,
            'dt' => $dateSnapshot,
            'freq' => $frequence,
            'kpi' => $kpiCode
        ]);
        $existingId = $stmt_check->fetchColumn();

        if ($existingId) {
            // Update
            $sql_up = "
                UPDATE reporting_snapshots
                SET valeur = :val, devise = :dev, source_version = :ver, periode_id = :per_id
                WHERE id = :id
            ";
            $stmt_up = $db->prepare($sql_up);
            return $stmt_up->execute([
                'val' => $valeur,
                'dev' => $devise,
                'ver' => $sourceVersion,
                'per_id' => $periodeId,
                'id' => $existingId
            ]);
        } else {
            // Insert
            $sql_ins = "
                INSERT INTO reporting_snapshots (lycee_id, exercice_financier_id, periode_id, date_snapshot, frequence, kpi_code, valeur, devise, source_version)
                VALUES (:lycee_id, :ex_id, :per_id, :dt, :freq, :kpi, :val, :dev, :ver)
            ";
            $stmt_ins = $db->prepare($sql_ins);
            return $stmt_ins->execute([
                'lycee_id' => $lyceeId,
                'ex_id' => $exerciceId,
                'per_id' => $periodeId,
                'dt' => $dateSnapshot,
                'freq' => $frequence,
                'kpi' => $kpiCode,
                'val' => $valeur,
                'dev' => $devise,
                'ver' => $sourceVersion
            ]);
        }
    }

    /**
     * Get thresholds for a lycée
     */
    public static function getThresholds($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM reporting_kpi_seuils WHERE lycee_id = :lycee_id");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $thresholds = [];
        foreach ($rows as $r) {
            $thresholds[$r['kpi_code']] = $r;
        }
        return $thresholds;
    }

    /**
     * Save/update threshold
     */
    public static function saveThreshold($lyceeId, $kpiCode, $seuilMin, $seuilWarning, $objectif, $seuilDanger, $sensVariation) {
        $db = Database::getInstance();

        $stmt_check = $db->prepare("SELECT id FROM reporting_kpi_seuils WHERE lycee_id = :lycee_id AND kpi_code = :kpi");
        $stmt_check->execute(['lycee_id' => $lyceeId, 'kpi' => $kpiCode]);
        $existingId = $stmt_check->fetchColumn();

        if ($existingId) {
            $sql = "
                UPDATE reporting_kpi_seuils
                SET seuil_min = :min, seuil_warning = :warn, objectif = :obj, seuil_danger = :dang, sens_variation = :sens, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'min' => $seuilMin,
                'warn' => $seuilWarning,
                'obj' => $objectif,
                'dang' => $seuilDanger,
                'sens' => $sensVariation,
                'id' => $existingId
            ]);
        } else {
            $sql = "
                INSERT INTO reporting_kpi_seuils (lycee_id, kpi_code, seuil_min, seuil_warning, objectif, seuil_danger, sens_variation)
                VALUES (:lycee_id, :kpi, :min, :warn, :obj, :dang, :sens)
            ";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'lycee_id' => $lyceeId,
                'kpi' => $kpiCode,
                'min' => $seuilMin,
                'warn' => $seuilWarning,
                'obj' => $objectif,
                'dang' => $seuilDanger,
                'sens' => $sensVariation
            ]);
        }
    }

    /**
     * Evaluate the visual class of a KPI value based on thresholds
     */
    public static function evaluateKpiValue($lyceeId, $kpiCode, $valeur) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM reporting_kpi_seuils WHERE lycee_id = :lycee_id AND kpi_code = :kpi");
        $stmt->execute(['lycee_id' => $lyceeId, 'kpi' => $kpiCode]);
        $th = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$th) {
            // Default visual checks if not set
            if ($kpiCode === 'taux_recouvrement' || $kpiCode === 'disponible_budget') {
                return $valeur >= 70 ? 'success' : ($valeur >= 40 ? 'warning' : 'danger');
            }
            return 'success';
        }

        $val = (float)$valeur;
        $min = (float)$th['seuil_min'];
        $warn = (float)$th['seuil_warning'];
        $obj = (float)$th['objectif'];
        $dang = (float)$th['seuil_danger'];
        $sens = $th['sens_variation'];

        if ($sens === 'croissant') {
            // Higher is better
            if ($val >= $obj) return 'success';
            if ($val <= $dang) return 'danger';
            if ($val <= $warn) return 'warning';
            return 'success';
        } else {
            // Lower is better (e.g. charges, outputs, supplier debt)
            if ($val <= $obj) return 'success';
            if ($val >= $dang) return 'danger';
            if ($val >= $warn) return 'warning';
            return 'success';
        }
    }

    /**
     * Log a sensitive decision-support reporting action
     */
    public static function logAudit($userId, $lyceeId, $operation, $details) {
        $db = Database::getInstance();
        $sql = "INSERT INTO reporting_audit_logs (user_id, lycee_id, operation, details) VALUES (:u, :l, :op, :dt)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'u' => $userId,
            'l' => $lyceeId,
            'op' => $operation,
            'dt' => $details
        ]);
    }
}
?>
