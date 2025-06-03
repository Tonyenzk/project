<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../config/database.php';

// Security check - verify user is logged in and has admin privileges
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login
    header('Location: ../login.php');
    exit();
}

// Check if user has Master_Admin privileges
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'Master_Admin') {
        // User doesn't have Master_Admin privileges, redirect to dashboard
        header('Location: ../dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    // Database error, redirect to dashboard for safety
    header('Location: ../dashboard.php');
    exit();
}

// User is authenticated and has Master_Admin privileges, proceed with the page

// Check if maintenance mode toggle was submitted
if (isset($_POST['toggle_maintenance'])) {
    $newStatus = isset($_POST['maintenance_status']) ? 1 : 0;
    
    // Check if the setting already exists and get current status
    $checkStmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'maintenance_mode'");
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $settingExists = true;
    } else {
        $settingExists = false;
    }
    
    try {
        if ($settingExists) {
            // Update existing setting - this will update the updated_at timestamp automatically
            $updateStmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_name = 'maintenance_mode'");
            $updateStmt->execute([$newStatus]);
        } else {
            // Insert new setting
            $insertStmt = $pdo->prepare("INSERT INTO site_settings (setting_name, setting_value) VALUES ('maintenance_mode', ?)");
            $insertStmt->execute([$newStatus]);
        }
        
        $_SESSION['maintenance_message'] = $newStatus ? "Modul de mentenanță a fost activat cu succes." : "Modul de mentenanță a fost dezactivat cu succes.";
    } catch (PDOException $e) {
        $_SESSION['maintenance_message'] = "Eroare la actualizarea modului de mentenanță: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: maintenance.php');
    exit();
}

// Check current maintenance mode status
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'maintenance_mode'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $maintenanceEnabled = $result && filter_var($result['setting_value'], FILTER_VALIDATE_BOOLEAN);
} catch (PDOException $e) {
    $maintenanceEnabled = false;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
    <base href="../" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/toast.css">
    <title>Maintenance - Social Land</title>
</head>

<body>
    <?php 
    // Define a flag to let navbar know we're in admin section
    $isAdminSection = true;
    include '../navbar.php';
    ?>
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="flex flex-col min-h-screen bg-white dark:bg-black transition-all duration-300 ease-in-out">
            <div class="flex-1 container max-w-7xl mx-auto px-4 py-8 lg:px-8">
                <div class="border-b border-neutral-200 dark:border-neutral-800 bg-white dark:bg-black rounded-lg p-6 mb-8 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Maintenance</h1>
                    </div>
                    <?php include 'tabs.php'; ?>
                </div>

                <!-- Maintenance Mode Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Maintenance Mode Control -->
                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold">Maintenance Mode</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-500">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                            </svg>
                        </div>
                        <div class="space-y-4">
                            <?php if (isset($_SESSION['maintenance_message'])): ?>
                            <div class="p-4 mb-4 <?php echo strpos($_SESSION['maintenance_message'], 'Eroare') !== false ? 'bg-red-50 text-red-800 dark:bg-red-900 dark:text-white' : 'bg-green-50 text-green-800 dark:bg-green-900 dark:text-white'; ?> rounded-lg">
                                <?php echo $_SESSION['maintenance_message']; ?>
                                <?php unset($_SESSION['maintenance_message']); ?>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="admin/maintenance.php">
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-black rounded-lg">
                                    <div>
                                        <h4 class="font-medium">Status</h4>
                                        <p class="text-sm text-gray-500 dark:text-white dark:text-white">
                                            Maintenance mode is currently 
                                            <span class="font-semibold <?php echo $maintenanceEnabled ? 'text-red-500 dark:text-red-400' : 'text-green-500 dark:text-green-400'; ?>">
                                                <?php echo $maintenanceEnabled ? 'enabled' : 'disabled'; ?>
                                            </span>
                                        </p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="maintenance_status" class="sr-only peer" id="maintenanceToggle" <?php echo $maintenanceEnabled ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                
                                <input type="hidden" name="toggle_maintenance" value="1">
                                <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">Save Changes</button>
                            </form>
                            <div class="p-4 bg-gray-50 dark:bg-black rounded-lg">
                                <p class="text-sm text-gray-500 dark:text-white dark:text-white mt-1">When maintenance mode is enabled, all regular users will be redirected to the maintenance page. Only administrators, moderators, and Master Admins will have access to the site.</p>
                            </div>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold">System Status</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"></path>
                            </svg>
                        </div>
                        <div class="space-y-4">
                            <?php
                            // Get server status
                            $serverStatus = 'Online';
                            $serverStatusClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-white';
                            
                            // Get database status
                            $dbStatus = 'Connected';
                            $dbStatusClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-white';
                            try {
                                $pdo->query("SELECT 1");
                            } catch (PDOException $e) {
                                $dbStatus = 'Disconnected';
                                $dbStatusClass = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-white';
                            }
                            
                            // Get last maintenance from the updated_at timestamp in site_settings
                            $lastMaintenance = 'Never';
                            try {
                                $stmt = $pdo->query("SELECT updated_at FROM site_settings WHERE setting_name = 'maintenance_mode'");
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($result && $result['updated_at']) {
                                    $lastMaintenance = date('d.m.Y H:i', strtotime($result['updated_at']));
                                }
                            } catch (PDOException $e) {
                                // Error getting last maintenance time
                            }
                            
                            // Get system load
                            if (function_exists('sys_getloadavg')) {
                                $load = sys_getloadavg();
                                $systemLoad = number_format($load[0] * 100 / 4, 1) . '%'; // Assuming 4 cores
                            } else {
                                $cpuUsage = '';
                                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                                    // Windows
                                    $cmd = "wmic cpu get loadpercentage";
                                    @exec($cmd, $output);
                                    if (isset($output[1])) {
                                        $systemLoad = trim($output[1]) . '%';
                                    } else {
                                        $systemLoad = 'N/A';
                                    }
                                } else {
                                    // Linux/Unix
                                    $systemLoad = '0.5%'; // Fallback
                                }
                            }
                            
                            // Get PHP version
                            $phpVersion = phpversion();
                            
                            // Get MySQL version
                            $mysqlVersion = 'Unknown';
                            try {
                                $stmt = $pdo->query("SELECT VERSION() as version");
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($result) {
                                    $mysqlVersion = $result['version'];
                                }
                            } catch (PDOException $e) {
                                // Can't get MySQL version
                            }
                            ?>
                            
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span>Server Status</span>
                                <span class="px-2 py-1 <?php echo $serverStatusClass; ?> rounded-full text-sm"><?php echo $serverStatus; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span>Database Status</span>
                                <span class="px-2 py-1 <?php echo $dbStatusClass; ?> rounded-full text-sm"><?php echo $dbStatus; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span>Last Maintenance</span>
                                <span class="text-sm text-gray-500 dark:text-white"><?php echo $lastMaintenance; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span>System Load</span>
                                <span class="text-sm text-gray-500 dark:text-white"><?php echo $systemLoad; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span>PHP Version</span>
                                <span class="text-sm text-gray-500 dark:text-white"><?php echo $phpVersion; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span>MySQL Version</span>
                                <span class="text-sm text-gray-500 dark:text-white"><?php echo $mysqlVersion; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Maintenance History Section -->
            </div>
        </div>
    </main>

    <!-- No JavaScript needed - form is submitted via the Save Changes button -->
</body>
</html>
