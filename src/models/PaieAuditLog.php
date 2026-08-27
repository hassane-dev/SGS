<?php

require_once __DIR__ . '/../config/database.php';

class PaieAuditLog {
    public static function log(string $entityType, int $entityId, string $action, int $userId, $payloadBefore = null, $payloadAfter = null): int {
        $db = Database::getInstance();
        $jsonBefore = is_array($payloadBefore) || is_object($payloadBefore) ? json_encode($payloadBefore) : $payloadBefore;
        $jsonAfter = is_array($payloadAfter) || is_object($payloadAfter) ? json_encode($payloadAfter) : $payloadAfter;

        $stmt = $db->prepare("
            INSERT INTO paie_audit_logs
            (entity_type, entity_id, action, user_id, payload_before, payload_after, created_at)
            VALUES (:entity_type, :entity_id, :action, :user_id, :payload_before, :payload_after, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'user_id' => $userId,
            'payload_before' => $jsonBefore,
            'payload_after' => $jsonAfter
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByEntity(string $entityType, int $entityId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT a.*, u.nom, u.prenom
            FROM paie_audit_logs a
            JOIN utilisateurs u ON a.user_id = u.id_user
            WHERE a.entity_type = :entity_type AND a.entity_id = :entity_id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
