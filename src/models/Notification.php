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
     * Notify all active supervisors assigned to a class (supporting co-guarding without exclusions or duplicate notifications).
     * @param int $classe_id
     * @param int $lycee_id
     * @param string $message
     * @param string $link
     * @return array List of supervisor IDs notified
     */
    public static function notifyClassSupervisors($classe_id, $lycee_id, $message, $link) {
        $db = Database::getInstance();
        $supervisor_ids = [];

        // Check active academic year
        $stmtYear = $db->prepare("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $stmtYear->execute();
        $annee_id = (int)$stmtYear->fetchColumn();

        // 1. Query surveillant_affectations (direct class, group, level, cycle, etablissement)
        if ($annee_id > 0) {
            try {
                $stmtC = $db->prepare("SELECT cycle_id, niveau FROM classes WHERE id_classe = :cid AND lycee_id = :lid");
                $stmtC->execute(['cid' => $classe_id, 'lid' => $lycee_id]);
                $cls = $stmtC->fetch(PDO::FETCH_ASSOC);

                if ($cls) {
                    $cycle_id = (int)$cls['cycle_id'];
                    $niveau = $cls['niveau'];

                    $stmtAff = $db->prepare("
                        SELECT DISTINCT sa.surveillant_id
                        FROM surveillant_affectations sa
                        LEFT JOIN surveillant_groupe_classes sgc ON sa.groupe_id = sgc.groupe_id
                        WHERE sa.lycee_id = :lid
                          AND sa.annee_academique_id = :aid
                          AND sa.statut = 'actif'
                          AND sa.surveillant_id IS NOT NULL
                          AND (
                              (sa.portee_type = 'classe' AND sa.classe_id = :cid) OR
                              (sa.portee_type = 'groupe_classes' AND sgc.classe_id = :cid) OR
                              (sa.portee_type = 'niveau' AND sa.niveau_code = :niveau) OR
                              (sa.portee_type = 'cycle' AND sa.cycle_id = :cyid) OR
                              (sa.portee_type = 'etablissement')
                          )
                    ");
                    $stmtAff->execute([
                        'lid' => $lycee_id,
                        'aid' => $annee_id,
                        'cid' => $classe_id,
                        'niveau' => $niveau,
                        'cyid' => $cycle_id
                    ]);
                    $supervisor_ids = array_merge($supervisor_ids, $stmtAff->fetchAll(PDO::FETCH_COLUMN));
                }
            } catch (Throwable $e) {}
        }

        // 2. Legacy fallback tables if surveillant_affectations yields empty
        if (empty($supervisor_ids)) {
            try {
                $stmtLegacy = $db->prepare("
                    SELECT DISTINCT sc.surveillant_id FROM surveillant_classes sc WHERE sc.classe_id = :cid AND (sc.lycee_id = :lid OR sc.lycee_id IS NULL)
                    UNION
                    SELECT DISTINCT sn.surveillant_id FROM surveillant_niveaux sn JOIN classes c ON sn.niveau = c.niveau WHERE c.id_classe = :cid AND (sn.lycee_id = :lid OR sn.lycee_id IS NULL)
                    UNION
                    SELECT DISTINCT sg.surveillant_id FROM surveillant_general sg WHERE (sg.lycee_id = :lid OR sg.lycee_id IS NULL)
                ");
                $stmtLegacy->execute(['cid' => $classe_id, 'lid' => $lycee_id]);
                $supervisor_ids = array_merge($supervisor_ids, $stmtLegacy->fetchAll(PDO::FETCH_COLUMN));
            } catch (Throwable $e) {}
        }

        // Deduplicate supervisor IDs so no supervisor receives duplicate notifications
        $unique_supervisor_ids = array_values(array_unique(array_map('intval', array_filter($supervisor_ids, fn($v) => $v > 0))));

        // Create notification for each active supervisor
        foreach ($unique_supervisor_ids as $uid) {
            self::create([
                'user_id' => $uid,
                'lycee_id' => $lycee_id,
                'message' => $message,
                'link' => $link
            ]);
        }

        return $unique_supervisor_ids;
    }

    /**
     * Mark notifications as read for a specific link and lycee.
     * Useful to clear "Pending" notifications once the action is performed.
     * @param string $link
     * @param int $lycee_id
     * @return bool
     */
    public static function markAsReadByLink($link, $lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE link = :link AND lycee_id = :lycee_id");
        return $stmt->execute(['link' => $link, 'lycee_id' => $lycee_id]);
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