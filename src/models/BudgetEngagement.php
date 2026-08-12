<?php
// src/models/BudgetEngagement.php

require_once __DIR__ . '/../config/database.php';

class BudgetEngagement {

    private static $hasSourceCols = null;

    private static function checkSourceColumns() {
        if (self::$hasSourceCols !== null) {
            return self::$hasSourceCols;
        }
        $db = Database::getInstance();
        try {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $stmt = $db->prepare("PRAGMA table_info(budget_engagements)");
                $stmt->execute();
                $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
            } else {
                $stmt = $db->query("SHOW COLUMNS FROM budget_engagements");
                $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            }
            self::$hasSourceCols = in_array('source_type', $cols);
        } catch (Exception $e) {
            self::$hasSourceCols = false;
        }
        return self::$hasSourceCols;
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budget_engagements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByDepense($depenseId) {
        if ($depenseId === null) return null;
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budget_engagements WHERE depense_id = :depense_id");
        $stmt->execute(['depense_id' => $depenseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findBySource($sourceType, $sourceId) {
        if (!self::checkSourceColumns()) {
            return null;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budget_engagements WHERE source_type = :source_type AND source_id = :source_id");
        $stmt->execute([
            'source_type' => $sourceType,
            'source_id' => $sourceId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByBudgetLigne($budgetLigneId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budget_engagements WHERE budget_ligne_id = :budget_ligne_id");
        $stmt->execute(['budget_ligne_id' => $budgetLigneId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();

        if (!empty($data['depense_id'])) {
            $existing = self::findByDepense($data['depense_id']);
            if ($existing) {
                throw new Exception("Un engagement budgétaire existe déjà pour cette dépense (ID: " . $data['depense_id'] . ").");
            }
        }

        $hasPoly = self::checkSourceColumns();

        if ($hasPoly && !empty($data['source_type']) && !empty($data['source_id'])) {
            $existing = self::findBySource($data['source_type'], $data['source_id']);
            if ($existing) {
                throw new Exception("Un engagement budgétaire existe déjà pour cette source (" . $data['source_type'] . " ID: " . $data['source_id'] . ").");
            }
        }

        $now = date('Y-m-d H:i:s');

        if ($hasPoly) {
            $stmt = $db->prepare("
                INSERT INTO budget_engagements (depense_id, budget_ligne_id, montant, statut, date_engagement, source_type, source_id)
                VALUES (:depense_id, :budget_ligne_id, :montant, :statut, :date_engagement, :source_type, :source_id)
            ");
            $stmt->execute([
                'depense_id' => $data['depense_id'] ?? null,
                'budget_ligne_id' => $data['budget_ligne_id'],
                'montant' => $data['montant'],
                'statut' => $data['statut'] ?? 'reserve',
                'date_engagement' => $now,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO budget_engagements (depense_id, budget_ligne_id, montant, statut, date_engagement)
                VALUES (:depense_id, :budget_ligne_id, :montant, :statut, :date_engagement)
            ");
            $stmt->execute([
                'depense_id' => $data['depense_id'] ?? null,
                'budget_ligne_id' => $data['budget_ligne_id'],
                'montant' => $data['montant'],
                'statut' => $data['statut'] ?? 'reserve',
                'date_engagement' => $now
            ]);
        }

        return $db->lastInsertId();
    }

    public static function updateStatus($id, $newStatus) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE budget_engagements SET statut = :statut WHERE id = :id");
        return $stmt->execute([
            'statut' => $newStatus,
            'id' => $id
        ]);
    }

    public static function updateStatusByDepense($depenseId, $newStatus) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE budget_engagements SET statut = :statut WHERE depense_id = :depense_id");
        return $stmt->execute([
            'statut' => $newStatus,
            'depense_id' => $depenseId
        ]);
    }

    public static function updateStatusBySource($sourceType, $sourceId, $newStatus) {
        if (!self::checkSourceColumns()) {
            return false;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE budget_engagements SET statut = :statut WHERE source_type = :source_type AND source_id = :source_id");
        return $stmt->execute([
            'statut' => $newStatus,
            'source_type' => $sourceType,
            'source_id' => $sourceId
        ]);
    }
}
?>