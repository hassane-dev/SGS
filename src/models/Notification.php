<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/User.php';

class Notification {

    /**
     * Create a new notification.
     * @param array $data
     * @return bool
     */
    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO notifications (user_id, lycee_id, message, link)
                VALUES (:user_id, :lycee_id, :message, :link)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'lycee_id' => $data['lycee_id'],
            'message' => $data['message'],
            'link' => $data['link']
        ]);
    }

    /**
     * Notify all users with a specific role in a lycee.
     * @param string $roleName
     * @param int $lycee_id
     * @param string $message
     * @param string $link
     */
    public static function notifyRole($roleName, $lycee_id, $message, $link) {
        $users = User::findAllByRoleName($roleName, $lycee_id);
        foreach ($users as $user) {
            self::create([
                'user_id' => $user['id_user'],
                'lycee_id' => $lycee_id,
                'message' => $message,
                'link' => $link
            ]);
        }
    }

    /**
     * Notify all accountants of a lycee.
     * @param int $lycee_id
     * @param string $message
     * @param string $link
     */
    public static function notifyAccountants($lycee_id, $message, $link) {
        self::notifyRole('comptable', $lycee_id, $message, $link);
    }

    /**
     * Find unread notifications for a user.
     * @param int $user_id
     * @return array
     */
    public static function findUnreadByUser($user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark a notification as read.
     * @param int $notification_id
     * @return bool
     */
    public static function markAsRead($notification_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id");
        return $stmt->execute(['id' => $notification_id]);
    }

    /**
     * Mark all notifications for a user as read.
     * @param int $user_id
     * @return bool
     */
    public static function markAllAsRead($user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $user_id]);
    }

    /**
     * Find a notification by its ID.
     * @param int $notification_id
     * @return array|false
     */
    public static function findById($notification_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE id = :id");
        $stmt->execute(['id' => $notification_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find all notifications for a user.
     * @param int $user_id
     * @return array
     */
    public static function findAllByUser($user_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>