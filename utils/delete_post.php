<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

        if (!$post_id) {
            throw new Exception('Invalid post ID');
        }

        // Check if the post belongs to the current user
        $stmt = $pdo->prepare("SELECT user_id, image_url FROM posts WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();

        if (!$post) {
            throw new Exception('Post not found');
        }

        if ($post['user_id'] != $_SESSION['user_id']) {
            throw new Exception('You do not have permission to delete this post');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Delete post tags
        $stmt = $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Delete likes
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Delete comments
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Delete saved posts
        $stmt = $pdo->prepare("DELETE FROM saved_posts WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Delete the post
        $stmt = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Commit transaction
        $pdo->commit();

        // Delete the image file
        if ($post['image_url']) {
            $image_path = '../' . $post['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction if started
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

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