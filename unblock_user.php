<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$blocked_user_id = $data['user_id'] ?? null;

if (!$blocked_user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

try {
    // Remove the blocked user entry
    $stmt = $pdo->prepare("DELETE FROM blocked_users WHERE user_id = ? AND blocked_user_id = ?");
    $stmt->execute([$_SESSION['user_id'], $blocked_user_id]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 