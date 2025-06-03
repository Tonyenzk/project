<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set proper content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if database config file exists
if (!file_exists('../config/database.php')) {
    echo json_encode(['success' => false, 'message' => 'Database configuration file not found']);
    exit;
}

require_once '../config/database.php';

// Include WebSocket client library
require_once '../vendor/autoload.php';
use WebSocket\Client as WebSocketClient;

// Check if post_id and action are provided
if (!isset($_POST['post_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$post_id = $_POST['post_id'];
$action = $_POST['action'];
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'like') {
        // Check if already liked
        $stmt = $pdo->prepare("SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Post was already liked, so unlike it
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$_SESSION['user_id'], $post_id]);
            $response = ['success' => true, 'action' => 'unliked'];
        } else {
            // Post wasn't liked, so like it
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $post_id]);
            $response = ['success' => true, 'action' => 'liked'];
            
            // Get post owner's user_id for notification
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $postOwnerId = $stmt->fetchColumn();
            
            // Send response immediately
            echo json_encode($response);
            
            // Handle notification asynchronously
            if ($postOwnerId && $postOwnerId != $_SESSION['user_id']) {
                // Check if a notification already exists for this like
                $stmt = $pdo->prepare("SELECT notification_id FROM notifications WHERE user_id = ? AND actor_id = ? AND type = 'like' AND post_id = ?");
                $stmt->execute([$postOwnerId, $_SESSION['user_id'], $post_id]);
                $existingNotification = $stmt->fetch();
                
                if ($existingNotification) {
                    // Update existing notification's creation time
                    $stmt = $pdo->prepare("UPDATE notifications SET created_at = NOW() WHERE notification_id = ?");
                    $stmt->execute([$existingNotification['notification_id']]);
                } else {
                    // Create new notification
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type, post_id) VALUES (?, ?, 'like', ?)");
                    $stmt->execute([$postOwnerId, $_SESSION['user_id'], $post_id]);
                }
                
                // Send notification through WebSocket asynchronously
                try {
                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'wss' : 'ws';
                    $wsHost = $_SERVER['HTTP_HOST']; // Gets the current domain
                    $client = new WebSocketClient("$protocol://$wsHost/ws");
                    
                    // Get actor info
                    $stmt = $pdo->prepare("SELECT username, profile_picture FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $actor = $stmt->fetch();
                    
                    // Get post image
                    $stmt = $pdo->prepare("SELECT image_url FROM posts WHERE post_id = ?");
                    $stmt->execute([$post_id]);
                    $postImage = $stmt->fetchColumn();
                    
                    // Prepare notification
                    $notification = [
                        'type' => 'like',
                        'user_id' => $postOwnerId,
                        'actor_id' => $_SESSION['user_id'],
                        'actor_username' => $actor['username'],
                        'actor_profile_picture' => $actor['profile_picture'] ?? './images/profile_placeholder.webp',
                        'post_id' => $post_id,
                        'post_image' => $postImage,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Send notification
                    $client->send(json_encode([
                        'type' => 'notification',
                        'notification' => $notification
                    ]));
                    
                    $client->close();
                } catch (Exception $e) {
                    error_log("Error sending WebSocket notification: " . $e->getMessage());
                }
            }
        }
    } else if ($action === 'unlike') {
        // Remove like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $response = ['success' => true, 'action' => 'unliked'];
        
        // Send response immediately
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    // Get updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $like_count = $stmt->fetchColumn();
    
    $response['like_count'] = $like_count;
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
} 