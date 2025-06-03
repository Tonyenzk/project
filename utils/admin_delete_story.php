<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if user has admin privileges
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !in_array($user['role'], ['Moderator', 'Admin', 'Master_Admin'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $story_id = isset($_POST['story_id']) ? (int)$_POST['story_id'] : 0;

        if (!$story_id) {
            throw new Exception('Invalid story ID');
        }

        // Get story info for image deletion
        $stmt = $pdo->prepare("SELECT image_url FROM stories WHERE story_id = ?");
        $stmt->execute([$story_id]);
        $story = $stmt->fetch();

        if (!$story) {
            throw new Exception('Story not found');
        }

        // Begin transaction to ensure atomicity
        $pdo->beginTransaction();
        
        try {
            // First delete all associated story views
            $deleteViewsStmt = $pdo->prepare("DELETE FROM story_views WHERE story_id = ?");
            $deleteViewsStmt->execute([$story_id]);
            
            // Delete story reports
            $deleteReportsStmt = $pdo->prepare("DELETE FROM story_reports WHERE story_id = ?");
            $deleteReportsStmt->execute([$story_id]);
            
            // Then delete the story itself
            $deleteStoryStmt = $pdo->prepare("DELETE FROM stories WHERE story_id = ?");
            $deleteStoryStmt->execute([$story_id]);
            
            // Commit the transaction
            $pdo->commit();
        } catch (Exception $e) {
            // Rollback on error
            $pdo->rollBack();
            throw new Exception('Error deleting story: ' . $e->getMessage());
        }

        // Delete the image file
        if ($story['image_url']) {
            $image_path = '../' . $story['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Story deleted successfully'
        ]);

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
} 