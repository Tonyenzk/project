<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to report a story']);
    exit;
}

if (!isset($_POST['story_id']) || !isset($_POST['description'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$story_id = filter_var($_POST['story_id'], FILTER_VALIDATE_INT);
$description = trim($_POST['description']);
$reporter_user_id = $_SESSION['user_id'];

if (!$story_id || empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Invalid story ID or description']);
    exit;
}

try {
    // Check if story exists
    $stmt = $pdo->prepare("SELECT user_id FROM stories WHERE story_id = ?");
    $stmt->execute([$story_id]);
    $story = $stmt->fetch();

    if (!$story) {
        echo json_encode(['success' => false, 'message' => 'Story not found']);
        exit;
    }

    // Check if user is not reporting their own story
    if ($story['user_id'] == $reporter_user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot report your own story']);
        exit;
    }

    // Check if user has already reported this story
    $stmt = $pdo->prepare("SELECT report_id FROM story_reports WHERE story_id = ? AND reporter_user_id = ?");
    $stmt->execute([$story_id, $reporter_user_id]);
    $existing_report = $stmt->fetch();

    if ($existing_report) {
        echo json_encode(['success' => false, 'message' => 'You have already reported this story']);
        exit;
    }

    // Check how many unique reports this story has
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT reporter_user_id) as report_count FROM story_reports WHERE story_id = ?");
    $stmt->execute([$story_id]);
    $report_count = $stmt->fetch()['report_count'];

    if ($report_count >= 2) {
        echo json_encode(['success' => false, 'message' => 'This story has already been reported. An admin will review it soon']);
        exit;
    }

    // Insert the report
    $stmt = $pdo->prepare("INSERT INTO story_reports (story_id, reporter_user_id, description, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$story_id, $reporter_user_id, $description]);

    echo json_encode(['success' => true, 'message' => 'Story reported successfully']);
} catch (PDOException $e) {
    error_log("Error in report_story.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while reporting the story']);
} 