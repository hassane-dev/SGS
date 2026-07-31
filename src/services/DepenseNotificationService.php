<?php
// src/services/DepenseNotificationService.php

class DepenseNotificationService {

    private static $listeners = [];

    /**
     * Register a callback listener for a specific event
     */
    public static function on($event, callable $callback) {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }
        self::$listeners[$event][] = $callback;
    }

    /**
     * Trigger an event, calling all registered listeners asynchronously or non-blockingly
     */
    public static function trigger($event, $data) {
        // Log locally or notify users in notifications table if DB exists
        self::createInAppNotification($event, $data);

        if (!isset(self::$listeners[$event])) {
            return;
        }

        foreach (self::$listeners[$event] as $callback) {
            try {
                // Execute without blocking the main workflow
                call_user_func($callback, $data);
            } catch (Exception $e) {
                // Prevent failures in listeners from rolling back the main transaction
                error_log("Error in listener for event '$event': " . $e->getMessage());
            }
        }
    }

    /**
     * Create in-app notifications in the notifications table for relevant school personnel
     */
    private static function createInAppNotification($event, $data) {
        try {
            $db = Database::getInstance();
            $lyceeId = $data['lycee_id'] ?? null;
            if (!$lyceeId) return;

            // Determine appropriate target users/roles to notify depending on event type
            // E.g., Notify comptable on DepenseApproved, notify Proviseur/Director on DepenseCreated
            $msg = "";
            $roleTarget = "";

            switch ($event) {
                case 'DepenseCreated':
                    $msg = "Nouvelle demande de dépense engagée (N° " . ($data['numero_piece'] ?? '') . ") d'un montant de " . ($data['montant'] ?? '') . " FCFA.";
                    $roleTarget = 'surveillant'; // notify managers
                    break;
                case 'DepenseApproved':
                    $msg = "Demande de dépense N° " . ($data['numero_piece'] ?? '') . " approuvée. Prête pour le règlement.";
                    $roleTarget = 'comptable';
                    break;
                case 'DepensePaid':
                    $msg = "Dépense N° " . ($data['numero_piece'] ?? '') . " payée d'un montant de " . ($data['montant'] ?? '') . " FCFA.";
                    $roleTarget = 'surveillant';
                    break;
                case 'DepenseCancelled':
                    $msg = "Dépense N° " . ($data['numero_piece'] ?? '') . " annulée avec contre-passation.";
                    $roleTarget = 'comptable';
                    break;
            }

            if (empty($msg)) return;

            // Let's find users with target role in the high school
            $stmt = $db->prepare("
                SELECT u.id_user FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id_role
                WHERE u.lycee_id = :lycee_id AND LOWER(r.nom_role) LIKE :role
            ");
            $stmt->execute([
                'lycee_id' => $lyceeId,
                'role' => '%' . strtolower($roleTarget) . '%'
            ]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($users)) {
                // Fallback: notify admins
                $stmt_adm = $db->prepare("
                    SELECT u.id_user FROM utilisateurs u
                    JOIN roles r ON u.role_id = r.id_role
                    WHERE u.lycee_id = :lycee_id OR u.lycee_id IS NULL
                ");
                $stmt_adm->execute(['lycee_id' => $lyceeId]);
                $users = $stmt_adm->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt_ins = $db->prepare("
                INSERT INTO notifications (user_id, lycee_id, message, link, is_read, created_at)
                VALUES (:user_id, :lycee_id, :message, :link, 0, :now)
            ");

            foreach ($users as $user) {
                $stmt_ins->execute([
                    'user_id' => $user['id_user'],
                    'lycee_id' => $lyceeId,
                    'message' => $msg,
                    'link' => '/depenses',
                    'now' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Exception $e) {
            // Silently fail to not block workflow
            error_log("Error creating notification for event '$event': " . $e->getMessage());
        }
    }
}
?>