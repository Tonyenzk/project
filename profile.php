<?php
// Enable error reporting only in development
error_reporting(0);
ini_set('display_errors', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'includes/check_viewed_stories.php';
require_once 'includes/image_helper.php';

try {
    // Test database connection
    $test_stmt = $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Database connection failed");
}

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_photo'])) {
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if ($file['error'] === 0) {
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('profile_') . '.' . $extension;
                $upload_path = 'uploads/profile_photo/' . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Update database with new profile picture path
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                        $stmt->execute([$upload_path, $_SESSION['user_id']]);
                        
                        // Redirect to refresh the page
                        header("Location: profile.php");
                        exit();
                    } catch (PDOException $e) {
                        $upload_error = "Database error occurred. Please try again.";
                    }
                } else {
                    $upload_error = "Failed to upload file. Please try again.";
                }
            } else {
                $upload_error = "Invalid file type or size. Please upload a JPEG, PNG, or WebP image under 5MB.";
            }
        } else {
            $upload_error = "Error uploading file. Please try again.";
        }
    } elseif (isset($_POST['remove_photo'])) {
        try {
            // Get current profile picture path
            $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_photo = $stmt->fetchColumn();

            // Delete the file if it exists and is not the default placeholder
            if ($current_photo && $current_photo !== 'images/profile_placeholder.webp' && file_exists($current_photo)) {
                unlink($current_photo);
            }

            // Update database to use default placeholder
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = 'images/profile_placeholder.webp' WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            // Redirect to refresh the page
            header("Location: profile.php");
            exit();
        } catch (PDOException $e) {
            $upload_error = "Error removing profile photo. Please try again.";
        }
    }
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT username, profile_picture, bio, verifybadge FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    $username = $user['username'];
    $profile_picture = $user['profile_picture'] ?: './images/profile_placeholder.webp';
    $bio = $user['bio'];
    $isVerified = $user['verifybadge'] === 'true';

    // Get followers count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $followers_count = $stmt->fetchColumn();

    // Get following count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $following_count = $stmt->fetchColumn();

    // Get posts count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $posts_count = $stmt->fetchColumn();

    // Check if user has any active stories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE user_id = ? AND expires_at > NOW()");
    $stmt->execute([$_SESSION['user_id']]);
    $has_stories = $stmt->fetchColumn() > 0;
    
    // Check if the current user has viewed all of their own stories
    $all_stories_viewed = $has_stories ? hasViewedAllStories($pdo, $_SESSION['user_id'], $_SESSION['user_id']) : false;

    // Fetch user's posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch saved posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
        FROM saved_posts sp
        JOIN posts p ON sp.post_id = p.post_id
        WHERE sp.user_id = ?
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $saved_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch tagged posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
        FROM post_tags pt
        JOIN posts p ON pt.post_id = p.post_id
        WHERE pt.tagged_user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tagged_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <?php include 'includes/header.php'; ?>
      <script src="assets/js/post_functions.js"></script>
      <script>
      // Single source of truth for initialization
      window.profileInitialized = window.profileInitialized || false;

      // Main initialization function
      async function initializeProfile() {
          if (window.profileInitialized) return;
          window.profileInitialized = true;

          // Initialize current user ID
          window.currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;

          // Load required scripts
          try {
              await Promise.all([
                  loadScript('utils/likes_modal.js'),
                  loadScript('utils/like.js')
              ]);
          } catch (error) {
              console.error('Error loading scripts:', error);
              return;
          }

          // Initialize WebSocket connection
          if (typeof ws !== 'undefined' && ws) {
              ws.onmessage = function(event) {
                  const data = JSON.parse(event.data);
                  console.log('Received WebSocket message:', data);

                  if (data.type === 'follow_request') {
                      // Update follow request count
                      const followRequestsCount = document.querySelector('button[onclick^="openFollowersModal(\'follow_requests\'"] span:first-child');
                      if (followRequestsCount) {
                          const currentCount = parseInt(followRequestsCount.textContent);
                          followRequestsCount.textContent = currentCount + 1;
                      }

                      // Show toast notification
                      showToast(`${data.requester_username} wants to follow you`);
                  } else if (data.type === 'follow') {
                      // Update followers count
                      const followersCount = document.querySelector('button[onclick^="openFollowersModal(\'followers\'"] span:first-child');
                      if (followersCount) {
                          const currentCount = parseInt(followersCount.textContent);
                          followersCount.textContent = currentCount + 1;
                      }
                      
                      // Show toast notification
                      showToast(`${data.requester_username} started following you`);
                  }
              };
          }

          // Initialize modals
          initializeModals();

          // Initialize tabs
          const tabs = document.querySelectorAll('[role="tab"]');
          const panels = document.querySelectorAll('[role="tabpanel"]');
          
          if (!tabs.length || !panels.length) return;

          // Get the tab parameter from URL
          const urlParams = new URLSearchParams(window.location.search);
          const activeTabParam = urlParams.get('tab');

          // Tab switching function
          function switchTab(tab) {
              if (!tab || !(tab instanceof Element)) return;

              // Deactivate all tabs and panels
              tabs.forEach(t => {
                  t.setAttribute('aria-selected', 'false');
                  t.setAttribute('data-state', 'inactive');
              });
              panels.forEach(p => {
                  p.setAttribute('data-state', 'inactive');
                  p.style.display = 'none';
              });

              // Activate the selected tab and panel
              tab.setAttribute('aria-selected', 'true');
              tab.setAttribute('data-state', 'active');
              const panelId = tab.getAttribute('aria-controls');
              const panel = document.getElementById(panelId);
              if (panel) {
                  panel.setAttribute('data-state', 'active');
                  panel.style.display = 'block';
              }
          }

          // Set up click handlers for tabs
          tabs.forEach(tab => {
              tab.addEventListener('click', () => switchTab(tab));
          });

          // Activate the appropriate tab based on URL parameter
          if (activeTabParam === 'saved') {
              const savedTab = document.getElementById('tab-saved');
              if (savedTab) {
                  switchTab(savedTab);
              }
          } else if (activeTabParam === 'tagged') {
              const taggedTab = document.getElementById('tab-tagged');
              if (taggedTab) {
                  switchTab(taggedTab);
              }
          } else {
              // Always default to posts tab (postari)
              const postsTab = document.getElementById('tab-posts');
              if (postsTab) {
                  switchTab(postsTab);
              } else {
                  // Fallback to first tab if posts tab not found
                  switchTab(tabs[0]);
              }
          }
      }

      // Load script helper function
      function loadScript(src) {
          return new Promise((resolve, reject) => {
              const script = document.createElement('script');
              script.src = src;
              script.onload = resolve;
              script.onerror = reject;
              document.head.appendChild(script);
          });
      }

      // Initialize when DOM is loaded
      document.addEventListener('DOMContentLoaded', initializeProfile);

      function openProfileOptionsModal() {
          document.getElementById('profile-picture-options-modal').style.display = 'flex';
          document.body.classList.add('overflow-hidden');
      }

      function closeProfileOptionsModal() {
          document.getElementById('profile-picture-options-modal').style.display = 'none';
          document.body.classList.remove('overflow-hidden');
      }

      function openChangePhotoModal() {
          closeProfileOptionsModal(); // Close the profile options modal first
          document.getElementById('change-profile-photo-modal').style.display = 'flex';
          document.body.classList.add('overflow-hidden');
      }

      function closeChangePhotoModal() {
          document.getElementById('change-profile-photo-modal').style.display = 'none';
          document.body.classList.remove('overflow-hidden');
      }

      // Initialize modal event listeners
      function initializeModals() {
          const profileAvatar = document.getElementById('profile-avatar');
          const avatarOptionsModal = document.getElementById('profile-picture-options-modal');
          const changePhotoModal = document.getElementById('change-profile-photo-modal');
          const closeChangePhotoBtn = document.getElementById('close-change-photo-modal');

          if (profileAvatar) {
              profileAvatar.addEventListener('click', openProfileOptionsModal);
          }

          if (avatarOptionsModal) {
              avatarOptionsModal.addEventListener('click', function(event) {
                  if (event.target === this) {
                      closeProfileOptionsModal();
                  }
              });
          }

          if (changePhotoModal) {
              changePhotoModal.addEventListener('click', function(event) {
                  if (event.target === this) {
                      closeChangePhotoModal();
                  }
              });
          }

          if (closeChangePhotoBtn) {
              closeChangePhotoBtn.addEventListener('click', closeChangePhotoModal);
          }
      }
      </script>
      <link rel="stylesheet" href="./css/style.css">
   </head>
   <body>
<div class="flex bg-white dark:bg-black snipcss-kaeog">
<?php include 'navbar.php'; ?>
<?php include 'postview.php'; ?>
<?php include 'modals/story_modal.php'; ?>
<?php include 'modals/followers_modal.php'; ?>
    <div class="fixed z-40 bg-black/20 inset-0 left-[72px] right-0 transition-opacity duration-200 ease-out opacity-0 pointer-events-none"></div>
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="flex flex-col bg-white dark:bg-black">
            <div class="flex flex-col">
                <main class="flex-1 pb-[56px] md:pb-0 bg-white dark:bg-black mt-[72px]">
                    <div class="max-w-[935px] mx-auto">
                        <section class="flex flex-col md:flex-row gap-y-4 px-4 pb-6">
                            <div class="shrink-0 md:w-[290px] md:mr-7 flex justify-center md:justify-center">
                                <div class="cursor-pointer">
                                    <div class="relative">
                                        <div class="<?php echo $has_stories ? ($all_stories_viewed ? 'story-ring-gray story-ring-large relative' : 'story-ring story-ring-large relative') : 'relative'; ?>">
                                            <div class="relative rounded-full bg-white dark:bg-black p-[2px]">
                                                <span id="profile-avatar" class="relative flex shrink-0 overflow-hidden rounded-full w-[86px] h-[86px] md:w-[150px] md:h-[150px] cursor-pointer">
                                                    <div class="relative aspect-square h-full w-full rounded-full">
                                                        <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                            <img alt="<?php echo htmlspecialchars($username); ?>'s profile picture" referrerpolicy="no-referrer" decoding="async" data-nimg="fill" class="object-cover rounded-full h-full w-full" src="<?php echo htmlspecialchars($profile_picture); ?>">
                                                        </div>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col flex-1 max-w-full gap-y-4 md:gap-y-3">
                                <div class="flex flex-col gap-y-4 md:gap-y-3">
                                    <div class="flex flex-col gap-y-3 md:flex-row md:items-center md:gap-x-4">
                                        <div class="flex items-center gap-x-2">
                                            <h2 class="inline-flex items-center gap-x-1.5 text-xl md:text-xl"><span class="font-semibold"><?php echo htmlspecialchars($username); ?></span>
                                                <?php if ($isVerified): ?>
                                                <div class="relative inline-block"><svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" style="width:16px;height:16px" viewBox="0 0 40 40" width="100%">
                                                        <title>Verified</title>
                                                        <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                    </svg></div>
                                                <?php endif; ?>
                                            </h2>
                                        </div>
                                        <div class="flex items-center gap-x-2"><a class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-neutral-200 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-300 dark:hover:bg-neutral-700 h-9 rounded-md px-3 !font-semibold text-sm h-9 px-6 w-full md:w-auto" href="edit-profile.php">Edit profile</a></div>
                                    </div>
                                    <div class="flex items-center justify-around md:justify-start md:gap-x-7 text-sm border-y border-neutral-200 dark:border-neutral-800 py-3 md:border-0 md:py-0">
                                        <div class="flex items-center justify-around w-full md:justify-start md:gap-x-10 text-sm border-y md:border-y-0 border-neutral-200 dark:border-neutral-800 py-3 md:py-0">
                                            <div class="flex flex-col md:flex-row items-center md:items-center gap-0 md:gap-1 transition-all">
                                                <span class="font-semibold text-lg md:text-base"><?php echo $posts_count; ?></span>
                                                <span class="text-neutral-500 dark:text-neutral-400 text-[11px] md:text-sm tracking-wide ml-1">posts</span>
                                            </div>
                                            <button onclick="openFollowersModal('followers', <?php echo $_SESSION['user_id']; ?>)" class="flex flex-col md:flex-row items-center md:items-center gap-0 md:gap-1 transition-all hover:opacity-75 active:scale-95 cursor-pointer">
                                                <span class="font-semibold text-lg md:text-base"><?php echo $followers_count; ?></span>
                                                <span class="text-neutral-500 dark:text-neutral-400 text-[11px] md:text-sm tracking-wide ml-1">followers</span>
                                            </button>
                                            <button onclick="openFollowersModal('following', <?php echo $_SESSION['user_id']; ?>)" class="flex flex-col md:flex-row items-center md:items-center gap-0 md:gap-1 transition-all hover:opacity-75 active:scale-95 cursor-pointer">
                                                <span class="font-semibold text-lg md:text-base"><?php echo $following_count; ?></span>
                                                <span class="text-neutral-500 dark:text-neutral-400 text-[11px] md:text-sm tracking-wide ml-1">following</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-y-1"><span class="font-semibold text-sm"><?php echo htmlspecialchars($username); ?></span>
                                    <?php if ($bio): ?>
                                    <p class="text-sm text-neutral-600 dark:text-neutral-400"><?php echo htmlspecialchars($bio); ?></p>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div class="px-4 md:px-8">
                            <div>
                                <div dir="ltr" data-orientation="horizontal" class="pt-4 md:pt-16">
                                    <div role="tablist" aria-orientation="horizontal" class="items-center rounded-md text-muted-foreground flex justify-center w-full h-auto p-0 bg-transparent border-t border-b border-neutral-200 dark:border-neutral-800" tabindex="0" data-orientation="horizontal" style="outline:none">
                                        <button type="button" role="tab" aria-selected="true" aria-controls="tab-panel-posts" data-state="active" id="tab-posts" class="inline-flex items-center justify-center whitespace-nowrap py-1.5 text-sm font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex-1 md:flex-none px-4 md:px-12 pt-3 pb-3 rounded-none border-t-2 -mt-[1px] border-transparent transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50 data-[state=active]:border-neutral-700 dark:data-[state=active]:border-neutral-200" tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
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
                                        <button type="button" role="tab" aria-selected="false" aria-controls="tab-panel-saved" data-state="inactive" id="tab-saved" class="inline-flex items-center justify-center whitespace-nowrap py-1.5 text-sm font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex-1 md:flex-none px-4 md:px-12 pt-3 pb-3 rounded-none border-t-2 -mt-[1px] border-transparent transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50 data-[state=active]:border-neutral-700 dark:data-[state=active]:border-neutral-200" tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
                                            <div class="flex items-center justify-center md:justify-start gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark w-4 h-4 md:w-3.5 md:h-3.5">
                                                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
                                                </svg>
                                                <span class="text-xs font-semibold tracking-wider uppercase">Salvate</span>
                                            </div>
                                        </button>
                                        <button type="button" role="tab" aria-selected="false" aria-controls="tab-panel-tagged" data-state="inactive" id="tab-tagged" class="inline-flex items-center justify-center whitespace-nowrap py-1.5 text-sm font-medium ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm flex-1 md:flex-none px-4 md:px-12 pt-3 pb-3 rounded-none border-t-2 -mt-[1px] border-transparent transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50 data-[state=active]:border-neutral-700 dark:data-[state=active]:border-neutral-200" tabindex="-1" data-orientation="horizontal" data-radix-collection-item="">
                                            <div class="flex items-center justify-center md:justify-start gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag w-4 h-4 md:w-3.5 md:h-3.5">
                                                    <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                                                    <path d="M7 7h.01"></path>
                                                </svg>
                                                <span class="text-xs font-semibold tracking-wider uppercase">Etichetate</span>
                                            </div>
                                        </button>
                                    </div>
                                    <div role="tabpanel" id="tab-panel-posts" aria-labelledby="tab-posts" class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" data-state="active" tabindex="0">
                                        <?php if (empty($user_posts)): ?>
                                            <div class="flex flex-col items-center justify-center py-20 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image w-12 h-12 text-neutral-500 mb-4">
                                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                                                    <circle cx="9" cy="9" r="2"></circle>
                                                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                                                </svg>
                                                <h1 class="text-2xl font-semibold mb-2">Nicio postare încă</h1>
                                                <p class="text-neutral-500 max-w-sm">Când distribui fotografii, ele vor apărea aici.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="grid grid-cols-3 gap-0.5 md:gap-1 lg:gap-2 mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 focus:outline-none">
                                                <?php foreach ($user_posts as $post): ?>
                                                    <a class="relative aspect-square group overflow-hidden focus:outline-none" onclick="openPostModal('<?php echo htmlspecialchars($post['post_id']); ?>')">
                                                        <div class="relative w-full h-full">
                                                            <img alt="Post" decoding="async" data-nimg="fill" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" src="<?php echo get_cached_image_url(htmlspecialchars($post['image_url'])); ?>">
                                                            <div class="absolute inset-0 bg-black/40 transition-opacity duration-200 opacity-0 group-hover:opacity-100">
                                                                <div class="absolute inset-0 flex items-center justify-center gap-6">
                                                                    <div class="flex items-center gap-2 font-bold text-white">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-6 h-6 fill-white text-white">
                                                                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                                                        </svg>
                                                                        <p class="text-base" data-grid-like-count="<?php echo $post['post_id']; ?>"><?php echo htmlspecialchars($post['like_count']); ?></p>
                                                                    </div>
                                                                    <div class="flex items-center gap-2 font-bold text-white">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle w-6 h-6 fill-transparent text-white">
                                                                            <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                                                                        </svg>
                                                                        <p class="text-base"><?php echo htmlspecialchars($post['comment_count']); ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div role="tabpanel" id="tab-panel-saved" aria-labelledby="tab-saved" class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" data-state="inactive" hidden tabindex="0">
                                        <?php if (empty($saved_posts)): ?>
                                            <div class="flex flex-col items-center justify-center py-20 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark-x w-12 h-12 text-neutral-500 mb-4">
                                                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"></path>
                                                    <path d="m14.5 7.5-5 5"></path>
                                                    <path d="m9.5 7.5 5 5"></path>
                                                </svg>
                                                <h1 class="text-2xl font-semibold mb-2">Nicio postare salvată</h1>
                                                <p class="text-neutral-500 max-w-sm">Salvează fotografii și videoclipuri pe care vrei să le revezi.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="grid grid-cols-3 gap-0.5 md:gap-1 lg:gap-2 mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                                                <?php foreach ($saved_posts as $post): ?>
                                                    <a class="relative aspect-square group overflow-hidden focus:outline-none" onclick="openPostModal('<?php echo htmlspecialchars($post['post_id']); ?>')">
                                                        <div class="relative w-full h-full">
                                                            <img alt="Saved Post" decoding="async" data-nimg="fill" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" src="<?php echo get_cached_image_url(htmlspecialchars($post['image_url'])); ?>">
                                                            <div class="absolute inset-0 bg-black/40 transition-opacity duration-200 opacity-0 group-hover:opacity-100">
                                                                <div class="absolute inset-0 flex items-center justify-center gap-6">
                                                                    <div class="flex items-center gap-2 font-bold text-white">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-6 h-6 fill-white text-white">
                                                                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                                                        </svg>
                                                                        <p class="text-base" data-grid-like-count="<?php echo $post['post_id']; ?>"><?php echo htmlspecialchars($post['like_count']); ?></p>
                                                                    </div>
                                                                    <div class="flex items-center gap-2 font-bold text-white">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle w-6 h-6 fill-transparent text-white">
                                                                            <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                                                                        </svg>
                                                                        <p class="text-base"><?php echo htmlspecialchars($post['comment_count']); ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div role="tabpanel" id="tab-panel-tagged" aria-labelledby="tab-tagged" class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" data-state="inactive" hidden tabindex="0">
                                        <?php if (empty($tagged_posts)): ?>
                                            <div class="flex flex-col items-center justify-center py-20 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-contact w-12 h-12 text-neutral-500 mb-4">
                                                    <path d="M17 18a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2"></path>
                                                    <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                                    <circle cx="12" cy="10" r="2"></circle>
                                                    <line x1="8" x2="8" y1="2" y2="4"></line>
                                                    <line x1="16" x2="16" y1="2" y2="4"></line>
                                                </svg>
                                                <h1 class="text-2xl font-semibold mb-2">Nicio postare cu etichetă</h1>
                                                <p class="text-neutral-500 max-w-sm">Când oamenii te etichetează în postări, acestea vor apărea aici.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="grid grid-cols-3 gap-0.5 md:gap-1 lg:gap-2 mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                                                <?php foreach ($tagged_posts as $post): ?>
                                                    <a class="relative aspect-square group overflow-hidden focus:outline-none" onclick="openPostModal('<?php echo htmlspecialchars($post['post_id']); ?>')">
                                                        <div class="relative w-full h-full">
                                                            <img alt="Tagged Post" decoding="async" data-nimg="fill" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" src="<?php echo get_cached_image_url(htmlspecialchars($post['image_url'])); ?>">
                                                            <div class="absolute inset-0 bg-black/40 transition-opacity duration-200 opacity-0 group-hover:opacity-100">
                                                                <div class="absolute inset-0 flex items-center justify-center gap-6">
                                                                    <div class="flex items-center gap-2 font-bold text-white">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-6 h-6 fill-white text-white">
                                                                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                                                        </svg>
                                                                        <p class="text-base" data-grid-like-count="<?php echo $post['post_id']; ?>"><?php echo htmlspecialchars($post['like_count']); ?></p>
                                                                    </div>
                                                                    <div class="flex items-center gap-2 font-bold text-white">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle w-6 h-6 fill-transparent text-white">
                                                                            <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                                                                        </svg>
                                                                        <p class="text-base"><?php echo htmlspecialchars($post['comment_count']); ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </main>
</div>

<!-- Profile Picture Options Modal -->
<div id="profile-picture-options-modal" class="fixed inset-0 z-50 flex items-center justify-center backdrop-filter backdrop-blur-lg" style="display: none; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);">
    <div class="bg-white dark:bg-neutral-900 rounded-lg overflow-hidden flex flex-col w-64">
        <button id="change-profile-picture" class="w-full text-left px-4 py-3 text-blue-500 font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors duration-200">
            Change profile picture
        </button>
        <button id="view-story-option" class="w-full text-left px-4 py-3 text-neutral-900 dark:text-white font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors duration-200">
            View Story
        </button>
        <button id="cancel-profile-options" class="w-full text-left px-4 py-3 text-red-600 font-semibold hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors duration-200">
            Cancel
        </button>
    </div>
</div>

<!-- Change Profile Photo Modal -->
<div id="change-profile-photo-modal" class="fixed inset-0 z-50 flex items-center justify-center backdrop-filter backdrop-blur-lg" style="display: none; background-color: rgba(0, 0, 0, 0.5);">
    <div class="bg-white dark:bg-neutral-900 rounded-2xl overflow-hidden flex flex-col w-full max-w-lg shadow-2xl border border-neutral-200 dark:border-neutral-800">
        <div class="flex justify-between items-center p-6 border-b border-neutral-200 dark:border-neutral-700">
            <h3 class="text-xl font-semibold text-neutral-900 dark:text-white">Change Profile Photo</h3>
            <button id="close-change-photo-modal" class="text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 focus:outline-none p-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-8 flex flex-col items-center">
            <div class="w-48 h-48 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <?php if (isset($upload_error)): ?>
                <div class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($upload_error); ?></div>
            <?php endif; ?>
            <form action="profile.php" method="POST" enctype="multipart/form-data" class="w-full">
                <input type="file" name="profile_photo" id="profile_photo" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="this.form.submit()">
                <button type="button" onclick="document.getElementById('profile_photo').click()" class="w-full bg-blue-500 text-white rounded-md py-2.5 px-4 text-sm font-semibold hover:bg-blue-600 transition-colors duration-200 mb-3">
                    <div class="flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Upload Photo
                    </div>
                </button>
            </form>
            <?php if ($profile_picture !== 'images/profile_placeholder.webp'): ?>
            <form action="profile.php" method="POST" class="w-full">
                <input type="hidden" name="remove_photo" value="1">
                <button type="submit" class="w-full bg-red-500 text-white rounded-md py-2.5 px-4 text-sm font-semibold hover:bg-red-600 transition-colors duration-200">
                    <div class="flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" />
                        </svg>
                        Remove Current Photo
                    </div>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Add click event listener to profile avatar
document.getElementById('profile-avatar').addEventListener('click', function() {
    document.getElementById('profile-picture-options-modal').style.display = 'flex';
    document.body.classList.add('overflow-hidden');
});

// Close modal when clicking outside
document.getElementById('profile-picture-options-modal').addEventListener('click', function(event) {
    if (event.target === this) {
        this.style.display = 'none';
        document.body.classList.remove('overflow-hidden');
    }
});

// Handle change profile picture button
document.getElementById('change-profile-picture').addEventListener('click', function() {
    document.getElementById('profile-picture-options-modal').style.display = 'none';
    document.getElementById('change-profile-photo-modal').style.display = 'flex';
});

// Handle view story button
document.getElementById('view-story-option').addEventListener('click', function() {
    document.getElementById('profile-picture-options-modal').style.display = 'none';
    document.body.classList.remove('overflow-hidden');
    openStoryModal();
});

// Handle cancel button
document.getElementById('cancel-profile-options').addEventListener('click', function() {
    document.getElementById('profile-picture-options-modal').style.display = 'none';
    document.body.classList.remove('overflow-hidden');
});

// Close change photo modal when clicking outside or on close button
document.getElementById('change-profile-photo-modal').addEventListener('click', function(event) {
    if (event.target === this) {
        this.style.display = 'none';
        document.body.classList.remove('overflow-hidden');
    }
});

// Add click event listener to close button
document.getElementById('close-change-photo-modal').addEventListener('click', function() {
    document.getElementById('change-profile-photo-modal').style.display = 'none';
    document.body.classList.remove('overflow-hidden');
});
</script>
</body>
</html>
