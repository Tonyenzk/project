<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set proper content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
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

// Check if post_id and comment body are provided
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$content = isset($_POST['body']) ? trim($_POST['body']) : '';
$parent_comment_id = isset($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
$user_id = $_SESSION['user_id'];

if (!$post_id || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Invalid post ID or empty comment']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert the comment
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, parent_comment_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $content, $parent_comment_id]);
    $comment_id = $pdo->lastInsertId();
    
    // Get the new comment with user info
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.profile_picture, u.verifybadge,
        TIMESTAMPDIFF(SECOND, c.created_at, NOW()) as time_diff
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.comment_id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comment) {
        throw new Exception("Failed to retrieve comment after insertion");
    }
    
    // Get updated comment count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $comment_count = $stmt->fetchColumn();

    // Get post owner for notification
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post_owner_id = $stmt->fetchColumn();

    // Send notification to post owner if it's not the same user
    if ($post_owner_id != $user_id) {
        try {
            // Check if a notification already exists for this post and user
            $stmt = $pdo->prepare("
                SELECT notification_id 
                FROM notifications 
                WHERE user_id = ? 
                AND actor_id = ? 
                AND post_id = ? 
                AND type = 'comment'
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$post_owner_id, $user_id, $post_id]);
            $existing_notification = $stmt->fetch();

            if (!$existing_notification) {
                // Get actor info
                $stmt = $pdo->prepare("SELECT username, profile_picture FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $actor = $stmt->fetch(PDO::FETCH_ASSOC);

                // Insert notification into database
                $stmt = $pdo->prepare("
                    INSERT INTO notifications 
                    (user_id, actor_id, type, post_id, comment_id, created_at) 
                    VALUES (?, ?, 'comment', ?, ?, NOW())
                ");
                $stmt->execute([$post_owner_id, $user_id, $post_id, $comment_id]);

                // Create notification for WebSocket
                $notification = [
                    'type' => $parent_comment_id ? 'comment_reply' : 'comment',
                    'user_id' => $post_owner_id,
                    'actor_id' => $user_id,
                    'actor_username' => $actor['username'],
                    'actor_profile_picture' => $actor['profile_picture'] ?? './images/profile_placeholder.webp',
                    'post_id' => $post_id,
                    'comment_id' => $comment_id,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                // Send WebSocket notification
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "wss" : "ws");
                $wsClient = new WebSocketClient($protocol . '://' . $_SERVER['HTTP_HOST'] . '/ws');
                $wsClient->send(json_encode([
                    'type' => 'notification',
                    'notification' => $notification
                ]));
                $wsClient->close();
            }
        } catch (Exception $e) {
            error_log("Error sending WebSocket notification: " . $e->getMessage());
            // Continue execution even if notification fails
        }
    }

    // If this is a reply, also notify the parent comment owner
    if ($parent_comment_id) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE comment_id = ?");
            $stmt->execute([$parent_comment_id]);
            $parent_comment_owner_id = $stmt->fetchColumn();

            // Only notify if it's not the same user and not the post owner (already notified)
            if ($parent_comment_owner_id != $user_id && $parent_comment_owner_id != $post_owner_id) {
                // Check if a notification already exists for this comment reply
                $stmt = $pdo->prepare("
                    SELECT notification_id 
                    FROM notifications 
                    WHERE user_id = ? 
                    AND actor_id = ? 
                    AND comment_id = ? 
                    AND type = 'comment_reply'
                    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                $stmt->execute([$parent_comment_owner_id, $user_id, $parent_comment_id]);
                $existing_reply_notification = $stmt->fetch();

                if (!$existing_reply_notification) {
                    // Get actor info
                    $stmt = $pdo->prepare("SELECT username, profile_picture FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $actor = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Insert notification into database
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications 
                        (user_id, actor_id, type, post_id, comment_id, created_at) 
                        VALUES (?, ?, 'comment_reply', ?, ?, NOW())
                    ");
                    $stmt->execute([$parent_comment_owner_id, $user_id, $post_id, $comment_id]);

                    // Create notification for WebSocket
                    $notification = [
                        'type' => 'comment_reply',
                        'user_id' => $parent_comment_owner_id,
                        'actor_id' => $user_id,
                        'actor_username' => $actor['username'],
                        'actor_profile_picture' => $actor['profile_picture'] ?? './images/profile_placeholder.webp',
                        'post_id' => $post_id,
                        'comment_id' => $comment_id,
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    // Send WebSocket notification
                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "wss" : "ws");
                    $wsClient = new WebSocketClient($protocol . '://' . $_SERVER['HTTP_HOST'] . '/ws');
                    $wsClient->send(json_encode([
                        'type' => 'notification',
                        'notification' => $notification
                    ]));
                    $wsClient->close();
                }
            }
        } catch (Exception $e) {
            error_log("Error sending WebSocket reply notification: " . $e->getMessage());
            // Continue execution even if notification fails
        }
    }
    
    // Format time ago
    if ($comment['time_diff'] < 60) {
        $comment['time_ago'] = 'Just now';
    } elseif ($comment['time_diff'] < 3600) {
        $comment['time_ago'] = floor($comment['time_diff'] / 60) . 'm';
    } elseif ($comment['time_diff'] < 86400) {
        $comment['time_ago'] = floor($comment['time_diff'] / 3600) . 'h';
    } elseif ($comment['time_diff'] < 604800) {
        $comment['time_ago'] = floor($comment['time_diff'] / 86400) . 'd';
    } elseif ($comment['time_diff'] < 2592000) {
        $comment['time_ago'] = floor($comment['time_diff'] / 604800) . 'w';
    } elseif ($comment['time_diff'] < 31536000) {
        $comment['time_ago'] = floor($comment['time_diff'] / 2592000) . 'mo';
    } else {
        $comment['time_ago'] = floor($comment['time_diff'] / 31536000) . 'y';
    }

    // Commit transaction
    $pdo->commit();

    // Ensure proper JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Comment added successfully',
        'comment' => $comment,
        'comment_count' => $comment_count
    ]);
    exit();
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Error adding comment: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Unexpected error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
    exit();
} 