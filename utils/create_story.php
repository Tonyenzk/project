<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['storyImage']) || $_FILES['storyImage']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No image uploaded or upload error']);
    exit;
}

try {
    // Create directory if it doesn't exist
    $uploadDir = '../uploads/stories/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Process file
    $file = $_FILES['storyImage'];
    $fileName = uniqid() . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Save story to database
        $stmt = $pdo->prepare("INSERT INTO stories (user_id, image_url) VALUES (?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'uploads/stories/' . $fileName
        ]);
        
        $storyId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Story created successfully',
            'story_id' => $storyId,
            'image_url' => 'uploads/stories/' . $fileName
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
