<?php
session_start();
require_once '../config/database.php';

// Function to check if request is AJAX
function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (isset($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

if (!isset($_SESSION['user_id'])) {
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Nu ești autentificat.'
        ]);
        exit;
    } else {
        header('Location: /login.php');
        exit;
    }
}

// Check if user is verified with verifybadge
$stmt = $pdo->prepare("SELECT verifybadge FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['verifybadge'] !== 'true') {
    $error_message = "Doar utilizatorii verificați pot crea evenimente.";
    $_SESSION['error'] = $error_message;
    
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $error_message
        ]);
        exit;
    } else {
        header('Location: /events.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $event_name = trim($_POST['event_name']);
    $event_type = trim($_POST['event_type']);
    $event_organizer = trim($_POST['event_organizer']);
    $event_location = trim($_POST['event_location']);
    $event_date = $_POST['event_date'];
    $event_description = trim($_POST['event_description']);
    $prizes = isset($_POST['prizes']) ? $_POST['prizes'] : [];
    
    // Validate required fields
    if (empty($event_name) || empty($event_type) || empty($event_organizer) || 
        empty($event_location) || empty($event_date) || empty($event_description)) {
        $_SESSION['error'] = "Toate câmpurile principale sunt obligatorii.";
        header('Location: /events.php');
        exit;
    }
    
    // Check if image was uploaded
    if (!isset($_FILES['event_image']) || $_FILES['event_image']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['error'] = "Te rugăm să încarci o imagine pentru eveniment.";
        header('Location: /events.php');
        exit;
    }
    
    // Handle image upload
    $target_dir = "../uploads/events/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Validate file size (max 5MB)
    if ($_FILES["event_image"]["size"] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "Imaginea nu poate depăși 5MB.";
        header('Location: /events.php');
        exit;
    }

    // Validate file type
    $file_extension = strtolower(pathinfo($_FILES["event_image"]["name"], PATHINFO_EXTENSION));
    $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $_SESSION['error'] = "Doar fișierele JPG, JPEG, PNG și GIF sunt permise.";
        header('Location: /events.php');
        exit;
    }
    
    $filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $filename;
    
    if (!move_uploaded_file($_FILES["event_image"]["tmp_name"], $target_file)) {
        $_SESSION['error'] = "A apărut o eroare la încărcarea imaginii.";
        header('Location: /events.php');
        exit;
    }
    
    $image_url = "uploads/events/" . $filename;
    
    // Determine event status based on date
    $event_datetime = new DateTime($event_date);
    $today = new DateTime('today');
    $this_week_end = new DateTime('Sunday this week');
    
    if ($event_datetime->format('Y-m-d') === $today->format('Y-m-d')) {
        $status = 'today';
    } elseif ($event_datetime <= $this_week_end) {
        $status = 'this_week';
    } elseif ($event_datetime > $today) {
        $status = 'upcoming';
    } else {
        $status = 'past';
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert event
        $stmt = $pdo->prepare("INSERT INTO events (user_id, name, type, organizer, location, event_date, description, image_url, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $event_name,
            $event_type,
            $event_organizer,
            $event_location,
            $event_date,
            $event_description,
            $image_url,
            $status
        ]);
        
        $event_id = $pdo->lastInsertId();
        
        // Insert prizes
        foreach ($prizes as $amount) {
            if (!empty($amount)) {
                // Remove any non-numeric characters (for formatted inputs)
                $clean_amount = preg_replace('/[^0-9]/', '', $amount);
                if (is_numeric($clean_amount) && $clean_amount > 0) {
                    $stmt = $pdo->prepare("INSERT INTO event_prizes (event_id, amount) VALUES (?, ?)");
                    $stmt->execute([$event_id, $clean_amount]);
                }
            }
        }
        
        $pdo->commit();
        
        $success_message = "Evenimentul a fost creat cu succes!";
        $_SESSION['success'] = $success_message;
        
        // Check if it's an AJAX request
        if (isAjaxRequest()) {
            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $success_message,
                'event_id' => $event_id
            ]);
            exit;
        } else {
            // Traditional redirect
            header('Location: /events.php');
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "A apărut o eroare: " . $e->getMessage();
        $_SESSION['error'] = $error_message;
        
        // Check if it's an AJAX request
        if (isAjaxRequest()) {
            // Return JSON error
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $error_message
            ]);
            exit;
        } else {
            // Traditional redirect
            header('Location: /events.php');
            exit;
        }
    }
}
?>
