<?php
// src/services/PersonnelHistoryService.php

require_once __DIR__ . '/../config/database.php';

class PersonnelHistoryService {

    /**
     * Records an immutable HR movement in personnel_historique_mouvements.
     */
    public static function logMovement(array $data): bool {
        $db = Database::getInstance();

        $sql = "INSERT INTO personnel_historique_mouvements (
                    personnel_id, type_mouvement, date_mouvement, motif, auteur_id,
                    ancien_etat, nouvel_etat, lycee_id, cycle_id
                ) VALUES (
                    :personnel_id, :type_mouvement, :date_mouvement, :motif, :auteur_id,
                    :ancien_etat, :nouvel_etat, :lycee_id, :cycle_id
                )";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'personnel_id' => $data['personnel_id'],
            'type_mouvement' => $data['type_mouvement'],
            'date_mouvement' => $data['date_mouvement'] ?? date('Y-m-d H:i:s'),
            'motif' => $data['motif'] ?? null,
            'auteur_id' => $data['auteur_id'],
            'ancien_etat' => isset($data['ancien_etat']) ? json_encode($data['ancien_etat'], JSON_UNESCAPED_UNICODE) : null,
            'nouvel_etat' => isset($data['nouvel_etat']) ? json_encode($data['nouvel_etat'], JSON_UNESCAPED_UNICODE) : null,
            'lycee_id' => $data['lycee_id'] ?? null,
            'cycle_id' => $data['cycle_id'] ?? null,
        ]);
    }

    /**
     * Fetches movement history for a specific personnel.
     */
    public static function getHistoryForPersonnel(int $personnel_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*, CONCAT(u.prenom, ' ', u.nom) AS auteur_nom, c.nom_cycle
            FROM personnel_historique_mouvements m
            JOIN utilisateurs u ON m.auteur_id = u.id_user
            LEFT JOIN cycles c ON m.cycle_id = c.id_cycle
            WHERE m.personnel_id = :personnel_id
            ORDER BY m.date_mouvement DESC, m.id DESC
        ");
        $stmt->execute(['personnel_id' => $personnel_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
