<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data for avatar and username
try {
    $stmt = $pdo->prepare("SELECT username, profile_picture FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $username = $user['username'] ?? 'User';
    $profile_picture = $user['profile_picture'] ?: 'images/profile_placeholder.webp';

    // Fetch likes count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $likes_count = $stmt->fetchColumn();

    // Fetch comments count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $comments_count = $stmt->fetchColumn();

    // Fetch saved posts count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM saved_posts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $saved_count = $stmt->fetchColumn();

    // Fetch recent likes with post and user details
    $stmt = $pdo->prepare("
        SELECT 
            l.created_at,
            p.post_id,
            u.username,
            u.profile_picture,
            u.verifybadge,
            p.image_url
        FROM likes l
        JOIN posts p ON l.post_id = p.post_id
        JOIN users u ON p.user_id = u.user_id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_likes = $stmt->fetchAll();

    // Fetch recent comments with post and user details
    $stmt = $pdo->prepare("
        SELECT 
            c.created_at,
            c.content,
            p.post_id,
            p.image_url,
            u.username,
            u.profile_picture,
            u.verifybadge
        FROM comments c
        JOIN posts p ON c.post_id = p.post_id
        JOIN users u ON p.user_id = u.user_id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_comments = $stmt->fetchAll();

    // Fetch saved posts with details
    $stmt = $pdo->prepare("
        SELECT 
            sp.created_at,
            p.post_id,
            p.image_url,
            u.username,
            u.profile_picture,
            u.verifybadge
        FROM saved_posts sp
        JOIN posts p ON sp.post_id = p.post_id
        JOIN users u ON p.user_id = u.user_id
        WHERE sp.user_id = ?
        ORDER BY sp.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $saved_posts = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $username = 'User';
    $profile_picture = 'images/profile_placeholder.webp';
    $likes_count = 0;
    $comments_count = 0;
    $saved_count = 0;
    $recent_likes = [];
    $recent_comments = [];
    $saved_posts = [];
}

// Function to format time difference
function getTimeAgo($timestamp) {
    $time_diff = time() - strtotime($timestamp);
    
    if ($time_diff < 60) {
        return 'Just now';
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return $minutes . 'm ago';
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . 'h ago';
    } elseif ($time_diff < 604800) {
        $days = floor($time_diff / 86400);
        return $days . 'd ago';
    } elseif ($time_diff < 2592000) {
        $weeks = floor($time_diff / 604800);
        return $weeks . 'w ago';
    } elseif ($time_diff < 31536000) {
        $months = floor($time_diff / 2592000);
        return $months . 'mo ago';
    } else {
        $years = floor($time_diff / 31536000);
        return $years . 'y ago';
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activitatea Ta</title>
    <?php include 'includes/header.php'; ?>
    <link rel="stylesheet" href="./css/style.css">
    <script src="/js/global-init.js"></script>
</head>
<body class="bg-white dark:bg-black min-h-screen">
<?php include 'navbar.php'; ?>
<main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
    <div class="max-w-2xl mx-auto py-10 px-4">
        <h1 class="text-3xl font-bold text-center mb-3 text-neutral-900 dark:text-white">Activitatea Ta</h1>
        <p class="text-center text-neutral-600 dark:text-neutral-400 mb-10 text-base">Urmărește interacțiunile tale cu postările și conținutul</p>
        <!-- Tabs -->
        <div class="flex justify-center mb-8">
            <div class="flex w-full max-w-xl rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 shadow-sm">
                <button id="tab-likes" class="flex-1 py-3 px-4 text-sm font-medium flex items-center justify-center gap-2 rounded-l-xl focus:outline-none transition-all duration-200 tab-active hover:bg-neutral-100 dark:hover:bg-neutral-800" onclick="showTab('likes')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    Aprecieri <span class="ml-1 text-xs text-neutral-500">(<?php echo $likes_count; ?>)</span>
                </button>
                <button id="tab-comments" class="flex-1 py-3 px-4 text-sm font-medium flex items-center justify-center gap-2 focus:outline-none transition-all duration-200 bg-white dark:bg-neutral-900 text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 border-l border-neutral-200 dark:border-neutral-800" onclick="showTab('comments')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
                    Comentarii <span class="ml-1 text-xs text-neutral-500">(<?php echo $comments_count; ?>)</span>
                </button>
                <button id="tab-saved" class="flex-1 py-3 px-4 text-sm font-medium flex items-center justify-center gap-2 rounded-r-xl focus:outline-none transition-all duration-200 bg-white dark:bg-neutral-900 text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 border-l border-neutral-200 dark:border-neutral-800" onclick="showTab('saved')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    Salvate <span class="ml-1 text-xs text-neutral-500">(<?php echo $saved_count; ?>)</span>
                </button>
            </div>
        </div>
        <!-- Tab Contents -->
        <div id="tab-content-likes">
            <div class="bg-white dark:bg-neutral-900 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 border border-neutral-200 dark:border-neutral-800">
                <?php if (empty($recent_likes)): ?>
                <div class="flex flex-col items-center justify-center min-h-[200px] p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="text-neutral-400 dark:text-neutral-600 mb-4">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                    </svg>
                    <p class="text-neutral-500 dark:text-neutral-400 text-center">Nu ai aprecieri încă.</p>
                </div>
                <?php else: ?>
                <ul class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    <?php foreach ($recent_likes as $like): ?>
                    <li class="flex items-center gap-4 p-6 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors duration-200">
                        <img src="<?php echo htmlspecialchars($like['profile_picture'] ?: 'images/profile_placeholder.webp'); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-neutral-100 dark:border-neutral-800" alt="<?php echo htmlspecialchars($like['username']); ?>">
                        <div>
                            <div class="flex items-center gap-1">
                                <span class="font-semibold text-neutral-900 dark:text-white"><?php echo htmlspecialchars($like['username']); ?></span>
                                <?php if ($like['verifybadge'] === 'true'): ?>
                                <div class="relative inline-block h-3.5 w-3.5">
                                    <svg aria-label="Verificat" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                        <title>Verificat</title>
                                        <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="text-neutral-600 dark:text-neutral-400 text-sm">Ai apreciat această postare</span>
                            <div class="text-xs text-neutral-500 dark:text-neutral-500 mt-1"><?php echo getTimeAgo($like['created_at']); ?></div>
                        </div>
                        <div class="ml-auto">
                            <img src="<?php echo htmlspecialchars($like['image_url']); ?>" class="w-12 h-12 rounded object-cover" alt="Miniatură postare">
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <div id="tab-content-comments" class="hidden">
            <div class="bg-white dark:bg-neutral-900 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 border border-neutral-200 dark:border-neutral-800">
                <?php if (empty($recent_comments)): ?>
                <div class="flex flex-col items-center justify-center min-h-[200px] p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="text-neutral-400 dark:text-neutral-600 mb-4">
                        <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/>
                    </svg>
                    <p class="text-neutral-500 dark:text-neutral-400 text-center">Nu ai comentarii încă.</p>
                </div>
                <?php else: ?>
                <ul class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    <?php foreach ($recent_comments as $comment): ?>
                    <li class="flex items-start gap-4 p-6 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors duration-200">
                        <img src="<?php echo htmlspecialchars($comment['profile_picture'] ?: 'images/profile_placeholder.webp'); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-neutral-100 dark:border-neutral-800" alt="<?php echo htmlspecialchars($comment['username']); ?>">
                        <div>
                            <div class="flex items-center gap-1">
                                <span class="font-semibold text-neutral-900 dark:text-white"><?php echo htmlspecialchars($comment['username']); ?></span>
                                <?php if ($comment['verifybadge'] === 'true'): ?>
                                <div class="relative inline-block h-3.5 w-3.5">
                                    <svg aria-label="Verificat" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                        <title>Verificat</title>
                                        <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="text-neutral-600 dark:text-neutral-400 text-sm">Ai comentat: <span class="italic">"<?php echo htmlspecialchars($comment['content']); ?>"</span></span>
                            <div class="text-xs text-neutral-500 dark:text-neutral-500 mt-1"><?php echo getTimeAgo($comment['created_at']); ?></div>
                        </div>
                        <div class="ml-auto">
                            <img src="<?php echo htmlspecialchars($comment['image_url']); ?>" class="w-12 h-12 rounded object-cover" alt="Miniatură postare">
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <div id="tab-content-saved" class="hidden">
            <div class="bg-white dark:bg-neutral-900 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 border border-neutral-200 dark:border-neutral-800">
                <?php if (empty($saved_posts)): ?>
                <div class="flex flex-col items-center justify-center min-h-[200px] p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="text-neutral-400 dark:text-neutral-600 mb-4">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                    </svg>
                    <p class="text-neutral-500 dark:text-neutral-400 text-center">Nu ai postări salvate încă.</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-3 gap-1 p-1">
                    <?php foreach ($saved_posts as $saved): ?>
                    <div class="relative aspect-square">
                        <img src="<?php echo htmlspecialchars($saved['image_url']); ?>" class="w-full h-full object-cover" alt="Postare salvată">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<script>
function showTab(tab) {
    // Remove active from all tabs
    document.querySelectorAll('[id^="tab-"]').forEach(btn => btn.classList.remove('tab-active', 'bg-white', 'dark:bg-neutral-900', 'text-black', 'dark:text-white'));
    // Hide all tab contents
    document.querySelectorAll('[id^="tab-content-"]').forEach(div => div.classList.add('hidden'));
    // Show selected
    document.getElementById('tab-' + tab).classList.add('tab-active', 'bg-white', 'dark:bg-neutral-900', 'text-black', 'dark:text-white');
    document.getElementById('tab-content-' + tab).classList.remove('hidden');
}
// Set initial active tab
showTab('likes');
</script>
<style>
.tab-active {
    border-bottom: 2px solid #3b82f6;
    color: #3b82f6 !important;
    background: #fff !important;
    z-index: 1;
}
.dark .tab-active {
    background: #18181b !important;
    color: #60a5fa !important;
    border-bottom: 2px solid #60a5fa;
}
</style>
</body>
</html>
