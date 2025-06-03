<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

try {
    // Get stories that haven't expired yet (less than 24 hours old)
    $stmt = $pdo->prepare("SELECT s.*, u.username, u.profile_picture, u.verifybadge 
                           FROM stories s 
                           JOIN users u ON s.user_id = u.user_id 
                           WHERE s.expires_at > NOW() 
                           AND s.user_id = ? 
                           ORDER BY s.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stories' => $stories
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
