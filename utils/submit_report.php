<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Trebuie să fii autentificat pentru a raporta o postare']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metodă invalidă']);
    exit;
}

// Validate required parameters
if (!isset($_POST['post_id']) || !isset($_POST['reason'])) {
    echo json_encode(['success' => false, 'error' => 'Parametri lipsă']);
    exit;
}

// Get and sanitize input
$postId = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);
$reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_SPECIAL_CHARS);
$userId = $_SESSION['user_id'];

// Validate input
if (empty($postId) || empty($reason)) {
    echo json_encode(['success' => false, 'error' => 'Te rugăm să completezi toate câmpurile']);
    exit;
}

// Check if post exists
try {
    $stmt = $pdo->prepare("SELECT post_id FROM posts WHERE post_id = ?");
    $stmt->execute([$postId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Postarea nu există']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Eroare la verificarea postării']);
    exit;
}

// Check if user has already reported this post
try {
    $stmt = $pdo->prepare("SELECT report_id FROM reports WHERE post_id = ? AND user_id = ? AND status != 'resolved'");
    $stmt->execute([$postId, $userId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ai raportat deja această postare și raportarea este în curs de procesare']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Eroare la verificarea raportărilor existente']);
    exit;
}

// Save report to database
try {
    $stmt = $pdo->prepare("INSERT INTO reports (post_id, user_id, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $result = $stmt->execute([$postId, $userId, $reason]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Raportarea a fost trimisă cu succes']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Eroare la salvarea raportării']);
    }
} catch (PDOException $e) {
    // Check if the reports table doesn't exist yet
    if ($e->getCode() == '42S02') { // Table doesn't exist error code
        // Create the reports table
        try {
            $createTable = $pdo->exec("CREATE TABLE reports (
                report_id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                reason TEXT NOT NULL,
                status ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
                admin_note TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME,
                FOREIGN KEY (post_id) REFERENCES posts(post_id),
                FOREIGN KEY (user_id) REFERENCES users(user_id)
            )");
            
            // Try inserting the report again
            $stmt = $pdo->prepare("INSERT INTO reports (post_id, user_id, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $result = $stmt->execute([$postId, $userId, $reason]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Raportarea a fost trimisă cu succes']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Eroare la salvarea raportării']);
            }
        } catch (PDOException $e2) {
            error_log('Database error creating reports table: ' . $e2->getMessage());
            echo json_encode(['success' => false, 'error' => 'Eroare la crearea tabelei de raportări']);
        }
    } else {
        error_log('Database error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Eroare la salvarea raportării: ' . $e->getMessage()]);
    }
}
