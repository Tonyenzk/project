<?php
// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start a session
session_start();

// Set up the page
echo "<html><head>";
echo "<title>Comment Report Debugging Tool</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #333; }
    .card { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .success { background-color: #d4edda; color: #155724; }
    .error { background-color: #f8d7da; color: #721c24; }
    pre { background: #f4f4f4; padding: 10px; overflow: auto; }
    .btn { background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; }
    input, textarea { width: 100%; padding: 8px; margin-bottom: 10px; }
</style>";
echo "</head><body>";
echo "<h1>Comment Report Debugging Tool</h1>";

// Load database connection
require_once 'config/database.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='card error'>";
    echo "<h2>Error: Not logged in</h2>";
    echo "<p>You need to be logged in to use this tool. Please log in and come back.</p>";
    echo "</div>";
    exit;
}

// Display user info
echo "<div class='card'>";
echo "<h2>User Information</h2>";
echo "<p>Logged in as User ID: {$_SESSION['user_id']}</p>";
echo "</div>";

// Check database connection
echo "<div class='card'>";
echo "<h2>Database Connection</h2>";
if ($pdo) {
    echo "<p class='success'>Database connection successful!</p>";
} else {
    echo "<p class='error'>Database connection failed!</p>";
}
echo "</div>";

// List a few comments from the database to choose from
echo "<div class='card'>";
echo "<h2>Available Comments</h2>";
try {
    $stmt = $pdo->query("SELECT c.comment_id, c.content, c.post_id, c.user_id, u.username, p.post_id as verified_post_id 
                         FROM comments c 
                         JOIN users u ON c.user_id = u.user_id
                         LEFT JOIN posts p ON c.post_id = p.post_id
                         ORDER BY c.comment_id DESC LIMIT 10");
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($comments) > 0) {
        echo "<table border='1' cellpadding='5' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr><th>Comment ID</th><th>Content</th><th>Post ID</th><th>Post Exists</th><th>User</th><th>Action</th></tr>";
        
        foreach ($comments as $comment) {
            $postExists = $comment['verified_post_id'] ? 'Yes' : 'No';
            $canReport = ($comment['user_id'] != $_SESSION['user_id']) ? true : false;
            
            echo "<tr>";
            echo "<td>{$comment['comment_id']}</td>";
            echo "<td>" . htmlspecialchars(substr($comment['content'], 0, 50)) . "...</td>";
            echo "<td>{$comment['post_id']}</td>";
            echo "<td>" . ($postExists == 'Yes' ? '<span style="color:green">Yes</span>' : '<span style="color:red">No</span>') . "</td>";
            echo "<td>{$comment['username']} (ID: {$comment['user_id']})</td>";
            echo "<td>";
            if ($canReport) {
                echo "<form method='post' action='utils/report_comment.php'>";
                echo "<input type='hidden' name='comment_id' value='{$comment['comment_id']}'>";
                echo "<input type='hidden' name='description' value='Test report from debugging tool'>";
                echo "<button type='submit' class='btn'>Report Comment</button>";
                echo "</form>";
            } else {
                echo "<em>Cannot report your own comment</em>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No comments found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error querying comments: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Direct test form
echo "<div class='card'>";
echo "<h2>Test Comment Report Directly</h2>";
echo "<form method='post' action='utils/report_comment.php'>";
echo "<label>Comment ID:</label>";
echo "<input type='number' name='comment_id' value='134'>";
echo "<label>Description:</label>";
echo "<textarea name='description'>Test report from debugging page</textarea>";
echo "<button type='submit' class='btn'>Submit Report</button>";
echo "</form>";
echo "</div>";

// Show tables structure
echo "<div class='card'>";
echo "<h2>Database Tables</h2>";

// Check comments table
try {
    echo "<h3>Comments Table</h3>";
    $result = $pdo->query("SHOW TABLES LIKE 'comments'");
    if ($result->rowCount() > 0) {
        $columns = $pdo->query("DESCRIBE comments")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Count comments
        $count = $pdo->query("SELECT COUNT(*) as count FROM comments")->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total comments in database: {$count['count']}</p>";
    } else {
        echo "<p class='error'>Comments table does not exist!</p>";
    }
    
    echo "<h3>Comment Reports Table</h3>";
    $result = $pdo->query("SHOW TABLES LIKE 'comment_reports'");
    if ($result->rowCount() > 0) {
        $columns = $pdo->query("DESCRIBE comment_reports")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Count reports
        $count = $pdo->query("SELECT COUNT(*) as count FROM comment_reports")->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total comment reports in database: {$count['count']}</p>";
    } else {
        echo "<p class='error'>Comment reports table does not exist!</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>Error checking database structure: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Direct check of specific comment
if (isset($_GET['check_comment_id'])) {
    $check_id = (int)$_GET['check_comment_id'];
    echo "<div class='card'>";
    echo "<h2>Checking Comment ID: $check_id</h2>";
    
    try {
        // Check if comment exists
        $stmt = $pdo->prepare("SELECT * FROM comments WHERE comment_id = ?");
        $stmt->execute([$check_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comment) {
            echo "<p class='success'>Comment exists!</p>";
            echo "<pre>";
            print_r($comment);
            echo "</pre>";
            
            // Check if post exists
            $post_id = (int)$comment['post_id'];
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($post) {
                echo "<p class='success'>Associated post exists!</p>";
                echo "<pre>";
                print_r($post);
                echo "</pre>";
            } else {
                echo "<p class='error'>Associated post DOES NOT exist!</p>";
            }
        } else {
            echo "<p class='error'>Comment DOES NOT exist!</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Error checking comment: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

echo "<div class='card'>";
echo "<h2>Check Specific Comment</h2>";
echo "<form method='get'>";
echo "<label>Comment ID to check:</label>";
echo "<input type='number' name='check_comment_id' value='134'>";
echo "<button type='submit' class='btn'>Check Comment</button>";
echo "</form>";
echo "</div>";

echo "</body></html>";
?>
