<?php
// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type header to JSON
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/NotificationHelper.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['story_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$storyId = $_POST['story_id'];
$viewerId = $_SESSION['user_id'];

try {
    // First check if this story exists and hasn't expired
    $storyStmt = $pdo->prepare("SELECT * FROM stories WHERE story_id = ? AND expires_at > NOW()");
    $storyStmt->execute([$storyId]);
    $story = $storyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$story) {
        echo json_encode([
            'success' => false,
            'message' => 'Story not found or has expired'
        ]);
        exit;
    }
    
    // Don't allow users to like their own stories
    if ($story['user_id'] == $viewerId) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot like your own story'
        ]);
        exit;
    }
    
    $storyOwnerId = $story['user_id'];
    $notificationHelper = new NotificationHelper($pdo);
    
    // Check if the view already exists
    $checkStmt = $pdo->prepare("SELECT * FROM story_views WHERE story_id = ? AND viewer_id = ?");
    $checkStmt->execute([$storyId, $viewerId]);
    $existingView = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingView) {
        // View exists, toggle the like status
        $newLikeStatus = $existingView['has_liked'] ? 0 : 1;
        $updateStmt = $pdo->prepare("UPDATE story_views SET has_liked = ? WHERE story_id = ? AND viewer_id = ?");
        $updateStmt->execute([$newLikeStatus, $storyId, $viewerId]);
        
        // Send notification only if liking (not unliking)
        if ($newLikeStatus) {
            // Prevent duplicate notifications: check if a notification already exists for this story like
            $stmt = $pdo->prepare("SELECT notification_id FROM notifications WHERE user_id = ? AND actor_id = ? AND type = 'story_like' AND story_id = ?");
            $stmt->execute([$storyOwnerId, $viewerId, $storyId]);
            $existingNotification = $stmt->fetch();
            if (!$existingNotification) {
                $notificationHelper->createOrUpdateNotification($storyOwnerId, $viewerId, 'story_like', null, null, $storyId);
            }
        }
        
        echo json_encode([
            'success' => true,
            'liked' => (bool)$newLikeStatus,
            'message' => (bool)$newLikeStatus ? 'Story liked' : 'Story unliked'
        ]);
    } else {
        // Create a view record with like
        $insertStmt = $pdo->prepare("INSERT INTO story_views (story_id, viewer_id, has_liked) VALUES (?, ?, 1)");
        $insertStmt->execute([$storyId, $viewerId]);
        
        // Prevent duplicate notifications: check if a notification already exists for this story like
        $stmt = $pdo->prepare("SELECT notification_id FROM notifications WHERE user_id = ? AND actor_id = ? AND type = 'story_like' AND story_id = ?");
        $stmt->execute([$storyOwnerId, $viewerId, $storyId]);
        $existingNotification = $stmt->fetch();
        if (!$existingNotification) {
            $notificationHelper->createOrUpdateNotification($storyOwnerId, $viewerId, 'story_like', null, null, $storyId);
        }
        
        echo json_encode([
            'success' => true,
            'liked' => true,
            'message' => 'Story liked and view recorded'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in like_story.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in like_story.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
