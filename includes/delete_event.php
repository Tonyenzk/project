<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Trebuie să fii conectat pentru a șterge un eveniment.';
    header('Location: ../events.php');
    exit;
}

// Check if event_id is provided
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    $_SESSION['error'] = 'ID eveniment invalid.';
    header('Location: ../events.php');
    exit;
}

$event_id = intval($_POST['event_id']);
$user_id = $_SESSION['user_id'];

// Check if the user is the creator of the event
$stmt = $pdo->prepare("SELECT user_id FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['error'] = 'Evenimentul nu a fost găsit.';
    header('Location: ../events.php');
    exit;
}

if ($event['user_id'] != $user_id) {
    $_SESSION['error'] = 'Nu poți șterge un eveniment creat de altcineva.';
    header('Location: ../events.php');
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete event registrations
    $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    // Delete event prizes
    $stmt = $pdo->prepare("DELETE FROM event_prizes WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    // Delete the event
    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success'] = 'Evenimentul a fost șters cu succes.';
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $_SESSION['error'] = 'A apărut o eroare la ștergerea evenimentului. Vă rugăm încercați din nou.';
}

// Redirect back to events page
header('Location: ../events.php');
exit;
