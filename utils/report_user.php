<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to report a user']);
    exit;
}

if (!isset($_POST['user_id']) || !isset($_POST['description'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$reported_user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
$description = trim($_POST['description']);
$reporter_user_id = $_SESSION['user_id'];

if (!$reported_user_id || empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID or description']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->execute([$reported_user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Check if user is not reporting themselves
    if ($reported_user_id == $reporter_user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot report yourself']);
        exit;
    }

    // Check if user has already reported this user
    $stmt = $pdo->prepare("SELECT report_id FROM user_reports WHERE reported_user_id = ? AND reporter_user_id = ?");
    $stmt->execute([$reported_user_id, $reporter_user_id]);
    $existing_report = $stmt->fetch();

    if ($existing_report) {
        echo json_encode(['success' => false, 'message' => 'You have already reported this user']);
        exit;
    }

    // Check how many unique reports this user has
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT reporter_user_id) as report_count FROM user_reports WHERE reported_user_id = ?");
    $stmt->execute([$reported_user_id]);
    $report_count = $stmt->fetch()['report_count'];

    if ($report_count >= 2) {
        echo json_encode(['success' => false, 'message' => 'This user has already been reported. An admin will review it soon']);
        exit;
    }

    // Insert the report
    $stmt = $pdo->prepare("INSERT INTO user_reports (reported_user_id, reporter_user_id, description, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$reported_user_id, $reporter_user_id, $description]);

    echo json_encode(['success' => true, 'message' => 'User reported successfully']);
} catch (PDOException $e) {
    error_log("Error in report_user.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while reporting the user']);
} 