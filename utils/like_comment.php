<?php
session_start();
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$comment_id || !in_array($action, ['like', 'unlike'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    $pdo->beginTransaction();

    if ($action === 'like') {
        // Check if already liked
        $stmt = $pdo->prepare("SELECT 1 FROM likes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            // Add like
            $stmt = $pdo->prepare("INSERT INTO likes (comment_id, user_id) VALUES (?, ?)");
            $stmt->execute([$comment_id, $_SESSION['user_id']]);
        }
    } else {
        // Remove like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $_SESSION['user_id']]);
    }

    // Get updated like count and like status
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as like_count,
            EXISTS(SELECT 1 FROM likes WHERE comment_id = ? AND user_id = ?) as is_liked
        FROM likes 
        WHERE comment_id = ?
    ");
    $stmt->execute([$comment_id, $_SESSION['user_id'], $comment_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'like_count' => (int)$result['like_count'],
        'is_liked' => (bool)$result['is_liked']
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error handling comment like: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Unexpected error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
} 