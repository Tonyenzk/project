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

// Check if user has admin privileges
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !in_array($user['role'], ['Moderator', 'Admin', 'Master_Admin'])) {
        // User doesn't have admin privileges, redirect to dashboard
        header('Location: ../dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    // Database error, redirect to dashboard for safety
    header('Location: ../dashboard.php');
    exit();
}

// User is authenticated and has admin privileges, proceed with the page
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
    <title>Admin Dashboard - Social Land</title>
    <script>
        // Define an empty function to prevent errors
        function updateCommentsStatus() {
            // This is a placeholder to prevent errors
            console.log('Comment status check disabled in admin panel');
        }
    </script>
</head>

<body>
    <?php 
    // Define a flag to let navbar know we're in admin section
    $isAdminSection = true;
    include '../navbar.php';
    ?>
        <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)] snipcss-L119r">
            <div class="flex flex-col min-h-screen bg-white dark:bg-black transition-all duration-300 ease-in-out">
                <div class="flex-1 container max-w-7xl mx-auto px-4 py-8 lg:px-8">
                <div class="border-b border-neutral-200 dark:border-neutral-800 bg-white dark:bg-black rounded-lg p-6 mb-8 shadow-sm">
                        <div class="flex justify-between items-center mb-8">
                            <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Panou de administrare</h1>
                        </div>
                        <?php include 'tabs.php'; ?>
                    </div>

                    <!-- Dashboard Content -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-6">
                        <!-- User Stats Card -->
                        <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Total Utilizatori</h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <?php
                        // Count total users
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
                            $stmt->execute();
                            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                        } catch (PDOException $e) {
                            $totalUsers = 'N/A';
                        }
                        ?>
                                    <div class="text-3xl font-bold dark:text-white">
                                        <?php echo $totalUsers; ?>
                                    </div>
                                    <p class="text-neutral-500 dark:text-white mt-2">Utilizatori înregistrați</p>
                            </div>
                        </div>

                        <!-- Active Users Card -->
                        <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Utilizatori activi</h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </div>
                                <div class="text-3xl font-bold dark:text-white">0</div>
                                <p class="text-neutral-500 dark:text-white mt-2">0 utilizatori noi astăzi</p>
                            </div>
                        </div>

                        <!-- Content Statistics Card -->
                        <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Statistici conținut</h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-500">
                                        <line x1="18" x2="18" y1="20" y2="10"></line>
                                        <line x1="12" x2="12" y1="20" y2="4"></line>
                                        <line x1="6" x2="6" y1="20" y2="14"></line>
                                    </svg>
                                </div>
                                <div class="text-3xl font-bold dark:text-white">47</div>
                                <p class="text-neutral-500 dark:text-white mt-2">197 comentarii</p>
                            </div>
                        </div>

                        <!-- Moderation Card -->
                        <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Moderare</h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"></path>
                                    </svg>
                                </div>
                                <div class="text-3xl font-bold dark:text-white">0</div>
                                <p class="text-neutral-500 dark:text-white mt-2">Acțiuni efectuate astăzi</p>
                            </div>
                        </div>

                        <!-- Posts Stats Card -->
                        <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Total Postări</h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500">
                                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                                        <line x1="3" x2="21" y1="9" y2="9"></line>
                                        <line x1="9" x2="9" y1="21" y2="9"></line>
                                    </svg>
                                </div>
                                <?php
                        // Count total posts
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts");
                            $stmt->execute();
                            $totalPosts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                        } catch (PDOException $e) {
                            $totalPosts = 'N/A';
                        }
                        ?>
                                    <div class="text-3xl font-bold dark:text-white">
                                        <?php echo $totalPosts; ?>
                                    </div>
                                    <p class="text-neutral-500 dark:text-white mt-2">Postări create</p>
                            </div>
                        </div>

                        <!-- Verified Users Card -->
                        <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Utilizatori Verificați</h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                                        <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                                        <path d="m9 12 2 2 4-4"></path>
                                    </svg>
                                </div>
                                <?php
                        // Count verified users
                        try {
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE verifybadge = 'true'");
                            $stmt->execute();
                            $verifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                        } catch (PDOException $e) {
                            $verifiedUsers = 'N/A';
                        }
                        ?>
                                    <div class="text-3xl font-bold dark:text-white">
                                        <?php echo $verifiedUsers; ?>
                                    </div>
                                    <p class="text-neutral-500 dark:text-white mt-2">Conturi verificate</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md p-6">
                                <h3 class="text-lg font-medium mb-4 text-neutral-900 dark:text-white">Stare Sistem</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <!-- System Status Card -->
                                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md">
                                        <div class="p-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Starea sistemului</h3>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                                                    <rect width="20" height="8" x="2" y="2" rx="2" ry="2"></rect>
                                                    <rect width="20" height="8" x="2" y="14" rx="2" ry="2"></rect>
                                                    <line x1="6" x2="6.01" y1="6" y2="6"></line>
                                                    <line x1="6" x2="6.01" y1="18" y2="18"></line>
                                                </svg>
                                            </div>
                                            <div class="text-3xl font-bold dark:text-white">
                                                <div class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors border-transparent bg-green-500/20 text-green-500 hover:bg-green-500/30">Sănătos</div>
                                            </div>
                                            <p class="text-neutral-500 dark:text-white mt-2">CPU: 21% | Memory: 52%</p>
                                        </div>
                                    </div>

                                    <!-- Response Time Card -->
                                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md">
                                        <div class="p-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Timp de răspuns</h3>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                            </div>
                                            <div class="text-3xl font-bold dark:text-white">66ms</div>
                                            <p class="text-neutral-500 dark:text-white mt-2">Timp mediu de răspuns al serverului</p>
                                        </div>
                                    </div>

                                    <!-- Uptime Card -->
                                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md">
                                        <div class="p-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Timp de funcționare</h3>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                                </svg>
                                            </div>
                                            <div class="text-3xl font-bold dark:text-white">7d 12h 30m</div>
                                            <p class="text-neutral-500 dark:text-white mt-2">Ultima repornire a sistemului</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    <div class="rounded-lg border bg-white dark:bg-black text-card-foreground shadow-sm">

                        <!-- System Metrics Cards (Moved) -->
                        <div class="mt-8">
 
                            <div class="flex flex-col space-y-1.5 p-6">
                                <h3 class="text-2xl font-semibold leading-none tracking-tight">Activitate zilnică</h3>
                            </div>
                            <div class="p-6 pt-0">
                                <div class="h-[300px]">
                                    <div class="recharts-responsive-container style-PcazQ" id="style-PcazQ">
                                        <div class="recharts-wrapper style-g488v" id="style-g488v">
                                            <svg class="recharts-surface" width="1102" height="300" viewBox="0 0 1102 300" style="width: 100%; height: 100%;">
                                                <title></title>
                                                <desc></desc>
                                                <defs>
                                                    <clipPath id="recharts1-clip">
                                                        <rect x="65" y="5" height="260" width="1032"></rect>
                                                    </clipPath>
                                                </defs>
                                                <g class="recharts-cartesian-grid">
                                                    <g class="recharts-cartesian-grid-horizontal">
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="65" y1="265" x2="1097" y2="265"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="65" y1="200" x2="1097" y2="200"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="65" y1="135" x2="1097" y2="135"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="65" y1="70" x2="1097" y2="70"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="65" y1="5" x2="1097" y2="5"></line>
                                                    </g>
                                                    <g class="recharts-cartesian-grid-vertical">
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="65" y1="5" x2="65" y2="265"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="237" y1="5" x2="237" y2="265"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="409" y1="5" x2="409" y2="265"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="581" y1="5" x2="581" y2="265"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="753" y1="5" x2="753" y2="265"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="925" y1="5" x2="925" y2="265"></line>
                                                        <line stroke-dasharray="3 3" stroke="#ccc" fill="none" x="65" y="5" width="1032" height="260" x1="1097" y1="5" x2="1097" y2="265"></line>
                                                    </g>
                                                </g>
                                                <g class="recharts-layer recharts-cartesian-axis recharts-xAxis xAxis">
                                                    <line orientation="bottom" width="1032" height="30" x="65" y="265" class="recharts-cartesian-axis-line" stroke="#666" fill="none" x1="65" y1="265" x2="1097" y2="265"></line>
                                                    <g class="recharts-cartesian-axis-ticks">
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="bottom" width="1032" height="30" x="65" y="265" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="65" y1="271" x2="65" y2="265"></line>
                                                            <text orientation="bottom" width="1032" height="30" stroke="none" x="65" y="273" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="middle" fill="#666">
                                                                <tspan x="65" dy="0.71em">2025-05-24</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="bottom" width="1032" height="30" x="65" y="265" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="237" y1="271" x2="237" y2="265"></line>
                                                            <text orientation="bottom" width="1032" height="30" stroke="none" x="237" y="273" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="middle" fill="#666">
                                                                <tspan x="237" dy="0.71em">2025-05-25</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="bottom" width="1032" height="30" x="65" y="265" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="409" y1="271" x2="409" y2="265"></line>
                                                            <text orientation="bottom" width="1032" height="30" stroke="none" x="409" y="273" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="middle" fill="#666">
                                                                <tspan x="409" dy="0.71em">2025-05-26</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="bottom" width="1032" height="30" x="65" y="265" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="581" y1="271" x2="581" y2="265"></line>
                                                            <text orientation="bottom" width="1032" height="30" stroke="none" x="581" y="273" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="middle" fill="#666">
                                                                <tspan x="581" dy="0.71em">2025-05-27</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="bottom" width="1032" height="30" x="65" y="265" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="753" y1="271" x2="753" y2="265"></line>
                                                            <text orientation="bottom" width="1032" height="30" stroke="none" x="753" y="273" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="middle" fill="#666">
                                                                <tspan x="753" dy="0.71em">2025-05-28</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="bottom" width="1032" height="30" x="65" y="265" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="925" y1="271" x2="925" y2="265"></line>
                                                            <text orientation="bottom" width="1032" height="30" stroke="none" x="925" y="273" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="middle" fill="#666">
                                                                <tspan x="925" dy="0.71em">2025-05-29</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="bottom" width="1032" height="30" x="65" y="265" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="1097" y1="271" x2="1097" y2="265"></line>
                                                            <text orientation="bottom" width="1032" height="30" stroke="none" x="1055.3984375" y="273" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="middle" fill="#666">
                                                                <tspan x="1055.3984375" dy="0.71em">2025-05-30</tspan>
                                                            </text>
                                                        </g>
                                                    </g>
                                                </g>
                                                <g class="recharts-layer recharts-cartesian-axis recharts-yAxis yAxis">
                                                    <line orientation="left" width="60" height="260" x="5" y="5" class="recharts-cartesian-axis-line" stroke="#666" fill="none" x1="65" y1="5" x2="65" y2="265"></line>
                                                    <g class="recharts-cartesian-axis-ticks">
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="left" width="60" height="260" x="5" y="5" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="59" y1="265" x2="65" y2="265"></line>
                                                            <text orientation="left" width="60" height="260" stroke="none" x="57" y="265" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="end" fill="#666">
                                                                <tspan x="57" dy="0.355em">0</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="left" width="60" height="260" x="5" y="5" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="59" y1="200" x2="65" y2="200"></line>
                                                            <text orientation="left" width="60" height="260" stroke="none" x="57" y="200" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="end" fill="#666">
                                                                <tspan x="57" dy="0.355em">0.5</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="left" width="60" height="260" x="5" y="5" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="59" y1="135" x2="65" y2="135"></line>
                                                            <text orientation="left" width="60" height="260" stroke="none" x="57" y="135" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="end" fill="#666">
                                                                <tspan x="57" dy="0.355em">1</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="left" width="60" height="260" x="5" y="5" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="59" y1="70" x2="65" y2="70"></line>
                                                            <text orientation="left" width="60" height="260" stroke="none" x="57" y="70" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="end" fill="#666">
                                                                <tspan x="57" dy="0.355em">1.5</tspan>
                                                            </text>
                                                        </g>
                                                        <g class="recharts-layer recharts-cartesian-axis-tick">
                                                            <line orientation="left" width="60" height="260" x="5" y="5" class="recharts-cartesian-axis-tick-line" stroke="#666" fill="none" x1="59" y1="5" x2="65" y2="5"></line>
                                                            <text orientation="left" width="60" height="260" stroke="none" x="57" y="12" class="recharts-text recharts-cartesian-axis-tick-value" text-anchor="end" fill="#666">
                                                                <tspan x="57" dy="0.355em">2</tspan>
                                                            </text>
                                                        </g>
                                                    </g>
                                                </g>
                                                <g class="recharts-layer recharts-line">
                                                    <path stroke="#8884d8" name="Users" width="1032" height="260" stroke-width="1" fill="none" class="recharts-curve recharts-line-curve" stroke-dasharray="1330.587890625px 0px" d="M65,265C122.333,265,179.667,265,237,265C294.333,265,351.667,265,409,265C466.333,265,523.667,265,581,265C638.333,265,695.667,5,753,5C810.333,5,867.667,265,925,265C982.333,265,1039.667,265,1097,265"></path>
                                                    <g class="recharts-layer"></g>
                                                    <g class="recharts-layer recharts-line-dots">
                                                        <circle r="3" stroke="#8884d8" name="Users" width="1032" height="260" stroke-width="1" fill="#fff" cx="65" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#8884d8" name="Users" width="1032" height="260" stroke-width="1" fill="#fff" cx="237" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#8884d8" name="Users" width="1032" height="260" stroke-width="1" fill="#fff" cx="409" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#8884d8" name="Users" width="1032" height="260" stroke-width="1" fill="#fff" cx="581" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#8884d8" name="Users" width="1032" height="260" stroke-width="1" fill="#fff" cx="753" cy="5" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#8884d8" name="Users" width="1032" height="260" stroke-width="1" fill="#fff" cx="925" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#8884d8" name="Users" width="1032" height="260" stroke-width="1" fill="#fff" cx="1097" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                    </g>
                                                </g>
                                                <g class="recharts-layer recharts-line">
                                                    <path stroke="#82ca9d" name="Page Views" width="1032" height="260" stroke-width="1" fill="none" class="recharts-curve recharts-line-curve" stroke-dasharray="1130.42333984375px 0px" d="M65,135C122.333,200,179.667,265,237,265C294.333,265,351.667,265,409,265C466.333,265,523.667,265,581,265C638.333,265,695.667,265,753,265C810.333,265,867.667,135,925,135C982.333,135,1039.667,135,1097,135"></path>
                                                    <g class="recharts-layer"></g>
                                                    <g class="recharts-layer recharts-line-dots">
                                                        <circle r="3" stroke="#82ca9d" name="Page Views" width="1032" height="260" stroke-width="1" fill="#fff" cx="65" cy="135" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#82ca9d" name="Page Views" width="1032" height="260" stroke-width="1" fill="#fff" cx="237" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#82ca9d" name="Page Views" width="1032" height="260" stroke-width="1" fill="#fff" cx="409" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#82ca9d" name="Page Views" width="1032" height="260" stroke-width="1" fill="#fff" cx="581" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#82ca9d" name="Page Views" width="1032" height="260" stroke-width="1" fill="#fff" cx="753" cy="265" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#82ca9d" name="Page Views" width="1032" height="260" stroke-width="1" fill="#fff" cx="925" cy="135" class="recharts-dot recharts-line-dot"></circle>
                                                        <circle r="3" stroke="#82ca9d" name="Page Views" width="1032" height="260" stroke-width="1" fill="#fff" cx="1097" cy="135" class="recharts-dot recharts-line-dot"></circle>
                                                    </g>
                                                </g>
                                            </svg>
                                            <div tabindex="-1" class="recharts-tooltip-wrapper recharts-tooltip-wrapper-left recharts-tooltip-wrapper-top style-OY67I" id="style-OY67I">
                                                <div class="recharts-default-tooltip style-hjotT" id="style-hjotT">
                                                    <p class="recharts-tooltip-label style-JAk7h" id="style-JAk7h">2025-05-30</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </main>
</body>

</html>
