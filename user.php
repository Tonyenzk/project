<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/check_viewed_stories.php';
require_once 'includes/image_helper.php';

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get the username from URL parameter
$profile_username = isset($_GET['username']) ? $_GET['username'] : '';

if (empty($profile_username)) {
    header("Location: index.php");
    exit();
}

// Debug output
error_log("Current logged in user ID: " . $_SESSION['user_id']);
error_log("Requested profile username: " . $profile_username);

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT u.user_id, u.username, u.profile_picture, u.bio, u.verifybadge, u.isPrivate, u.is_banned, 
                          (SELECT role FROM users WHERE user_id = ?) as current_user_role 
                          FROM users u WHERE u.username = ?");
    $stmt->execute([$_SESSION['user_id'], $profile_username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // User not found
        error_log("User not found: " . $profile_username);
        header("Location: index.php");
        exit();
    }
    
    $viewed_username = $user['username'];
    $viewed_profile_picture = $user['profile_picture'] ?: './images/profile_placeholder.webp';
    $viewed_bio = $user['bio'];
    $isVerified = $user['verifybadge'] === 'true';
    $viewed_user_id = $user['user_id'];
    $isPrivate = $user['isPrivate'] == 1;
    $is_banned = $user['is_banned'] == 1;
    $current_user_role = $user['current_user_role'];

    // Debug output to check values
    error_log("Profile username: " . $profile_username);
    error_log("Profile picture path: " . $viewed_profile_picture);
    error_log("Profile user ID: " . $viewed_user_id);
    error_log("Is Private: " . $isPrivate);

    // Check if current user is following the viewed user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $viewed_user_id]);
    $is_following = $stmt->fetchColumn() > 0;

    // Check if there's a pending follow request
    $stmt = $pdo->prepare("SELECT status FROM follow_requests WHERE requester_id = ? AND requested_id = ?");
    $stmt->execute([$_SESSION['user_id'], $viewed_user_id]);
    $follow_request = $stmt->fetch();
    $has_pending_request = $follow_request && $follow_request['status'] === 'pending';

    // Check if current user has blocked the viewed user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->execute([$_SESSION['user_id'], $viewed_user_id]);
    $has_blocked = $stmt->fetchColumn() > 0;

    // Check if current user is blocked by the viewed user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->execute([$viewed_user_id, $_SESSION['user_id']]);
    $is_blocked_by = $stmt->fetchColumn() > 0;

    // Get followers count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
    $stmt->execute([$viewed_user_id]);
    $followers_count = $stmt->fetchColumn();

    // Get following count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $stmt->execute([$viewed_user_id]);
    $following_count = $stmt->fetchColumn();

    // Get posts count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmt->execute([$viewed_user_id]);
    $posts_count = $stmt->fetchColumn();

    // Check if user has any active stories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stories WHERE user_id = ? AND expires_at > NOW()");
    $stmt->execute([$viewed_user_id]);
    $has_stories = $stmt->fetchColumn() > 0;
    
    // Check if the current user has viewed all stories from this user
    $all_stories_viewed = $has_stories ? hasViewedAllStories($pdo, $_SESSION['user_id'], $viewed_user_id) : false;

    // Fetch user's posts with like and comment counts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
        FROM posts p 
        JOIN users u ON p.user_id = u.user_id
        WHERE p.user_id = ? AND u.is_banned = 0
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$viewed_user_id]);
    $user_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch saved posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
        FROM saved_posts sp
        JOIN posts p ON sp.post_id = p.post_id
        JOIN users u ON p.user_id = u.user_id
        WHERE sp.user_id = ? AND u.is_banned = 0
        ORDER BY sp.created_at DESC
    ");
    $stmt->execute([$viewed_user_id]);
    $saved_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch tagged posts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
        FROM post_tags pt
        JOIN posts p ON pt.post_id = p.post_id
        JOIN users u ON p.user_id = u.user_id
        WHERE pt.tagged_user_id = ? AND u.is_banned = 0
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$viewed_user_id]);
    $tagged_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if user can view posts
    $can_view_posts = !$isPrivate || $is_following || $_SESSION['user_id'] === $viewed_user_id;
} catch (PDOException $e) {
    error_log("Error fetching follow data: " . $e->getMessage());
    $is_following = false;
    $followers_count = 0;
    $following_count = 0;
    $posts_count = 0;
    $can_view_posts = false;
}
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <?php include 'includes/header.php'; ?>
      <link rel="stylesheet" href="./css/style.css">
      <link rel="stylesheet" href="./css/toast.css">
      <script src="utils/likes_modal.js"></script>
      <script>
        // User menu functions
        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        }

        function toggleBanUser(userId, username, isBanned) {
            // Close the three dots menu first
            const menu = document.getElementById('userMenu');
            menu.classList.add('hidden');
            
            const modal = document.getElementById('banConfirmModal');
            const title = document.getElementById('banConfirmTitle');
            const message = document.getElementById('banConfirmMessage');
            const button = document.getElementById('banConfirmButton');
            
            // Set the modal content based on ban status
            title.textContent = isBanned ? 'Unban User' : 'Ban User';
            message.textContent = isBanned 
                ? `Are you sure you want to unban ${username}? This will restore their account access.`
                : `Are you sure you want to ban ${username}? This will remove all their followers and following relationships.`;
            button.textContent = isBanned ? 'Unban User' : 'Ban User';
            
            // Set direct styles instead of classes
            button.style.backgroundColor = '#dc2626'; // red-600
            button.style.color = '#ffffff'; // white
            button.style.padding = '0.5rem 1rem';
            button.style.borderRadius = '0.375rem';
            button.style.fontWeight = '500';
            button.style.transition = 'background-color 0.2s';
            button.onmouseover = function() {
                this.style.backgroundColor = '#b91c1c'; // red-700
            };
            button.onmouseout = function() {
                this.style.backgroundColor = '#dc2626'; // red-600
            };
            
            // Store the action details
            pendingBanAction = { userId, username, isBanned };
            
            // Show the modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeBanConfirmModal() {
            const modal = document.getElementById('banConfirmModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
            pendingBanAction = null;
        }

        function confirmBanAction() {
            if (!pendingBanAction) return;
            
            const { userId, username, isBanned } = pendingBanAction;
            
            const formData = new FormData();
            formData.append('action', 'ban_user');
            formData.append('user_id', userId);

            fetch('utils/handle_ban.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    showToast(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while processing your request');
            })
            .finally(() => {
                closeBanConfirmModal();
            });
        }

        // Close menu when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(event) {
                const menu = document.getElementById('userMenu');
                const button = event.target.closest('button');
                if (menu && !menu.contains(event.target) && !button?.onclick?.toString().includes('toggleUserMenu')) {
                    menu.classList.add('hidden');
                }
            });
        });

        // Initialize likes modal handler
        document.addEventListener('DOMContentLoaded', function() {
            initializeLikesModalHandler();
            
            // Follow button functionality
            const followButton = document.getElementById('followButton');
            if (followButton) {
                const userId = followButton.dataset.userId;
                let lastActionTime = 0;
                const COOLDOWN = 5000; // 5 seconds cooldown

                followButton.addEventListener('click', function() {
                    const currentTime = Date.now();
                    const timeSinceLastAction = currentTime - lastActionTime;

                    if (timeSinceLastAction < COOLDOWN) {
                        const remainingTime = Math.ceil((COOLDOWN - timeSinceLastAction) / 1000);
                        showToast(`Trebuie sa astepti ${remainingTime} secunde inainte de a urmari aceasta persoana!`, 'error');
                        return;
                    }

                    const isFollowing = this.textContent.trim() === 'Following' || this.textContent.trim() === 'Unfollow';
                    const isRequested = this.textContent.trim() === 'Requested' || this.textContent.trim() === 'Cancel';

                    // Handle cancel request
                    if (isRequested && this.textContent.trim() === 'Cancel') {
                        const formData = new FormData();
                        formData.append('user_id', userId);
                        formData.append('action', 'cancel_request');
                        
                        fetch('utils/follow.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                lastActionTime = Date.now();
                                this.textContent = 'Follow';
                                this.classList.remove('bg-neutral-200', 'text-neutral-900', 'hover:bg-red-500', 'hover:text-white');
                                this.classList.add('bg-blue-500', 'text-white', 'hover:bg-blue-600', 'shadow-sm', 'hover:shadow-md', 'border', 'border-blue-600', 'hover:border-blue-700');
                                
                                // Dispatch custom event for notification update
                                const cancelEvent = new CustomEvent('followRequestCanceled', {
                                    detail: { userId: userId }
                                });
                                document.dispatchEvent(cancelEvent);
                            } else if (data.error) {
                                showToast(data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                        return;
                    }

                    const formData = new FormData();
                    formData.append('user_id', userId);
                    formData.append('action', isFollowing ? 'unfollow' : 'follow');

                    fetch('utils/follow.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            lastActionTime = Date.now();
                            
                            // Update button state based on response
                            if (isFollowing) {
                                // Unfollowing
                                this.textContent = 'Follow';
                                this.classList.remove(
                                    'bg-secondary', 'text-secondary-foreground',
                                    'hover:bg-red-500', 'hover:text-white',
                                    'bg-neutral-200', 'text-neutral-900',
                                    'bg-red-500', 'text-white'
                                );
                                this.classList.add('bg-blue-500', 'text-white', 'hover:bg-blue-600', 'shadow-sm', 'hover:shadow-md', 'border', 'border-blue-600', 'hover:border-blue-700');
                                
                                // Update followers count
                                const followersCount = document.querySelector('button[onclick^="openFollowersModal(\'followers\'"] span:first-child');
                                if (followersCount) {
                                    const currentCount = parseInt(followersCount.textContent);
                                    followersCount.textContent = Math.max(0, currentCount - 1);
                                }
                            } else {
                                // Following
                                if (data.message === 'Follow request sent') {
                                    // If it's a private profile and request is pending
                                    this.textContent = 'Requested';
                                    this.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600', 'shadow-sm', 'hover:shadow-md', 'border', 'border-blue-600', 'hover:border-blue-700');
                                    this.classList.add('bg-neutral-200', 'text-neutral-900', 'dark:bg-neutral-800', 'dark:text-neutral-100', 'hover:bg-red-500', 'hover:text-white');
                                    
                                    // Start polling for follow request status
                                    startFollowRequestPolling();
                                } else {
                                    // If it's a public profile or request was accepted
                                    this.textContent = 'Following';
                                    this.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600', 'shadow-sm', 'hover:shadow-md', 'border', 'border-blue-600', 'hover:border-blue-700');
                                    this.classList.add('bg-neutral-200', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-neutral-100', 'hover:bg-neutral-300', 'dark:hover:bg-neutral-700');
                                    
                                    // Update followers count
                                    const followersCount = document.querySelector('button[onclick^="openFollowersModal(\'followers\'"] span:first-child');
                                    if (followersCount) {
                                        const currentCount = parseInt(followersCount.textContent);
                                        followersCount.textContent = currentCount + 1;
                                    }
                                }
                            }
                        } else if (data.error) {
                            showToast(data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });

                // Add hover effects
                followButton.addEventListener('mouseenter', function() {
                    if (this.textContent.trim() === 'Following') {
                        this.textContent = 'Unfollow';
                        this.classList.remove('bg-neutral-200', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-neutral-100', 'hover:bg-neutral-300', 'dark:hover:bg-neutral-700');
                        this.classList.add('bg-red-500', 'hover:bg-red-600', 'text-white');
                        // Remove any existing hover classes that might interfere
                        this.classList.remove('hover:bg-blue-500', 'hover:bg-neutral-300', 'dark:hover:bg-neutral-700');
                    } else if (this.textContent.trim() === 'Requested') {
                        this.textContent = 'Cancel';
                        this.classList.remove('bg-neutral-200', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-neutral-100');
                        this.classList.add('bg-red-500', 'hover:bg-red-600', 'text-white');
                    }
                });

                followButton.addEventListener('mouseleave', function() {
                    if (this.textContent.trim() === 'Unfollow' || this.textContent.trim() === 'Cancel') {
                        if (this.textContent.trim() === 'Unfollow') {
                            this.textContent = 'Following';
                            this.classList.remove('bg-red-500', 'hover:bg-red-600', 'text-white');
                            this.classList.add('bg-neutral-200', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-neutral-100', 'hover:bg-neutral-300', 'dark:hover:bg-neutral-700');
                        } else {
                            this.textContent = 'Requested';
                            this.classList.remove('bg-red-500', 'hover:bg-red-600', 'text-white');
                            this.classList.add('bg-neutral-200', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-neutral-100', 'hover:bg-neutral-300', 'dark:hover:bg-neutral-700');
                        }
                        // Ensure we remove any lingering hover classes
                        this.classList.remove('hover:bg-blue-500');
                    }
                });
            }

            // Tab switching functionality
            const tabs = document.querySelectorAll('[role="tab"]');
            const tabPanels = document.querySelectorAll('[role="tabpanel"]');

            function switchTab(tab) {
                // Remove active state from all tabs
                tabs.forEach(t => {
                    t.setAttribute('aria-selected', 'false');
                    t.setAttribute('data-state', 'inactive');
                });

                // Hide all tab panels
                tabPanels.forEach(panel => {
                    panel.setAttribute('data-state', 'inactive');
                    panel.hidden = true;
                });

                // Activate the selected tab
                tab.setAttribute('aria-selected', 'true');
                tab.setAttribute('data-state', 'active');

                // Show the corresponding panel
                const panelId = tab.getAttribute('aria-controls');
                const panel = document.getElementById(panelId);
                if (panel) {
                    panel.setAttribute('data-state', 'active');
                    panel.hidden = false;
                }
            }

            // Add click handlers to all tabs
            tabs.forEach(tab => {
                tab.addEventListener('click', () => switchTab(tab));
            });

            // Always activate the Posts tab on page load
            const postsTab = document.querySelector('[aria-controls*="posts"]');
            if (postsTab) {
                switchTab(postsTab);
            }
        });

        const currentUserId = <?php echo $_SESSION['user_id']; ?>;

        // Add WebSocket connection
        let ws = null;
        let reconnectAttempts = 0;
        const MAX_RECONNECT_ATTEMPTS = 5;
        const RECONNECT_DELAY = 5000; // 5 seconds

        function connectWebSocket() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                console.log('WebSocket already connected');
                return;
            }

            if (ws) {
                ws.close();
            }

            try {
                const protocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
                ws = new WebSocket(protocol + window.location.hostname + '/ws');

                ws.onopen = function() {
                    console.log('WebSocket connected');
                    reconnectAttempts = 0;
                    
                    // Send authentication message
                    ws.send(JSON.stringify({
                        type: 'auth',
                        user_id: currentUserId
                    }));
                };

                ws.onclose = function() {
                    console.log('WebSocket disconnected');
                    if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                        setTimeout(connectWebSocket, RECONNECT_DELAY);
                        reconnectAttempts++;
                    }
                };

                ws.onerror = function(error) {
                    console.error('WebSocket error:', error);
                };

                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    console.log('Received WebSocket message:', data);

                    if (data.type === 'follow_request') {
                        // Update follow request count
                        const followRequestsCount = document.querySelector('button[onclick="toggleFollowRequests()"] span');
                        if (followRequestsCount) {
                            const currentCount = parseInt(followRequestsCount.textContent || '0');
                            followRequestsCount.textContent = currentCount + 1;
                            followRequestsCount.style.display = 'block';
                        }

                        // Update UI for follow request
                        const followButton = document.getElementById('followButton');
                        if (followButton && followButton.dataset.userId === data.requester_id) {
                            followButton.textContent = 'Requested';
                            followButton.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600', 'shadow-sm', 'hover:shadow-md', 'border', 'border-blue-600', 'hover:border-blue-700');
                            followButton.classList.add('bg-neutral-200', 'text-neutral-900', 'hover:bg-red-500', 'hover:text-white');
                            
                            // Show toast notification
                            showToast(`${data.requester_username} wants to follow you`);
                        }
                    } else if (data.type === 'follow') {
                        // Update UI for accepted follow request
                        const followButton = document.getElementById('followButton');
                        if (followButton && followButton.dataset.userId === data.actor_id) {
                            followButton.textContent = 'Following';
                            followButton.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600', 'shadow-sm', 'hover:shadow-md', 'border', 'border-blue-600', 'hover:border-blue-700');
                            followButton.classList.add('bg-neutral-200', 'text-neutral-900', 'hover:bg-blue-500', 'hover:text-white');
                            
                            // Update followers count
                            const followersCount = document.querySelector('button[onclick^="openFollowersModal(\'followers\'"] span:first-child');
                            if (followersCount) {
                                const currentCount = parseInt(followersCount.textContent);
                                followersCount.textContent = currentCount + 1;
                            }
                            
                            // Show toast notification
                            showToast(`${data.actor_username} started following you`);
                        }
                    }
                };
            } catch (error) {
                console.error('Error creating WebSocket connection:', error);
                if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                    setTimeout(connectWebSocket, RECONNECT_DELAY);
                    reconnectAttempts++;
                }
            }
        }

        // Connect WebSocket when page loads
        document.addEventListener('DOMContentLoaded', function() {
            connectWebSocket();
            
            // Load initial notifications
            loadInitialNotifications();
        });

        // Reconnect WebSocket when window gains focus
        window.addEventListener('focus', function() {
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                console.log('Window focused, reconnecting WebSocket...');
                connectWebSocket();
            }
        });

        // Close WebSocket when window is unloaded
        window.addEventListener('beforeunload', function() {
            if (ws) {
                ws.close();
            }
        });

        function showToast(message) {
            // Remove any existing toasts
            const container = document.getElementById('toast-container');
            if (!container) {
                const newContainer = document.createElement('ol');
                newContainer.id = 'toast-container';
                newContainer.className = 'fixed bottom-8 right-8 z-50 flex flex-col gap-3.5 w-[356px] m-0 p-0 list-none';
                document.body.appendChild(newContainer);
            }

            const toast = document.createElement('li');
            toast.className = 'toast';
            toast.setAttribute('data-type', 'error');
            toast.setAttribute('data-mounted', 'true');
            toast.setAttribute('data-visible', 'true');
            toast.innerHTML = `
                <button aria-label="Close toast" data-close-button="true" class="absolute top-2 right-2 p-1 rounded-full hover:bg-red-100 dark:hover:bg-red-900/20">
                    <div class="w-4 h-4 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center text-red-600 dark:text-red-400 group-hover:text-red-700 dark:group-hover:text-red-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </div>
                </button>
                <div data-icon class="flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" height="20" width="20" class="text-red-500">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div data-content class="flex-1 text-sm">
                    <div data-title class="font-semibold leading-snug text-red-600 dark:text-red-400">${message}</div>
                </div>
            `;

            // Add click handler for close button
            const closeButton = toast.querySelector('button');
            closeButton.addEventListener('click', () => {
                toast.remove();
            });

            container.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        function toggleBlockUser(userId, username, isBlocked) {
            // Close the three dots menu first
            const menu = document.getElementById('userMenu');
            menu.classList.add('hidden');
            
            const formData = new FormData();
            formData.append('action', isBlocked ? 'unblock' : 'block');
            formData.append('user_id', userId);

            fetch('utils/handle_block.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    showToast(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while processing your request');
            });
        }

        // Load initial notifications
        async function loadInitialNotifications() {
            try {
                console.log('Loading initial notifications...');
                const response = await fetch('api/notifications.php');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                console.log('Received notifications:', data);
                
                const notificationsList = document.getElementById('notificationsList');
                if (!notificationsList) {
                    console.error('Notifications list element not found');
                    return;
                }
                
                // Clear existing notifications
                notificationsList.innerHTML = '';
                
                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notification => {
                        console.log('Processing notification:', notification);
                        const notificationElement = createNotificationElement(notification);
                        notificationsList.appendChild(notificationElement);
                    });
                    
                    // Update notification count badge
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        badge.textContent = data.notifications.length;
                        badge.classList.remove('hidden');
                    }
                } else {
                    notificationsList.innerHTML = `
                        <div class="p-4 text-center text-neutral-500 dark:text-neutral-400">
                            Nu ai notificări noi
                        </div>
                    `;
                    
                    // Hide notification count badge
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        badge.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }

        function createNotificationElement(notification) {
            console.log('Creating notification element:', notification);
            
            const div = document.createElement('div');
            div.className = 'flex items-start gap-3 p-3 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer';
            
            // Add data attributes for notification identification
            if (notification.post_id) {
                div.setAttribute('data-post-id', notification.post_id);
            }
            if (notification.story_id) {
                div.setAttribute('data-story-id', notification.story_id);
            }
            if (notification.actor_id) {
                div.setAttribute('data-actor-id', notification.actor_id);
            }
            div.setAttribute('data-notif-type', notification.type);
            
            // Get the notification icon
            const icon = getNotificationIcon(notification.type);
            
            // Format the notification message
            let message = '';
            const count = parseInt(notification.count) || 1;
            const lastActor = notification.last_actor_username || notification.actor_username;
            
            if (!lastActor) {
                console.error('Missing actor username in notification:', notification);
                return div;
            }
            
            // Format the message based on notification type
            switch (notification.type) {
                case 'like':
                    if (count === 1) {
                        message = `${lastActor} a apreciat postarea ta`;
                    } else {
                        message = `${lastActor} si altii ${count - 1} au apreciat postarea ta`;
                    }
                    break;
                case 'comment':
                    if (count === 1) {
                        message = `${lastActor} a adaugat un comentariu la postarea ta`;
                    } else {
                        message = `${lastActor} si altii ${count - 1} au adaugat comentarii la postarea ta`;
                    }
                    break;
                case 'story_like':
                    if (count === 1) {
                        message = `${lastActor} a apreciat povestea ta`;
                    } else {
                        message = `${lastActor} si altii ${count - 1} au apreciat povestea ta`;
                    }
                    break;
                case 'comment_reply':
                    if (count === 1) {
                        message = `${lastActor} a raspuns la comentariul tau`;
                    } else {
                        message = `${lastActor} si altii ${count - 1} au raspuns la comentariul tau`;
                    }
                    break;
                case 'event':
                    message = `Un nou eveniment a fost postat! ${notification.event_name}`;
                    break;
                case 'follow_request':
                    message = `${lastActor} vrea să te urmărească`;
                    break;
                case 'follow':
                    message = `${lastActor} a început să te urmărească`;
                    break;
                default:
                    message = 'Nouă notificare';
            }
            
            // Calculate time difference
            const created = new Date(notification.created_at);
            const now = new Date();
            const timeDiff = Math.floor((now - created) / 1000); // difference in seconds
            
            // Create notification content
            div.innerHTML = `
                <div class="relative h-10 w-10 flex-shrink-0">
                    <img alt="${notification.actor_username || 'User'}'s profile picture" 
                         class="rounded-full object-cover h-10 w-10" 
                         src="${notification.actor_profile_picture || './images/profile_placeholder.webp'}">
                    <div class="absolute -bottom-1 -right-1 bg-white dark:bg-black rounded-full p-1">
                        ${icon}
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm">${message}</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">${formatTimeAgo(timeDiff)}</p>
                </div>
                ${notification.post_image ? `
                    <div class="h-10 w-10 flex-shrink-0">
                        <img src="${notification.post_image}" alt="Post image" class="h-10 w-10 object-cover rounded">
                    </div>
                ` : ''}
                ${notification.story_media ? `
                    <div class="h-10 w-10 flex-shrink-0">
                        <img src="${notification.story_media}" alt="Story media" class="h-10 w-10 object-cover rounded">
                    </div>
                ` : ''}
            `;
            
            // Add click handler based on notification type
            div.onclick = () => handleNotificationClick(notification);
            
            return div;
        }

        function getNotificationIcon(type) {
            switch (type) {
                case 'like':
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                            </svg>`;
                case 'comment':
                case 'comment_reply':
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>`;
                case 'story_like':
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                            </svg>`;
                case 'event':
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                <line x1="16" x2="16" y1="2" y2="6"></line>
                                <line x1="8" x2="8" y1="2" y2="6"></line>
                                <line x1="3" x2="21" y1="10" y2="10"></line>
                            </svg>`;
                case 'follow':
                case 'follow_request':
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>`;
                default:
                    return '';
            }
        }

        function formatTimeAgo(seconds) {
            if (seconds < 60) {
                return 'acum';
            } else if (seconds < 3600) {
                const minutes = Math.floor(seconds / 60);
                return `${minutes}m`;
            } else if (seconds < 86400) {
                const hours = Math.floor(seconds / 3600);
                return `${hours}h`;
            } else if (seconds < 604800) {
                const days = Math.floor(seconds / 86400);
                return `${days}z`;
            } else if (seconds < 2592000) {
                const weeks = Math.floor(seconds / 604800);
                return `${weeks}s`;
            } else if (seconds < 31536000) {
                const months = Math.floor(seconds / 2592000);
                return `${months}l`;
            } else {
                const years = Math.floor(seconds / 31536000);
                return `${years}a`;
            }
        }

        function handleNotificationClick(notification) {
            // Check if it's a post like notification
            if (notification.type === 'follow' && notification.post_id !== null) {
                notification.type = 'like'; // Convert to like notification if it has a post_id
            }

            switch (notification.type) {
                case 'like':
                case 'comment':
                case 'comment_reply':
                    window.location.href = `postview.php?id=${notification.post_id}`;
                    break;
                case 'story_like':
                    // Handle story view
                    break;
                case 'event':
                    window.location.href = `events.php?id=${notification.event_id}`;
                    break;
                case 'follow':
                    // Already handled in the follow requests section
                    break;
            }
        }
      </script>
   </head>
   <body>
<div class="flex bg-white dark:bg-black snipcss-kaeog">
<?php include 'navbar.php'; ?>
<?php include 'postview.php'; ?>
<?php include 'modals/story_modal.php'; ?>
<?php include 'modals/followers_modal.php'; ?>

<!-- Add Ban Confirmation Modal -->
<div id="banConfirmModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50"></div>
    <div class="relative z-50 w-full max-w-md mx-4">
        <div class="bg-white dark:bg-neutral-900 rounded-lg shadow-lg p-6">
            <div class="flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600 dark:text-red-400">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="4.93" y1="19.07" x2="19.07" y2="4.93"></line>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2" id="banConfirmTitle">Ban User</h3>
                <p class="text-neutral-600 dark:text-neutral-400 mb-6" id="banConfirmMessage"></p>
                <div class="flex gap-3 w-full">
                    <button onclick="closeBanConfirmModal()" class="flex-1 px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmBanAction()" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors" id="banConfirmButton">
                        Ban User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="fixed z-40 bg-black/20 inset-y-0 left-[72px] right-0 transition-opacity duration-200 ease-out opacity-0 pointer-events-none"></div>
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="flex flex-col bg-white dark:bg-black">
            <div class="flex flex-col">
                <main class="flex-1 pb-[56px] md:pb-0 bg-white dark:bg-black mt-[72px]">
                    <div class="max-w-[935px] mx-auto">
                        <section class="flex flex-col md:flex-row gap-y-4 px-4 pb-6">
                            <div class="shrink-0 md:w-[290px] md:mr-7 flex justify-center md:justify-center">
                                <div class="cursor-pointer">
                                    <div class="relative">
                                        <?php if (!$is_banned && $has_stories && $can_view_posts && !$has_blocked && !$is_blocked_by): ?>
                                        <div class="<?php echo $all_stories_viewed ? 'story-ring-gray' : 'story-ring'; ?> story-ring-large absolute inset-0 z-0"></div>
                                        <div class="relative rounded-full p-[3px] z-10">
                                            <span class="relative flex shrink-0 overflow-hidden rounded-full w-[86px] h-[86px] md:w-[150px] md:h-[150px] cursor-pointer" onclick="openStoryModal(<?php echo $viewed_user_id; ?>)">
                                        <?php else: ?>
                                        <div class="relative rounded-full p-[3px]">
                                            <span class="relative flex shrink-0 overflow-hidden rounded-full w-[86px] h-[86px] md:w-[150px] md:h-[150px]">
                                        <?php endif; ?>
                                                <div class="relative aspect-square h-full w-full rounded-full">
                                                    <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                        <img alt="<?php echo htmlspecialchars($viewed_username); ?>'s profile picture" referrerpolicy="no-referrer" decoding="async" data-nimg="fill" class="object-cover rounded-full h-full w-full" src="<?php echo htmlspecialchars($viewed_profile_picture); ?>">
                                                    </div>
                                                </div>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col flex-1 max-w-full gap-y-4 md:gap-y-3">
                                <div class="flex flex-col gap-y-4 md:gap-y-3">
                                    <div class="flex flex-col gap-y-3 md:flex-row md:items-center md:gap-x-4">
                                        <div class="flex items-center gap-x-2">
                                            <h2 class="inline-flex items-center gap-x-1.5 text-xl md:text-xl">
                                                <span class="font-semibold"><?php echo htmlspecialchars($viewed_username); ?></span>
                                                <?php if ($isVerified): ?>
                                                <div class="relative inline-block h-4 w-4">
                                                    <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 16px; height: 16px;">
                                                        <title>Verified</title>
                                                        <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($isPrivate): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock w-4 h-4 text-neutral-500">
                                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                </svg>
                                                <?php endif; ?>
                                            </h2>
                                        </div>
                                        <div class="flex items-center gap-x-2 w-full md:w-auto">
                                            <?php if (!$is_banned && $_SESSION['user_id'] !== $viewed_user_id && !$has_blocked && !$is_blocked_by): ?>
                                                <button id="followButton" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm ring-offset-background disabled:pointer-events-none py-2 relative font-semibold transition-all duration-200 px-4 min-w-[120px] hover:scale-[0.98] active:scale-[0.97] disabled:opacity-50 disabled:cursor-not-allowed <?php 
                                                    echo $is_following ? 'bg-neutral-200 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-neutral-300 dark:hover:bg-neutral-700' : 
                                                    ($has_pending_request ? 'bg-neutral-200 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100 hover:bg-red-500 hover:text-white' : 
                                                    'bg-blue-500 hover:bg-blue-600 text-white shadow-sm hover:shadow-md border border-blue-600 hover:border-blue-700'); 
                                                ?> !font-semibold h-9 w-full md:w-auto" data-user-id="<?php echo $viewed_user_id; ?>">
                                                    <span class="flex items-center justify-center gap-1 w-full text-sm"><?php 
                                                        if ($is_following) {
                                                            echo 'Following';
                                                        } elseif ($has_pending_request) {
                                                            echo 'Requested';
                                                        } else {
                                                            echo 'Follow';
                                                        }
                                                    ?></span>
                                                </button>
                                            <?php endif; ?>
                                            <div class="relative">
                                                <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-10 w-10" type="button" onclick="toggleUserMenu()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-more-horizontal h-5 w-5">
                                                        <circle cx="12" cy="12" r="1"></circle>
                                                        <circle cx="19" cy="12" r="1"></circle>
                                                        <circle cx="5" cy="12" r="1"></circle>
                                                    </svg>
                                                </button>
                                                <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-neutral-900 ring-1 ring-black ring-opacity-5 z-50">
                                                    <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                                                        <?php if ($current_user_role === 'Master_Admin'): ?>
                                                            <button onclick="toggleBanUser(<?php echo $viewed_user_id; ?>, '<?php echo htmlspecialchars($viewed_username); ?>', <?php echo $is_banned ? 'true' : 'false'; ?>)" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-neutral-100 dark:hover:bg-neutral-800" role="menuitem">
                                                                <?php echo $is_banned ? 'Unban User' : 'Ban User'; ?>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($_SESSION['user_id'] !== $viewed_user_id): ?>
                                                            <button onclick="toggleBlockUser(<?php echo $viewed_user_id; ?>, '<?php echo htmlspecialchars($viewed_username); ?>', <?php echo $has_blocked ? 'true' : 'false'; ?>)" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-neutral-100 dark:hover:bg-neutral-800" role="menuitem">
                                                                <?php echo $has_blocked ? 'Unblock User' : 'Block User'; ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-around md:justify-start md:gap-x-7 text-sm border-y border-neutral-200 dark:border-neutral-800 py-3 md:border-0 md:py-0">
                                        <div class="flex items-center justify-around w-full md:justify-start md:gap-x-10 text-sm border-y md:border-y-0 border-neutral-200 dark:border-neutral-800 py-3 md:py-0">
                                            <div class="flex flex-col md:flex-row items-center md:items-center gap-0 md:gap-1 transition-all">
                                                <span class="font-semibold text-lg md:text-base"><?php echo $posts_count; ?></span>
                                                <span class="text-neutral-500 dark:text-neutral-400 text-[11px] md:text-sm tracking-wide ml-1">posts</span>
                                            </div>
                                            <?php if ($can_view_posts): ?>
                                            <button onclick="openFollowersModal('followers', <?php echo $viewed_user_id; ?>)" class="flex flex-col md:flex-row items-center md:items-center gap-0 md:gap-1 transition-all hover:opacity-75 active:scale-95 cursor-pointer">
                                                <span class="font-semibold text-lg md:text-base"><?php echo $followers_count; ?></span>
                                                <span class="text-neutral-500 dark:text-neutral-400 text-[11px] md:text-sm tracking-wide ml-1">followers</span>
                                            </button>
                                            <button onclick="openFollowersModal('following', <?php echo $viewed_user_id; ?>)" class="flex flex-col md:flex-row items-center md:items-center gap-0 md:gap-1 transition-all hover:opacity-75 active:scale-95 cursor-pointer">
                                                <span class="font-semibold text-lg md:text-base"><?php echo $following_count; ?></span>
                                                <span class="text-neutral-500 dark:text-neutral-400 text-[11px] md:text-sm tracking-wide ml-1">following</span>
                                            </button>
                                            <?php else: ?>
                                            <div class="flex flex-col md:flex-row items-center md:items-center gap-0 md:gap-1 transition-all">
                                                <span class="font-semibold text-lg md:text-base"><?php echo $followers_count; ?></span>
                                                <span class="text-neutral-500 dark:text-neutral-400 text-[11px] md:text-sm tracking-wide ml-1">followers</span>
                                            </div>
                                            <div class="flex flex-col md:flex-row items-center md:items-center gap-0 md:gap-1 transition-all">
                                                <span class="font-semibold text-lg md:text-base"><?php echo $following_count; ?></span>
                                                <span class="text-neutral-500 dark:text-neutral-400 text-[11px] md:text-sm tracking-wide ml-1">following</span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-y-1">
                                        <span class="font-semibold text-sm"><?php echo htmlspecialchars($viewed_username); ?></span>
                                    <?php if ($viewed_bio): ?>
                                    <p class="text-sm text-neutral-600 dark:text-neutral-400"><?php echo htmlspecialchars($viewed_bio); ?></p>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div class="px-4 md:px-8">
                            <?php if ($is_banned): ?>
                            <div id="bannedProfileContent" class="flex flex-col items-center justify-center py-20 text-center border-t border-neutral-200 dark:border-neutral-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ban w-12 h-12 text-red-500 mb-4">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="4.93" y1="19.07" x2="19.07" y2="4.93"></line>
                                </svg>
                                <h1 class="text-2xl font-semibold mb-2">Acest utilizator este banat</h1>
                                <p class="text-neutral-500 max-w-sm px-4">Acest cont a fost suspendat de către administratori.</p>
                            </div>
                            <?php elseif (!$can_view_posts): ?>
                            <div id="privateProfileContent" class="flex flex-col items-center justify-center py-20 text-center border-t border-neutral-200 dark:border-neutral-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock w-12 h-12 text-neutral-500 mb-4">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <h1 class="text-2xl font-semibold mb-2">This Account is Private</h1>
                                <p class="text-neutral-500 max-w-sm px-4">Follow this account to see their photos and videos.</p>
                            </div>
                            <?php elseif ($is_blocked_by): ?>
                                <div id="blockedByUserContent" class="flex flex-col items-center justify-center py-20 text-center border-t border-neutral-200 dark:border-neutral-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ban w-12 h-12 text-red-500 mb-4">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="4.93" y1="19.07" x2="19.07" y2="4.93"></line>
                                    </svg>
                                    <h1 class="text-2xl font-semibold mb-2">Acest utilizator te-a blocat</h1>
                                    <p class="text-neutral-500 max-w-sm px-4">Nu poți vedea conținutul acestui utilizator.</p>
                                </div>
                            <?php elseif ($has_blocked): ?>
                                <div id="blockedUserContent" class="flex flex-col items-center justify-center py-20 text-center border-t border-neutral-200 dark:border-neutral-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ban w-12 h-12 text-red-500 mb-4">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="4.93" y1="19.07" x2="19.07" y2="4.93"></line>
                                    </svg>
                                    <h1 class="text-2xl font-semibold mb-2">Acest utilizator este blocat</h1>
                                    <p class="text-neutral-500 max-w-sm px-4">Ai blocat acest utilizator.</p>
                                    <button onclick="toggleBlockUser(<?php echo $viewed_user_id; ?>, '<?php echo htmlspecialchars($viewed_username); ?>', true)" class="mt-4 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                                        Deblochează utilizatorul
                                    </button>
                                </div>
                            <?php elseif (!$is_banned && $can_view_posts): ?>
                            <div id="publicProfileContent">
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
                                            <div class="grid grid-cols-3 gap-0.5 md:gap-1 lg:gap-2 mt-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
                                                                        <p class="text-base"><?php echo htmlspecialchars($post['like_count']); ?></p>
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
                                    <div data-state="inactive" data-orientation="horizontal" role="tabpanel" aria-labelledby="radix-«R53qtdjb»-trigger-tagged" hidden="" id="radix-«R53qtdjb»-content-tagged" tabindex="0" class="ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 mt-0.5">
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
                                                                        <p class="text-base"><?php echo htmlspecialchars($post['like_count']); ?></p>
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
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </main>
</div>

<!-- Add Toast Container -->
<ol id="toast-container" class="fixed bottom-8 right-8 z-50 flex flex-col gap-3.5 w-[356px] m-0 p-0 list-none" style="--front-toast-height: 53.5px; --width: 356px; --gap: 14px; --offset-top: 32px; --offset-right: 32px; --offset-bottom: 32px; --offset-left: 32px;"></ol>

<script>
const currentUserId = <?php echo $_SESSION['user_id']; ?>;

// Add WebSocket connection
let ws = null;
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;
const RECONNECT_DELAY = 5000; // 5 seconds

// User menu functions
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('hidden');
}

function toggleBanUser(userId, username, isBanned) {
    // Close the three dots menu first
    const menu = document.getElementById('userMenu');
    menu.classList.add('hidden');
    
    const modal = document.getElementById('banConfirmModal');
    const title = document.getElementById('banConfirmTitle');
    const message = document.getElementById('banConfirmMessage');
    const button = document.getElementById('banConfirmButton');
    
    // Set the modal content based on ban status
    title.textContent = isBanned ? 'Unban User' : 'Ban User';
    message.textContent = isBanned 
        ? `Are you sure you want to unban ${username}? This will restore their account access.`
        : `Are you sure you want to ban ${username}? This will remove all their followers and following relationships.`;
    button.textContent = isBanned ? 'Unban User' : 'Ban User';
    
    // Set direct styles instead of classes
    button.style.backgroundColor = '#dc2626'; // red-600
    button.style.color = '#ffffff'; // white
    button.style.padding = '0.5rem 1rem';
    button.style.borderRadius = '0.375rem';
    button.style.fontWeight = '500';
    button.style.transition = 'background-color 0.2s';
    button.onmouseover = function() {
        this.style.backgroundColor = '#b91c1c'; // red-700
    };
    button.onmouseout = function() {
        this.style.backgroundColor = '#dc2626'; // red-600
    };
    
    // Store the action details
    pendingBanAction = { userId, username, isBanned };
    
    // Show the modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeBanConfirmModal() {
    const modal = document.getElementById('banConfirmModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    pendingBanAction = null;
}

function confirmBanAction() {
    if (!pendingBanAction) return;
    
    const { userId, username, isBanned } = pendingBanAction;
    
    const formData = new FormData();
    formData.append('action', 'ban_user');
    formData.append('user_id', userId);

    fetch('utils/handle_ban.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            // Reload the page to reflect changes
            window.location.reload();
        } else {
            showToast(data.error || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while processing your request');
    })
    .finally(() => {
        closeBanConfirmModal();
    });
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('userMenu');
    const button = event.target.closest('button');
    if (!menu.contains(event.target) && !button?.onclick?.toString().includes('toggleUserMenu')) {
        menu.classList.add('hidden');
    }
});

function connectWebSocket() {
    if (ws && ws.readyState === WebSocket.OPEN) {
        console.log('WebSocket already connected');
        return;
    }

    if (ws) {
        ws.close();
    }

    try {
        const protocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
        ws = new WebSocket(protocol + window.location.hostname + '/ws');

        ws.onopen = function() {
            console.log('WebSocket connected');
            reconnectAttempts = 0;
            
            // Send authentication message
            ws.send(JSON.stringify({
                type: 'auth',
                user_id: currentUserId
            }));
        };

        ws.onclose = function() {
            console.log('WebSocket disconnected');
            if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                setTimeout(connectWebSocket, RECONNECT_DELAY);
                reconnectAttempts++;
            }
        };

        ws.onerror = function(error) {
            console.error('WebSocket error:', error);
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            console.log('Received WebSocket message:', data);

            if (data.type === 'follow_request') {
                // Update follow request count
                const followRequestsCount = document.querySelector('button[onclick="toggleFollowRequests()"] span');
                if (followRequestsCount) {
                    const currentCount = parseInt(followRequestsCount.textContent || '0');
                    followRequestsCount.textContent = currentCount + 1;
                    followRequestsCount.style.display = 'block';
                }

                // Update UI for follow request
                const followButton = document.getElementById('followButton');
                if (followButton && followButton.dataset.userId === data.requester_id) {
                    followButton.textContent = 'Requested';
                    followButton.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600', 'shadow-sm', 'hover:shadow-md', 'border', 'border-blue-600', 'hover:border-blue-700');
                    followButton.classList.add('bg-neutral-200', 'text-neutral-900', 'hover:bg-red-500', 'hover:text-white');
                    
                    // Show toast notification
                    showToast(`${data.requester_username} wants to follow you`);
                }
            } else if (data.type === 'follow') {
                // Update UI for accepted follow request
                const followButton = document.getElementById('followButton');
                if (followButton && followButton.dataset.userId === data.actor_id) {
                    followButton.textContent = 'Following';
                    followButton.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600', 'shadow-sm', 'hover:shadow-md', 'border', 'border-blue-600', 'hover:border-blue-700');
                    followButton.classList.add('bg-neutral-200', 'text-neutral-900', 'hover:bg-blue-500', 'hover:text-white');
                    
                    // Update followers count
                    const followersCount = document.querySelector('button[onclick^="openFollowersModal(\'followers\'"] span:first-child');
                    if (followersCount) {
                        const currentCount = parseInt(followersCount.textContent);
                        followersCount.textContent = currentCount + 1;
                    }
                    
                    // Show toast notification
                    showToast(`${data.actor_username} started following you`);
                }
            }
        };
    } catch (error) {
        console.error('Error creating WebSocket connection:', error);
        if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
            setTimeout(connectWebSocket, RECONNECT_DELAY);
            reconnectAttempts++;
        }
    }
}

// Connect WebSocket when page loads
document.addEventListener('DOMContentLoaded', function() {
    connectWebSocket();
    
    // Load initial notifications
    loadInitialNotifications();
});

// Reconnect WebSocket when window gains focus
window.addEventListener('focus', function() {
    if (!ws || ws.readyState !== WebSocket.OPEN) {
        console.log('Window focused, reconnecting WebSocket...');
        connectWebSocket();
    }
});

// Close WebSocket when window is unloaded
window.addEventListener('beforeunload', function() {
    if (ws) {
        ws.close();
    }
});

function showToast(message) {
    // Remove any existing toasts
    const container = document.getElementById('toast-container');
    if (!container) {
        const newContainer = document.createElement('ol');
        newContainer.id = 'toast-container';
        newContainer.className = 'fixed bottom-8 right-8 z-50 flex flex-col gap-3.5 w-[356px] m-0 p-0 list-none';
        document.body.appendChild(newContainer);
    }

    const toast = document.createElement('li');
    toast.className = 'toast';
    toast.setAttribute('data-type', 'error');
    toast.setAttribute('data-mounted', 'true');
    toast.setAttribute('data-visible', 'true');
    toast.innerHTML = `
        <button aria-label="Close toast" data-close-button="true" class="absolute top-2 right-2 p-1 rounded-full hover:bg-red-100 dark:hover:bg-red-900/20">
            <div class="w-4 h-4 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center text-red-600 dark:text-red-400 group-hover:text-red-700 dark:group-hover:text-red-300">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
        </button>
        <div data-icon class="flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" height="20" width="20" class="text-red-500">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div data-content class="flex-1 text-sm">
            <div data-title class="font-semibold leading-snug text-red-600 dark:text-red-400">${message}</div>
        </div>
    `;

    // Add click handler for close button
    const closeButton = toast.querySelector('button');
    closeButton.addEventListener('click', () => {
        toast.remove();
    });

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

function toggleBlockUser(userId, username, isBlocked) {
    // Close the three dots menu first
    const menu = document.getElementById('userMenu');
    menu.classList.add('hidden');
    
    const formData = new FormData();
    formData.append('action', isBlocked ? 'unblock' : 'block');
    formData.append('user_id', userId);

    fetch('utils/handle_block.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            // Reload the page to reflect changes
            window.location.reload();
        } else {
            showToast(data.error || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while processing your request');
    });
}

// Load initial notifications
async function loadInitialNotifications() {
    try {
        console.log('Loading initial notifications...');
        const response = await fetch('api/notifications.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log('Received notifications:', data);
        
        const notificationsList = document.getElementById('notificationsList');
        if (!notificationsList) {
            console.error('Notifications list element not found');
            return;
        }
        
        // Clear existing notifications
        notificationsList.innerHTML = '';
        
        if (data.notifications && data.notifications.length > 0) {
            data.notifications.forEach(notification => {
                console.log('Processing notification:', notification);
                const notificationElement = createNotificationElement(notification);
                notificationsList.appendChild(notificationElement);
            });
            
            // Update notification count badge
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = data.notifications.length;
                badge.classList.remove('hidden');
            }
        } else {
            notificationsList.innerHTML = `
                <div class="p-4 text-center text-neutral-500 dark:text-neutral-400">
                    Nu ai notificări noi
                </div>
            `;
            
            // Hide notification count badge
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.classList.add('hidden');
            }
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

function createNotificationElement(notification) {
    console.log('Creating notification element:', notification);
    
    const div = document.createElement('div');
    div.className = 'flex items-start gap-3 p-3 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer';
    
    // Add data attributes for notification identification
    if (notification.post_id) {
        div.setAttribute('data-post-id', notification.post_id);
    }
    if (notification.story_id) {
        div.setAttribute('data-story-id', notification.story_id);
    }
    if (notification.actor_id) {
        div.setAttribute('data-actor-id', notification.actor_id);
    }
    div.setAttribute('data-notif-type', notification.type);
    
    // Get the notification icon
    const icon = getNotificationIcon(notification.type);
    
    // Format the notification message
    let message = '';
    const count = parseInt(notification.count) || 1;
    const lastActor = notification.last_actor_username || notification.actor_username;
    
    if (!lastActor) {
        console.error('Missing actor username in notification:', notification);
        return div;
    }
    
    // Format the message based on notification type
    switch (notification.type) {
        case 'like':
            if (count === 1) {
                message = `${lastActor} a apreciat postarea ta`;
            } else {
                message = `${lastActor} si altii ${count - 1} au apreciat postarea ta`;
            }
            break;
        case 'comment':
            if (count === 1) {
                message = `${lastActor} a adaugat un comentariu la postarea ta`;
            } else {
                message = `${lastActor} si altii ${count - 1} au adaugat comentarii la postarea ta`;
            }
            break;
        case 'story_like':
            if (count === 1) {
                message = `${lastActor} a apreciat povestea ta`;
            } else {
                message = `${lastActor} si altii ${count - 1} au apreciat povestea ta`;
            }
            break;
        case 'comment_reply':
            if (count === 1) {
                message = `${lastActor} a raspuns la comentariul tau`;
            } else {
                message = `${lastActor} si altii ${count - 1} au raspuns la comentariul tau`;
            }
            break;
        case 'event':
            message = `Un nou eveniment a fost postat! ${notification.event_name}`;
            break;
        case 'follow_request':
            message = `${lastActor} vrea să te urmărească`;
            break;
        case 'follow':
            message = `${lastActor} a început să te urmărească`;
            break;
        default:
            message = 'Nouă notificare';
    }
    
    // Calculate time difference
    const created = new Date(notification.created_at);
    const now = new Date();
    const timeDiff = Math.floor((now - created) / 1000); // difference in seconds
    
    // Create notification content
    div.innerHTML = `
        <div class="relative h-10 w-10 flex-shrink-0">
            <img alt="${notification.actor_username || 'User'}'s profile picture" 
                 class="rounded-full object-cover h-10 w-10" 
                 src="${notification.actor_profile_picture || './images/profile_placeholder.webp'}">
            <div class="absolute -bottom-1 -right-1 bg-white dark:bg-black rounded-full p-1">
                ${icon}
            </div>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm">${message}</p>
            <p class="text-xs text-neutral-500 dark:text-neutral-400">${formatTimeAgo(timeDiff)}</p>
        </div>
        ${notification.post_image ? `
            <div class="h-10 w-10 flex-shrink-0">
                <img src="${notification.post_image}" alt="Post image" class="h-10 w-10 object-cover rounded">
            </div>
        ` : ''}
        ${notification.story_media ? `
            <div class="h-10 w-10 flex-shrink-0">
                <img src="${notification.story_media}" alt="Story media" class="h-10 w-10 object-cover rounded">
            </div>
        ` : ''}
    `;
    
    // Add click handler based on notification type
    div.onclick = () => handleNotificationClick(notification);
    
    return div;
}

function getNotificationIcon(type) {
    switch (type) {
        case 'like':
            return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                    </svg>`;
        case 'comment':
        case 'comment_reply':
            return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>`;
        case 'story_like':
            return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                    </svg>`;
        case 'event':
            return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                        <line x1="16" x2="16" y1="2" y2="6"></line>
                        <line x1="8" x2="8" y1="2" y2="6"></line>
                        <line x1="3" x2="21" y1="10" y2="10"></line>
                    </svg>`;
        case 'follow':
        case 'follow_request':
            return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>`;
        default:
            return '';
    }
}

function formatTimeAgo(seconds) {
    if (seconds < 60) {
        return 'acum';
    } else if (seconds < 3600) {
        const minutes = Math.floor(seconds / 60);
        return `${minutes}m`;
    } else if (seconds < 86400) {
        const hours = Math.floor(seconds / 3600);
        return `${hours}h`;
    } else if (seconds < 604800) {
        const days = Math.floor(seconds / 86400);
        return `${days}z`;
    } else if (seconds < 2592000) {
        const weeks = Math.floor(seconds / 604800);
        return `${weeks}s`;
    } else if (seconds < 31536000) {
        const months = Math.floor(seconds / 2592000);
        return `${months}l`;
    } else {
        const years = Math.floor(seconds / 31536000);
        return `${years}a`;
    }
}

function handleNotificationClick(notification) {
    // Check if it's a post like notification
    if (notification.type === 'follow' && notification.post_id !== null) {
        notification.type = 'like'; // Convert to like notification if it has a post_id
    }

    switch (notification.type) {
        case 'like':
        case 'comment':
        case 'comment_reply':
            window.location.href = `postview.php?id=${notification.post_id}`;
            break;
        case 'story_like':
            // Handle story view
            break;
        case 'event':
            window.location.href = `events.php?id=${notification.event_id}`;
            break;
        case 'follow':
            // Already handled in the follow requests section
            break;
    }
}
