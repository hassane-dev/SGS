<?php
// src/models/BudgetHistorique.php

require_once __DIR__ . '/../config/database.php';

class BudgetHistorique {

    public static function findByLycee($lyceeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT bh.*, u.nom as user_nom, u.prenom as user_prenom
            FROM budget_historique bh
            LEFT JOIN utilisateurs u ON bh.execute_par = u.id_user
            WHERE bh.lycee_id = :lycee_id
            ORDER BY bh.date_evenement DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function log($lyceeId, $evenement, $details, $userId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO budget_historique (lycee_id, evenement, details, execute_par, date_evenement)
            VALUES (:lycee_id, :evenement, :details, :execute_par, :date_evenement)
        ");

        $now = date('Y-m-d H:i:s');
        return $stmt->execute([
            'lycee_id' => $lyceeId,
            'evenement' => $evenement,
            'details' => $details,
            'execute_par' => $userId,
            'date_evenement' => $now
        ]);
    }
}
?>