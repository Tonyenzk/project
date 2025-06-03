<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Debug log the parameters
error_log("Type: " . $type . ", User ID: " . $user_id);

if (!in_array($type, ['followers', 'following']) || $user_id <= 0) {
    error_log("Invalid parameters - Type: " . $type . ", User ID: " . $user_id);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    if ($type === 'followers') {
        // Get followers
        $query = "
            SELECT u.user_id, u.username, u.profile_picture, u.name, u.isPrivate, u.verifybadge,
                   EXISTS(SELECT 1 FROM followers WHERE follower_id = ? AND following_id = u.user_id) as is_following,
                   EXISTS(SELECT 1 FROM follow_requests WHERE requester_id = ? AND requested_id = u.user_id AND status = 'pending') as has_pending_request
            FROM followers f
            JOIN users u ON f.follower_id = u.user_id
            WHERE f.following_id = ?
            ORDER BY u.username
        ";
        error_log("Followers Query: " . $query);
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $user_id]);
    } else {
        // Get following
        $query = "
            SELECT u.user_id, u.username, u.profile_picture, u.name, u.isPrivate, u.verifybadge,
                   EXISTS(SELECT 1 FROM followers WHERE follower_id = ? AND following_id = u.user_id) as is_following,
                   EXISTS(SELECT 1 FROM follow_requests WHERE requester_id = ? AND requested_id = u.user_id AND status = 'pending') as has_pending_request
            FROM followers f
            JOIN users u ON f.following_id = u.user_id
            WHERE f.follower_id = ?
            ORDER BY u.username
        ";
        error_log("Following Query: " . $query);
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $user_id]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Number of users found: " . count($users));
    
    // Process profile pictures
    foreach ($users as &$user) {
        $user['profile_picture'] = $user['profile_picture'] ?: './images/profile_placeholder.webp';
        // Map 'name' to 'full_name' for frontend compatibility
        $user['full_name'] = $user['name'];
        unset($user['name']);
        // Add isVerified flag
        $user['isVerified'] = $user['verifybadge'] === 'true';
        unset($user['verifybadge']);
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error',
        'debug' => [
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]
    ]);
} 