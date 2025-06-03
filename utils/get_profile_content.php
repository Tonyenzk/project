<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Get the user ID from the request
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

try {
    // Check if the current user is following the target user or has an accepted follow request
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM followers 
                    WHERE follower_id = ? AND following_id = ?
                ) THEN 1
                WHEN EXISTS (
                    SELECT 1 FROM follow_requests 
                    WHERE requester_id = ? AND requested_id = ? AND status = 'accepted'
                ) THEN 1
                ELSE 0
            END as can_view
    ");
    $stmt->execute([$_SESSION['user_id'], $user_id, $_SESSION['user_id'], $user_id]);
    $can_view = $stmt->fetchColumn() > 0;

    if (!$can_view) {
        echo json_encode(['success' => false, 'error' => 'Not following this user']);
        exit();
    }

    // Get user's posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll();

    // Get tagged posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
        FROM posts p 
        INNER JOIN post_tags pt ON p.id = pt.post_id 
        WHERE pt.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $tagged = $stmt->fetchAll();

    // Generate HTML for the profile content
    $html = '
    <div>
        <div dir="ltr" data-orientation="horizontal" class="pt-4 md:pt-16">
            <div role="tablist" aria-orientation="horizontal" class="items-center rounded-md text-muted-foreground flex justify-center w-full h-auto p-0 bg-transparent border-t border-b border-neutral-200 dark:border-neutral-800" tabindex="0" data-orientation="horizontal" style="outline:none">
                <button type="button" role="tab" aria-selected="true" aria-controls="radix-«R53qtdjb»-content-posts" data-state="active" id="radix-«R53qtdjb»-trigger-posts" class="inline-flex items-center justify-center whitespace-nowrap py-1.5 text-sm font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex-1 md:flex-none px-4 md:px-12 pt-3 pb-3 rounded-none border-t-2 -mt-[1px] border-transparent transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50 data-[state=active]:border-neutral-700 dark:data-[state=active]:border-neutral-200" tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
                    <div class="flex items-center justify-center md:justify-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-grid3x3 w-4 h-4 md:w-3.5 md:h-3.5">
                            <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                            <path d="M3 9h18"></path>
                            <path d="M3 15h18"></path>
                            <path d="M9 3v18"></path>
                            <path d="M15 3v18"></path>
                        </svg>
                        <span class="text-xs font-semibold tracking-wider uppercase">Postări</span>
                    </div>
                </button>
                <button type="button" role="tab" aria-selected="false" aria-controls="radix-«R53qtdjb»-content-tagged" data-state="inactive" id="radix-«R53qtdjb»-trigger-tagged" class="inline-flex items-center justify-center whitespace-nowrap py-1.5 text-sm font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex-1 md:flex-none px-4 md:px-12 pt-3 pb-3 rounded-none border-t-2 -mt-[1px] border-transparent transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50 data-[state=active]:border-neutral-700 dark:data-[state=active]:border-neutral-200" tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
                    <div class="flex items-center justify-center md:justify-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-contact w-4 h-4 md:w-3.5 md:h-3.5">
                            <path d="M17 18a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2"></path>
                            <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                            <circle cx="12" cy="10" r="2"></circle>
                            <line x1="8" x2="8" y1="2" y2="4"></line>
                            <line x1="16" x2="16" y1="2" y2="4"></line>
                        </svg>
                        <span class="text-xs font-semibold tracking-wider uppercase">Etichetat</span>
                    </div>
                </button>
            </div>
            <div data-state="active" data-orientation="horizontal" role="tabpanel" aria-labelledby="radix-«R53qtdjb»-trigger-posts" id="radix-«R53qtdjb»-content-posts" tabindex="0" class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-0.5" style="animation-duration:0s">
                <div class="grid grid-cols-3 gap-0.5 md:gap-1 lg:gap-2 mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';

    // Add posts
    foreach ($posts as $post) {
        $html .= '
            <div class="relative aspect-square group cursor-pointer" onclick="openPostModal(\'' . $post['id'] . '\')">
                <div class="block w-full h-full">
                    <img alt="Post" decoding="async" data-nimg="fill" class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105" src="' . htmlspecialchars($post['image_url']) . '">
                </div>
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all duration-200 ease-in-out flex items-center justify-center pointer-events-none">
                    <div class="flex items-center gap-8">
                        <div class="flex items-center gap-2 font-bold text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-8 h-8 fill-white text-white">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                            </svg>
                            <p class="text-lg">' . $post['likes_count'] . '</p>
                        </div>
                        <div class="flex items-center gap-2 font-bold text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle w-8 h-8 fill-transparent text-white">
                                <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                            </svg>
                            <p class="text-lg">' . $post['comments_count'] . '</p>
                        </div>
                    </div>
                </div>
            </div>';
    }

    $html .= '
                </div>
            </div>
            <div data-state="inactive" data-orientation="horizontal" role="tabpanel" aria-labelledby="radix-«R53qtdjb»-trigger-tagged" hidden="" id="radix-«R53qtdjb»-content-tagged" tabindex="0" class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-0.5">
                <div class="grid grid-cols-3 gap-0.5 md:gap-1 lg:gap-2 mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';

    // Add tagged posts
    foreach ($tagged as $post) {
        $html .= '
            <div class="relative aspect-square group cursor-pointer" onclick="openPostModal(\'' . $post['id'] . '\')">
                <div class="block w-full h-full">
                    <img alt="Tagged Post" decoding="async" data-nimg="fill" class="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105" src="' . htmlspecialchars($post['image_url']) . '">
                </div>
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all duration-200 ease-in-out flex items-center justify-center pointer-events-none">
                    <div class="flex items-center gap-8">
                        <div class="flex items-center gap-2 font-bold text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-8 h-8 fill-white text-white">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                            </svg>
                            <p class="text-lg">' . $post['likes_count'] . '</p>
                        </div>
                        <div class="flex items-center gap-2 font-bold text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle w-8 h-8 fill-transparent text-white">
                                <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                            </svg>
                            <p class="text-lg">' . $post['comments_count'] . '</p>
                        </div>
                    </div>
                </div>
            </div>';
    }

    $html .= '
                </div>
            </div>
        </div>
    </div>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (PDOException $e) {
    error_log("Error fetching profile content: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} 