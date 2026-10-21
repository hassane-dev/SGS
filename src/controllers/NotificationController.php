<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CsrfService.php';

class NotificationController {

    public function index() {
        $user_id = Auth::get('id_user');
        $notifications = Notification::findAllByUser($user_id);

        require_once __DIR__ . '/../views/notifications/index.php';
    }

    public function markAsRead() {
        if (isset($_GET['id'])) {
            $notification_id = (int)$_GET['id'];
            $user_id = Auth::get('id_user');

            // Security Check: Ensure the notification belongs to the current user
            $notification = Notification::findById($notification_id);

            if ($notification && $notification['user_id'] == $user_id) {
                Notification::markAsRead($notification_id);
            }
        }

        if (isset($_GET['redirect_to'])) {
            if (!defined('TEST_MODE')) { header('Location: ' . $_GET['redirect_to']); exit(); } return;
        } elseif (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            if (!defined('TEST_MODE')) { header('Location: ' . $_SERVER['HTTP_REFERER']); exit(); } return;
        } else {
            if (!defined('TEST_MODE')) { header('Location: /notifications/all'); exit(); } return;
        }
    }

    public function markAllAsRead() {
        $user_id = Auth::get('id_user');
        Notification::markAllAsRead($user_id);

        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            if (!defined('TEST_MODE')) { header('Location: ' . $_SERVER['HTTP_REFERER']); exit(); } return;
        } else {
            if (!defined('TEST_MODE')) { header('Location: /notifications/all'); exit(); } return;
        }
    }

    /**
     * JSON Endpoint: Fetch unread notifications, unread count, and new items since specified ID.
     */
    public function unread() {
        if (!Auth::check()) {
            http_response_code(401);
            if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
            echo json_encode(['status' => 'error', 'message' => 'Non authentifié.']);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $user_id = Auth::get('id_user');
        if (!$user_id) {
            http_response_code(401);
            if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
            echo json_encode(['status' => 'error', 'message' => 'Utilisateur non identifié.']);
            if (!defined('TEST_MODE')) exit(); return;
        }

        // Release session lock so background polling does not block concurrent user requests
        if (!defined('TEST_MODE')) {
            session_write_close();
        }

        $unread_notifications = Notification::findUnreadByUser($user_id);
        $unread_count = count($unread_notifications);

        $since_id = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;

        $new_notifications = array_values(array_filter($unread_notifications, function($n) use ($since_id) {
            return (int)$n['id'] > $since_id;
        }));

        $max_id = 0;
        if (!empty($unread_notifications)) {
            $ids = array_column($unread_notifications, 'id');
            $max_id = !empty($ids) ? (int)max($ids) : 0;
        } elseif ($since_id > 0) {
            $max_id = $since_id;
        }

        if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
        echo json_encode([
            'status' => 'success',
            'unread_count' => (int)$unread_count,
            'notifications' => $unread_notifications,
            'new_notifications' => $new_notifications,
            'max_id' => (int)$max_id
        ]);
        if (!defined('TEST_MODE')) exit(); return;
    }

    /**
     * JSON AJAX Endpoint: Mark a single notification as read for authenticated owner.
     */
    public function ajaxMarkAsRead() {
        if (!Auth::check()) {
            http_response_code(401);
            if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
            echo json_encode(['status' => 'error', 'message' => 'Non authentifié.']);
            if (!defined('TEST_MODE')) exit(); return;
        }

        CsrfService::requireValid();

        $user_id = Auth::get('id_user');

        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?: [];
        $notification_id = (int)($_POST['id'] ?? $data['id'] ?? $_GET['id'] ?? 0);

        if ($notification_id <= 0) {
            http_response_code(400);
            if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
            echo json_encode(['status' => 'error', 'message' => 'Identifiant de notification invalide.']);
            if (!defined('TEST_MODE')) exit(); return;
        }

        $notification = Notification::findById($notification_id);

        if (!$notification || $notification['user_id'] != $user_id) {
            http_response_code(403);
            if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
            echo json_encode(['status' => 'error', 'message' => 'Notification non trouvée ou accès refusé.']);
            if (!defined('TEST_MODE')) exit(); return;
        }

        Notification::markAsRead($notification_id);
        $unread = Notification::findUnreadByUser($user_id);

        if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
        echo json_encode([
            'status' => 'success',
            'message' => 'Notification marquée comme lue.',
            'unread_count' => count($unread)
        ]);
        if (!defined('TEST_MODE')) exit(); return;
    }

    /**
     * JSON AJAX Endpoint: Mark all notifications as read for authenticated user.
     */
    public function ajaxMarkAllAsRead() {
        if (!Auth::check()) {
            http_response_code(401);
            if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
            echo json_encode(['status' => 'error', 'message' => 'Non authentifié.']);
            if (!defined('TEST_MODE')) exit(); return;
        }

        CsrfService::requireValid();

        $user_id = Auth::get('id_user');
        Notification::markAllAsRead($user_id);

        if (!defined('TEST_MODE')) { header('Content-Type: application/json; charset=utf-8'); }
        echo json_encode([
            'status' => 'success',
            'message' => 'Toutes les notifications ont été marquées comme lues.',
            'unread_count' => 0
        ]);
        if (!defined('TEST_MODE')) exit(); return;
    }
}
?>
