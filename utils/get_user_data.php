<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Get user ID from query parameter
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No user ID provided']);
    exit();
}

try {
    // Fetch user data
    $stmt = $pdo->prepare("
        SELECT user_id, username, profile_picture 
        FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'profile_picture' => $user['profile_picture'] ?: './images/profile_placeholder.webp'
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
} 