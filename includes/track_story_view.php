<?php
// Suppress all errors and warnings to avoid polluting JSON output
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type header to JSON
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

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
    
    // Special handling for viewing your own stories
    if ($story['user_id'] == $viewerId) {
        // For your own stories, only record in stories_viewed table
        // This allows tracking which of your own stories you've seen
        $viewedStmt = $pdo->prepare("INSERT INTO stories_viewed (user_id, story_id) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP");
        $viewedStmt->execute([$viewerId, $storyId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Own story view recorded in stories_viewed'
        ]);
        exit;
    }
    
    // Check if the view already exists
    $checkStmt = $pdo->prepare("SELECT * FROM story_views WHERE story_id = ? AND viewer_id = ?");
    $checkStmt->execute([$storyId, $viewerId]);
    $existingView = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingView) {
        // View already recorded, just return success
        echo json_encode([
            'success' => true,
            'message' => 'View already recorded'
        ]);
        exit;
    }
    
    // Insert the new view into story_views table (for viewer lists)  
    $insertStmt = $pdo->prepare("INSERT INTO story_views (story_id, viewer_id) VALUES (?, ?)");
    $insertStmt->execute([$storyId, $viewerId]);
    
    // Also record in stories_viewed table (for tracking which stories a user has viewed)
    $viewedStmt = $pdo->prepare("INSERT INTO stories_viewed (user_id, story_id) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP");
    $viewedStmt->execute([$viewerId, $storyId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'View recorded successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
