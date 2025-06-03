<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$requested_id = $data['user_id'] ?? null;

if (!$requested_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Check if user is private
    $stmt = $pdo->prepare("SELECT isPrivate FROM users WHERE user_id = ?");
    $stmt->execute([$requested_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }

    // Check if already following
    $stmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $requested_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Already following']);
        exit();
    }

    if ($user['isPrivate']) {
        // Check if request already exists
        $stmt = $pdo->prepare("SELECT * FROM follow_requests WHERE requester_id = ? AND requested_id = ?");
        $stmt->execute([$_SESSION['user_id'], $requested_id]);
        $existing_request = $stmt->fetch();

        if ($existing_request) {
            if ($existing_request['status'] === 'pending') {
                echo json_encode(['success' => false, 'message' => 'Request already sent']);
                exit();
            } else if ($existing_request['status'] === 'declined') {
                // Update declined request to pending
                $stmt = $pdo->prepare("UPDATE follow_requests SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE requester_id = ? AND requested_id = ?");
                $stmt->execute([$_SESSION['user_id'], $requested_id]);
            }
        } else {
            // Create new follow request
            $stmt = $pdo->prepare("INSERT INTO follow_requests (requester_id, requested_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $requested_id]);
        }

        // Create notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'follow_request')");
        $stmt->execute([$requested_id, $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'status' => 'requested']);
    } else {
        // For public accounts, follow directly
        $stmt = $pdo->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $requested_id]);

        // Create notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'follow')");
        $stmt->execute([$requested_id, $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'status' => 'following']);
    }
} catch (Exception $e) {
    error_log("Error in send_follow_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 