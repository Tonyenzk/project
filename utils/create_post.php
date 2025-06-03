<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $is_update = isset($_POST['post_id']) && !empty($_POST['post_id']);
        $post_id = $is_update ? (int)$_POST['post_id'] : null;

        if ($is_update) {
            // Check if the post belongs to the current user
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();

            if (!$post) {
                throw new Exception('Post not found');
            }

            if ($post['user_id'] != $_SESSION['user_id']) {
                throw new Exception('You do not have permission to edit this post');
            }
        }

        // Start a transaction to ensure data integrity
        $pdo->beginTransaction();

        // Get post details from form
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        $deactivated_comments = isset($_POST['deactivated_comments']) ? (int)$_POST['deactivated_comments'] : 0;

        if ($is_update) {
            // Update existing post
            $stmt = $pdo->prepare("UPDATE posts SET caption = ?, location = ?, deactivated_comments = ? WHERE post_id = ? AND user_id = ?");
            $result = $stmt->execute([$description, $location, $deactivated_comments, $post_id, $_SESSION['user_id']]);

            if (!$result) {
                throw new Exception('Failed to update post in database');
            }

            // Delete old tags first when updating a post
            // This ensures that any tags removed by the user are properly deleted
            $deleteStmt = $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?");
            $deleteStmt->execute([$post_id]);
        } else {
            // Handle new post creation
            // Validate file upload for new posts
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No image uploaded or upload error');
            }

            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Invalid file type. Only JPEG, PNG, and WebP are allowed.');
            }

            if ($file['size'] > $max_size) {
                throw new Exception('File too large. Maximum size is 5MB.');
            }

            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/posts';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('post_') . '.' . $extension;
            $upload_path = $upload_dir . '/' . $filename;
            $db_image_url = 'uploads/posts/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception('Failed to save uploaded file.');
            }

            // Insert new post
            $stmt = $pdo->prepare("
                INSERT INTO posts (user_id, image_url, caption, location, deactivated_comments, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $_SESSION['user_id'],
                $db_image_url,
                $description,
                $location,
                $deactivated_comments
            ]);

            $post_id = $pdo->lastInsertId();
        }

        // Handle tagged users
        // Extract usernames from tags text field
        $tagged_usernames = [];
        if (isset($_POST['tags']) && !empty($_POST['tags'])) {
            // Get the usernames from the tags string (format: @username1 @username2 ...)
            preg_match_all('/@(\w+)/', $_POST['tags'], $matches);
            $tagged_usernames = $matches[1];
        }
        
        // Handle the array format that PHP receives from FormData's append('tagged_usernames[]', value)
        $tagged_array_keys = preg_grep('/^tagged_usernames/', array_keys($_POST));
        
        if (!empty($tagged_array_keys)) {
            foreach ($tagged_array_keys as $key) {
                if (isset($_POST[$key]) && !empty($_POST[$key])) {
                    $username = $_POST[$key];
                    $tagged_usernames[] = $username;
                }
            }
        }
        
        // Remove duplicates
        $tagged_usernames = array_unique($tagged_usernames);
        
        if (!empty($tagged_usernames)) {
            try {
                // Get user IDs for the usernames
                $placeholders = str_repeat('?,', count($tagged_usernames) - 1) . '?';
                
                $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE username IN ($placeholders)");
                $stmt->execute($tagged_usernames);
                $tagged_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($post_id) && is_numeric($post_id)) {
                    // First, check if the post exists
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE post_id = ?");
                    $check_stmt->execute([$post_id]);
                    $post_exists = (int)$check_stmt->fetchColumn() > 0;
                    
                    if ($post_exists) {
                        // Insert tags into post_tags table
                        $insert_stmt = $pdo->prepare("INSERT INTO post_tags (post_id, tagged_user_id) VALUES (?, ?)");
                        
                        foreach ($tagged_users as $user) {
                            $insert_stmt->execute([$post_id, $user['user_id']]);
                        }
                    }
                }
            } catch (PDOException $e) {
                // Handle any database errors silently
            }
        }

        // Return success response
        header('Content-Type: application/json');
        // Commit the transaction to save everything at once
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => $is_update ? 'Post updated successfully' : 'Post created successfully',
            'post_id' => $post_id
        ]);
    } catch (PDOException $e) {
        // Roll back the transaction on database error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // If there was an error, delete the uploaded file if it exists
        if (!$is_update && isset($upload_path) && file_exists($upload_path)) {
            unlink($upload_path);
        }
        
        echo json_encode(['success' => false, 'error' => 'Database error']);
    } catch (Exception $e) {
        // Roll back the transaction on any other error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // If there was an error, delete the uploaded file if it exists
        if (!$is_update && isset($upload_path) && file_exists($upload_path)) {
            unlink($upload_path);
        }
        
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}