<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class DisciplineHistorique {

    public static function log($data) {
        $db = Database::getInstance();
        $lyceeId = $data['lycee_id'] ?? Auth::getLyceeId();
        $userId = $data['user_id'] ?? Auth::getUserId();

        if (!$lyceeId || !$userId) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO discipline_historique
                (lycee_id, annee_academique_id, incident_id, sanction_id, eleve_id, user_id, action, statut_avant, statut_apres, description, metadata, created_at)
                VALUES (:lycee_id, :annee_id, :incident_id, :sanction_id, :eleve_id, :user_id, :action, :statut_avant, :statut_apres, :description, :metadata, '$now')";

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'lycee_id' => $lyceeId,
                'annee_id' => $data['annee_academique_id'] ?? null,
                'incident_id' => $data['incident_id'] ?? null,
                'sanction_id' => $data['sanction_id'] ?? null,
                'eleve_id' => $data['eleve_id'] ?? null,
                'user_id' => $userId,
                'action' => strtoupper(trim($data['action'] ?? 'EVENEMENT')),
                'statut_avant' => $data['statut_avant'] ?? null,
                'statut_apres' => $data['statut_apres'] ?? null,
                'description' => trim($data['description'] ?? ''),
                'metadata' => !empty($data['metadata']) ? (is_string($data['metadata']) ? $data['metadata'] : json_encode($data['metadata'])) : null
            ]);
        } catch (PDOException $e) {
            error_log("Error in DisciplineHistorique::log: " . $e->getMessage());
            return false;
        }
    }

    public static function findByTarget($targetType, $targetId, $lyceeId = null) {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();
        if (!$lyceeId || !$targetId) {
            return [];
        }

        $column = match($targetType) {
            'incident' => 'incident_id',
            'sanction' => 'sanction_id',
            'eleve' => 'eleve_id',
            default => null
        };

        if (!$column) {
            return [];
        }

        $sql = "SELECT h.*, u.nom as user_nom, u.prenom as user_prenom, u.identifiant_public
                FROM discipline_historique h
                JOIN utilisateurs u ON h.user_id = u.id_user
                WHERE h.lycee_id = :lycee_id AND h.{$column} = :target_id
                ORDER BY h.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lyceeId, 'target_id' => $targetId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineHistorique::findByTarget: " . $e->getMessage());
            return [];
        }
    }
}
?>