<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if (!isset($_POST['action']) || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

$action = $_POST['action'];
$blocker_id = $_SESSION['user_id'];
$blocked_id = $_POST['user_id'];

// Prevent self-blocking
if ($blocker_id == $blocked_id) {
    echo json_encode(['success' => false, 'error' => 'Cannot block yourself']);
    exit();
}

try {
    if ($action === 'block') {
        // Check if already blocked
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$blocker_id, $blocked_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'User is already blocked']);
            exit();
        }

        // Start transaction
        $pdo->beginTransaction();

        // Add block
        $stmt = $pdo->prepare("INSERT INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)");
        $stmt->execute([$blocker_id, $blocked_id]);

        // Remove any existing follow relationships
        $stmt = $pdo->prepare("DELETE FROM followers WHERE (follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)");
        $stmt->execute([$blocker_id, $blocked_id, $blocked_id, $blocker_id]);

        // Remove any pending follow requests
        $stmt = $pdo->prepare("DELETE FROM follow_requests WHERE (requester_id = ? AND requested_id = ?) OR (requester_id = ? AND requested_id = ?)");
        $stmt->execute([$blocker_id, $blocked_id, $blocked_id, $blocker_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'User blocked successfully']);
    } 
    else if ($action === 'unblock') {
        // Remove block
        $stmt = $pdo->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$blocker_id, $blocked_id]);
        
        echo json_encode(['success' => true, 'message' => 'User unblocked successfully']);
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error in handle_block.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
} 