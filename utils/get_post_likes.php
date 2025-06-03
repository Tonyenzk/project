<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15; // Default 15 likes per page

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
    exit();
}

try {
    // First get total like count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $total_likes = $stmt->fetchColumn();

    // Fetch likes with user information and pagination, ensuring each user appears only once
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            u.user_id,
            u.username,
            u.name,
            u.profile_picture,
            u.verifybadge,
            MAX(l.created_at) as created_at
        FROM likes l
        JOIN users u ON l.user_id = u.user_id
        WHERE l.post_id = ?
        GROUP BY u.user_id, u.username, u.name, u.profile_picture, u.verifybadge
        ORDER BY MAX(l.created_at) DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$post_id, $limit, $offset]);
    $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $has_more = ($offset + count($likes)) < $total_likes;

    echo json_encode([
        'success' => true, 
        'data' => [
            'likes' => $likes,
            'has_more' => $has_more,
            'total_likes' => $total_likes,
            'next_offset' => $offset + $limit
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
