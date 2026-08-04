<?php
// src/models/BudgetEngagement.php

require_once __DIR__ . '/../config/database.php';

class BudgetEngagement {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budget_engagements WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByDepense($depenseId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budget_engagements WHERE depense_id = :depense_id");
        $stmt->execute(['depense_id' => $depenseId]);
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

        $existing = self::findByDepense($data['depense_id']);
        if ($existing) {
            throw new Exception("Un engagement budgétaire existe déjà pour cette dépense (ID: " . $data['depense_id'] . ").");
        }

        $stmt = $db->prepare("
            INSERT INTO budget_engagements (depense_id, budget_ligne_id, montant, statut, date_engagement)
            VALUES (:depense_id, :budget_ligne_id, :montant, :statut, :date_engagement)
        ");

        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'depense_id' => $data['depense_id'],
            'budget_ligne_id' => $data['budget_ligne_id'],
            'montant' => $data['montant'],
            'statut' => $data['statut'] ?? 'reserve',
            'date_engagement' => $now
        ]);

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
}
?>