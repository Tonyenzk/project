<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$theme = in_array($data['theme'] ?? '', ['dark', 'light']) ? $data['theme'] : 'light';

require_once __DIR__ . '/../config/database.php';
$stmt = $pdo->prepare('UPDATE users SET theme_preference = ? WHERE user_id = ?');
$stmt->execute([$theme, $user_id]);

echo json_encode(['success' => true, 'theme' => $theme]); 