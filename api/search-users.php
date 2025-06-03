<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search)) {
    echo json_encode([]);
    exit();
}

try {
    // First, let's check if the user exists at all
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE username = ? OR name = ?
    ");
    $checkStmt->execute([$search, $search]);
    $userCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Search for users matching the query
    $stmt = $pdo->prepare("
        SELECT 
            user_id,
            username,
            name as full_name,
            profile_picture,
            verifybadge,
            isPrivate
        FROM users 
        WHERE (username LIKE ? OR name LIKE ?)
        AND user_id != ?
        LIMIT 5
    ");
    
    $searchTerm = "%{$search}%";
    $stmt->execute([$searchTerm, $searchTerm, $_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $formattedUsers = array_map(function($user) {
        return [
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'profile_picture' => $user['profile_picture'] ?: 'images/profile_placeholder.webp',
            'is_verified' => $user['verifybadge'] === 'true'
        ];
    }, $users);
    
    echo json_encode($formattedUsers);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} 