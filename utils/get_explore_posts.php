<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

// Get pagination parameters
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12; // Default to 12 posts per page

// Ensure reasonable limits
$limit = min(max($limit, 3), 24); // Between 3 and 24 posts

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.post_id,
            p.user_id,
            p.image_url,
            p.caption,
            p.location,
            p.deactivated_comments,
            p.created_at,
            u.username,
            u.name,
            u.profile_picture,
            u.verifybadge,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
            EXISTS(SELECT 1 FROM likes WHERE post_id = p.post_id AND user_id = ?) as is_liked,
            EXISTS(SELECT 1 FROM saved_posts WHERE post_id = p.post_id AND user_id = ?) as is_saved,
            TIMESTAMPDIFF(SECOND, p.created_at, NOW()) as time_diff
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE u.is_banned = 0
        AND NOT EXISTS (
            SELECT 1 FROM blocked_users 
            WHERE (blocker_id = ? AND blocked_id = p.user_id)
            OR (blocker_id = p.user_id AND blocked_id = ?)
        )
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $limit,
        $offset
    ]);
    
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format time for each post
    foreach ($posts as &$post) {
        $time_diff = (int)$post['time_diff'];
        
        if ($time_diff < 60) {
            $post['time_ago'] = 'Just now';
        } elseif ($time_diff < 3600) {
            $post['time_ago'] = floor($time_diff / 60) . 'm';
        } elseif ($time_diff < 86400) {
            $post['time_ago'] = floor($time_diff / 3600) . 'h';
        } elseif ($time_diff < 604800) {
            $post['time_ago'] = floor($time_diff / 86400) . 'd';
        } elseif ($time_diff < 2592000) {
            $post['time_ago'] = floor($time_diff / 604800) . 'w';
        } elseif ($time_diff < 31536000) {
            $post['time_ago'] = floor($time_diff / 2592000) . 'mo';
        } else {
            $post['time_ago'] = floor($time_diff / 31536000) . 'y';
        }
    }
    
    // Get total count for pagination info
    $totalStmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE u.is_banned = 0
        AND NOT EXISTS (
            SELECT 1 FROM blocked_users 
            WHERE (blocker_id = ? AND blocked_id = p.user_id)
            OR (blocker_id = p.user_id AND blocked_id = ?)
        )
    ");
    
    $totalStmt->execute([
        $_SESSION['user_id'],
        $_SESSION['user_id']
    ]);
    $total = $totalStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $posts,
        'pagination' => [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching explore posts: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
?>
