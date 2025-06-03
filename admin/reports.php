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

// Fetch report statistics
try {
    // Post reports
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM post_reports");
    $postStats = $stmt->fetch();

    // Story reports
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM story_reports");
    $storyStats = $stmt->fetch();

    // User reports
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM user_reports");
    $userStats = $stmt->fetch();

    // Comment reports
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM comment_reports");
    $commentStats = $stmt->fetch();

    // Fetch recent reports (last 10)
    try {
        $stmt = $pdo->query("
            (SELECT 'post' as type, r.report_id, r.reason, r.description, r.status, r.created_at, 
                    u.username as reporter, p.post_id as content_id, p.caption as content_preview,
                    (SELECT username FROM users WHERE user_id = p.user_id) as content_owner
             FROM post_reports r
             JOIN users u ON r.reporter_user_id = u.user_id
             JOIN posts p ON r.post_id = p.post_id)
            UNION ALL
            (SELECT 'story' as type, r.report_id, r.reason, r.description, r.status, r.created_at,
                    u.username as reporter, s.story_id as content_id, 
                    'Story' as content_preview,
                    (SELECT username FROM users WHERE user_id = s.user_id) as content_owner
             FROM story_reports r
             JOIN users u ON r.reporter_user_id = u.user_id
             JOIN stories s ON r.story_id = s.story_id)
            UNION ALL
            (SELECT 'user' as type, r.report_id, r.reason, r.description, r.status, r.created_at,
                    u.username as reporter, r.reported_user_id as content_id, 
                    (SELECT username FROM users WHERE user_id = r.reported_user_id) as content_preview,
                    (SELECT username FROM users WHERE user_id = r.reported_user_id) as content_owner
             FROM user_reports r
             JOIN users u ON r.reporter_user_id = u.user_id)
            UNION ALL
            (SELECT 'comment' as type, r.report_id, r.reason, r.description, r.status, r.created_at,
                    u.username as reporter, c.comment_id as content_id, c.content as content_preview,
                    (SELECT username FROM users WHERE user_id = c.user_id) as content_owner
             FROM comment_reports r
             JOIN users u ON r.reporter_user_id = u.user_id
             JOIN comments c ON r.comment_id = c.comment_id)
            UNION ALL
            (SELECT 'location' as type, r.report_id, r.reason, r.description, r.status, r.created_at,
                    u.username as reporter, r.location as content_id, r.location as content_preview,
                    NULL as content_owner
             FROM location_reports r
             JOIN users u ON r.reporter_user_id = u.user_id)
            ORDER BY created_at DESC
            LIMIT 10");
        $recentReports = $stmt->fetchAll();

        if (empty($recentReports)) {
            $error = "No reports found in the database.";
        }
    } catch (PDOException $e) {
        error_log("Error fetching reports: " . $e->getMessage());
        $error = "Database error: " . $e->getMessage();
    }

} catch (PDOException $e) {
    // Handle database error
    $error = "Database error: " . $e->getMessage();
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
    <title>Rapoarte - Social Land</title>
    <script>
        // Define openStoryModal in global scope
        window.openStoryModal = function(storyId) {
            console.log('[Story Modal] Opening story:', storyId);
            
            // First ensure the story modal is in the DOM
            const storyModalElement = document.getElementById('story-modal');
            if (!storyModalElement) {
                console.error('[Story Modal] Modal element not found');
                console.log('[Story Modal] Available elements:', document.body.innerHTML);
                return;
            }
            console.log('[Story Modal] Found modal element');

            // Show the modal
            storyModalElement.style.display = 'flex';
            document.body.classList.add('no-scroll');
            console.log('[Story Modal] Modal displayed');
            
            // Reset story state
            window.currentStoryIndex = 0;
            window.stories = [];
            
            // Fetch the specific story
            console.log('[Story Modal] Fetching story data...');
            fetch(`../includes/get_story.php?story_id=${storyId}`)
                .then(response => {
                    console.log('[Story Modal] Fetch response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('[Story Modal] Received data:', data);
                    
                    if (data.success && data.story) {
                        console.log('[Story Modal] Story data valid, updating modal');
                        // Set the story in the array
                        window.stories = [data.story];
                        
                        // Update story content
                        const storyImage = document.getElementById('story-image');
                        const storyOwnerAvatar = document.getElementById('story-owner-avatar');
                        const storyOwnerUsername = document.getElementById('story-owner-username');
                        const storyTimestamp = document.getElementById('story-timestamp');
                        const storyVerifiedBadge = document.getElementById('story-owner-verified');
                        
                        console.log('[Story Modal] Found elements:', {
                            storyImage: !!storyImage,
                            storyOwnerAvatar: !!storyOwnerAvatar,
                            storyOwnerUsername: !!storyOwnerUsername,
                            storyTimestamp: !!storyTimestamp,
                            storyVerifiedBadge: !!storyVerifiedBadge
                        });
                        
                        if (storyImage) {
                            const imageUrl = data.story.image_url;
                            storyImage.src = imageUrl;
                            console.log('[Story Modal] Set story image:', imageUrl);
                        } else {
                            console.error('[Story Modal] Story image element not found');
                        }
                        
                        if (storyOwnerAvatar) {
                            const avatarUrl = data.story.profile_picture || '../images/profile_placeholder.webp';
                            storyOwnerAvatar.src = avatarUrl;
                            console.log('[Story Modal] Set avatar:', avatarUrl);
                        } else {
                            console.error('[Story Modal] Story owner avatar element not found');
                        }
                        
                        if (storyOwnerUsername) {
                            storyOwnerUsername.textContent = data.story.username || 'Unknown User';
                            console.log('[Story Modal] Set username:', data.story.username);
                        } else {
                            console.error('[Story Modal] Story owner username element not found');
                        }
                        
                        if (storyTimestamp) {
                            storyTimestamp.textContent = data.story.time_ago || 'Just now';
                            console.log('[Story Modal] Set timestamp:', data.story.time_ago);
                        } else {
                            console.error('[Story Modal] Story timestamp element not found');
                        }
                        
                        if (storyVerifiedBadge) {
                            storyVerifiedBadge.style.display = data.story.verifybadge ? 'inline-flex' : 'none';
                            console.log('[Story Modal] Set verified badge:', data.story.verifybadge);
                        } else {
                            console.error('[Story Modal] Story verified badge element not found');
                        }

                        // Create progress bar
                        const progressBarsContainer = document.getElementById('story-progress-bars');
                        if (progressBarsContainer) {
                            console.log('[Story Modal] Creating progress bar');
                            progressBarsContainer.innerHTML = `
                                <div id="story-progress-bar-0" class="h-1 flex-1 bg-white/30 rounded-full overflow-hidden">
                                    <div class="h-full bg-white transition-all duration-100" style="width: 0%"></div>
                                </div>
                            `;
                        } else {
                            console.error('[Story Modal] Progress bars container not found');
                        }

                        // Start story progress
                        if (typeof startStoryProgress === 'function') {
                            console.log('[Story Modal] Starting story progress');
                            startStoryProgress();
                        } else {
                            console.log('[Story Modal] Using fallback progress animation');
                            // Fallback progress animation
                            const progressBar = document.querySelector('#story-progress-bar-0 .h-full');
                            if (progressBar) {
                                progressBar.style.width = '100%';
                                setTimeout(() => {
                                    closeStoryModal();
                                }, 5000);
                            } else {
                                console.error('[Story Modal] Progress bar element not found');
                            }
                        }

                        // Track story view
                        if (typeof trackStoryView === 'function') {
                            console.log('[Story Modal] Tracking story view');
                            trackStoryView(storyId);
                        } else {
                            console.log('[Story Modal] trackStoryView function not available');
                        }
                    } else {
                        console.error('[Story Modal] Invalid story data:', data);
                        closeStoryModal();
                    }
                })
                .catch(error => {
                    console.error('[Story Modal] Error loading story:', error);
                    closeStoryModal();
                });
        };

        // Add direct click handler for story links
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Story Links] Initializing click handlers');
            document.querySelectorAll('.story-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const storyId = this.getAttribute('data-story-id');
                    if (storyId) {
                        console.log('[Story Links] Direct click detected');
                        console.log('[Story Links] Opening story:', storyId);
                        
                        // First ensure the story modal is in the DOM
                        const storyModalElement = document.getElementById('story-modal');
                        if (!storyModalElement) {
                            console.error('[Story Links] Story modal element not found');
                            return;
                        }
                        console.log('[Story Links] Found modal element');

                        // Show the modal
                        storyModalElement.style.display = 'flex';
                        document.body.classList.add('no-scroll');
                        console.log('[Story Links] Modal displayed');
                        
                        // Reset story state
                        window.currentStoryIndex = 0;
                        window.stories = [];
                        
                        // Fetch the specific story
                        console.log('[Story Links] Fetching story data...');
                        fetch(`../includes/get_story.php?story_id=${storyId}`)
                            .then(response => {
                                console.log('[Story Links] Fetch response status:', response.status);
                                return response.json();
                            })
                            .then(data => {
                                console.log('[Story Links] Received data:', data);
                                
                                if (data.success && data.story) {
                                    console.log('[Story Links] Story data valid, updating modal');
                                    // Set the story in the array
                                    window.stories = [data.story];
                                    
                                    // Update story content
                                    const storyImage = document.getElementById('story-image');
                                    const storyOwnerAvatar = document.getElementById('story-owner-avatar');
                                    const storyOwnerUsername = document.getElementById('story-owner-username');
                                    const storyTimestamp = document.getElementById('story-timestamp');
                                    const storyVerifiedBadge = document.getElementById('story-owner-verified');
                                    
                                    console.log('[Story Links] Found elements:', {
                                        storyImage: !!storyImage,
                                        storyOwnerAvatar: !!storyOwnerAvatar,
                                        storyOwnerUsername: !!storyOwnerUsername,
                                        storyTimestamp: !!storyTimestamp,
                                        storyVerifiedBadge: !!storyVerifiedBadge
                                    });
                                    
                                    if (storyImage) {
                                        const imageUrl = data.story.image_url;
                                        storyImage.src = imageUrl;
                                        console.log('[Story Links] Set story image:', imageUrl);
                                    } else {
                                        console.error('[Story Links] Story image element not found');
                                    }
                                    
                                    if (storyOwnerAvatar) {
                                        const avatarUrl = data.story.profile_picture || '../images/profile_placeholder.webp';
                                        storyOwnerAvatar.src = avatarUrl;
                                        console.log('[Story Links] Set avatar:', avatarUrl);
                                    } else {
                                        console.error('[Story Links] Story owner avatar element not found');
                                    }
                                    
                                    if (storyOwnerUsername) {
                                        storyOwnerUsername.textContent = data.story.username || 'Unknown User';
                                        console.log('[Story Links] Set username:', data.story.username);
                                    } else {
                                        console.error('[Story Links] Story owner username element not found');
                                    }
                                    
                                    if (storyTimestamp) {
                                        storyTimestamp.textContent = data.story.time_ago || 'Just now';
                                        console.log('[Story Links] Set timestamp:', data.story.time_ago);
                                    } else {
                                        console.error('[Story Links] Story timestamp element not found');
                                    }
                                    
                                    if (storyVerifiedBadge) {
                                        storyVerifiedBadge.style.display = data.story.verifybadge ? 'inline-flex' : 'none';
                                        console.log('[Story Links] Set verified badge:', data.story.verifybadge);
                                    } else {
                                        console.error('[Story Links] Story verified badge element not found');
                                    }

                                    // Create progress bar
                                    const progressBarsContainer = document.getElementById('story-progress-bars');
                                    if (progressBarsContainer) {
                                        console.log('[Story Links] Creating progress bar');
                                        progressBarsContainer.innerHTML = `
                                            <div id="story-progress-bar-0" class="h-1 flex-1 bg-white/30 rounded-full overflow-hidden">
                                                <div class="h-full bg-white transition-all duration-100" style="width: 0%"></div>
                                            </div>
                                        `;
                                    } else {
                                        console.error('[Story Links] Progress bars container not found');
                                    }

                                    // Start story progress
                                    if (typeof startStoryProgress === 'function') {
                                        console.log('[Story Links] Starting story progress');
                                        startStoryProgress();
                                    } else {
                                        console.log('[Story Links] Using fallback progress animation');
                                        // Fallback progress animation
                                        const progressBar = document.querySelector('#story-progress-bar-0 .h-full');
                                        if (progressBar) {
                                            progressBar.style.width = '100%';
                                            setTimeout(() => {
                                                closeStoryModal();
                                            }, 5000);
                                        } else {
                                            console.error('[Story Links] Progress bar element not found');
                                        }
                                    }

                                    // Track story view
                                    if (typeof trackStoryView === 'function') {
                                        console.log('[Story Links] Tracking story view');
                                        trackStoryView(storyId);
                                    } else {
                                        console.log('[Story Links] trackStoryView function not available');
                                    }
                                } else {
                                    console.error('[Story Links] Invalid story data:', data);
                                    closeStoryModal();
                                }
                            })
                            .catch(error => {
                                console.error('[Story Links] Error loading story:', error);
                                closeStoryModal();
                            });
                    }
                });
            });
        });

        // Function to open post modal
        function openPostModal(postId) {
            console.log('[Post Modal] Opening post:', postId);
            const modal = document.getElementById('postModal');
            if (!modal) {
                console.error('[Post Modal] Modal element not found');
                return;
            }
            
            // Show the modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            window.currentPostId = postId;
            
            // Fetch post data
            console.log('[Post Modal] Fetching post data...');
            fetch(`../utils/get_post_data.php?post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('[Post Modal] Received data:', data);
                    if (data.success) {
                        const post = data.data;
                        console.log('[Post Modal] Updating modal with post data:', post);
                        
                        // Update post image
                        const modalPostImage = document.getElementById('modalPostImage');
                        if (modalPostImage) {
                            modalPostImage.src = post.image_url;
                            console.log('[Post Modal] Set image URL:', post.image_url);
                        }
                        
                        // Update user info
                        const modalUserProfilePic = document.getElementById('modalUserProfilePic');
                        const modalUsername = document.getElementById('modalUsername');
                        const modalVerifiedBadge = document.getElementById('modalVerifiedBadge');
                        const modalPostCaption = document.getElementById('modalPostCaption');
                        const modalTimeAgo = document.getElementById('modalTimeAgo');
                        
                        if (modalUserProfilePic) {
                            modalUserProfilePic.src = post.profile_picture || '../images/profile_placeholder.webp';
                            console.log('[Post Modal] Set profile picture:', post.profile_picture);
                        }
                        if (modalUsername) {
                            modalUsername.textContent = post.username;
                            modalUsername.href = `../user.php?username=${post.username}`;
                            console.log('[Post Modal] Set username:', post.username);
                        }
                        if (modalVerifiedBadge) {
                            modalVerifiedBadge.style.display = post.verifybadge ? 'block' : 'none';
                            console.log('[Post Modal] Set verified badge:', post.verifybadge);
                        }
                        if (modalPostCaption) {
                            modalPostCaption.textContent = post.caption;
                            console.log('[Post Modal] Set caption:', post.caption);
                        }
                        if (modalTimeAgo) {
                            modalTimeAgo.textContent = post.time_ago;
                            console.log('[Post Modal] Set time ago:', post.time_ago);
                        }

                        // Initialize likes if the function exists
                        if (typeof initializePostviewLikes === 'function') {
                            console.log('[Post Modal] Initializing likes');
                            initializePostviewLikes();
                        }
                    } else {
                        console.error('[Post Modal] Failed to load post data:', data);
                    }
                })
                .catch(error => {
                    console.error('[Post Modal] Error loading post:', error);
                });
        }

        // Function to close post modal
        function closePostModal() {
            console.log('[Post Modal] Closing modal');
            const modal = document.getElementById('postModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                window.currentPostId = null;
            } else {
                console.error('[Post Modal] Modal element not found for closing');
            }
        }

        // Function to close story modal
        function closeStoryModal() {
            console.log('[Story Modal] Closing modal');
            const modal = document.getElementById('story-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.classList.remove('no-scroll');
                window.currentStoryIndex = null;
                // Stop any ongoing story progress
                if (typeof stopStoryProgress === 'function') {
                    console.log('[Story Modal] Stopping story progress');
                    stopStoryProgress();
                } else {
                    console.log('[Story Modal] stopStoryProgress function not available');
                }
            } else {
                console.error('[Story Modal] Modal element not found for closing');
            }
        }

        // Add event listeners for closing modals
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Modals] Initializing event listeners');
            
            // Check if story modal exists
            const storyModal = document.getElementById('story-modal');
            console.log('[Modals] Story modal exists:', !!storyModal);
            if (!storyModal) {
                console.error('[Modals] Story modal not found in DOM');
                console.log('[Modals] Available elements:', document.body.innerHTML);
            }
            
            // Close post modal when clicking outside
            const postModal = document.getElementById('postModal');
            if (postModal) {
                postModal.addEventListener('click', function(event) {
                    if (event.target === postModal) {
                        closePostModal();
                    }
                });
            }

            // Close story modal when clicking outside
            if (storyModal) {
                storyModal.addEventListener('click', function(event) {
                    if (event.target === storyModal) {
                        closeStoryModal();
                    }
                });
            }

            // Close modals on escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closePostModal();
                    closeStoryModal();
                }
            });

            // Close story modal button
            const closeStoryModalButton = document.getElementById('close-story-modal');
            if (closeStoryModalButton) {
                closeStoryModalButton.addEventListener('click', closeStoryModal);
            } else {
                console.error('[Story Modal] Close button not found');
            }
        });

        // Function to delete content (post or story)
        function deleteContent(type, id) {
            const modal = document.getElementById('delete-confirmation-modal');
            const confirmBtn = document.getElementById('confirm-delete-btn');
            const cancelBtn = document.getElementById('cancel-delete-btn');
            
            // Show the modal
            modal.style.display = 'flex';
            
            // Handle confirm button click
            confirmBtn.onclick = function() {
                const endpoint = type === 'post' ? '../utils/admin_delete_post.php' : '../utils/admin_delete_story.php';
                const formData = new FormData();
                formData.append(type === 'post' ? 'post_id' : 'story_id', id);

                fetch(endpoint, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        const row = document.querySelector(`tr[data-${type}-id="${id}"]`);
                        if (row) {
                            row.remove();
                        }
                        // Show success toast
                        if (typeof showToast === 'function') {
                            showToast('Conținut șters cu succes', 'success');
                        }
                        // Hide the modal
                        modal.style.display = 'none';
                        // Reload the page to update statistics
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Show error toast
                        if (typeof showToast === 'function') {
                            showToast(data.error || 'Eroare la ștergerea conținutului', 'error');
                        }
                        // Hide the modal
                        modal.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Eroare la ștergerea conținutului', 'error');
                    }
                    // Hide the modal
                    modal.style.display = 'none';
                });
            };
            
            // Handle cancel button click
            cancelBtn.onclick = function() {
                modal.style.display = 'none';
            };
            
            // Close modal when clicking outside
            modal.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            };
        }
    </script>
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
                <div class="border-b border-neutral-200 dark:border-neutral-700 bg-white dark:bg-black rounded-lg p-6 mb-8 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Rapoarte</h1>
                    </div>
                    <?php include 'tabs.php'; ?>
                </div>

                <!-- Reports Content -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Posts Reports -->
                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-700 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-neutral-900 dark:text-white">Rapoarte Postări</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                                <line x1="3" x2="21" y1="9" y2="9"></line>
                                <line x1="9" x2="9" y1="21" y2="9"></line>
                            </svg>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Postări raportate</span>
                                <span class="font-semibold dark:text-white"><?php echo $postStats['total'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Postări șterse</span>
                                <span class="font-semibold dark:text-white"><?php echo $postStats['resolved'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Postări moderate</span>
                                <span class="font-semibold dark:text-white"><?php echo $postStats['pending'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Stories Reports -->
                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-700 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-neutral-900 dark:text-white">Rapoarte Story</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-500">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                            </svg>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Story raportate</span>
                                <span class="font-semibold dark:text-white"><?php echo $storyStats['total'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Story șterse</span>
                                <span class="font-semibold dark:text-white"><?php echo $storyStats['resolved'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Story moderate</span>
                                <span class="font-semibold dark:text-white"><?php echo $storyStats['pending'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Users Reports -->
                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-700 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-neutral-900 dark:text-white">Rapoarte Utilizatori</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Utilizatori raportați</span>
                                <span class="font-semibold dark:text-white"><?php echo $userStats['total'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Utilizatori blocați</span>
                                <span class="font-semibold dark:text-white"><?php echo $userStats['resolved'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Utilizatori suspendați</span>
                                <span class="font-semibold dark:text-white"><?php echo $userStats['pending'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Comments Reports -->
                    <div class="bg-white dark:bg-black border border-neutral-200 dark:border-neutral-700 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-neutral-900 dark:text-white">Rapoarte Comentarii</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-orange-500">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Comentarii raportate</span>
                                <span class="font-semibold dark:text-white"><?php echo $commentStats['total'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Comentarii șterse</span>
                                <span class="font-semibold dark:text-white"><?php echo $commentStats['resolved'] ?? 0; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-black rounded-lg">
                                <span class="dark:text-white">Comentarii moderate</span>
                                <span class="font-semibold dark:text-white"><?php echo $commentStats['pending'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Reports Table -->
                <div class="mt-8 bg-white dark:bg-black border border-neutral-200 dark:border-neutral-700 rounded-lg p-6">
                    <h3 class="text-xl font-semibold mb-4 text-neutral-900 dark:text-white">Rapoarte Recente</h3>
                    <?php if (isset($error)): ?>
                        <div class="bg-yellow-50 dark:bg-yellow-900/50 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-white px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-neutral-200 dark:border-neutral-800">
                                    <th class="text-left py-3 px-4 dark:text-white">Tip</th>
                                    <th class="text-left py-3 px-4 dark:text-white">Raportat de</th>
                                    <th class="text-left py-3 px-4 dark:text-white">Conținut</th>
                                    <th class="text-left py-3 px-4 dark:text-white">Autor</th>
                                    <th class="text-left py-3 px-4 dark:text-white">Motiv</th>
                                    <th class="text-left py-3 px-4 dark:text-white">Data</th>
                                    <th class="text-left py-3 px-4 dark:text-white">Status</th>
                                    <th class="text-left py-3 px-4 dark:text-white">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentReports)): ?>
                                <tr class="border-b border-neutral-200 dark:border-neutral-800">
                                    <td class="py-3 px-4" colspan="8" class="text-center text-gray-500 dark:text-white">
                                        Nu există rapoarte recente
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recentReports as $report): ?>
                                    <tr class="border-b border-neutral-200 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700 dark:text-white">
                                        <td class="py-3 px-4">
                                            <?php
                                            $typeLabels = [
                                                'post' => 'Postare',
                                                'story' => 'Story',
                                                'user' => 'Utilizator',
                                                'comment' => 'Comentariu',
                                                'location' => 'Locație'
                                            ];
                                            $typeIcons = [
                                                'post' => '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><line x1="3" x2="21" y1="9" y2="9"></line><line x1="9" x2="9" y1="21" y2="9"></line></svg>',
                                                'story' => '<svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>',
                                                'user' => '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                                                'comment' => '<svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
                                                'location' => '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>'
                                            ];
                                            echo '<div class="flex items-center gap-2">' . $typeIcons[$report['type']] . '<span class="dark:text-white">' . $typeLabels[$report['type']] . '</span></div>';
                                            ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <a href="../user.php?username=<?php echo htmlspecialchars($report['reporter']); ?>" 
                                               class="text-blue-600 hover:text-blue-800 dark:text-white dark:hover:text-white">
                                                <?php echo htmlspecialchars($report['reporter']); ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="max-w-xs truncate dark:text-white">
                                                <?php echo htmlspecialchars(substr($report['content_preview'], 0, 100)) . (strlen($report['content_preview']) > 100 ? '...' : ''); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($report['content_owner']): ?>
                                                <a href="../user.php?username=<?php echo htmlspecialchars($report['content_owner']); ?>" 
                                                   class="text-blue-600 hover:text-blue-800 dark:text-white dark:hover:text-white">
                                                    <?php echo htmlspecialchars($report['content_owner']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-500 dark:text-white">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="max-w-xs">
                                                <div class="font-medium dark:text-white"><?php echo htmlspecialchars($report['reason']); ?></div>
                                                <?php if ($report['description']): ?>
                                                    <div class="text-sm text-gray-600 dark:text-white">
                                                        <?php echo htmlspecialchars(substr($report['description'], 0, 50)) . (strlen($report['description']) > 50 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 dark:text-white"><?php echo date('d.m.Y H:i', strtotime($report['created_at'])); ?></td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $statusClasses = [
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-white',
                                                'reviewed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-white',
                                                'resolved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-white',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-white'
                                            ];
                                            $statusLabels = [
                                                'pending' => 'În așteptare',
                                                'reviewed' => 'Revizuit',
                                                'resolved' => 'Rezolvat',
                                                'rejected' => 'Respins'
                                            ];
                                            $statusClass = $statusClasses[$report['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
                                            $statusLabel = $statusLabels[$report['status']] ?? ucfirst($report['status']);
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo $statusLabel; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php
                                            $contentUrl = '';
                                            $onClick = '';
                                            $dataStoryId = null;
                                            switch ($report['type']) {
                                                case 'post':
                                                    $contentUrl = "javascript:void(0)";
                                                    $onClick = "openPostModal('" . $report['content_id'] . "')";
                                                    break;
                                                case 'story':
                                                    $contentUrl = "javascript:void(0)";
                                                    $onClick = "";
                                                    $dataStoryId = $report['content_id'];
                                                    break;
                                                case 'user':
                                                    // Get the username for the reported user
                                                    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
                                                    $stmt->execute([$report['content_id']]);
                                                    $user = $stmt->fetch();
                                                    if ($user) {
                                                        $contentUrl = "http://localhost/user.php?username=" . urlencode($user['username']);
                                                        $onClick = "";
                                                    }
                                                    break;
                                                case 'comment':
                                                    // For comments, we need to get the post ID first
                                                    $stmt = $pdo->prepare("SELECT c.post_id, c.user_id FROM comments c WHERE c.comment_id = ?");
                                                    $stmt->execute([$report['content_id']]);
                                                    $comment = $stmt->fetch();
                                                    if ($comment) {
                                                        $contentUrl = "javascript:void(0)";
                                                        $onClick = "openPostModal('" . $comment['post_id'] . "')";
                                                    }
                                                    break;
                                                case 'location':
                                                    $contentUrl = "http://localhost/location.php?location=" . urlencode($report['content_id']);
                                                    $onClick = "";
                                                    break;
                                            }
                                            ?>
                                            <div class="flex gap-2">
                                                <?php if ($contentUrl): ?>
                                                    <a href="<?php echo $contentUrl; ?>" 
                                                       <?php if ($onClick): ?>onclick="<?php echo $onClick; ?>"<?php endif; ?>
                                                       <?php if (isset($dataStoryId)): ?>data-story-id="<?php echo $dataStoryId; ?>"<?php endif; ?>
                                                       class="text-blue-600 hover:text-blue-800 dark:text-white dark:hover:text-white story-link">
                                                        Vezi conținutul
                                                    </a>
                                                <?php endif; ?>
                                                <a href="review_report.php?type=<?php echo $report['type']; ?>&id=<?php echo $report['content_id']; ?>" 
                                                   class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                                    Revizuiește
                                                </a>
                                                <?php if (in_array($report['type'], ['post', 'story'])): ?>
                                                    <button onclick="deleteContent('<?php echo $report['type']; ?>', <?php echo $report['content_id']; ?>)" 
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                        Șterge
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php 
    // Include necessary modals
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<!-- Loading postview.php -->\n";
    if (!include '../postview.php') {
        echo "<!-- Error loading postview.php -->\n";
    }
    
    echo "<!-- Loading story_modal.php -->\n";
    if (!include '../modals/story_modal.php') {
        echo "<!-- Error loading story_modal.php -->\n";
    }
    echo "<!-- Finished loading modals -->\n";
    ?>

    <!-- Delete Confirmation Modal -->
    <div id="delete-confirmation-modal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none; background-color: rgba(0, 0, 0, 0.7);">
        <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-black text-left shadow-xl transition-all p-6">
            <div class="flex items-center justify-center mb-4 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-center mb-2 text-neutral-900 dark:text-white">Șterge Conținut</h3>
            <p class="text-sm text-neutral-600 dark:text-white text-center mb-4">Sigur doriți să ștergeți acest conținut? Această acțiune nu poate fi anulată.</p>
            
            <div class="flex justify-center gap-3 mt-6">
                <button type="button" id="cancel-delete-btn" class="px-4 py-2 bg-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 dark:bg-black dark:text-white dark:hover:bg-neutral-600 dark:focus:ring-neutral-700">
                    Anulează
                </button>
                <button type="button" id="confirm-delete-btn" class="px-4 py-2 rounded-lg" style="background-color: red !important; color: white !important; display: inline-block !important;">
                    Șterge
                </button>
            </div>
        </div>
    </div>
</body>
</html>
