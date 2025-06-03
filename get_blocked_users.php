<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    // Fetch blocked users with their details
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.name, u.profile_picture 
        FROM blocked_users b 
        JOIN users u ON b.blocked_id = u.user_id 
        WHERE b.blocker_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $blocked_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'blocked_users' => $blocked_users]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 