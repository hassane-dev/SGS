<?php
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../core/Auth.php';

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
            $notification = Notification::findById($notification_id); // Assumes findById exists

            if ($notification && $notification['user_id'] == $user_id) {
                Notification::markAsRead($notification_id);
            }
        }

        if (isset($_GET['redirect_to'])) {
            header('Location: ' . $_GET['redirect_to']);
        } else {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        }
        exit();
    }

    public function markAllAsRead() {
        $user_id = Auth::get('id_user');
        Notification::markAllAsRead($user_id);

        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
