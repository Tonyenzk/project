<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/database.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login
    header('Location: login.php');
    exit();
}

// Check if the user is banned
try {
    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If user is not banned or the user is an admin/moderator, redirect to dashboard
    if (!$user || !$user['is_banned']) {
        header('Location: dashboard.php');
        exit();
    }
    
    // Check if user has admin privileges (they should never be banned, but just in case)
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userRole && ($userRole['role'] === 'Admin' || $userRole['role'] === 'Master_Admin' || $userRole['role'] === 'Moderator')) {
        // Admins and moderators should never be banned, redirect to dashboard
        header('Location: dashboard.php');
        exit();
    }
    
} catch (PDOException $e) {
    // Database error, redirect to login for safety
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended - Social Land</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #f9fafb;
            padding: 1rem;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }
        
        .dark body {
            background-color: #121212;
            color: #e5e5e5;
        }
        
        .banned-container {
            max-width: 650px;
            width: 100%;
            text-align: center;
            padding: 2.5rem;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            animation: slideIn 0.5s ease-in-out;
        }
        
        .dark .banned-container {
            background-color: #1e1e1e;
            border: 1px solid #333;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .ban-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem auto;
            color: #ef4444;
        }
        
        h1 {
            color: #ef4444;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .dark h1 {
            color: #f87171;
        }
        
        p {
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .dark p {
            color: #9ca3af;
        }
        
        .ban-details {
            background-color: #f3f4f6;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .dark .ban-details {
            background-color: #262626;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .dark .detail-item {
            border-bottom: 1px solid #374151;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4b5563;
        }
        
        .dark .detail-label {
            color: #d1d5db;
        }
        
        .detail-value {
            color: #6b7280;
        }
        
        .dark .detail-value {
            color: #9ca3af;
        }
        
        .permanent-ban {
            color: #ef4444;
            font-weight: 600;
        }
        
        .dark .permanent-ban {
            color: #f87171;
        }
        
        .temporary-ban {
            color: #f59e0b;
            font-weight: 600;
        }
        
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background-color 0.2s;
            border: none;
            cursor: pointer;
            margin: 0.5rem;
        }
        
        .button:hover {
            background-color: #1d4ed8;
        }
        
        .dark .button {
            background-color: #3b82f6;
        }
        
        .dark .button:hover {
            background-color: #2563eb;
        }
        
        .button-outline {
            background-color: transparent;
            color: #2563eb;
            border: 1px solid #2563eb;
        }
        
        .button-outline:hover {
            background-color: rgba(37, 99, 235, 0.1);
        }
        
        .dark .button-outline {
            color: #3b82f6;
            border: 1px solid #3b82f6;
        }
        
        .dark .button-outline:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .footer-text {
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .dark .footer-text {
            color: #9ca3af;
        }
        
        .contact-link {
            color: #2563eb;
            text-decoration: none;
        }
        
        .contact-link:hover {
            text-decoration: underline;
        }
        
        .dark .contact-link {
            color: #3b82f6;
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark' : ''; ?>">
    <div class="banned-container">
        <div class="ban-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
            </svg>
        </div>
        <h1>Cont Suspendat</h1>
        <p class="romanian-message">Contul dumneavoastră a fost suspendat pentru încălcarea regulilor comunității noastre.</p>
    </div>
    
    <script>
        // Check for dark mode preference
        const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDarkMode && !document.cookie.includes('darkMode')) {
            document.body.classList.add('dark');
        }
    </script>
</body>
</html>