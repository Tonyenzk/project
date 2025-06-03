<?php
// Simple script to check the comments table structure
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Check if comments table exists
    $result = $conn->query("SHOW TABLES LIKE 'comments'");
    $tableExists = $result->num_rows > 0;
    
    $response = [
        'success' => true,
        'table_exists' => $tableExists,
        'columns' => []
    ];
    
    if ($tableExists) {
        // Get columns
        $columns = $conn->query("DESCRIBE comments");
        while ($row = $columns->fetch_assoc()) {
            $response['columns'][] = $row;
        }
        
        // Get sample comment
        $sampleQuery = $conn->query("SELECT * FROM comments LIMIT 1");
        if ($sampleQuery->num_rows > 0) {
            $response['sample_comment'] = $sampleQuery->fetch_assoc();
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
