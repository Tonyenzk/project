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
if (!isset($_GET['story_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$storyId = $_GET['story_id'];
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
    
    // Don't check likes for your own stories
    if ($story['user_id'] == $viewerId) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot like your own story'
        ]);
        exit;
    }
    
    // Check if the user has liked this story
    $checkStmt = $pdo->prepare("SELECT has_liked FROM story_views WHERE story_id = ? AND viewer_id = ?");
    $checkStmt->execute([$storyId, $viewerId]);
    $view = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($view) {
        echo json_encode([
            'success' => true,
            'liked' => (bool)$view['has_liked']
        ]);
    } else {
        // No view record exists, which means the user hasn't viewed or liked the story
        echo json_encode([
            'success' => true,
            'liked' => false
        ]);
    }
    
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
