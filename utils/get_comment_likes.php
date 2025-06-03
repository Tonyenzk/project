<?php
session_start();
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Get parameters
$comment_id = isset($_GET['comment_id']) ? (int)$_GET['comment_id'] : 0;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 10; // Number of likes to fetch per request

if (!$comment_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid comment ID']);
    exit();
}

try {
    // Query to get users who liked the comment
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.username,
            u.profile_picture,
            u.verifybadge,
            EXISTS(SELECT 1 FROM followers WHERE follower_id = ? AND following_id = u.user_id) AS is_following
        FROM 
            likes l
        JOIN 
            users u ON l.user_id = u.user_id
        WHERE 
            l.comment_id = ?
        ORDER BY 
            l.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $comment_id, $limit, $offset]);
    $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if there are more likes to load
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE comment_id = ?");
    $countStmt->execute([$comment_id]);
    $totalLikes = $countStmt->fetchColumn();
    
    $hasMore = ($offset + $limit) < $totalLikes;
    
    echo json_encode([
        'success' => true,
        'likes' => $likes,
        'hasMore' => $hasMore,
        'totalCount' => $totalLikes
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
