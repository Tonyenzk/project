<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/database.php';

// Check if the site is in maintenance mode
try {
    // Get maintenance mode status from database
    $stmt = $pdo->prepare("SELECT * FROM site_settings WHERE setting_name = 'maintenance_mode'");
    $stmt->execute();
    $maintenanceMode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isMaintenanceMode = isset($maintenanceMode['setting_value']) && filter_var($maintenanceMode['setting_value'], FILTER_VALIDATE_BOOLEAN);
    
    // If maintenance mode is not enabled, redirect to dashboard
    if (!$isMaintenanceMode) {
        header('Location: dashboard.php');
        exit();
    }
    
    // If user is logged in, check if they're an admin/moderator
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Only admins can bypass maintenance mode
        if ($user && in_array($user['role'], ['Admin', 'Master_Admin', 'Moderator'])) {
            header('Location: dashboard.php');
            exit();
        }
    }
    
    // Default maintenance message
    $message = 'Site-ul este în proces de mentenanță. Vă rugăm să reveniți mai târziu.';
    
} catch (PDOException $e) {
    // Database error, use default maintenance message
    $message = 'Site-ul este în proces de mentenanță. Vă rugăm să reveniți mai târziu.';
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentenanță - Social Land</title>
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
        }
        
        .dark body {
            background-color: #121212;
            color: #e5e5e5;
        }
        
        .maintenance-container {
            max-width: 600px;
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .dark .maintenance-container {
            background-color: #1e1e1e;
            border: 1px solid #333;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .maintenance-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem auto;
        }
        
        h1 {
            color: #333;
            font-size: 1.875rem;
            margin-bottom: 1rem;
        }
        
        .dark h1 {
            color: #f3f4f6;
        }
        
        p {
            color: #6b7280;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .dark p {
            color: #9ca3af;
        }
        
        .estimated-time {
            font-weight: 600;
            margin-top: 1rem;
            color: #4b5563;
        }
        
        .dark .estimated-time {
            color: #d1d5db;
        }
        
        .login-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .login-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
        
        .dark .login-link {
            color: #60a5fa;
        }
        
        .dark .login-link:hover {
            color: #93c5fd;
        }
        
        .social-icons {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 1rem;
        }
        
        .social-icons a {
            color: #6b7280;
            transition: color 0.2s;
        }
        
        .social-icons a:hover {
            color: #4b5563;
        }
        
        .dark .social-icons a {
            color: #9ca3af;
        }
        
        .dark .social-icons a:hover {
            color: #d1d5db;
        }
        
        .progress-container {
            width: 100%;
            background-color: #e5e7eb;
            border-radius: 9999px;
            height: 8px;
            margin: 2rem 0;
            overflow: hidden;
        }
        
        .dark .progress-container {
            background-color: #374151;
        }
        
        .progress-bar {
            height: 100%;
            width: 75%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 9999px;
            animation: progress 2s ease-in-out infinite alternate;
        }
        
        @keyframes progress {
            from { width: 30%; }
            to { width: 75%; }
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark' : ''; ?>">
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full text-blue-500">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
        </div>
        <h1>Mentenanță</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
        

    </div>
    
    <script>
        // Check for dark mode preference
        const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDarkMode && !document.cookie.includes('darkMode')) {
            document.cookie = 'darkMode=true; path=/; max-age=31536000';
        }
    </script>
</body>
</html>