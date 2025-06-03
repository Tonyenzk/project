<?php
// Start output buffering to catch any unexpected output
ob_start();

session_start();
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display_errors to prevent HTML in JSON

// Set header to return JSON response
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message) {
    ob_clean(); // Clear any output buffer
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'You must be logged in to report a post');
}

// Get the current user's ID
$reporter_id = $_SESSION['user_id'];

// Validate required fields
if (!isset($_POST['post_id']) || !isset($_POST['description'])) {
    sendJsonResponse(false, 'Missing required fields');
}

$post_id = intval($_POST['post_id']);
$description = trim($_POST['description']);

// Validate description length
if (strlen($description) > 150) {
    sendJsonResponse(false, 'Description must be 150 characters or less');
}

try {
    // Check if post exists
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        sendJsonResponse(false, 'Post not found');
    }
    
    // Prevent users from reporting their own posts
    if ($post['user_id'] === $reporter_id) {
        sendJsonResponse(false, 'You cannot report your own post');
    }
    
    // Check if user has already reported this post
    $stmt = $pdo->prepare("SELECT report_id FROM post_reports WHERE post_id = ? AND reporter_user_id = ? AND status = 'pending'");
    $stmt->execute([$post_id, $reporter_id]);
    
    if ($stmt->rowCount() > 0) {
        sendJsonResponse(false, 'You have already reported this post');
    }
    
    // Insert the report
    $stmt = $pdo->prepare("INSERT INTO post_reports (post_id, reporter_user_id, description, status) VALUES (?, ?, ?, 'pending')");
    
    if ($stmt->execute([$post_id, $reporter_id, $description])) {
        sendJsonResponse(true, 'Post reported successfully');
    } else {
        throw new Exception("Failed to insert report");
    }
    
} catch (Exception $e) {
    error_log("Error in report_post.php: " . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while reporting the post');
}
?> 