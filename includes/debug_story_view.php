<?php
// Debugging script to manually record a story view
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html');
echo "<h1>Story View Debug</h1>";

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>Error: User not logged in</p>";
    exit;
}

// Get story ID from GET parameter
$storyId = isset($_GET['story_id']) ? intval($_GET['story_id']) : null;
if (!$storyId) {
    echo "<p>Error: No story ID provided</p>";
    exit;
}

$viewerId = $_SESSION['user_id'];

echo "<p>Attempting to record view for Story ID: $storyId by User ID: $viewerId</p>";

try {
    // First check if this story exists and hasn't expired
    $storyStmt = $pdo->prepare("SELECT * FROM stories WHERE story_id = ? AND expires_at > NOW()");
    $storyStmt->execute([$storyId]);
    $story = $storyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$story) {
        echo "<p>Error: Story not found or has expired</p>";
        exit;
    }
    
    echo "<p>Story found: Owner ID {$story['user_id']}, Created at {$story['created_at']}</p>";
    
    // Special handling for viewing your own stories
    if ($story['user_id'] == $viewerId) {
        echo "<p>This is your own story</p>";
        
        // For your own stories, only record in stories_viewed table
        // This allows tracking which of your own stories you've seen
        $viewedStmt = $pdo->prepare("INSERT INTO stories_viewed (user_id, story_id) VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP");
        $viewedStmt->execute([$viewerId, $storyId]);
        
        echo "<p>Success: Own story view recorded in stories_viewed table</p>";
        echo "<p>SQL: INSERT INTO stories_viewed (user_id, story_id) VALUES ($viewerId, $storyId) ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP</p>";
        
        // Check if the record was added
        $checkStmt = $pdo->prepare("SELECT * FROM stories_viewed WHERE user_id = ? AND story_id = ?");
        $checkStmt->execute([$viewerId, $storyId]);
        $viewRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($viewRecord) {
            echo "<p>Verified: Record exists in stories_viewed table with viewed_at: {$viewRecord['viewed_at']}</p>";
        } else {
            echo "<p>Error: Failed to verify record in stories_viewed table</p>";
        }
    } else {
        echo "<p>This is another user's story</p>";
        
        // Check if the view already exists in story_views
        $checkStmt = $pdo->prepare("SELECT * FROM story_views WHERE story_id = ? AND viewer_id = ?");
        $checkStmt->execute([$storyId, $viewerId]);
        $existingView = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingView) {
            echo "<p>View already recorded in story_views table with ID: {$existingView['view_id']}</p>";
        } else {
            // Insert the new view in story_views
            $insertStmt = $pdo->prepare("INSERT INTO story_views (story_id, viewer_id) VALUES (?, ?)");
            $insertStmt->execute([$storyId, $viewerId]);
            echo "<p>Success: View recorded in story_views table</p>";
        }
        
        // Also record in stories_viewed table
        $viewedStmt = $pdo->prepare("INSERT INTO stories_viewed (user_id, story_id) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP");
        $viewedStmt->execute([$viewerId, $storyId]);
        echo "<p>Success: View recorded in stories_viewed table</p>";
        
        // Check if the record was added to stories_viewed
        $checkStmt = $pdo->prepare("SELECT * FROM stories_viewed WHERE user_id = ? AND story_id = ?");
        $checkStmt->execute([$viewerId, $storyId]);
        $viewRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($viewRecord) {
            echo "<p>Verified: Record exists in stories_viewed table with viewed_at: {$viewRecord['viewed_at']}</p>";
        } else {
            echo "<p>Error: Failed to verify record in stories_viewed table</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
?>
