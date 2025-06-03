<?php
header('Content-Type: application/json');

// Log all POST data for debugging
error_log("Debug POST data: " . print_r($_POST, true));
error_log("Debug SERVER data: " . print_r($_SERVER, true));

// Return the POST data as JSON for inspection
echo json_encode([
    'success' => true,
    'data' => $_POST,
    'message' => 'Debug data received'
]);
