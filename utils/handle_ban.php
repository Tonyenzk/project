<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if user has Master_Admin role
$stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'Master_Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['action']) || !isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

$action = $_POST['action'];
$user_id = $_POST['user_id'];

// Validate user_id
if (!is_numeric($user_id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get current ban status
    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('User not found');
    }

    // Prevent self-banning
    if ($user_id == $_SESSION['user_id']) {
        throw new Exception('Cannot ban yourself');
    }

    // Toggle ban status
    $new_status = !$user['is_banned'];
    
    // Update user's ban status
    $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE user_id = ?");
    $stmt->execute([$new_status, $user_id]);

    // If user is being banned, remove all following and followers records
    if ($new_status) {
        // Remove all followers (where user is being followed)
        $stmt = $pdo->prepare("DELETE FROM followers WHERE following_id = ?");
        $stmt->execute([$user_id]);

        // Remove all following (where user is following others)
        $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = ?");
        $stmt->execute([$user_id]);

        // Remove all pending follow requests
        $stmt = $pdo->prepare("DELETE FROM follow_requests WHERE requested_id = ? OR requester_id = ?");
        $stmt->execute([$user_id, $user_id]);
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $new_status ? 'User has been banned' : 'User has been unbanned'
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 