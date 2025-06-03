<?php
session_start();
require_once '../config/database.php';

// Set content type to plain text for easier reading
header('Content-Type: text/plain');

echo "===== COMMENT REPORT DEBUG OUTPUT =====\n\n";

// Print all request data
echo "REQUEST METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n\n";

echo "SESSION DATA:\n";
echo "Logged in user ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n\n";

echo "POST DATA:\n";
echo print_r($_POST, true) . "\n\n";

echo "RAW INPUT:\n";
echo file_get_contents('php://input') . "\n\n";

// If we have a comment_id, check if it exists
if (isset($_POST['comment_id']) || isset($_GET['comment_id'])) {
    // Use either POST or GET parameter
    $comment_id = $_POST['comment_id'] ?? $_GET['comment_id'];
    
    echo "CHECKING COMMENT ID: $comment_id\n\n";
    
    try {
        // Check if comment exists directly
        $stmt = $pdo->prepare("SELECT * FROM comments WHERE comment_id = ?");
        $stmt->execute([$comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comment) {
            echo "COMMENT EXISTS:\n";
            echo print_r($comment, true) . "\n\n";
            
            // Check if the post exists
            $post_id = $comment['post_id'];
            $stmt = $pdo->prepare("SELECT post_id FROM posts WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if ($post) {
                echo "ASSOCIATED POST EXISTS (ID: {$post['post_id']})\n";
            } else {
                echo "CRITICAL ERROR: ASSOCIATED POST DOES NOT EXIST (ID: $post_id)\n";
            }
        } else {
            echo "CRITICAL ERROR: COMMENT DOES NOT EXIST (ID: $comment_id)\n";
            
            // Show a few sample comments to verify database connection
            $sample = $pdo->query("SELECT comment_id, post_id FROM comments ORDER BY comment_id DESC LIMIT 5");
            $samples = $sample->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\nMOST RECENT COMMENTS IN DATABASE:\n";
            echo print_r($samples, true);
        }
    } catch (PDOException $e) {
        echo "DATABASE ERROR: " . $e->getMessage() . "\n";
    }
}

// Check for tables and their structure
echo "\n===== DATABASE STRUCTURE =====\n";
try {
    // Check comments table
    $tables = $pdo->query("SHOW TABLES LIKE 'comments'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Comments table exists: " . (!empty($tables) ? "YES" : "NO") . "\n";
    
    if (!empty($tables)) {
        $columns = $pdo->query("DESCRIBE comments")->fetchAll(PDO::FETCH_ASSOC);
        echo "Comments table columns:\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
    }
    
    // Check comment_reports table
    $tables = $pdo->query("SHOW TABLES LIKE 'comment_reports'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nComment_reports table exists: " . (!empty($tables) ? "YES" : "NO") . "\n";
    
    if (!empty($tables)) {
        $columns = $pdo->query("DESCRIBE comment_reports")->fetchAll(PDO::FETCH_ASSOC);
        echo "Comment_reports table columns:\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
    }
} catch (PDOException $e) {
    echo "ERROR CHECKING DATABASE STRUCTURE: " . $e->getMessage() . "\n";
}

echo "\n===== END OF DEBUG OUTPUT =====";
?>
