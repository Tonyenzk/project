<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if required parameters are present
if (!isset($_POST['post_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$post_id = $_POST['post_id'];
$action = $_POST['action'];
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'save') {
        // Check if post is already saved
        $stmt = $pdo->prepare("SELECT 1 FROM saved_posts WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        
        if (!$stmt->fetch()) {
            // Save the post
            $stmt = $pdo->prepare("INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $post_id]);
        }
    } else if ($action === 'unsave') {
        // Remove the saved post
        $stmt = $pdo->prepare("DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 