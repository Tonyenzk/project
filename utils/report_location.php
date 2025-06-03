<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to report a location']);
    exit;
}

// Get POST data
$location = $_POST['location'] ?? null;
$description = $_POST['description'] ?? null;

// Validate inputs
if (!$location) {
    echo json_encode(['success' => false, 'message' => 'Location is required']);
    exit;
}

if (!$description) {
    echo json_encode(['success' => false, 'message' => 'Please provide a reason for your report']);
    exit;
}

try {
    // Check if user has already reported this location
    $checkStmt = $pdo->prepare("SELECT report_id FROM location_reports WHERE location = ? AND reporter_user_id = ?");
    $checkStmt->execute([$location, $_SESSION['user_id']]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already reported this location']);
        exit;
    }
    
    // Insert the report
    $stmt = $pdo->prepare("INSERT INTO location_reports (location, reporter_user_id, reason, description) VALUES (?, ?, 'inappropriate', ?)");
    $stmt->execute([$location, $_SESSION['user_id'], $description]);
    
    echo json_encode(['success' => true, 'message' => 'Location reported successfully']);
} catch (PDOException $e) {
    error_log("Error in report_location.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while reporting the location']);
} 