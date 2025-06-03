<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/NotificationHelper.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the start of the request
error_log("Follow request started - Session user ID: " . $_SESSION['user_id']);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("Error: User not logged in");
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if user_id is provided
if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No user specified']);
    exit();
}

$follower_id = $_SESSION['user_id'];
$following_id = $_POST['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : 'follow';

try {
    error_log("Starting database transaction");
    $pdo->beginTransaction();

    // Check if the target user exists
    error_log("Checking target user existence");
    $stmt = $pdo->prepare("SELECT isPrivate FROM users WHERE user_id = ?");
    $stmt->execute([$following_id]);
    $target_user = $stmt->fetch();

    if (!$target_user) {
        error_log("Error: Target user not found - User ID: " . $following_id);
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    $is_private = $target_user['isPrivate'];
    error_log("Target user found - Is Private: " . ($is_private ? 'true' : 'false'));

    // Handle unfollow action
    if ($action === 'unfollow') {
        error_log("Processing unfollow action");
        // Remove follow relationship
        $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        
        // Remove any pending follow requests
        $stmt = $pdo->prepare("DELETE FROM follow_requests WHERE requester_id = ? AND requested_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        
        error_log("Committing transaction for unfollow");
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Unfollowed successfully']);
        exit();
    }

    // Handle cancel request action
    if ($action === 'cancel_request') {
        error_log("Processing cancel request action");
        $stmt = $pdo->prepare("DELETE FROM follow_requests WHERE requester_id = ? AND requested_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        
        error_log("Committing transaction for cancel request");
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Follow request canceled']);
        exit();
    }

    // Handle follow action
    if ($is_private) {
        error_log("Processing follow request for private profile");
        // Check if request already exists
        $stmt = $pdo->prepare("SELECT status FROM follow_requests WHERE requester_id = ? AND requested_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        $existing_request = $stmt->fetch();

        if ($existing_request) {
            error_log("Existing request found - Status: " . $existing_request['status']);
            if ($existing_request['status'] === 'pending') {
                error_log("Error: Request already pending");
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Follow request already sent']);
                exit();
            } else if ($existing_request['status'] === 'declined') {
                error_log("Updating declined request to pending");
                // Update the declined request to pending
                $stmt = $pdo->prepare("UPDATE follow_requests SET status = 'pending', updated_at = CURRENT_TIMESTAMP WHERE requester_id = ? AND requested_id = ?");
                $stmt->execute([$follower_id, $following_id]);

                // Create notification
                $notificationHelper = new NotificationHelper($pdo);
                $notificationHelper->createFollowRequestNotification($follower_id, $following_id);

                error_log("Committing transaction for updated follow request");
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Follow request sent']);
                exit();
            }
        }

        error_log("Creating new follow request");
        // Create follow request
        $stmt = $pdo->prepare("INSERT INTO follow_requests (requester_id, requested_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);

        error_log("Creating follow request notification");
        // Create notification using NotificationHelper
        $notificationHelper = new NotificationHelper($pdo);
        $notificationHelper->createFollowRequestNotification($follower_id, $following_id);

        error_log("Committing transaction for private follow request");
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Follow request sent',
            'is_private' => true
        ]);
    } else {
        error_log("Processing direct follow for public profile");
        // For public accounts, follow directly
        // Check if already following
        $stmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Already following']);
            exit();
        }

        // Add follow relationship
        $stmt = $pdo->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);

        error_log("Creating follow notification");
        // Create notification
        $notificationHelper = new NotificationHelper($pdo);
        $notificationHelper->createOrUpdateNotification($following_id, $follower_id, 'follow');

        error_log("Committing transaction for public follow");
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Followed successfully']);
    }
} catch (Exception $e) {
    error_log("Error in follow.php: " . $e->getMessage());
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?> 