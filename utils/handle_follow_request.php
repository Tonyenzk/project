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
$requester_id = $data['user_id'] ?? null;
$action = $data['action'] ?? null;

if (!$requester_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Check if request exists and is pending
    $stmt = $pdo->prepare("SELECT * FROM follow_requests WHERE requester_id = ? AND requested_id = ? AND status = 'pending'");
    $stmt->execute([$requester_id, $_SESSION['user_id']]);
    $request = $stmt->fetch();

    if (!$request) {
        error_log("Follow request not found or already handled - Requester: $requester_id, Requested: {$_SESSION['user_id']}");
        throw new Exception('Follow request not found or already handled');
    }

    if ($action === 'accept') {
        error_log("Processing accept action for follow request - Requester: $requester_id, Requested: {$_SESSION['user_id']}");
        // Check if already following
        $stmt = $pdo->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$requester_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            // Add to followers table
            $stmt = $pdo->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
            $stmt->execute([$requester_id, $_SESSION['user_id']]);
            error_log("Added new follower relationship - Follower: $requester_id, Following: {$_SESSION['user_id']}");
        }

        // Update follow request status
        $stmt = $pdo->prepare("UPDATE follow_requests SET status = 'accepted' WHERE requester_id = ? AND requested_id = ?");
        $stmt->execute([$requester_id, $_SESSION['user_id']]);
        error_log("Updated follow request status to accepted - Requester: $requester_id, Requested: {$_SESSION['user_id']}");

        // Create notification for the requester (who is now following)
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'follow_request_accepted')");
        $stmt->execute([$requester_id, $_SESSION['user_id']]);
        error_log("Created follow request accepted notification - User: $requester_id, Actor: {$_SESSION['user_id']}");

        // Create notification for the current user (who was followed)
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, actor_id, type) VALUES (?, ?, 'new_follower')");
        $stmt->execute([$_SESSION['user_id'], $requester_id]);
        error_log("Created new follower notification - User: {$_SESSION['user_id']}, Actor: $requester_id");
    } else if ($action === 'decline') {
        error_log("Processing decline action for follow request - Requester: $requester_id, Requested: {$_SESSION['user_id']}");
        // Update follow request status to declined
        $stmt = $pdo->prepare("UPDATE follow_requests SET status = 'declined' WHERE requester_id = ? AND requested_id = ?");
        $stmt->execute([$requester_id, $_SESSION['user_id']]);
        error_log("Updated follow request status to declined - Requester: $requester_id, Requested: {$_SESSION['user_id']}");
    } else {
        error_log("Invalid action received: $action");
        throw new Exception('Invalid action');
    }

    $pdo->commit();
    error_log("Successfully processed follow request action: $action - Requester: $requester_id, Requested: {$_SESSION['user_id']}");
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error in handle_follow_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 