<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Get the user ID from the request
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

try {
    // Check if there's an active follow request
    $stmt = $pdo->prepare("SELECT status FROM follow_requests WHERE requester_id = ? AND requested_id = ?");
    $stmt->execute([$_SESSION['user_id'], $user_id]);
    $request = $stmt->fetch();

    // Check if the user is now following
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $user_id]);
    $is_following = $stmt->fetchColumn() > 0;

    if ($is_following) {
        echo json_encode(['success' => true, 'status' => 'accepted']);
    } else if ($request && $request['status'] === 'pending') {
        echo json_encode(['success' => true, 'status' => 'pending']);
    } else {
        echo json_encode(['success' => true, 'status' => 'none']);
    }
} catch (PDOException $e) {
    error_log("Error checking follow status: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} 