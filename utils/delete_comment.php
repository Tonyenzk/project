<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly, but log them

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Buffer output to prevent any unwanted output before JSON
ob_start();

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => 'An error occurred'
];

try {
    // Include database connection
    require_once '../config/database.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'You must be logged in to delete a comment';
        throw new Exception('Not logged in');
    }

    // Get user ID from session
    $user_id = $_SESSION['user_id'];

    // Check if comment_id is provided
    if (!isset($_POST['comment_id']) || empty($_POST['comment_id'])) {
        $response['message'] = 'Comment ID is required';
        throw new Exception('Comment ID missing');
    }

    // Get comment ID
    $comment_id = intval($_POST['comment_id']);

    // Verify the comment exists and belongs to the user
    $query = "SELECT user_id FROM comments WHERE comment_id = :comment_id";
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        $response['message'] = 'Database error during comment verification';
        throw new Exception("PDO prepare failed");
    }
    
    $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        $response['message'] = 'Error executing comment query';
        throw new Exception("PDO execute failed");
    }
    
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($comment === false) {
        $response['message'] = 'Comment not found';
        throw new Exception('Comment not found');
    }

    // Check if the user is the owner of the comment
    if ($comment['user_id'] != $user_id) {
        $response['message'] = 'You can only delete your own comments';
        throw new Exception('Not comment owner');
    }

    // Delete the comment
    $deleteQuery = "DELETE FROM comments WHERE comment_id = :comment_id AND user_id = :user_id";
    $deleteStmt = $pdo->prepare($deleteQuery);
    if (!$deleteStmt) {
        $response['message'] = 'Database error during comment deletion';
        throw new Exception("PDO prepare failed for delete");
    }
    
    $deleteStmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
    $deleteStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if ($deleteStmt->execute()) {
        // Check number of affected rows
        $affectedRows = $deleteStmt->rowCount();
        
        if ($affectedRows > 0) {
            $response['success'] = true;
            $response['message'] = 'Comment deleted successfully';
            // Any child comments will be automatically deleted by the ON DELETE CASCADE
            // constraint in the database
        } else {
            // This shouldn't happen since we already verified the comment exists
            $response['message'] = 'Comment was not deleted';
        }
    } else {
        $response['message'] = 'Failed to delete comment';
        throw new Exception('Failed to delete comment');
    }

} catch (Exception $e) {
    // Log the exception but don't expose details to client
    error_log("Error in delete_comment.php: " . $e->getMessage());
} finally {
    // Clean any buffered output
    ob_end_clean();
    
    // Return JSON response
    echo json_encode($response);
    exit;
}
?>
