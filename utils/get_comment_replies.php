<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$comment_id = isset($_GET['comment_id']) ? (int)$_GET['comment_id'] : 0;

if (!$comment_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
    exit();
}

try {
    // Fetch replies with user information
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username,
            u.profile_picture,
            u.verifybadge,
            EXISTS(SELECT 1 FROM likes WHERE comment_id = c.comment_id AND user_id = ?) as is_liked,
            (SELECT COUNT(*) FROM likes WHERE comment_id = c.comment_id) as like_count,
            TIMESTAMPDIFF(SECOND, c.created_at, NOW()) as time_diff
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.parent_comment_id = ?
        ORDER BY c.created_at ASC
    ");
    
    $stmt->execute([$_SESSION['user_id'], $comment_id]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format time for each reply
    foreach ($replies as &$reply) {
        $time_diff = (int)$reply['time_diff'];
        
        if ($time_diff < 60) {
            $reply['time_ago'] = 'Just now';
        } elseif ($time_diff < 3600) {
            $reply['time_ago'] = floor($time_diff / 60) . 'm';
        } elseif ($time_diff < 86400) {
            $reply['time_ago'] = floor($time_diff / 3600) . 'h';
        } elseif ($time_diff < 604800) {
            $reply['time_ago'] = floor($time_diff / 86400) . 'd';
        } elseif ($time_diff < 2592000) {
            $reply['time_ago'] = floor($time_diff / 604800) . 'w';
        } elseif ($time_diff < 31536000) {
            $reply['time_ago'] = floor($time_diff / 2592000) . 'mo';
        } else {
            $reply['time_ago'] = floor($time_diff / 31536000) . 'y';
        }
    }

    echo json_encode(['success' => true, 'replies' => $replies]);

} catch (PDOException $e) {
    error_log("Error fetching comment replies: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
} 