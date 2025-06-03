<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/NotificationHelper.php';

header('Content-Type: application/json');

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$notificationHelper = new NotificationHelper($pdo);

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get notifications
        try {
            $stmt = $pdo->prepare("
                SELECT n.*, 
                       u.username as actor_username,
                       u.profile_picture as actor_profile_picture,
                       p.image_url as post_image,
                       e.name as event_name
                FROM notifications n
                LEFT JOIN users u ON n.actor_id = u.user_id
                LEFT JOIN posts p ON n.post_id = p.post_id
                LEFT JOIN events e ON n.event_id = e.event_id
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format notifications
            foreach ($notifications as &$notification) {
                $notification['message'] = $notificationHelper->formatNotificationMessage($notification);
            }

            echo json_encode(['success' => true, 'notifications' => $notifications]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
        break;

    case 'POST':
        // Mark notifications as read
        if (isset($_POST['action']) && $_POST['action'] === 'mark_read') {
            if ($notificationHelper->markNotificationsAsRead($_SESSION['user_id'])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to mark notifications as read']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 