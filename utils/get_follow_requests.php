<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

try {
    // Fetch pending follow requests
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.profile_picture 
        FROM follow_requests fr 
        JOIN users u ON fr.requester_id = u.user_id 
        WHERE fr.requested_id = ? AND fr.status = 'pending'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $follow_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'requests' => $follow_requests
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
} 