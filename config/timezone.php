<?php
// Set default timezone to Europe/Bucharest
date_default_timezone_set('Europe/Bucharest');

// Set MySQL timezone to match PHP timezone
try {
    global $pdo;
    if (isset($pdo)) {
        // Use SYSTEM timezone setting
        $pdo->exec("SET time_zone = SYSTEM");
    }
} catch (PDOException $e) {
    error_log("Error setting MySQL timezone: " . $e->getMessage());
}
?> 