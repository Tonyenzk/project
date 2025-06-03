<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/check_viewed_stories.php';
require_once 'includes/image_helper.php';

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT username, name, profile_picture, verifybadge FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // User no longer exists in database
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    $profile_picture = $user['profile_picture'] ?: 'images/profile_placeholder.webp';
    $username = $user['username'];
    $name = $user['name'];
    $isVerified = $user['verifybadge'] === 'true';

    // Fetch posts from followed users
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            p.post_id,
            p.user_id,
            p.image_url,
            p.caption,
            p.location,
            p.deactivated_comments,
            p.created_at,
            u.username,
            u.name,
            u.profile_picture,
            u.verifybadge,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count,
            EXISTS(SELECT 1 FROM likes WHERE post_id = p.post_id AND user_id = ?) as is_liked,
            EXISTS(SELECT 1 FROM saved_posts WHERE post_id = p.post_id AND user_id = ?) as is_saved,
            TIMESTAMPDIFF(SECOND, p.created_at, NOW()) as time_diff,
            (
                SELECT GROUP_CONCAT(DISTINCT u2.username)
                FROM post_tags pt
                JOIN users u2 ON pt.tagged_user_id = u2.user_id
                WHERE pt.post_id = p.post_id
            ) as tagged_usernames,
            (
                SELECT COUNT(DISTINCT tagged_user_id)
                FROM post_tags
                WHERE post_id = p.post_id
            ) as tagged_count
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE (p.user_id = ? OR EXISTS (
            SELECT 1 FROM followers f 
            WHERE f.following_id = p.user_id 
            AND f.follower_id = ?
        ))
        AND u.is_banned = 0
        AND NOT EXISTS (
            SELECT 1 FROM blocked_users 
            WHERE (blocker_id = ? AND blocked_id = p.user_id)
            OR (blocker_id = p.user_id AND blocked_id = ?)
        )
        ORDER BY p.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format time differences
    foreach ($posts as &$post) {
        // Initialize time_ago if not set
        if (!isset($post['time_diff'])) {
            $post['time_diff'] = 0;
        }
        
        if ($post['time_diff'] < 60) {
            $post['time_ago'] = 'Just now';
        } elseif ($post['time_diff'] < 3600) {
            $post['time_ago'] = floor($post['time_diff'] / 60) . 'm';
        } elseif ($post['time_diff'] < 86400) {
            $post['time_ago'] = floor($post['time_diff'] / 3600) . 'h';
        } elseif ($post['time_diff'] < 604800) {
            $post['time_ago'] = floor($post['time_diff'] / 86400) . 'd';
        } elseif ($post['time_diff'] < 2592000) {
            $post['time_ago'] = floor($post['time_diff'] / 604800) . 'w';
        } elseif ($post['time_diff'] < 31536000) {
            $post['time_ago'] = floor($post['time_diff'] / 2592000) . 'mo';
        } else {
            $post['time_ago'] = floor($post['time_diff'] / 31536000) . 'y';
        }

        // Initialize comment_count if not set
        if (!isset($post['comment_count'])) {
            $post['comment_count'] = 0;
        }

        // Initialize like_count if not set
        if (!isset($post['like_count'])) {
            $post['like_count'] = 0;
        }

        // Initialize is_liked if not set
        if (!isset($post['is_liked'])) {
            $post['is_liked'] = false;
        }

        // Initialize is_saved if not set
        if (!isset($post['is_saved'])) {
            $post['is_saved'] = false;
        }

        // Ensure username is set
        if (!isset($post['username'])) {
            $post['username'] = '';
        }
    }
    unset($post); // Break the reference
} catch (PDOException $e) {
    // If there's a database error, log out the user for security
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch user suggestions (users not followed by current user)
try {
    // Get suggestions excluding private accounts and blocked users
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.name, u.profile_picture, u.verifybadge
        FROM users u
        WHERE u.user_id != ? 
        AND u.isPrivate = 0
        AND u.is_banned = 0
        AND NOT EXISTS (
            SELECT 1 FROM followers 
            WHERE follower_id = ? AND following_id = u.user_id
        )
        AND NOT EXISTS (
            SELECT 1 FROM blocked_users 
            WHERE (blocker_id = ? AND blocked_id = u.user_id)
            OR (blocker_id = u.user_id AND blocked_id = ?)
        )
        ORDER BY RAND()
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $suggestions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $suggestions = [];
}

// Check if user has active stories
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE user_id = ? AND expires_at > NOW()");
    $stmt->execute([$_SESSION['user_id']]);
    $hasStories = $stmt->fetchColumn() > 0;
    
    // Check if current user has viewed all of their own stories
    $currentUserAllStoriesViewed = $hasStories ? hasViewedAllStories($pdo, $_SESSION['user_id'], $_SESSION['user_id']) : false;
} catch (PDOException $e) {
    $hasStories = false;
}

// Fetch stories from followed users
try {
    // Select distinct users who have active stories that the current user follows
    // Exclude the current user and blocked users from this list
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            u.user_id, 
            u.username, 
            u.profile_picture, 
            u.verifybadge,
            -- Check if all stories for this user have been viewed
            CASE WHEN (
                SELECT COUNT(*) FROM stories s1 
                WHERE s1.user_id = u.user_id 
                AND s1.expires_at > NOW() 
                AND NOT EXISTS (
                    SELECT 1 FROM stories_viewed sv 
                    WHERE sv.story_id = s1.story_id 
                    AND sv.user_id = ?
                )
            ) = 0 THEN 1 ELSE 0 END AS all_viewed
        FROM stories s 
        JOIN users u ON s.user_id = u.user_id
        WHERE s.expires_at > NOW()
        AND s.user_id IN (
            SELECT following_id FROM followers WHERE follower_id = ?
        )
        AND s.user_id != ? -- Exclude current user
        AND NOT EXISTS (
            SELECT 1 FROM blocked_users 
            WHERE (blocker_id = ? AND blocked_id = s.user_id)
            OR (blocker_id = s.user_id AND blocked_id = ?)
        )
        GROUP BY u.user_id
        ORDER BY 
            all_viewed ASC, -- Unviewed stories first (all_viewed = 0)
            (SELECT MAX(created_at) FROM stories WHERE user_id = u.user_id) DESC -- Then by most recent
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $followedUsersWithStories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Map the all_viewed database flag to our all_stories_viewed variable for consistency
    foreach ($followedUsersWithStories as &$user) {
        $user['all_stories_viewed'] = ($user['all_viewed'] == 1);
    }
    unset($user); // Break the reference
} catch (PDOException $e) {
    $followedUsersWithStories = [];
}
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <?php include 'includes/header.php'; ?>
      <link rel="stylesheet" href="./css/common.css">
      <script src="utils/likes_modal.js"></script>
      <script src="assets/js/tagging.js"></script>
      <script src="assets/js/post_functions.js"></script>
   </head>
   <body>
   <?php include 'navbar.php'; ?>
   <?php include 'modals/report_modal.php'; ?>
   <?php include 'postview.php'; ?>
   <?php include 'modals/story_modal.php'; ?>
   <?php include 'modals/likes_modal.php'; ?>
<main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
    <div class="flex flex-row max-w-[1200px] mx-auto pt-4 md:pt-8 gap-8 px-4">
        <div class="flex-grow max-w-[630px]">
            <div class="flex flex-col gap-6">
                <div class="bg-white dark:bg-black rounded-lg">
                    <div class="w-full bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 rounded-xl p-4 mb-4">
                        <div class="relative">
                            <div dir="ltr" class="relative overflow-hidden w-full whitespace-nowrap style-VAmJP" id="style-VAmJP">
                                <div data-radix-scroll-area-viewport="" class="h-full w-full rounded-[inherit] style-FdDPt" id="style-FdDPt">
                                    <div id="style-EjXjS" class="style-EjXjS">
                                        <div class="flex space-x-5">
                                            <div class="flex flex-col items-center space-y-1">
                                                <div class="flex flex-col items-center space-y-1">
                                                    <?php if ($hasStories): ?>
                                                    <button class="<?php echo $currentUserAllStoriesViewed ? 'story-ring-gray' : 'story-ring'; ?> story-ring-large relative h-[72px] w-[72px] p-0 border-none overflow-hidden" id="open-story-modal" onclick="openStoryModal(<?php echo $_SESSION['user_id']; ?>)">
                                                        <div class="relative rounded-full p-[2px] <?php echo $currentUserAllStoriesViewed ? '' : 'bg-gradient-to-r from-[#FCAF45] via-[#E1306C] to-[#833AB4]'; ?>">
                                                            <div class="relative rounded-full bg-white dark:bg-black p-[2px]">
                                                                <span class="relative flex shrink-0 overflow-hidden rounded-full h-full w-full">
                                                                    <div class="relative aspect-square h-full w-full rounded-full">
                                                                        <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                                            <img alt="Your profile picture" referrerpolicy="no-referrer" decoding="async" data-nimg="fill" class="object-cover rounded-full h-full w-full" src="<?php echo htmlspecialchars($profile_picture); ?>">
                                                                        </div>
                                                                    </div>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </button>
                                                    <span class="text-xs truncate max-w-[64px] dark:text-white">Your story</span>
                                                    <?php else: ?>
                                                    <div class="relative h-[72px] w-[72px] p-0 border-none overflow-hidden">
                                                        <div class="relative rounded-full p-[2px]">
                                                            <div class="relative rounded-full bg-white dark:bg-black p-[2px]">
                                                                <span class="relative flex shrink-0 overflow-hidden rounded-full h-full w-full">
                                                                    <div class="relative aspect-square h-full w-full rounded-full">
                                                                        <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                                            <img alt="Your profile picture" referrerpolicy="no-referrer" decoding="async" data-nimg="fill" class="object-cover rounded-full h-full w-full" src="<?php echo htmlspecialchars($profile_picture); ?>">
                                                                        </div>
                                                                    </div>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span class="text-xs truncate max-w-[64px] text-neutral-500 dark:text-white">Your story</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($followedUsersWithStories)): ?>
                                            <div data-orientation="vertical" role="none" class="shrink-0 bg-border w-[1px] h-16"></div>
                                            <?php foreach ($followedUsersWithStories as $user): ?>
                                            <div class="flex flex-col items-center space-y-1">
                                                <button class="<?php echo $user['all_stories_viewed'] ? 'story-ring-gray' : 'story-ring'; ?> story-ring-large relative h-[72px] w-[72px] p-0 border-none overflow-hidden" onclick="openStoryModal(<?php echo $user['user_id']; ?>)">
                                                    <div class="relative rounded-full p-[2px] <?php echo $user['all_stories_viewed'] ? '' : 'bg-gradient-to-r from-[#FCAF45] via-[#E1306C] to-[#833AB4]'; ?>">
                                                        <div class="relative rounded-full bg-white dark:bg-black p-[2px]">
                                                            <span class="relative flex shrink-0 overflow-hidden rounded-full h-full w-full">
                                                                <div class="relative aspect-square h-full w-full rounded-full">
                                                                    <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                                        <img alt="<?php echo htmlspecialchars($user['username']); ?>'s profile picture" referrerpolicy="no-referrer" decoding="async" data-nimg="fill" class="object-cover rounded-full h-full w-full" src="<?php echo htmlspecialchars($user['profile_picture'] ?: 'images/profile_placeholder.webp'); ?>">
                                                                    </div>
                                                                </div>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </button>
                                                <a href="user.php?username=<?php echo urlencode($user['username']); ?>" class="text-xs truncate max-w-[64px] text-neutral-900 dark:text-white hover:underline"><?php echo htmlspecialchars($user['username']); ?></a>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <!-- Ensure all posts are displayed with custom style -->
                    <div class="flex flex-col gap-4" id="posts-feed-container" style="min-height: auto; height: auto; overflow: visible !important;">
                        <?php if (empty($posts)): ?>
                            <div class="text-center py-4">
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">Nu există postări momentan</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                            <div class="flex flex-col space-y-2 mb-7 border-b border-gray-200 dark:border-neutral-800 pb-4" 
                                 data-post-id="<?php echo 'post-' . htmlspecialchars($post['post_id']); ?>">
                                <div class="flex items-center justify-between px-3 sm:px-0">
                                    <div class="flex items-center gap-2">
                                        <div class="relative cursor-pointer">
                                            <div class="relative rounded-full overflow-hidden w-[32px] h-[32px]">
                                                <span class="relative flex shrink-0 overflow-hidden rounded-full w-full h-full object-cover">
                                                    <div class="relative aspect-square h-full w-full rounded-full">
                                                        <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                            <img alt="<?php echo htmlspecialchars($post['username']); ?>'s profile picture" 
                                                                 referrerpolicy="no-referrer" 
                                                                 loading="lazy" 
                                                                 decoding="async" 
                                                                 data-nimg="fill" 
                                                                 class="rounded-full w-full h-full object-cover" 
                                                                 src="<?php echo htmlspecialchars($post['profile_picture'] ?: 'images/profile_placeholder.webp'); ?>">
                                                        </div>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-sm flex-grow min-w-0">
                                            <div class="flex items-center gap-1 flex-wrap">
                                                <div class="flex items-center gap-1">
                                                    <a class="text-sm font-semibold hover:underline truncate" href="<?php echo ($post['user_id'] == $_SESSION['user_id']) ? 'profile.php' : 'user.php?username=' . urlencode($post['username']); ?>">
                                                        <?php echo htmlspecialchars($post['username']); ?>
                                                    </a>
                                                    <?php if ($post['verifybadge'] === 'true'): ?>
                                                    <span class="inline-flex flex-shrink-0">
                                                        <div class="relative inline-block h-3.5 w-3.5">
                                                            <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                                                <title>Verified</title>
                                                                <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($post['tagged_count'] > 0): ?>
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-sm text-neutral-500 dark:text-neutral-400">with</span>
                                                        <?php if ($post['tagged_count'] == 1): ?>
                                                            <a class="text-sm font-semibold hover:underline" href="user.php?username=<?php echo urlencode(explode(',', $post['tagged_usernames'])[0]); ?>">
                                                                <?php echo htmlspecialchars(explode(',', $post['tagged_usernames'])[0]); ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <button class="text-sm font-semibold hover:underline" onclick="openPostModal('<?php echo htmlspecialchars($post['post_id']); ?>')">
                                                                <?php echo $post['tagged_count']; ?> others
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="text-sm text-neutral-500 dark:text-neutral-400">•</span>
                                                <span class="font-medium text-neutral-500 dark:text-neutral-400 text-sm">
                                                    <time datetime="<?php echo htmlspecialchars($post['created_at']); ?>" title="<?php echo htmlspecialchars($post['created_at']); ?>">
                                                        <?php echo htmlspecialchars($post['time_ago']); ?>
                                                    </time>
                                                </span>
                                            </div>
                                            <?php if (!empty($post['location'])): ?>
                                            <a class="text-xs text-neutral-500 dark:text-neutral-400 hover:underline block mt-0.5 flex items-center" href="location.php?location=<?php echo urlencode($post['location']); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline-block align-middle mr-1">
                                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                                    <circle cx="12" cy="10" r="3"></circle>
                                                </svg>
                                                <span class="align-middle"><?php echo htmlspecialchars($post['location']); ?></span>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="relative">
                                        <button class="hover:bg-gray-100 dark:hover:bg-neutral-800 p-2 rounded-full text-neutral-900 dark:text-white" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="radix-«r1a»" data-state="closed" onclick="togglePostOptions('<?php echo htmlspecialchars($post['post_id']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-more-horizontal h-5 w-5">
                                                <circle cx="12" cy="12" r="1"></circle>
                                                <circle cx="19" cy="12" r="1"></circle>
                                                <circle cx="5" cy="12" r="1"></circle>
                                            </svg>
                                        </button>
                                        <!-- Post Options Dropdown -->
                                        <div id="post-options-<?php echo htmlspecialchars($post['post_id']); ?>" class="hidden absolute top-full right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-neutral-900 ring-1 ring-black ring-opacity-5 z-50">
                                            <div class="py-1" role="menu" aria-orientation="vertical">
                                                <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                                <button onclick="editPost('<?php echo htmlspecialchars($post['post_id']); ?>')" class="w-full text-left px-4 py-2 text-sm text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800" role="menuitem">
                                                    Edit
                                                </button>
                                                <button onclick="deletePost('<?php echo htmlspecialchars($post['post_id']); ?>')" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-neutral-100 dark:hover:bg-neutral-800" role="menuitem">
                                                    Delete
                                                </button>
                                                <?php else: ?>
                                                <button onclick="reportPost('<?php echo htmlspecialchars($post['post_id']); ?>')" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-neutral-100 dark:hover:bg-neutral-800" role="menuitem">
                                                    Report
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button class="relative block w-full" onclick="openPostModal('<?php echo htmlspecialchars($post['post_id']); ?>')">
                                    <div class="border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-black text-card-foreground shadow-sm relative w-full overflow-hidden rounded-none sm:rounded-md">
                                        <img alt="Post Image" loading="lazy" width="1000" height="1000" decoding="async" data-nimg="1" class="h-auto w-full object-contain" src="<?php echo get_cached_image_url(htmlspecialchars($post['image_url'])); ?>">
                                    </div>
                                </button>
                                <div class="relative flex flex-col w-full gap-y-1 px-3 sm:px-0 mt-2">
                                    <div class="flex items-start w-full gap-x-2">
                                        <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9" type="button" data-like-button-id="like-<?php echo $post['post_id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart h-6 w-6 transition-colors duration-200 <?php echo $post['is_liked'] ? 'text-red-500 fill-red-500' : ''; ?>">
                                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                            </svg>
                                        </button>
                                        <button type="button" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9 comment-button" data-post-id="<?php echo $post['post_id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle h-6 w-6">
                                                <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                                            </svg>
                                        </button>
                                        <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9" type="submit">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send h-6 w-6">
                                                <path d="m22 2-7 20-4-9-9-4Z"></path>
                                                <path d="M22 2 11 13"></path>
                                            </svg>
                                        </button>
                                        <div class="ml-auto">
                                            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9" type="button" data-save-button-id="save-<?php echo $post['post_id']; ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark h-6 w-6 <?php echo $post['is_saved'] ? 'fill-current text-black dark:text-white' : ''; ?>">
                                                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($post['like_count'] > 0): ?>
                                        <div id="modalLikeCount" class="font-normal text-sm text-neutral-500 dark:text-neutral-400" data-like-count-id="like-count-<?php echo $post['post_id']; ?>">
                                            <button onclick="openLikesModal('<?php echo $post['post_id']; ?>', event)" class="font-semibold text-sm text-left hover:underline text-neutral-900 dark:text-neutral-100"><?php echo $post['like_count']; ?> like<?php echo $post['like_count'] === 1 ? '' : 's'; ?></button>
                                        </div>
                                        <?php else: ?>
                                        <div class="font-normal text-sm text-neutral-500 dark:text-neutral-400">Be the first to like this</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-sm px-3 sm:px-0 mt-1">
                                    <div>
                                        <span>
                                            <a class="text-sm font-semibold hover:underline" href="<?php echo ($post['user_id'] == $_SESSION['user_id']) ? 'profile.php' : 'user.php?username=' . urlencode($post['username']); ?>">
                                                <?php echo htmlspecialchars($post['username']); ?>
                                            </a>
                                            <?php if ($post['verifybadge'] === 'true'): ?>
                                            <span class="inline-flex flex-shrink-0">
                                                <div class="relative inline-block h-3.5 w-3.5">
                                                    <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                                        <title>Verified</title>
                                                        <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            </span>
                                            <?php endif; ?>
                                            <span class="text-sm whitespace-pre-line break-all overflow-wrap overflow-hidden text-pretty"><?php echo htmlspecialchars($post['caption']); ?></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="comment-count-area" data-comment-count-id="comment-count-<?php echo $post['post_id']; ?>">
                                <?php if ($post['comment_count'] > 0 && !$post['deactivated_comments']): ?>
                                <a class="text-sm text-neutral-500 dark:text-neutral-400 px-3 sm:px-0 hover:underline mt-1" href="javascript:void(0)" onclick="openPostModal('<?php echo htmlspecialchars($post['post_id']); ?>')">
                                    View all <?php echo $post['comment_count']; ?> comment<?php echo $post['comment_count'] > 1 ? 's' : ''; ?>
                                </a>
                                <?php endif; ?>
                                </div>
                                <div class="mt-1">
                                    <div class="space-y-4">
                                        <form class="relative flex items-center space-x-2 w-full px-3 sm:px-0" data-post-id="<?php echo $post['post_id']; ?>" id="comment-form-<?php echo $post['post_id']; ?>">
                                            <?php if ($post['deactivated_comments']): ?>
                                            <div class="w-full flex justify-center items-center text-sm py-4 text-neutral-500 dark:text-neutral-400">
                                                Comentariile sunt dezactivate
                                            </div>
                                            <?php else: ?>
                                             <button type="button" id="emoji-button-<?php echo $post['post_id']; ?>" aria-label="Add emoji" class="rounded-full p-1.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-smile w-5 h-5 text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200 cursor-pointer transition-colors">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                                    <line x1="9" x2="9.01" y1="9" y2="9"></line>
                                                    <line x1="15" x2="15.01" y1="9" y2="9"></line>
                                                </svg>
                                            </button>
                                            <div class="space-y-2 flex-grow">
                                                <input placeholder="Add a comment..." class="w-full text-sm py-1 px-3 bg-transparent border-none focus:outline-none dark:text-white disabled:opacity-50" type="text" value="" name="body" maxlength="150" onkeypress="return preventSpecialChars(event)" onpaste="return false">
                                            </div>
                                            <button type="submit" class="text-neutral-500 text-sm font-semibold hover:text-neutral-700 dark:hover:text-neutral-300 disabled:cursor-not-allowed disabled:opacity-50 transition-colors">Post</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                             </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="w-[320px] flex-shrink-0">
            <div class="w-full space-y-6">
                <div class="flex items-center justify-between mb-8 bg-white/20 dark:bg-black/20 rounded-xl border border-neutral-200 dark:border-neutral-800 p-4">
                    <a class="flex items-center gap-x-3" href="profile.php">
                        <img alt="<?php echo htmlspecialchars($username); ?>'s profile picture" referrerpolicy="no-referrer" loading="lazy" decoding="async" data-nimg="fill" class="object-cover rounded-full w-10 h-10" src="<?php echo htmlspecialchars($profile_picture); ?>">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-x-1">
                                <span class="font-medium"><?php echo htmlspecialchars($username); ?></span>
                                <?php if ($isVerified): ?>
                                <div class="relative inline-block h-4 w-4">
                                    <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 16px; height: 16px;">
                                        <title>Verified</title>
                                        <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span class="text-neutral-600 dark:text-neutral-400 text-sm"><?php echo htmlspecialchars($name); ?></span>
                        </div>
                    </a>

                </div>
                <div class="bg-white/20 dark:bg-black/20 rounded-xl border border-neutral-200 dark:border-neutral-800 overflow-hidden p-5 transition-all duration-300 ease-in-out hover:bg-white/30 dark:hover:bg-black/30">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex flex-col gap-1">
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">Sugestii pentru tine</span>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400">Persoane care te-ar putea interesa să urmărești</span>
                        </div>
                    </div>
                    <div>
                        <div class="flex flex-col mt-4">
                            <?php if (!empty($suggestions)): ?>
                                <?php foreach ($suggestions as $suggestion): ?>
                                <div class="flex flex-col">
                                    <div class="flex items-center justify-between px-4 py-2">
                                        <a class="flex items-center gap-x-3 flex-1 min-w-0" href="user.php?username=<?php echo htmlspecialchars($suggestion['username']); ?>">
                                            <span class="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8">
                                                <div class="relative aspect-square h-full w-full rounded-full">
                                                    <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                        <img alt="<?php echo htmlspecialchars($suggestion['username']); ?>'s profile picture" referrerpolicy="no-referrer" loading="lazy" decoding="async" data-nimg="fill" class="object-cover rounded-full h-8 w-8" src="<?php echo htmlspecialchars($suggestion['profile_picture'] ?: 'images/profile_placeholder.webp'); ?>">
                                                    </div>
                                                </div>
                                            </span>
                                            <div class="flex flex-col flex-1 min-w-0">
                                                <div class="flex items-center gap-1">
                                                    <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($suggestion['username']); ?></p>
                                                    <?php if ($suggestion['verifybadge'] === 'true'): ?>
                                                    <div class="relative inline-block h-3.5 w-3.5">
                                                        <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                                            <title>Verified</title>
                                                            <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                        </svg>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate"><?php echo htmlspecialchars($suggestion['name']); ?></p>
                                            </div>
                                        </a>
                                        <button onclick="followUser(<?php echo $suggestion['user_id']; ?>)" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm ring-offset-background disabled:pointer-events-none py-2 relative font-semibold transition-all duration-200 px-6 h-10 min-w-[120px] w-[120px] hover:scale-[0.98] active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed bg-blue-500 hover:bg-blue-600 text-white shadow-sm hover:shadow-md border border-blue-600 hover:border-blue-700 ml-2">
                                            <span class="flex items-center justify-center gap-1 w-full text-sm">Follow</span>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">Nu există sugestii momentan</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <footer class="text-xs text-neutral-500"></footer>
            </div>
        </div>
    </div>
</main>

<!-- Post Delete Confirmation Modal -->
<div id="delete-post-confirmation-modal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none; background-color: rgba(0, 0, 0, 0.7);">
    <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-neutral-900 text-left shadow-xl transition-all p-6">
        <div class="flex items-center justify-center mb-4 text-red-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"></path>
                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-center mb-2 text-neutral-900 dark:text-white">Delete Post</h3>
        <p class="text-sm text-neutral-600 dark:text-neutral-400 text-center mb-4">Are you sure you want to delete this post? This action cannot be undone.</p>
        
        <div class="flex justify-center gap-3 mt-6">
            <button type="button" id="cancel-delete-post-btn" class="px-4 py-2 bg-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 dark:bg-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-600 dark:focus:ring-neutral-700">
                Cancel
            </button>
            <button type="button" id="confirm-delete-post-btn" class="px-4 py-2 rounded-lg" style="background-color: red !important; color: white !important; display: inline-block !important;">
                Sterge
            </button>
        </div>
    </div>
</div>

<script>
// Initialize current user ID
window.currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;

function followUser(userId) {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    
    // Disable button and show loading state
    button.disabled = true;
    button.innerHTML = '<span class="flex items-center justify-center gap-1 w-full text-sm">Loading...</span>';
    
    fetch('utils/follow.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the suggestion from the list
            const suggestionElement = button.closest('.flex-col');
            if (suggestionElement) {
                suggestionElement.remove();
            }
            
            // If no suggestions left, show the "no suggestions" message
            const suggestionsContainer = document.querySelector('.flex.flex-col.mt-4');
            if (suggestionsContainer && !suggestionsContainer.querySelector('.flex-col')) {
                suggestionsContainer.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Nu există sugestii momentan</p>
                    </div>
                `;
            }
        } else {
            // Show error message
            alert(data.message || 'A apărut o eroare. Vă rugăm să încercați din nou.');
            // Reset button
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('A apărut o eroare. Vă rugăm să încercați din nou.');
        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

// Add this function to prevent special characters
function preventSpecialChars(event) {
    // Allow: backspace, delete, tab, escape, enter
    if ([8, 9, 13, 27, 46].indexOf(event.keyCode) !== -1 ||
        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        (event.keyCode >= 35 && event.keyCode <= 39) ||
        (event.ctrlKey && [65, 67, 86, 88].indexOf(event.keyCode) !== -1)) {
        return true;
    }
    
    // Allow only letters, numbers, spaces, and basic punctuation
    const regex = /^[a-zA-Z0-9\s.,!?-]*$/;
    if (!regex.test(event.key)) {
        event.preventDefault();
        return false;
    }
    
    return true;
}

// Add input event listeners for all comment forms
document.addEventListener('input', function(event) {
    if (event.target.matches('input[name="body"]')) {
        const form = event.target.closest('form');
        const submitButton = form.querySelector('button[type="submit"]');
        const currentLength = event.target.value.length;
        
        // Enable/disable submit button and update its style
        const hasText = currentLength > 0;
        if (hasText) {
            submitButton.classList.remove('text-neutral-500', 'dark:hover:text-neutral-300');
            submitButton.classList.add('text-sky-500', 'hover:text-sky-700', 'dark:hover:text-sky-400');
        } else {
            submitButton.classList.remove('text-sky-500', 'hover:text-sky-700', 'dark:hover:text-sky-400');
            submitButton.classList.add('text-neutral-500', 'dark:hover:text-neutral-300');
        }
        
        submitButton.disabled = !hasText;
    }
});

// Handle save button clicks
document.addEventListener('click', function(event) {
    const saveButton = event.target.closest('button[data-save-button-id]');
    if (saveButton) {
        const postId = saveButton.dataset.saveButtonId.replace('save-', '');
        const bookmarkIcon = saveButton.querySelector('svg');
        const isSaved = bookmarkIcon.classList.contains('fill-current');
        
        // Send the save/unsave request to the server
        fetch('utils/save_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}&action=${isSaved ? 'unsave' : 'save'}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Toggle the bookmark icon in dashboard
                bookmarkIcon.classList.toggle('fill-current');
                bookmarkIcon.classList.toggle('text-black');
                bookmarkIcon.classList.toggle('dark:text-white');
                
                // If the post modal is open for this post, update its save button too
                if (currentPostId === postId) {
                    const modalSaveButton = document.getElementById('modalSaveButton');
                    if (modalSaveButton) {
                        const modalBookmarkIcon = modalSaveButton.querySelector('svg');
                        if (modalBookmarkIcon) {
                            modalBookmarkIcon.classList.toggle('fill-current');
                            modalBookmarkIcon.classList.toggle('text-black');
                            modalBookmarkIcon.classList.toggle('dark:text-white');
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
});

// Toggle post options dropdown
function togglePostOptions(postId) {
    const dropdown = document.getElementById(`post-options-${postId}`);
    const allDropdowns = document.querySelectorAll('[id^="post-options-"]');
    
    // Close all other dropdowns
    allDropdowns.forEach(d => {
        if (d.id !== `post-options-${postId}`) {
            d.classList.add('hidden');
        }
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('hidden');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!dropdown.contains(e.target) && !e.target.closest('button[onclick*="togglePostOptions"]')) {
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

// Delete post function
function deletePost(postId) {
    // Close the post options dropdown
    const dropdown = document.getElementById(`post-options-${postId}`);
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
    
    const deleteConfirmationModal = document.getElementById('delete-post-confirmation-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete-post-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-post-btn');
    
    // Show the confirmation modal
    deleteConfirmationModal.style.display = 'flex';
    
    // Handle confirm button click
    confirmDeleteBtn.onclick = function() {
        fetch('utils/delete_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove post from the feed
                const postElement = document.querySelector(`[data-post-id="post-${postId}"]`);
                if (postElement) {
                    postElement.remove();
                }
                // Show success toast
                showToast('Post deleted successfully', 'success');
            } else {
                showToast(data.error || 'Error deleting post. Please try again.', 'error');
            }
            // Hide the modal
            deleteConfirmationModal.style.display = 'none';
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error deleting post. Please try again.', 'error');
            // Hide the modal
            deleteConfirmationModal.style.display = 'none';
        });
    };
    
    // Handle cancel button click
    cancelDeleteBtn.onclick = function() {
        deleteConfirmationModal.style.display = 'none';
    };
    
    // Close modal when clicking outside
    deleteConfirmationModal.onclick = function(event) {
        if (event.target === deleteConfirmationModal) {
            deleteConfirmationModal.style.display = 'none';
        }
    };
}

// Report Modal Functions
function openReportModal(id, type = 'post') {
    const reportIdInput = document.getElementById('reportId');
    const reportTypeInput = document.getElementById('reportType');
    const reportTypeLabel = document.getElementById('reportTypeLabel');
    const reportTypeTitle = document.getElementById('reportTypeTitle');
    const modal = document.getElementById('reportModal');
    const charCountEl = document.getElementById('charCount');
    const textareaEl = document.getElementById('reportDescription');
    
    if (!reportIdInput || !reportTypeInput || !reportTypeLabel || !reportTypeTitle || !modal) {
        console.error('Report modal elements not found');
        showToast('Error: Report modal not properly initialized', 'error');
        return;
    }
    
    // Reset the form
    if (textareaEl) textareaEl.value = '';
    if (charCountEl) charCountEl.textContent = '0';
    
    // Set values
    reportIdInput.value = id;
    reportTypeInput.value = type;
    reportTypeLabel.textContent = type;
    reportTypeTitle.textContent = type.charAt(0).toUpperCase() + type.slice(1);
    
    modal.classList.remove('hidden');
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    const form = document.getElementById('reportForm');
    const charCount = document.getElementById('charCount');
    
    if (modal) modal.classList.add('hidden');
    if (form) form.reset();
    if (charCount) charCount.textContent = '0';
}

// Update the report form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        // Remove any existing event listeners
        const newForm = reportForm.cloneNode(true);
        reportForm.parentNode.replaceChild(newForm, reportForm);
        
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const reportId = document.getElementById('reportId').value;
            const reportType = document.getElementById('reportType').value;
            const description = document.getElementById('reportDescription').value.trim();
            
            // Validate inputs
            if (!reportId || reportId === 'undefined' || reportId === 'null') {
                showToast('Unable to report this item. Please try again.', 'error');
                return false;
            }
            
            if (!description) {
                showToast('Please provide a reason for your report', 'error');
                return false;
            }
            
            // Disable the form while submitting
            const submitButton = newForm.querySelector('button[type="submit"]');
            if (submitButton) submitButton.disabled = true;
            
            // Determine the endpoint based on report type
            const endpoint = reportType === 'comment' ? 'utils/report_comment.php' : 'utils/report_post.php';
            const paramName = reportType === 'comment' ? 'comment_id' : 'post_id';
            
            // Create form data
            const formData = new FormData();
            formData.append(paramName, reportId);
            formData.append('description', description);
            
            // Send the request
            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Close the report modal regardless of success/failure
                closeReportModal();
                
                if (data.success) {
                    showToast(data.message || `${reportType.charAt(0).toUpperCase() + reportType.slice(1)} reported successfully`, 'success');
                    // Close post modal only if reporting a post
                    if (reportType === 'post') {
                        closePostModal();
                    }
                } else {
                    // Only show error toast if there's a specific error message
                    if (data.message && data.message !== 'Post not found') {
                        showToast(data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(`Network error when reporting ${reportType}`, 'error');
            })
            .finally(() => {
                // Re-enable the form
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit Report';
                }
            });
            
            return false;
        });
    }
});

function reportPost(postId) {
    // Close the post options dropdown
    const dropdown = document.querySelector(`#post-options-${postId}`);
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
    
    // Open the report modal
    openReportModal(postId, 'post');
}

// Handle dashboard comment form submissions
document.addEventListener('submit', function(e) {
    if (e.target.matches('form[data-post-id]')) {
        e.preventDefault();
        const form = e.target;
        const postId = form.dataset.postId;
        const input = form.querySelector('input[name="body"]');
        const submitButton = form.querySelector('button[type="submit"]');
        const comment = input.value.trim();
        
        if (comment) {
            // Disable the form while submitting
            input.disabled = true;
            submitButton.disabled = true;
            
            // Build request body
            const requestBody = `post_id=${postId}&body=${encodeURIComponent(comment)}`;
            
            // Send the comment to the server
            fetch('utils/comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: requestBody
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error parsing JSON:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Clear the input
                    input.value = '';
                    
                    // Update comment count
                    const commentCountContainer = document.querySelector(`[data-comment-count-id="comment-count-${postId}"]`);
                    if (commentCountContainer) {
                        if (data.comment_count === 0) {
                            commentCountContainer.innerHTML = '';
                        } else {
                            commentCountContainer.innerHTML = `
                                <a class="text-sm text-neutral-500 dark:text-neutral-400 px-3 sm:px-0 hover:underline mt-1" href="javascript:void(0)" onclick="openPostModal('${postId}')">
                                    View all ${data.comment_count} comment${data.comment_count === 1 ? '' : 's'}
                                </a>
                            `;
                        }
                    }
                    
                    // Show success message
                    showToast('Comment added successfully', 'success');
                } else {
                    showToast(data.message || 'Failed to add comment', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while adding the comment', 'error');
            })
            .finally(() => {
                // Re-enable the form
                input.disabled = false;
                submitButton.disabled = false;
            });
        }
    }
});

// Add event listener for comment buttons
document.addEventListener('click', function(event) {
    // Check if the clicked element is a comment button or a child of a comment button
    const commentButton = event.target.closest('.comment-button');
    
    if (commentButton) {
        event.preventDefault();
        event.stopPropagation();
        
        // Get the post ID from the button's data attribute
        const postId = commentButton.getAttribute('data-post-id');
        
        if (postId) {
            // Find the comment form for this post
            const commentForm = document.querySelector(`form[data-post-id="${postId}"]`);
            
            if (commentForm) {
                // Find the comment input field
                const commentInput = commentForm.querySelector('input[name="body"]');
                
                // Focus the input field if it exists
                if (commentInput) {
                    commentInput.focus();
                }
            }
        }
    }
});
</script>

<!-- Add emoji-picker-element library which is better for vanilla JS -->
<script type="module">
  // Import the emoji picker element
  import 'https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js';
  
  window.emojiPickerLoaded = true;
  console.log('Emoji picker element loaded');
</script>

<!-- Fallback for non-module browsers -->
<script nomodule src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>

<!-- Emoji picker styles -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/css/emoji-picker.css">

<!-- Load emoji-picker-element as a module for modern browsers -->
<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>

<!-- Fallback for legacy browsers -->
<script nomodule src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>

<!-- Include the final emoji picker implementation -->
<script src="js/emoji-picker-final.js"></script>

<!-- Include comment handlers script for instant comment count updates -->
<script src="js/comment-handlers.js"></script>
   </body>
</html>
