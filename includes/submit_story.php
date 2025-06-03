<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['storyImage']) || $_FILES['storyImage']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
    exit;
}

try {
    // Process the uploaded image
    $file = $_FILES['storyImage'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    
    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and WebP are allowed.']);
        exit;
    }
    
    // Validate file size (5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/stories/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid(md5(time())) . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
    
    // Get music data if provided
    $musicData = null;
    if (isset($_POST['music_data']) && !empty($_POST['music_data'])) {
        $musicData = json_decode($_POST['music_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $musicData = null;
        }
    }
    
    // Get music start time if provided (for 15-second clip selection)
    if ($musicData && isset($_POST['music_start_time'])) {
        $musicData['startTime'] = (float)$_POST['music_start_time'];
    }
    
    // Save story to database
    $imageUrl = 'uploads/stories/' . $filename; // Path relative to root
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO stories (user_id, image_url, music_data) VALUES (?, ?, ?)");
    $stmt->execute([
        $userId,
        $imageUrl,
        $musicData ? json_encode($musicData) : null
    ]);
    
    $storyId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Story created successfully',
        'story_id' => $storyId,
        'image_url' => $imageUrl,
        'music_data' => $musicData
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
