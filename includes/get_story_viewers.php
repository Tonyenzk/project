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
$currentUserId = $_SESSION['user_id'];

try {
    // First check if the user requesting is the owner of the story
    $ownerCheckStmt = $pdo->prepare("SELECT user_id FROM stories WHERE story_id = ?");
    $ownerCheckStmt->execute([$storyId]);
    $owner = $ownerCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$owner) {
        echo json_encode([
            'success' => false,
            'message' => 'Story not found'
        ]);
        exit;
    }
    
    // Only the story owner should be able to see who viewed their story
    if ($owner['user_id'] != $currentUserId) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access'
        ]);
        exit;
    }
    
    // Get all viewers with their profile info
    $viewersStmt = $pdo->prepare("
        SELECT v.*, u.username, u.profile_picture, u.verifybadge
        FROM story_views v
        JOIN users u ON v.viewer_id = u.user_id
        WHERE v.story_id = ?
        ORDER BY v.created_at DESC
    ");
    $viewersStmt->execute([$storyId]);
    $viewers = $viewersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'viewers' => $viewers
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
