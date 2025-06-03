<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Set content type header to JSON
header('Content-Type: application/json');

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

try {
    // Get the story with user info
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.profile_picture, u.verifybadge 
        FROM stories s 
        JOIN users u ON s.user_id = u.user_id 
        WHERE s.story_id = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$storyId]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$story) {
        echo json_encode([
            'success' => false,
            'message' => 'Story not found or has expired'
        ]);
        exit;
    }
    
    // Format the time ago
    $time_difference = time() - strtotime($story['created_at']);
    if ($time_difference < 60) {
        $story['time_ago'] = '1s';
    } elseif ($time_difference < 3600) {
        $minutes = floor($time_difference / 60);
        $story['time_ago'] = $minutes . 'm';
    } elseif ($time_difference < 86400) {
        $hours = floor($time_difference / 3600);
        $story['time_ago'] = $hours . 'h';
    } else {
        $days = floor($time_difference / 86400);
        $story['time_ago'] = $days . 'd';
    }
    
    // Ensure image URL is properly formatted
    if (isset($story['image_url'])) {
        // Remove any leading slashes or '../' to avoid double paths
        $story['image_url'] = ltrim($story['image_url'], '/');
        $story['image_url'] = str_replace('../', '', $story['image_url']);
    }
    
    // Ensure profile picture URL is properly formatted
    if (isset($story['profile_picture'])) {
        // Remove any leading slashes or '../' to avoid double paths
        $story['profile_picture'] = ltrim($story['profile_picture'], '/');
        $story['profile_picture'] = str_replace('../', '', $story['profile_picture']);
    }
    
    echo json_encode([
        'success' => true,
        'story' => $story
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