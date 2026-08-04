<?php
// src/models/Budget.php

require_once __DIR__ . '/../config/database.php';

class Budget {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budgets WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLyceeAndExercice($lyceeId, $exerciceId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM budgets WHERE lycee_id = :lycee_id AND exercice_financier_id = :exercice_id");
        $stmt->execute([
            'lycee_id' => $lyceeId,
            'exercice_id' => $exerciceId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT b.*, ef.libelle as exercice_libelle
            FROM budgets b
            LEFT JOIN exercices_financiers ef ON b.exercice_financier_id = ef.id
            WHERE b.lycee_id = :lycee_id
            ORDER BY b.date_creation DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();

        $existing = self::findByLyceeAndExercice($data['lycee_id'], $data['exercice_financier_id']);
        if ($existing) {
            throw new Exception("Un budget existe déjà pour cet exercice financier dans votre établissement.");
        }

        $stmt = $db->prepare("
            INSERT INTO budgets (lycee_id, exercice_financier_id, libelle, statut, cree_par, date_creation)
            VALUES (:lycee_id, :exercice_financier_id, :libelle, :statut, :cree_par, :date_creation)
        ");

        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'exercice_financier_id' => $data['exercice_financier_id'],
            'libelle' => $data['libelle'],
            'statut' => $data['statut'] ?? 'brouillon',
            'cree_par' => $data['cree_par'],
            'date_creation' => $now
        ]);

        return $db->lastInsertId();
    }

    public static function updateStatus($id, $newStatus) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE budgets SET statut = :statut WHERE id = :id");
        return $stmt->execute([
            'statut' => $newStatus,
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $budget = self::findById($id);
        if (!$budget) {
            return false;
        }

        if ($budget['statut'] !== 'brouillon') {
            throw new Exception("Seul un budget à l'état de brouillon peut être supprimé.");
        }

        $stmt = $db->prepare("DELETE FROM budgets WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>