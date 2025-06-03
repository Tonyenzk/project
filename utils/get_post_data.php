<?php
session_start();
require_once '../config/database.php';
require_once '../includes/image_helper.php';

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
    exit();
}

try {
    // Fetch post data with user information
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.username,
            u.name,
            u.profile_picture,
            u.verifybadge,
            u.bio,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
            EXISTS(SELECT 1 FROM likes WHERE post_id = p.post_id AND user_id = ?) as is_liked,
            EXISTS(SELECT 1 FROM saved_posts WHERE post_id = p.post_id AND user_id = ?) as is_saved,
            (p.user_id = ?) as is_owner,
            TIMESTAMPDIFF(SECOND, p.created_at, NOW()) as time_diff
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.post_id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit();
    }

    // Format the time using time_diff from database
    $time_diff = (int)$post['time_diff'];
    
    $time_ago = '';
    if ($time_diff < 60) {
        $time_ago = 'Just now';
    } elseif ($time_diff < 3600) {
        $time_ago = floor($time_diff / 60) . 'm';
    } elseif ($time_diff < 86400) {
        $time_ago = floor($time_diff / 3600) . 'h';
    } elseif ($time_diff < 604800) {
        $time_ago = floor($time_diff / 86400) . 'd';
    } elseif ($time_diff < 2592000) {
        $time_ago = floor($time_diff / 604800) . 'w';
    } elseif ($time_diff < 31536000) {
        $time_ago = floor($time_diff / 2592000) . 'mo';
    } else {
        $time_ago = floor($time_diff / 31536000) . 'y';
    }

    // Fetch comments with user information and reply count
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $comments_per_page = 10;
    $offset = ($page - 1) * $comments_per_page;

    // First, get total count of comments
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_comments
        FROM comments
        WHERE post_id = ? AND parent_comment_id IS NULL
    ");
    $stmt->execute([$post_id]);
    $total_comments = $stmt->fetch(PDO::FETCH_ASSOC)['total_comments'];

    // Then fetch paginated comments
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username,
            u.profile_picture,
            u.verifybadge,
            EXISTS(SELECT 1 FROM likes WHERE comment_id = c.comment_id AND user_id = ?) as is_liked,
            (SELECT COUNT(*) FROM likes WHERE comment_id = c.comment_id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) as reply_count,
            TIMESTAMPDIFF(SECOND, c.created_at, NOW()) as time_diff
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.post_id = ? AND c.parent_comment_id IS NULL
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $post_id, $comments_per_page, $offset]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate if there are more comments
    $has_more_comments = ($offset + $comments_per_page) < $total_comments;

    // Format time for each comment
    foreach ($comments as &$comment) {
        $time_diff = (int)$comment['time_diff'];
        
        if ($time_diff < 60) {
            $comment['time_ago'] = 'Just now';
        } elseif ($time_diff < 3600) {
            $comment['time_ago'] = floor($time_diff / 60) . 'm';
        } elseif ($time_diff < 86400) {
            $comment['time_ago'] = floor($time_diff / 3600) . 'h';
        } elseif ($time_diff < 604800) {
            $comment['time_ago'] = floor($time_diff / 86400) . 'd';
        } elseif ($time_diff < 2592000) {
            $comment['time_ago'] = floor($time_diff / 604800) . 'w';
        } elseif ($time_diff < 31536000) {
            $comment['time_ago'] = floor($time_diff / 2592000) . 'mo';
        } else {
            $comment['time_ago'] = floor($time_diff / 31536000) . 'y';
        }
    }

    // Fetch tagged users for this post
    $stmt = $pdo->prepare("
        SELECT 
            pt.tagged_user_id,
            u.username,
            u.name as full_name,
            u.profile_picture,
            u.verifybadge as is_verified
        FROM post_tags pt
        JOIN users u ON pt.tagged_user_id = u.user_id
        WHERE pt.post_id = ?
        ORDER BY pt.created_at
    ");
    $stmt->execute([$post_id]);
    $tagged_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add tagged usernames as a comma-separated string for easy parsing
    $tagged_usernames = [];
    foreach ($tagged_users as $user) {
        $tagged_usernames[] = $user['username'];
    }
    
    $post['time_ago'] = $time_ago;
    $post['comments'] = $comments;
    $post['tagged_users'] = $tagged_users;
    $post['tagged_usernames'] = implode(',', $tagged_usernames);
    $post['has_more_comments'] = $has_more_comments;
    
    // Add cached image URL for the post
    $post['cached_image_url'] = get_cached_image_url($post['image_url']);

    echo json_encode(['success' => true, 'data' => $post]);

} catch (PDOException $e) {
    error_log("Error fetching post data: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred']);
} 