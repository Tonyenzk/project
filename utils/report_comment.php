<?php
// Version: 3.1 - FORCE REFRESH - NO POST CHECKS - 2025-05-31 10:00
session_start();

// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../config/database.php';

// Debug logging to separate log file to avoid any confusion
$debug_log = "../report_debug.log";
file_put_contents($debug_log, "\n\n======== NEW REPORT ATTEMPT: " . date('Y-m-d H:i:s') . " ========\n", FILE_APPEND);
file_put_contents($debug_log, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($debug_log, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Always respond with JSON
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// AUTHENTICATION CHECK
if (!isset($_SESSION['user_id'])) {
    file_put_contents($debug_log, "ERROR: Not logged in\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Please log in to report this comment']);
    exit;
}

// DATA VALIDATION
$reporter_user_id = (int)$_SESSION['user_id'];
$comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

file_put_contents($debug_log, "Normalized values: comment_id=$comment_id, reporter=$reporter_user_id\n", FILE_APPEND);

if ($comment_id <= 0 || empty($description)) {
    file_put_contents($debug_log, "ERROR: Invalid comment ID or empty description\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Invalid comment ID or missing description']);
    exit;
}

try {
    // FIRST: Check if the comment exists
    $stmt = $pdo->prepare("SELECT comment_id, user_id FROM comments WHERE comment_id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    file_put_contents($debug_log, "Comment exists check: " . ($comment ? "YES" : "NO") . "\n", FILE_APPEND);
    
    // COMMENT EXISTENCE CHECK
    if (!$comment) {
        file_put_contents($debug_log, "ERROR: Comment $comment_id not found in database\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }
    
    // SELF-REPORT CHECK
    if ($comment['user_id'] == $reporter_user_id) {
        file_put_contents($debug_log, "ERROR: Self-report attempt\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'You cannot report your own comment']);
        exit;
    }
    
    // DUPLICATE REPORT CHECK
    $stmt = $pdo->prepare("SELECT report_id FROM comment_reports WHERE comment_id = ? AND reporter_user_id = ?");
    $stmt->execute([$comment_id, $reporter_user_id]);
    $existing = $stmt->fetch();
    
    file_put_contents($debug_log, "Duplicate check: " . ($existing ? "DUPLICATE" : "NOT DUPLICATE") . "\n", FILE_APPEND);
    
    if ($existing) {
        file_put_contents($debug_log, "ERROR: Duplicate report\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'You have already reported this comment']);
        exit;
    }
    
    // IMPORTANT: ACTUALLY INSERT THE REPORT - NO POST VALIDATION
    $stmt = $pdo->prepare("INSERT INTO comment_reports 
                         (comment_id, reporter_user_id, reason, description, status, created_at) 
                         VALUES (?, ?, 'inappropriate', ?, 'pending', NOW())");
    
    $success = $stmt->execute([$comment_id, $reporter_user_id, $description]);
    
    file_put_contents($debug_log, "Insert result: " . ($success ? "SUCCESS" : "FAILED") . "\n", FILE_APPEND);
    
    if ($success) {
        $report_id = $pdo->lastInsertId();
        file_put_contents($debug_log, "SUCCESS: Report created with ID $report_id\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Comment reported successfully', 'report_id' => $report_id]);
    } else {
        $error = implode(", ", $stmt->errorInfo());
        file_put_contents($debug_log, "DATABASE ERROR: $error\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Database error: Unable to save report']);
    }
    
} catch (Exception $e) {
    file_put_contents($debug_log, "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
