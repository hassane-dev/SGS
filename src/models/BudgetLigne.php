<?php
// src/models/BudgetLigne.php

require_once __DIR__ . '/../config/database.php';

class BudgetLigne {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budget_lignes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByBudget($budgetId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT bl.*,
                   cat.nom_categorie,
                   cc.nom_centre
            FROM budget_lignes bl
            LEFT JOIN depense_categories cat ON bl.categorie_id = cat.id
            LEFT JOIN depense_centres_couts cc ON bl.centre_cout_id = cc.id
            WHERE bl.budget_id = :budget_id
            ORDER BY cat.nom_categorie ASC, cc.nom_centre ASC
        ");
        $stmt->execute(['budget_id' => $budgetId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByUnique($budgetId, $categorieId, $centreCoutId = null) {
        $db = Database::getInstance();
        if ($centreCoutId === null) {
            $stmt = $db->prepare("
                SELECT * FROM budget_lignes
                WHERE budget_id = :budget_id
                  AND categorie_id = :categorie_id
                  AND centre_cout_id IS NULL
            ");
            $stmt->execute([
                'budget_id' => $budgetId,
                'categorie_id' => $categorieId
            ]);
        } else {
            $stmt = $db->prepare("
                SELECT * FROM budget_lignes
                WHERE budget_id = :budget_id
                  AND categorie_id = :categorie_id
                  AND centre_cout_id = :centre_cout_id
            ");
            $stmt->execute([
                'budget_id' => $budgetId,
                'categorie_id' => $categorieId,
                'centre_cout_id' => $centreCoutId
            ]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create($data) {
        $db = Database::getInstance();

        $existing = self::findByUnique($data['budget_id'], $data['categorie_id'], $data['centre_cout_id'] ?? null);
        if ($existing) {
            throw new Exception("Une ligne budgétaire existe déjà pour cette catégorie et ce centre de coûts.");
        }

        $stmt = $db->prepare("
            INSERT INTO budget_lignes (
                budget_id, categorie_id, centre_cout_id, allocation_initiale,
                montant_ajustements, montant_engage, montant_consomme
            ) VALUES (
                :budget_id, :categorie_id, :centre_cout_id, :allocation_initiale,
                0.00, 0.00, 0.00
            )
        ");

        $stmt->execute([
            'budget_id' => $data['budget_id'],
            'categorie_id' => $data['categorie_id'],
            'centre_cout_id' => $data['centre_cout_id'] ?? null,
            'allocation_initiale' => $data['allocation_initiale'] ?? 0.00
        ]);

        return $db->lastInsertId();
    }

    public static function updateAllocation($id, $allocation) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE budget_lignes SET allocation_initiale = :allocation WHERE id = :id");
        return $stmt->execute([
            'allocation' => $allocation,
            'id' => $id
        ]);
    }

    public static function updateBalances($id, $ajustements, $engage, $consomme) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE budget_lignes
            SET montant_ajustements = :ajustements,
                montant_engage = :engage,
                montant_consomme = :consomme
            WHERE id = :id
        ");
        return $stmt->execute([
            'ajustements' => $ajustements,
            'engage' => $engage,
            'consomme' => $consomme,
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $ligne = self::findById($id);
        if (!$ligne) {
            return false;
        }

        // Verify budget state
        $budget = Budget::findById($ligne['budget_id']);
        if ($budget && $budget['statut'] !== 'brouillon') {
            throw new Exception("Impossible de supprimer une ligne budgétaire d'un budget approuvé ou actif.");
        }

        $stmt = $db->prepare("DELETE FROM budget_lignes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>