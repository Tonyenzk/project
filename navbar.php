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

// Verify if user still exists in database and get verification status, role, and ban status
try {
    $stmt = $pdo->prepare("SELECT user_id, verifybadge, role, is_banned FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        // User no longer exists in database
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    // Check user role for admin privileges
    $userRole = $user['role'] ?? 'User';
    $isAdmin = in_array($userRole, ['Moderator', 'Admin', 'Master_Admin']);
    
    // Get current script name to avoid redirect loops
    $currentScript = basename($_SERVER['SCRIPT_NAME']);
    
    // Check if user is banned and not an admin
    if (isset($user['is_banned']) && $user['is_banned'] && !$isAdmin) {
        // User is banned and is not an admin - redirect to banned page
        if ($currentScript !== 'banned.php') {
            header("Location: banned.php");
            exit();
        }
    }
    
    // Check if maintenance mode is enabled
    if ($currentScript !== 'maintenance.php' && $currentScript !== 'login.php') {
        $maintenanceStmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_name = 'maintenance_mode'");
        $maintenanceStmt->execute();
        $maintenanceMode = $maintenanceStmt->fetch(PDO::FETCH_ASSOC);
        
        // If maintenance mode is enabled and user is not an admin, redirect to maintenance page
        if ($maintenanceMode && filter_var($maintenanceMode['setting_value'], FILTER_VALIDATE_BOOLEAN) && !$isAdmin) {
            header("Location: maintenance.php");
            exit();
        }
    }
    
    $isVerified = $user['verifybadge'] === 'true';
    $isPending = $user['verifybadge'] === 'pending';
} catch (PDOException $e) {
    // If there's a database error, log out the user for security
    session_destroy();
    header("Location: login.php");
    exit();
}


// Fetch user's profile picture if logged in
$profile_picture = './images/profile_placeholder.webp';
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        if ($result && $result['profile_picture']) {
            $profile_picture = $result['profile_picture'];
        }
    } catch (PDOException $e) {
        // If there's an error, use default profile picture
    }
}
?>
<?php
// Check if we're being included from the admin section
$isAdminSection = isset($isAdminSection) && $isAdminSection === true;

// Set path prefix based on whether we're in admin section
$pathPrefix = $isAdminSection ? '../' : '';
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <?php if(!$isAdminSection): // Don't duplicate these in admin pages since they already have them ?>
      <link rel="stylesheet" href="<?php echo $pathPrefix; ?>css/common.css">
      <link rel="stylesheet" href="<?php echo $pathPrefix; ?>css/toast.css">
      <script src="<?php echo $pathPrefix; ?>assets/js/toast-handler.js"></script>
      <script src="<?php echo $pathPrefix; ?>assets/js/search.js"></script>
      <?php endif; ?>
      <script>
         // Create a global event handler that will be available immediately
         window.handleFollowRequestCanceled = function(userId) {
            console.log('Handling follow request cancellation for user:', userId);
            
            // Find and remove the notification for this user
            const requestElement = document.querySelector(`[data-request-id="${userId}"]`);
            console.log('Found request element:', requestElement);
            
            if (requestElement) {
                // Add fade out animation
                requestElement.style.transition = 'opacity 0.3s ease-out';
                requestElement.style.opacity = '0';
                
                // Remove element after animation
                setTimeout(() => {
                    requestElement.remove();
                    
                    // Check if there are any remaining requests
                    const remainingRequests = document.querySelectorAll('[data-request-id]');
                    if (remainingRequests.length === 0) {
                        // If no requests left, show the "No follow requests" message
                        const followRequestsSection = document.getElementById('followRequestsSection');
                        const noRequestsMessage = document.createElement('p');
                        noRequestsMessage.className = 'text-sm text-neutral-500 dark:text-neutral-400 text-center py-4';
                        noRequestsMessage.textContent = 'Nu ai cereri de urmărire';
                        followRequestsSection.appendChild(noRequestsMessage);
                    }
                    
                    // Update count badge
                    const countBadge = document.querySelector('button[onclick="toggleFollowRequests()"] span');
                    if (countBadge) {
                        const currentCount = parseInt(countBadge.textContent);
                        const newCount = Math.max(0, currentCount - 1);
                        countBadge.textContent = newCount;
                        
                        // If count reaches 0, hide the count badge
                        if (newCount === 0) {
                            countBadge.style.display = 'none';
                        }
                    }
                }, 300);
            } else {
                console.warn('Request element not found for user:', userId);
            }
         };

         // Add polling mechanism to check for follow request changes
         function startFollowRequestPolling() {
            let lastCheck = Date.now();
            let isPolling = true;
            
            function checkFollowRequests() {
                if (!isPolling) return;
                
                fetch('utils/get_follow_requests.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            const currentRequests = new Set(data.requests.map(req => req.user_id));
                            const existingRequests = document.querySelectorAll('[data-request-id]');
                            
                            // Check for removed requests
                            existingRequests.forEach(request => {
                                const requestId = request.dataset.requestId;
                                if (!currentRequests.has(parseInt(requestId))) {
                                    window.handleFollowRequestCanceled(requestId);
                                }
                            });
                            
                            // Update count badge
                            const countBadge = document.querySelector('button[onclick="toggleFollowRequests()"] span');
                            if (countBadge) {
                                const newCount = data.requests.length;
                                countBadge.textContent = newCount;
                                countBadge.style.display = newCount > 0 ? 'block' : 'none';
                            }
                        } else {
                            console.error('Error in follow requests data:', data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error checking follow requests:', error);
                        // If there's an error, stop polling
                        isPolling = false;
                    });
            }
            
            // Check every 2 seconds
            const pollInterval = setInterval(checkFollowRequests, 2000);
            
            // Cleanup function to stop polling
            window.stopFollowRequestPolling = function() {
                isPolling = false;
                clearInterval(pollInterval);
            };
         }

         // Start polling when the page loads
         document.addEventListener('DOMContentLoaded', startFollowRequestPolling);

         // Add event listener for follow request cancellation
         document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('followRequestCanceled', function(event) {
                console.log('Received followRequestCanceled event for user:', event.detail.userId);
                window.handleFollowRequestCanceled(event.detail.userId);
            });
            
            // Start polling when the notifications sidebar is opened
            const notificationsButton = document.querySelector('button[onclick="toggleNotifications()"]');
            if (notificationsButton) {
                notificationsButton.addEventListener('click', function() {
                    startFollowRequestPolling();
                });
            }
         });
         

            // Function to handle navbar collapse based on screen width
            function handleNavbarCollapse() {
               const navbar = document.querySelector('nav');
               const navTexts = document.querySelectorAll('.nav-text');
               const navItems = document.querySelectorAll('nav a, nav button');
               const fullLogo = document.querySelector('h1.nav-text');
               const shortLogo = document.querySelector('h1.collapsed-text');

               if (!navbar || !navTexts || !navItems || !fullLogo || !shortLogo) {
                  return; // Exit if any required elements are not found
               }

               if (window.innerWidth <= 1024) {
                  // Collapse navbar
                  navbar.classList.remove('w-[240px]');
                  navbar.classList.add('w-[72px]');
                  
                  // Hide text
                  navTexts.forEach(text => {
                     text.classList.add('hidden');
                  });
                  
                  // Show short logo
                  fullLogo.classList.add('hidden');
                  shortLogo.classList.remove('hidden');
                  
                  // Center icons
                  navItems.forEach(item => {
                     item.classList.add('justify-center');
                     item.classList.remove('justify-start');
                  });
               } else {
                  // Expand navbar
                  navbar.classList.remove('w-[72px]');
                  navbar.classList.add('w-[240px]');
                  
                  // Show text
                  navTexts.forEach(text => {
                     text.classList.remove('hidden');
                  });
                  
                  // Show full logo
                  fullLogo.classList.remove('hidden');
                  shortLogo.classList.add('hidden');
                  
                  // Align icons to start
                  navItems.forEach(item => {
                     item.classList.remove('justify-center');
                     item.classList.add('justify-start');
                  });
               }
            }

            // Initialize when DOM is loaded
            document.addEventListener('DOMContentLoaded', function() {
               // Initial call
               handleNavbarCollapse();

               // Add resize listener
               window.addEventListener('resize', handleNavbarCollapse);
            });

         // Add event listener for clicks outside sidebars
         document.addEventListener('click', function(event) {
            const navbar = document.querySelector('nav');
            const searchSidebar = document.querySelector('[data-search-sidebar]');
            const notificationsSidebar = document.querySelector('[data-notifications-sidebar]');
            const navTexts = document.querySelectorAll('.nav-text');
            const navItems = document.querySelectorAll('nav a, nav button');
            const fullLogo = document.querySelector('h1.nav-text');
            const shortLogo = document.querySelector('h1.collapsed-text');
            
            // Check if click is outside both sidebars and navbar
            const isClickInsideNavbar = navbar.contains(event.target);
            const isClickInsideSearchSidebar = searchSidebar && searchSidebar.contains(event.target);
            const isClickInsideNotificationsSidebar = notificationsSidebar && notificationsSidebar.contains(event.target);
            
            // If click is outside and a sidebar is open, close it
            if (!isClickInsideNavbar && !isClickInsideSearchSidebar && !isClickInsideNotificationsSidebar) {
               // Restore navbar width
               navbar.classList.remove('w-[72px]');
               navbar.classList.add('w-[240px]');
               
               // Show text
               navTexts.forEach(text => {
                  text.classList.remove('hidden');
               });
               
               // Restore logo text
               fullLogo.classList.remove('hidden');
               shortLogo.classList.add('hidden');
               
               // Restore icon alignment
               navItems.forEach(item => {
                  item.classList.remove('justify-center');
                  item.classList.add('justify-start');
               });
               
               // Hide search sidebar if open
               if (searchSidebar && searchSidebar.classList.contains('translate-x-0')) {
                  searchSidebar.classList.remove('translate-x-0');
                  searchSidebar.classList.add('-translate-x-full');
               }
               
               // Hide notifications sidebar if open
               if (notificationsSidebar && notificationsSidebar.classList.contains('translate-x-0')) {
                  notificationsSidebar.classList.remove('translate-x-0');
                  notificationsSidebar.classList.add('-translate-x-full');
               }
            }
         });

         function toggleSearch() {
            const navbar = document.querySelector('nav');
            const searchSidebar = document.querySelector('[data-search-sidebar]');
            const notificationsSidebar = document.querySelector('[data-notifications-sidebar]');
            const navTexts = document.querySelectorAll('.nav-text');
            const navItems = document.querySelectorAll('nav a, nav button');
            const fullLogo = document.querySelector('h1.nav-text');
            const shortLogo = document.querySelector('h1.collapsed-text');
            
            // If notifications is open, close it first
            if (notificationsSidebar && notificationsSidebar.classList.contains('translate-x-0')) {
                notificationsSidebar.classList.remove('translate-x-0');
                notificationsSidebar.classList.add('-translate-x-full');
            }
            
            // Toggle search sidebar
            searchSidebar.classList.toggle('translate-x-0');
            searchSidebar.classList.toggle('-translate-x-full');
            
            // Always keep navbar collapsed when any sidebar is open
            if (searchSidebar.classList.contains('translate-x-0') || 
                (notificationsSidebar && notificationsSidebar.classList.contains('translate-x-0'))) {
                navbar.classList.remove('w-[240px]');
                navbar.classList.add('w-[72px]');
                
                // Hide text
                navTexts.forEach(text => {
                    text.classList.add('hidden');
                });
                
                // Show short logo
                fullLogo.classList.add('hidden');
                shortLogo.classList.remove('hidden');
                
                // Center icons
                navItems.forEach(item => {
                    item.classList.add('justify-center');
                    item.classList.remove('justify-start');
                });
            } else {
                // Only expand navbar if no sidebars are open
                navbar.classList.remove('w-[72px]');
                navbar.classList.add('w-[240px]');
                
                // Show text
                navTexts.forEach(text => {
                    text.classList.remove('hidden');
                });
                
                // Show full logo
                fullLogo.classList.remove('hidden');
                shortLogo.classList.add('hidden');
                
                // Align icons to start
                navItems.forEach(item => {
                    item.classList.remove('justify-center');
                    item.classList.add('justify-start');
                });
            }
            
            // If opening search, clear and focus the search input
            if (searchSidebar.classList.contains('translate-x-0')) {
                const searchInput = searchSidebar.querySelector('input');
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                    
                    // Clear search results
                    const searchResults = searchSidebar.querySelector('[data-search-results]');
                    if (searchResults) {
                        searchResults.style.display = 'none';
                        searchResults.innerHTML = '';
                    }
                    
                    // Show recent searches
                    const recentSearchesContainer = searchSidebar.querySelector('[data-recent-searches-container]');
                    if (recentSearchesContainer) {
                        recentSearchesContainer.style.display = 'block';
                    }
                }
            }
         }

         function toggleNotifications() {
            const notificationsSidebar = document.querySelector('[data-notifications-sidebar]');
            const searchSidebar = document.querySelector('[data-search-sidebar]');
            const navbar = document.querySelector('nav');
            const navTexts = document.querySelectorAll('.nav-text');
            const navItems = document.querySelectorAll('nav a, nav button');
            const fullLogo = document.querySelector('h1.nav-text');
            const shortLogo = document.querySelector('h1.collapsed-text');
            
            // If search is open, close it first
            if (searchSidebar && searchSidebar.classList.contains('translate-x-0')) {
                searchSidebar.classList.remove('translate-x-0');
                searchSidebar.classList.add('-translate-x-full');
                
                // Clear search input and results
                const searchInput = searchSidebar.querySelector('input');
                if (searchInput) {
                    searchInput.value = '';
                }
                
                const searchResults = searchSidebar.querySelector('[data-search-results]');
                if (searchResults) {
                    searchResults.style.display = 'none';
                    searchResults.innerHTML = '';
                }
            }
            
            // Toggle notifications sidebar
            notificationsSidebar.classList.toggle('translate-x-0');
            notificationsSidebar.classList.toggle('-translate-x-full');
            
            // Always keep navbar collapsed when any sidebar is open
            if (notificationsSidebar.classList.contains('translate-x-0') || 
                (searchSidebar && searchSidebar.classList.contains('translate-x-0'))) {
                navbar.classList.remove('w-[240px]');
                navbar.classList.add('w-[72px]');
                
                // Hide text
                navTexts.forEach(text => {
                    text.classList.add('hidden');
                });
                
                // Show short logo
                fullLogo.classList.add('hidden');
                shortLogo.classList.remove('hidden');
                
                // Center icons
                navItems.forEach(item => {
                    item.classList.add('justify-center');
                    item.classList.remove('justify-start');
                });
            } else {
                // Only expand navbar if no sidebars are open
                navbar.classList.remove('w-[72px]');
                navbar.classList.add('w-[240px]');
                
                // Show text
                navTexts.forEach(text => {
                    text.classList.remove('hidden');
                });
                
                // Show full logo
                fullLogo.classList.remove('hidden');
                shortLogo.classList.add('hidden');
                
                // Align icons to start
                navItems.forEach(item => {
                    item.classList.remove('justify-center');
                    item.classList.add('justify-start');
                });
            }
            
            // If opening notifications, mark them as read and load initial notifications
            if (notificationsSidebar.classList.contains('translate-x-0')) {
                // Mark notifications as read
                fetch('api/notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=mark_read'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        notificationCount = 0;
                        updateNotificationBadge();
                    }
                })
                .catch(error => console.error('Error marking notifications as read:', error));
                
                // Load initial notifications
                loadInitialNotifications();
            }
         }

         function closeSearch() {
            const navbar = document.querySelector('nav');
            const searchSidebar = document.querySelector('[data-search-sidebar]');
            const navTexts = document.querySelectorAll('.nav-text');
            const navItems = document.querySelectorAll('nav a, nav button');
            const fullLogo = document.querySelector('h1.nav-text');
            const shortLogo = document.querySelector('h1.collapsed-text');
            
            // Restore navbar width
            navbar.classList.remove('w-[72px]');
            navbar.classList.add('w-[240px]');
            
            // Show text
            navTexts.forEach(text => {
               text.classList.remove('hidden');
            });
            
            // Restore logo text
            fullLogo.classList.remove('hidden');
            shortLogo.classList.add('hidden');
            
            // Restore icon alignment
            navItems.forEach(item => {
               item.classList.remove('justify-center');
               item.classList.add('justify-start');
            });
            
            // Hide search sidebar
            if (searchSidebar) {
               searchSidebar.classList.remove('translate-x-0');
               searchSidebar.classList.add('-translate-x-full');
            }
         }

         function closeNotifications() {
            const navbar = document.querySelector('nav');
            const notificationsSidebar = document.querySelector('[data-notifications-sidebar]');
            const navTexts = document.querySelectorAll('.nav-text');
            const navItems = document.querySelectorAll('nav a, nav button');
            const fullLogo = document.querySelector('h1.nav-text');
            const shortLogo = document.querySelector('h1.collapsed-text');
            
            // Restore navbar width
            navbar.classList.remove('w-[72px]');
            navbar.classList.add('w-[240px]');
            
            // Show text
            navTexts.forEach(text => {
               text.classList.remove('hidden');
            });
            
            // Restore logo text
            fullLogo.classList.remove('hidden');
            shortLogo.classList.add('hidden');
            
            // Restore icon alignment
            navItems.forEach(item => {
               item.classList.remove('justify-center');
               item.classList.add('justify-start');
            });
            
            // Hide notifications sidebar
            if (notificationsSidebar) {
               notificationsSidebar.classList.remove('translate-x-0');
               notificationsSidebar.classList.add('-translate-x-full');
            }
         }

         // Add More dropdown functionality
         document.addEventListener('DOMContentLoaded', function() {
            const moreButton = document.getElementById('more-button');
            const moreDropdown = document.getElementById('more-dropdown');
            let isDropdownOpen = false;

            // Toggle dropdown
            moreButton.addEventListener('click', function(event) {
               event.stopPropagation();
               isDropdownOpen = !isDropdownOpen;
               moreDropdown.classList.toggle('hidden');
               moreButton.setAttribute('aria-expanded', isDropdownOpen);
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
               if (isDropdownOpen && !moreDropdown.contains(event.target) && !moreButton.contains(event.target)) {
                  isDropdownOpen = false;
                  moreDropdown.classList.add('hidden');
                  moreButton.setAttribute('aria-expanded', 'false');
               }
            });
         });

         function handleFollowRequest(userId, action) {
            // Find the request element by the user ID
            const requestElement = document.querySelector(`[data-request-id="${userId}"]`);
            if (!requestElement) {
                console.error('Request element not found:', userId);
                return;
            }

            fetch('utils/handle_follow_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the request element with a fade out animation
                    requestElement.style.transition = 'opacity 0.3s ease-out';
                    requestElement.style.opacity = '0';
                    
                    setTimeout(() => {
                        requestElement.remove();
                        
                        // Check if there are any remaining requests
                        const remainingRequests = document.querySelectorAll('[data-request-id]');
                        if (remainingRequests.length === 0) {
                            // If no requests left, show the "No follow requests" message
                            const followRequestsSection = document.getElementById('followRequestsSection');
                            const noRequestsMessage = document.createElement('p');
                            noRequestsMessage.className = 'text-sm text-neutral-500 dark:text-neutral-400 text-center py-4';
                            noRequestsMessage.textContent = 'Nu ai cereri de urmărire';
                            followRequestsSection.appendChild(noRequestsMessage);
                        }
                    }, 300);
                    
                    // Update the follow requests count in the button
                    const countBadge = document.querySelector('button[onclick="toggleFollowRequests()"] span');
                    if (countBadge) {
                        const currentCount = parseInt(countBadge.textContent);
                        const newCount = Math.max(0, currentCount - 1);
                        countBadge.textContent = newCount;
                        
                        // If count reaches 0, hide the count badge
                        if (newCount === 0) {
                            countBadge.style.display = 'none';
                        }
                    }
                    
                    // If accepted, update the followers count
                    if (action === 'accept') {
                        const followersButton = document.querySelector('button[onclick^="openFollowersModal(\'followers\'"]');
                        if (followersButton) {
                            const followersCount = followersButton.querySelector('span:first-child');
                            if (followersCount) {
                                const currentCount = parseInt(followersCount.textContent);
                                followersCount.textContent = currentCount + 1;
                            }
                        }
                    }
                } else {
                    showToast(data.error || 'A apărut o eroare. Vă rugăm să încercați din nou.');
                }
            })
            .catch(error => {
                console.error('Error handling follow request:', error);
                showToast('A apărut o eroare. Vă rugăm să încercați din nou.');
            });
         }

         function toggleFollowRequests() {
            const followRequestsSection = document.getElementById('followRequestsSection');
            const allNotificationsSection = document.getElementById('allNotificationsSection');
            
            if (followRequestsSection.style.display === 'none') {
               followRequestsSection.style.display = 'block';
               allNotificationsSection.style.display = 'none';
            } else {
               followRequestsSection.style.display = 'none';
               allNotificationsSection.style.display = 'block';
            }
         }



         // Initialize the status text when the page loads
         document.addEventListener('DOMContentLoaded', function() {
            updateCommentsStatus();
            // ... rest of your existing DOMContentLoaded code ...
         });

         // Add this JavaScript function
         function preventSpecialChars(event) {
            // Allow: backspace, delete, tab, escape, enter
            if ([8, 9, 13, 27, 46].indexOf(event.keyCode) !== -1 ||
               // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
               (event.keyCode >= 35 && event.keyCode <= 39) ||
               (event.ctrlKey && [65, 67, 86, 88].indexOf(event.keyCode) !== -1)) {
               return true;
            }
            
            // Prevent line breaks
            if (event.keyCode === 13) {
               event.preventDefault();
               return false;
            }
            
            // Allow only letters, numbers, spaces, and basic punctuation
            const regex = /^[a-zA-Z0-9\s.,!?-]*$/;
            if (!regex.test(event.key)) {
               event.preventDefault();
               return false;
            }
            
            return true;
         }

         // Update the character count function
         document.addEventListener('DOMContentLoaded', function() {
            const postDescriptionInput = document.getElementById('postDescriptionInput');
            const descriptionCharCount = document.getElementById('descriptionCharCount');
            
            if (postDescriptionInput && descriptionCharCount) {
               postDescriptionInput.addEventListener('input', function() {
                  // Remove any special characters that might have been pasted
                  this.value = this.value.replace(/[^a-zA-Z0-9\s.,!?-]/g, '');
                  // Remove line breaks
                  this.value = this.value.replace(/[\r\n]+/g, ' ');
                  
                  const remaining = 250 - this.value.length;
                  descriptionCharCount.textContent = `${remaining} characters remaining`;
               });
            }
            // ... rest of your existing DOMContentLoaded code ...
         });



      </script>
   </head>
   <body>
<nav class="fixed inset-y-0 left-0 z-50 h-full w-[240px] flex-col bg-white dark:bg-black border-r border-neutral-200 dark:border-neutral-800 shadow-lg transition-all duration-300 ease-in-out max-md:hidden flex snipcss-JIVHE">
    <a class="p-6 justify-start flex items-center" href="dashboard.php">
        <h1 class="font-bold text-xl nav-text">Social Land</h1>
        <h1 class="font-bold text-xl hidden collapsed-text">
            <img src="favicon.ico" alt="Social Land" class="h-6 w-6">
        </h1>
    </a>
    <div class="flex-1 flex flex-col gap-1 px-3">

        <a class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4" href="dashboard.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-home w-5 h-5 min-w-[20px]">
                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span class="text-sm tracking-wide whitespace-nowrap nav-text">Acasă</span>
        </a>
        <a class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4" href="explore.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-compass w-5 h-5 min-w-[20px]">
                <circle cx="12" cy="12" r="10"></circle>
                <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>
            </svg><span class="text-sm tracking-wide whitespace-nowrap nav-text">Explorează</span></a><a class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4" href="events.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-5 h-5 min-w-[20px]">
                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                <line x1="16" x2="16" y1="2" y2="6"></line>
                <line x1="8" x2="8" y1="2" y2="6"></line>
                <line x1="3" x2="21" y1="10" y2="10"></line>
            </svg><span class="text-sm tracking-wide whitespace-nowrap nav-text">Evenimente</span></a><button onclick="toggleSearch()" class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4">
            <div class="relative"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search w-6 h-6 min-w-[24px]">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg></div><span class="text-sm tracking-wide whitespace-nowrap nav-text">Caută</span>
        </button><button onclick="toggleNotifications()" class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4">
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-6 h-6 min-w-[24px]">
            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
        </svg>
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center" style="display: none;"></span>
    </div>
    <span class="text-sm tracking-wide whitespace-nowrap nav-text">Notificări</span>
</button>
                <!-- Create Button -->
                <?php if ($isAdmin): ?>
                <!-- Admin Tab - Only visible for moderators and admins -->
                <a href="admin/dashboard.php" class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield w-5 h-5 min-w-[20px]">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    <span class="text-sm tracking-wide whitespace-nowrap nav-text">Admin</span>
                </a>
                <?php endif; ?>
                <button onclick="openCreateModal()" class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 min-w-[20px]">
                        <path d="M5 12h14"></path>
                        <path d="M12 5v14"></path>
                    </svg>
                    <span class="text-sm tracking-wide whitespace-nowrap nav-text">Create</span>
                </button></div>
    <div class="flex flex-col gap-1 mt-auto p-3 border-t border-neutral-200 dark:border-neutral-800">
        <a class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4" href="profile.php">
            <span class="relative flex shrink-0 overflow-hidden rounded-full h-8 w-8">
                <div class="relative aspect-square h-full w-full rounded-full">
                    <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                        <img alt="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>'s profile picture" referrerpolicy="no-referrer" decoding="async" data-nimg="fill" class="object-cover rounded-full h-8 w-8" src="<?php echo htmlspecialchars($profile_picture); ?>">
                    </div>
                </div>
            </span>
            <div class="text-sm tracking-wide whitespace-nowrap nav-text">
                <div class="flex items-center gap-1">
                    <span class="font-medium truncate"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <?php if ($isVerified): ?>
                    <div class="relative inline-block h-4 w-4 nav-text">
                        <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 16px; height: 16px;">
                            <title>Verified</title>
                            <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <div class="relative">
            <button class="whitespace-nowrap text-sm font-medium ring-offset-background disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-11 rounded-md w-full flex items-center py-3 transition-all duration-200 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 justify-start px-4 gap-4" type="button" id="more-button" aria-haspopup="menu" aria-expanded="false" data-state="closed">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-more-horizontal w-5 h-5 min-w-[20px]">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="19" cy="12" r="1"></circle>
                    <circle cx="5" cy="12" r="1"></circle>
                </svg>
                <span class="text-sm tracking-wide nav-text">Mai multe</span>
            </button>

            <!-- More Dropdown Menu -->
            <div id="more-dropdown" class="hidden absolute bottom-full left-0 mb-2 w-[240px] bg-white dark:bg-black border border-neutral-200 dark:border-neutral-800 rounded-lg shadow-lg overflow-hidden z-50">
                <div class="p-2">
                    <!-- Edit Profile -->
                    <a href="edit-profile.php" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19.5 3 21l1.5-4L16.5 3.5z"/></svg>
                        Editează Profilul
                    </a>
                    <!-- Activity -->
                    <a href="activity.php" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Activitatea Ta
                    </a>
                    <!-- Saved -->
                    <a href="profile.php?tab=saved" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        Salvate
                    </a>
                    <!-- Verified Account (only if verified) -->
                    <?php if ($isVerified): ?>
                    <a href="verify.php" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-green-600 font-semibold bg-green-50 dark:bg-green-900/10 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        <span class="text-green-600">Verified Account</span>
                    </a>
                    <?php elseif ($isPending): ?>
                    <a href="verify.php" class="w-full flex items-center gap-3 px-3 py-2 text-sm font-semibold bg-orange-50 dark:bg-orange-900/10 rounded-md hover:bg-orange-100 dark:hover:bg-orange-900/20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        <span style="color: #f97316 !important;">In verificare</span>
                    </a>
                    <?php else: ?>
                    <a href="verify.php" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-blue-500 font-semibold hover:bg-blue-50 dark:hover:bg-blue-900/10 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        <span class="text-blue-500">Get verified</span>
                    </a>
                    <?php endif; ?>
                    <div class="h-px bg-neutral-200 dark:bg-neutral-800 my-2"></div>
                    <!-- Light/Dark Mode -->
                    <button id="theme-toggle" type="button" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 rounded-md transition-colors">
                        <span id="theme-icon"></span>
                        <span id="theme-label">Mod Luminos</span>
                    </button>
                    <!-- Language -->
                    <button type="button" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-neutral-900 dark:text-neutral-100 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 rounded-md transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Limba
                    </button>
                    <div class="h-px bg-neutral-200 dark:bg-neutral-800 my-2"></div>
                    <!-- Logout -->
                    <a href="logout.php" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Deconectare
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Search Sidebar -->
<div class="fixed z-40 inset-y-0 left-[72px] border-r border-neutral-200 dark:border-neutral-800 bg-white dark:bg-black shadow-sm dark:shadow-neutral-800/10 overflow-hidden transform-gpu backface-visibility-hidden will-change-transform transition-all duration-200 ease-out w-[397px] -translate-x-full opacity-100" data-search-sidebar="true">
    <div class="sticky top-0 z-10 bg-white dark:bg-black border-b border-neutral-200 dark:border-neutral-800 p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Caută</h2>
            <button onclick="closeSearch()" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-10 w-10 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left w-5 h-5">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>
        </div>
        <input class="flex h-10 rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 w-full bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 placeholder:text-neutral-500 dark:placeholder:text-neutral-400" placeholder="Caută utilizatori..." autocomplete="off" value="">
    </div>
    <!-- Search Results Container -->
    <div class="p-4" data-search-results style="display: none;"></div>
    <!-- Recent Searches Container -->
    <div class="p-4" data-recent-searches-container>
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold">Recente</h3>
                <button class="text-sm text-blue-500 hover:text-blue-600" data-clear-all-searches style="display: none;">Șterge tot</button>
            </div>
            <!-- No Recent Searches Message -->
            <p class="text-center text-sm text-neutral-500 dark:text-neutral-400 py-4" data-no-recent-searches>
                Nu ai cautari recente. Cauta pe cineva si cautarile tale vor aparea aici.
            </p>
            <!-- Recent Searches List -->
            <div class="space-y-4" data-recent-searches-list></div>
        </div>
    </div>
</div>

<!-- Notifications Sidebar -->
<div class="fixed z-40 inset-y-0 left-[72px] border-r border-neutral-200 dark:border-neutral-800 bg-white dark:bg-black shadow-sm dark:shadow-neutral-800/10 overflow-hidden transform-gpu backface-visibility-hidden will-change-transform transition-all duration-200 ease-out w-[397px] -translate-x-full opacity-100" data-notifications-sidebar="true">
    <div class="sticky top-0 z-10 bg-white dark:bg-black border-b border-neutral-200 dark:border-neutral-800 p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Notificări</h2>
            <button onclick="closeNotifications()" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-10 w-10 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left w-5 h-5">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>
        </div>
        <div class="flex gap-2 w-full">
            <?php
            // Get count of pending follow requests
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM follow_requests WHERE requested_id = ? AND status = 'pending'");
            $stmt->execute([$_SESSION['user_id']]);
            $follow_requests_count = $stmt->fetchColumn();
            ?>
            <button onclick="toggleFollowRequests()" class="px-3 py-1.5 text-sm font-medium bg-neutral-100 dark:bg-neutral-800 rounded-full flex items-center justify-between hover:bg-neutral-200 dark:hover:bg-neutral-700 transition-colors w-full">
                Cereri de urmărire
                <?php if ($follow_requests_count > 0): ?>
                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"><?php echo $follow_requests_count; ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>
    <div class="p-4 space-y-4">
        <!-- Follow Requests Section -->
        <div id="followRequestsSection" class="space-y-4" style="display: none;">
            <div class="flex items-center gap-2 mb-4">
                <button onclick="toggleFollowRequests()" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:text-accent-foreground h-8 w-8 hover:bg-neutral-100 dark:hover:bg-neutral-800/50 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left w-5 h-5">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                </button>
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Cereri de urmărire</h3>
            </div>
            <div class="space-y-4">
                <?php
                // Fetch pending follow requests
                $stmt = $pdo->prepare("
                    SELECT u.user_id, u.username, u.profile_picture 
                    FROM follow_requests fr 
                    JOIN users u ON fr.requester_id = u.user_id 
                    WHERE fr.requested_id = ? AND fr.status = 'pending'
                    ORDER BY fr.created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $follow_requests = $stmt->fetchAll();

                if (empty($follow_requests)) {
                    echo '<p class="text-sm text-neutral-500 dark:text-neutral-400 text-center py-4">Nu ai cereri de urmărire</p>';
                } else {
                    foreach ($follow_requests as $request) {
                        ?>
                        <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors" data-request-id="<?php echo $request['user_id']; ?>">
                            <div class="relative h-10 w-10 flex-shrink-0">
                                <img alt="<?php echo htmlspecialchars($request['username']); ?>'s profile picture" 
                                     class="rounded-full object-cover h-10 w-10" 
                                     src="<?php echo htmlspecialchars($request['profile_picture'] ?: './images/profile_placeholder.webp'); ?>">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm">
                                    <span class="font-medium"><?php echo htmlspecialchars($request['username']); ?></span> 
                                    vrea să te urmărească
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="handleFollowRequest(<?php echo $request['user_id']; ?>, 'accept')" 
                                        class="px-3 py-1 text-sm font-medium text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full">
                                    Acceptă
                                </button>
                                <button onclick="handleFollowRequest(<?php echo $request['user_id']; ?>, 'decline')" 
                                        class="px-3 py-1 text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full">
                                    Refuză
                                </button>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

        <!-- All Notifications Section -->
        <div id="allNotificationsSection" class="space-y-4">
            <div class="space-y-4">
                <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Astăzi</h3>
                <div id="notificationsList" class="space-y-4">
                    <!-- Notifications will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>



   <!-- Create Modal Container -->
   <div id="createModal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; overflow: auto; backdrop-filter: blur(4px);">
      <div role="dialog" aria-describedby="dialog-description" aria-labelledby="dialog-title" data-state="open" class="fixed left-[50%] top-[50%] z-50 w-full translate-x-[-50%] translate-y-[-50%] gap-4 animate-none transition-none duration-0 max-w-5xl h-[90vh] flex flex-col p-0 border border-neutral-200 dark:border-neutral-800 rounded-xl overflow-hidden shadow-xl bg-white dark:bg-neutral-900">
         <h2 id="dialog-title" class="sr-only">Dialog</h2>
         <p id="dialog-description" class="sr-only">This dialog window contains interactive content. Press Escape to close the dialog.</p>
         
         <div class="flex flex-col space-y-1.5 text-center sm:text-left px-6 py-4 border-b border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
            <h2 class="tracking-tight text-center text-lg font-semibold">Creează postare nouă</h2>
            <p class="text-center text-sm text-neutral-500 dark:text-neutral-400">Share a photo with your followers</p>
            <button onclick="closeCreateModal()" class="absolute right-4 top-4 p-2 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                  <path d="M18 6 6 18"></path>
                  <path d="m6 6 12 12"></path>
               </svg>
            </button>
         </div>

         <div dir="ltr" data-orientation="horizontal" class="flex-1 flex flex-col overflow-hidden">
            <!-- Tabs -->
            <div role="tablist" aria-orientation="horizontal" class="items-center justify-center text-muted-foreground bg-neutral-100 dark:bg-neutral-800 p-1 mx-6 mt-4 mb-2 grid w-auto rounded-lg h-auto" style="grid-template-columns: repeat(2, 1fr);">
               <button id="postTabBtn" onclick="switchTab('post')" type="button" role="tab" aria-selected="true" class="justify-center whitespace-nowrap px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 rounded-md h-10 flex items-center gap-1.5 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white" data-state="active">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                     <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                     <circle cx="9" cy="9" r="2"></circle>
                     <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                  </svg>
                  <span>Post</span>
               </button>
               <button id="storyTabBtn" onclick="switchTab('story')" type="button" role="tab" aria-selected="false" class="justify-center whitespace-nowrap px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 rounded-md h-10 flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                     <circle cx="12" cy="12" r="10"></circle>
                     <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                  <span>Story</span>
               </button>
            </div>

            <div class="h-[calc(90vh-120px)] overflow-hidden">
               <!-- Post Content -->
               <div id="postContent" class="tab-content h-full">
                  <!-- Initial Upload State -->
                  <div id="initialPostState" class="flex w-full h-full">
                     <div class="flex-1 flex items-center justify-center bg-white dark:bg-white">
                         <div class="flex flex-col items-center justify-center w-full h-full gap-4 transition-colors p-6">
                            <div class="flex flex-col items-center justify-center gap-4 max-w-md text-center p-8 bg-white dark:bg-white rounded-2xl shadow-sm border border-neutral-200 dark:border-neutral-800">
                                <div class="w-24 h-24 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image h-10 w-10 text-neutral-500">
                                        <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                                        <circle cx="9" cy="9" r="2"></circle>
                                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold">Creează o postare nouă</h3>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Distribuie fotografiile tale cu urmăritorii tăi</p>
                                <div class="w-full border-t border-neutral-200 dark:border-neutral-800 my-4"></div>
                                <p class="text-sm font-medium">Trage fotografii și videoclipuri aici</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">Sau apasă pentru a încărca</p>
                                <input id="fileInput" class="h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 hidden" accept="image/*,video/*" type="file">
                                <button onclick="document.getElementById('fileInput').click()" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-primary text-white hover:bg-primary/90 h-10 px-4 py-2 mt-2">Adaugă fotografie</button>
                            </div>
                         </div>
                     </div>
                  </div>

                  <!-- Preview and Input State -->
                  <div id="previewAndInputState" class="flex w-full h-full" style="display: none;">
                     <div class="flex-1 flex items-center justify-center bg-white dark:bg-white">
                        <div class="relative w-full h-full flex items-center justify-center p-6">
                           <div class="relative overflow-hidden rounded-lg shadow-lg bg-white max-w-3xl w-full">
                              <div style="position: relative; width: 100%; padding-bottom: 56.25%;">
                                 <div class="w-full bg-black overflow-hidden" style="position: absolute; inset: 0px;">
                                    <div class="relative w-full h-full">
                                       <img id="postPreviewImage" alt="Preview" decoding="async" data-nimg="fill" class="object-contain" src="" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent; transform: scale(1);">
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="w-[350px] h-full border-l border-neutral-200 dark:border-neutral-800 flex flex-col bg-white dark:bg-neutral-900 overflow-hidden">
                        <div class="flex-1 p-6 space-y-6 overflow-y-auto">
                           <form id="createPostForm" class="space-y-6">
                              <div>
                                 <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Scrie o descriere</h3>
                                 <div class="relative flex items-start rounded-sm border border-neutral-200 dark:border-neutral-800 px-3 py-2 focus-within:outline-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4 text-neutral-500 dark:text-neutral-400 mr-2 mt-[5px]">
                                       <circle cx="12" cy="12" r="10"></circle>
                                       <path d="M12 16v-4"></path>
                                       <path d="M12 8h.01"></path>
                                    </svg>
                                    <textarea id="postDescriptionInput" name="description" class="flex w-full rounded-lg border border-input ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 min-h-[120px] resize-none bg-transparent border-none text-sm text-neutral-800 dark:text-neutral-200 placeholder:text-neutral-500 dark:placeholder:text-neutral-400 focus-visible:ring-0 focus-visible:ring-offset-0 focus:outline-none p-0" placeholder="Scrie o descriere" maxlength="250" onkeydown="return preventSpecialChars(event)" onpaste="return false" ondrop="return false"></textarea>
                                 </div>
                                 <div class="flex justify-end mt-1">
                                    <span id="descriptionCharCount" class="text-xs text-neutral-500">250 characters remaining</span>
                                 </div>
                              </div>
                              <div>
                                 <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Locație</h3>
                                 <div class="relative flex items-center rounded-sm border border-neutral-200 dark:border-neutral-800 px-3 py-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin h-4 w-4 text-neutral-500 dark:text-neutral-400 mr-2">
                                       <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                       <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <input id="postLocationInput" name="location" class="flex w-full rounded-lg border ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 h-9 border-none bg-transparent text-sm text-neutral-800 dark:text-neutral-200 placeholder:text-neutral-500 dark:placeholder:text-neutral-400 focus-visible:ring-0 focus-visible:ring-offset-0 p-0" placeholder="Adaugă locație" maxlength="20" value="">
                                 </div>
                                 <div class="flex justify-end mt-1">
                                    <span id="locationCharCount" class="text-xs text-neutral-500">20 characters remaining</span>
                                 </div>
                              </div>
                              <div>
                                 <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Etichetează persoane</h3>
                                 <div class="w-full relative">
                                    <div class="flex items-center rounded-sm border border-neutral-200 dark:border-neutral-800 px-3 py-2">
                                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-round h-4 w-4 text-neutral-500 dark:text-neutral-400 mr-2">
                                          <path d="M18 21a8 8 0 0 0-16 0"></path>
                                          <circle cx="10" cy="8" r="5"></circle>
                                          <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"></path>
                                       </svg>
                                       <input id="postTaggingInput" name="tags" class="flex h-10 rounded-lg border ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 w-full bg-transparent border-none text-sm text-neutral-800 dark:text-neutral-200 focus-visible:ring-0 focus-visible:ring-offset-0 p-0" placeholder="Tag people (use @ to search)" type="text" value="">
                                    </div>
                                 </div>
                              </div>
                              <div>
                                 <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Dezactiveaza comentarii</h3>
                                 <div class="flex items-center justify-between">
                                    <span id="commentsStatus" class="text-sm text-green-600 dark:text-green-400">Comentarii active</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                       <input type="checkbox" id="commentsToggle" class="sr-only peer" onchange="updateCommentsStatus()">
                                       <div class="w-11 h-6 bg-neutral-200 rounded-full peer dark:bg-neutral-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-blue-600"></div>
                                    </label>
                                 </div>
                              </div>
                           </form>
                        </div>
                        <div class="p-6 border-t border-neutral-200 dark:border-neutral-800">
                           <button onclick="submitPost()" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-primary text-white hover:bg-primary/90 h-10 px-4 py-2 w-full rounded-lg">
                              <span>Partajează</span>
                           </button>
                        </div>
                     </div>
                  </div>
               </div>

               <!-- Story Content -->
               <div id="storyContent" class="tab-content h-full" style="display: none;">
                  <div id="initialStoryState" class="flex w-full h-full">
                     <div class="flex-1 flex items-center justify-center bg-white dark:bg-white">
                         <div class="flex flex-col items-center justify-center w-full h-full gap-4 transition-colors p-6">
                            <div class="flex flex-col items-center justify-center gap-4 max-w-md text-center p-8 bg-white dark:bg-white rounded-2xl shadow-sm border border-neutral-200 dark:border-neutral-800">
                                <div class="w-24 h-24 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock h-10 w-10 text-neutral-500">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold">Creează o poveste nouă</h3>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Distribuie un moment care dispare în 24 de ore</p>
                                <div class="w-full border-t border-neutral-200 dark:border-neutral-800 my-4"></div>
                                <p class="text-sm font-medium">Trage fotografii și videoclipuri aici</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">Sau apasă pentru a încărca</p>
                                <input id="storyFileInput" class="h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 hidden" accept="image/*" type="file">
                                <button onclick="document.getElementById('storyFileInput').click()" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-primary text-white hover:bg-primary/90 h-10 px-4 py-2 mt-2">Adaugă fotografie</button>
                            </div>
                         </div>
                     </div>
                  </div>

                  <!-- Preview and Info State for Story -->
                  <div id="previewAndInputStoryState" class="flex w-full h-full" style="display: none;">
                     <div class="flex-1 flex items-center justify-center bg-white dark:bg-white">
                         <div class="relative w-full h-full flex items-center justify-center p-6">
                             <div class="relative overflow-hidden rounded-lg shadow-lg bg-black w-[350px]">
                                 <div data-radix-aspect-ratio-wrapper="" style="position: relative; width: 100%; padding-bottom: 177.778%;">
                                     <div class="w-full bg-black overflow-hidden" style="position: absolute; inset: 0px;">
                                         <div class="relative w-full h-full">
                                             <img id="storyPreviewImage" alt="Preview" decoding="async" data-nimg="fill" class="object-contain" src="" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent; transform: scale(1);">
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="w-[350px] h-full border-l border-neutral-200 dark:border-neutral-800 flex flex-col bg-white dark:bg-neutral-900 overflow-hidden">
                         <div class="flex-1 p-6 space-y-6 overflow-y-auto">
                             <div class="bg-white dark:bg-white p-4 rounded-lg">
                                 <div class="flex items-center mb-4">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info w-4 h-4 mr-2 text-neutral-500">
                                         <circle cx="12" cy="12" r="10"></circle>
                                         <path d="M12 16v-4"></path>
                                         <path d="M12 8h.01"></path>
                                     </svg>
                                     <h3 class="text-sm font-medium">Poveștile dispar după 24 de ore</h3>
                                 </div>
                                 <p class="text-xs text-neutral-500 dark:text-neutral-400">Poveștile sunt vizibile pentru urmăritorii tăi timp de 24 de ore înainte de a dispărea.</p>
                             </div>
                         </div>
                         <div class="p-6 border-t border-neutral-200 dark:border-neutral-800 bg-white dark:bg-white">
                             <button class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-primary text-white hover:bg-primary/90 h-10 px-4 py-2 w-full rounded-lg">
                                 <span>Partajează</span>
                             </button>
                         </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
   
   <script>
      // Tab switching functionality
  function toggleFollowRequests() {
      const followRequestsSection = document.getElementById('followRequestsSection');
      const allNotificationsSection = document.getElementById('allNotificationsSection');

      if (followRequestsSection.style.display === 'none') {
          followRequestsSection.style.display = 'block';
          allNotificationsSection.style.display = 'none';
      } else {
          followRequestsSection.style.display = 'none';
          allNotificationsSection.style.display = 'block';
      }
  }




  // No loadCreateModal function here - we'll use the one from create-modal.js

  function showToast(message, type = 'error') {
      // Create toast container if it doesn't exist
      let container = document.getElementById('toast-container');
      if (!container) {
          container = document.createElement('ul');
          container.id = 'toast-container';
          document.body.appendChild(container);
      }

      // Create toast element
      const toast = document.createElement('li');
      toast.className = 'toast';
      toast.setAttribute('data-type', type);
      toast.setAttribute('tabindex', '0');
      toast.setAttribute('data-dismissible', 'true');

      // Create close button
      const closeButton = document.createElement('button');
      closeButton.setAttribute('aria-label', 'Close toast');
      closeButton.innerHTML = `
          <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
          </div>
      `;
      closeButton.onclick = () => toast.remove();

      // Create icon based on type
      const iconDiv = document.createElement('div');
      iconDiv.setAttribute('data-icon', '');
      if (type === 'success') {
          iconDiv.innerHTML = `
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" height="20" width="20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"></path>
              </svg>
          `;
      } else {
          iconDiv.innerHTML = `
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" height="20" width="20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"></path>
              </svg>
          `;
      }

      // Create content
      const contentDiv = document.createElement('div');
      contentDiv.setAttribute('data-content', '');
      contentDiv.innerHTML = `
          <div data-title="">${message}</div>
      `;

      // Append all elements
      toast.appendChild(closeButton);
      toast.appendChild(iconDiv);
      toast.appendChild(contentDiv);
      container.appendChild(toast);

      // Remove toast after 3 seconds
      setTimeout(() => {
          toast.style.opacity = '0';
          setTimeout(() => toast.remove(), 300);
      }, 3000);
  }

  // Add WebSocket connection
  let ws = null;
  let notificationCount = 0;
  let reconnectAttempts = 0;
  const MAX_RECONNECT_ATTEMPTS = 5;
  const RECONNECT_DELAY = 5000; // 5 seconds
  let pingInterval = null;
  let reconnectTimeout = null;

  function connectWebSocket() {
      if (ws && ws.readyState === WebSocket.OPEN) {
          return;
      }

      // Clear any existing reconnect timeout
      if (reconnectTimeout) {
          clearTimeout(reconnectTimeout);
          reconnectTimeout = null;
      }

      try {
        const protocol = window.location.protocol === 'https:' ? 'wss://' : 'ws://';
        ws = new WebSocket(protocol + window.location.hostname + '/ws');
          
          ws.onopen = function() {
              reconnectAttempts = 0;
              
              // Authenticate with user ID
              const authMessage = {
                  type: 'auth',
                  user_id: <?php echo $_SESSION['user_id']; ?>
              };
              ws.send(JSON.stringify(authMessage));

              // Set up ping interval to keep connection alive
              if (pingInterval) {
                  clearInterval(pingInterval);
              }
              pingInterval = setInterval(() => {
                  if (ws.readyState === WebSocket.OPEN) {
                      ws.send(JSON.stringify({ type: 'ping' }));
                  }
              }, 15000); // Send ping every 15 seconds
          };
          
          ws.onmessage = function(event) {
              try {
                  const data = JSON.parse(event.data);

                  if (data.type === 'pong') {
                      // Update last ping time
                      ws.lastPing = Date.now();
                      return;
                  }

                  if (data.type === 'follow_request') {
                      // Update follow request count
                      const countBadge = document.querySelector('button[onclick="toggleFollowRequests()"] span');
                      if (countBadge) {
                          const currentCount = parseInt(countBadge.textContent || '0');
                          countBadge.textContent = currentCount + 1;
                          countBadge.style.display = 'block';
                      } else {
                          // If badge doesn't exist, create it
                          const button = document.querySelector('button[onclick="toggleFollowRequests()"]');
                          if (button) {
                              const newBadge = document.createElement('span');
                              newBadge.className = 'bg-red-500 text-white text-xs px-2 py-0.5 rounded-full';
                              newBadge.textContent = '1';
                              button.appendChild(newBadge);
                          }
                      }

                      // Add to follow requests list
                      const followRequestsSection = document.getElementById('followRequestsSection');
                      if (followRequestsSection) {
                          const noRequestsMessage = followRequestsSection.querySelector('p.text-center');
                          if (noRequestsMessage) {
                              noRequestsMessage.remove();
                          }

                          const requestElement = document.createElement('div');
                          requestElement.className = 'flex items-start gap-3 p-3 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors';
                          requestElement.setAttribute('data-request-id', data.requester_id);
                          requestElement.style.opacity = '0';
                          requestElement.style.transition = 'opacity 0.3s ease-in';
                          
                          requestElement.innerHTML = `
                              <div class="relative h-10 w-10 flex-shrink-0">
                                  <img alt="${data.requester_username}'s profile picture" 
                                       class="rounded-full object-cover h-10 w-10" 
                                       src="${data.profile_picture || './images/profile_placeholder.webp'}">
                              </div>
                              <div class="flex-1 min-w-0">
                                  <p class="text-sm">
                                      <span class="font-medium">${data.requester_username}</span> 
                                      vrea să te urmărească
                                  </p>
                              </div>
                              <div class="flex items-center gap-2">
                                  <button onclick="handleFollowRequest(${data.requester_id}, 'accept')" 
                                          class="px-3 py-1 text-sm font-medium text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full">
                                      Acceptă
                                  </button>
                                  <button onclick="handleFollowRequest(${data.requester_id}, 'decline')" 
                                          class="px-3 py-1 text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full">
                                      Refuză
                                  </button>
                              </div>
                          `;
                          
                          // Add the new request at the beginning of the list
                          const requestsContainer = followRequestsSection.querySelector('.space-y-4');
                          if (requestsContainer) {
                              requestsContainer.insertBefore(requestElement, requestsContainer.firstChild);
                          } else {
                              followRequestsSection.insertBefore(requestElement, followRequestsSection.firstChild);
                          }
                          
                          // Fade in the new request
                          setTimeout(() => {
                              requestElement.style.opacity = '1';
                          }, 50);
                      }

                      // Show toast notification
                      showToast(`${data.requester_username} vrea să te urmărească`);
                  }
              } catch (error) {
                  // Error handling without console.log
              }
          };
          
          ws.onclose = function(e) {
              // Clear ping interval
              if (pingInterval) {
                  clearInterval(pingInterval);
                  pingInterval = null;
              }
              
              // Attempt to reconnect if we haven't exceeded max attempts
              if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                  reconnectAttempts++;
                  reconnectTimeout = setTimeout(connectWebSocket, RECONNECT_DELAY);
              } else {
                  showToast('Connection lost. Please refresh the page.', 'error');
              }
          };

          ws.onerror = function(error) {
              // Error handling without console.log
          };
      } catch (error) {
          // Error handling without console.log
      }
  }

  function handleNewFollowRequest(requesterId, requesterUsername, profilePicture = './images/profile_placeholder.webp') {
      // Update follow requests count
      const countBadge = document.querySelector('button[onclick="toggleFollowRequests()"] span');
      if (countBadge) {
          const currentCount = parseInt(countBadge.textContent || '0');
          countBadge.textContent = currentCount + 1;
          countBadge.style.display = 'block';
      }

      // Add to follow requests list
      const followRequestsSection = document.getElementById('followRequestsSection');
      if (followRequestsSection) {
          const noRequestsMessage = followRequestsSection.querySelector('p.text-center');
          if (noRequestsMessage) {
              noRequestsMessage.remove();
          }

          const requestElement = document.createElement('div');
          requestElement.className = 'flex items-start gap-3 p-3 rounded-lg hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors';
          requestElement.setAttribute('data-request-id', requesterId);
          requestElement.style.opacity = '0';
          requestElement.style.transition = 'opacity 0.3s ease-in';
          
          requestElement.innerHTML = `
              <div class="relative h-10 w-10 flex-shrink-0">
                  <img alt="${requesterUsername}'s profile picture" 
                       class="rounded-full object-cover h-10 w-10" 
                       src="${profilePicture}">
              </div>
              <div class="flex-1 min-w-0">
                  <p class="text-sm">
                      <span class="font-medium">${requesterUsername}</span> 
                      vrea să te urmărească
                  </p>
              </div>
              <div class="flex items-center gap-2">
                  <button onclick="handleFollowRequest(${requesterId}, 'accept')" 
                          class="px-3 py-1 text-sm font-medium text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full">
                      Acceptă
                  </button>
                  <button onclick="handleFollowRequest(${requesterId}, 'decline')" 
                          class="px-3 py-1 text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full">
                      Refuză
                  </button>
              </div>
          `;
          
          // Add the new request at the beginning
          followRequestsSection.insertBefore(requestElement, followRequestsSection.firstChild);
          
          // Fade in the new request
          setTimeout(() => {
              requestElement.style.opacity = '1';
          }, 10);
      }
  }

  // Initialize WebSocket connection when page loads
  document.addEventListener('DOMContentLoaded', function() {
      connectWebSocket();
      
      // Load initial notifications
      fetch('api/notifications.php')
          .then(response => {
              if (!response.ok) {
                  throw new Error('Network response was not ok');
              }
              return response.json();
          })
          .then(data => {
              // Loaded notifications data
              if (data.success) {
                  const notificationsList = document.getElementById('notificationsList');
                  if (notificationsList) {
                      // Clear existing notifications
                      notificationsList.innerHTML = '';
                      
                      if (data.notifications && data.notifications.length > 0) {
                          data.notifications.forEach(notification => {
                              const notificationElement = createNotificationElement(notification);
                              notificationsList.appendChild(notificationElement);
                          });
                          
                          // Update notification count
                          notificationCount = data.notifications.filter(n => !n.is_read).length;
                          updateNotificationBadge();
                      } else {
                          notificationsList.innerHTML = `
                              <div class="p-4 text-center text-neutral-500 dark:text-neutral-400">
                                  Nu ai notificări noi
                              </div>
                          `;
                      }
                  }
              }
          })
          .catch(error => {
              showToast('Error loading notifications. Please refresh the page.', 'error');
          });
  });

  // Add window focus/blur handlers to manage WebSocket connection
  window.addEventListener('focus', function() {
      if (!ws || ws.readyState !== WebSocket.OPEN) {
          console.log('Window focused, reconnecting WebSocket...');
          connectWebSocket();
      }
  });

  window.addEventListener('beforeunload', function() {
      if (ws) {
          ws.close();
      }
      if (pingInterval) {
          clearInterval(pingInterval);
      }
  });

  function handleNewNotification(notification) {
      console.log('Handling new notification:', notification);
      
      const notificationsList = document.getElementById('notificationsList');
      if (!notificationsList) {
          console.error('Notifications list element not found');
          return;
      }
      
      // Remove "no notifications" message if it exists
      if (notificationsList.querySelector('.text-center')) {
          notificationsList.innerHTML = '';
      }
      
      // Check for existing notifications of the same type/user/post/story to avoid duplicates
      let existingNotification = null;
      const allNotifications = notificationsList.querySelectorAll('.flex.items-start.gap-3');
      
      // Add specific handling for story notifications
      if (notification.type === 'story_like' && notification.story_id && notification.actor_id) {
          console.log('Checking for existing story notification - Story ID:', notification.story_id, 'Actor ID:', notification.actor_id);
          
          for (const notifElem of allNotifications) {
              const notifStoryId = notifElem.getAttribute('data-story-id');
              const notifActorId = notifElem.getAttribute('data-actor-id');
              const notifType = notifElem.getAttribute('data-notif-type');
              
              if (notifStoryId === notification.story_id.toString() &&
                  notifActorId === notification.actor_id.toString() &&
                  notifType === 'story_like') {
                  console.log('Found matching story notification!');
                  existingNotification = notifElem;
                  break;
              }
          }
      } else if ((notification.type === 'like' && notification.post_id && notification.actor_id)) {
          // Existing post like notification handling
          for (const notifElem of allNotifications) {
              const notifPostId = notifElem.getAttribute('data-post-id');
              const notifActorId = notifElem.getAttribute('data-actor-id');
              const notifType = notifElem.getAttribute('data-notif-type');
              
              if (notifPostId === notification.post_id.toString() &&
                  notifActorId === notification.actor_id.toString() &&
                  notifType === 'like') {
                  existingNotification = notifElem;
                  break;
              }
          }
      }
      
      if (existingNotification) {
          // Update existing notification timestamp
          const timestamp = existingNotification.querySelector('p.text-xs');
          if (timestamp) {
              timestamp.textContent = 'Just now';
          }
          
          // Move to top
          notificationsList.removeChild(existingNotification);
          notificationsList.insertBefore(existingNotification, notificationsList.firstChild);
          
          // Show notification toast
          showNotificationToast(notification);
      } else {
          // Create and add the new notification
          const notificationElement = createNotificationElement(notification);
          notificationsList.insertBefore(notificationElement, notificationsList.firstChild);
          
          // Update notification count badge
          const badge = document.querySelector('button[onclick="toggleNotifications()"] span');
          if (badge) {
              const currentCount = parseInt(badge.textContent || '0');
              badge.textContent = currentCount + 1;
              badge.style.display = 'block';
          }
          
          // Show notification toast
          showNotificationToast(notification);
      }
  }

  function showNotificationToast(notification) {
      const message = formatNotificationMessage(notification);
      const toast = document.createElement('div');
      toast.className = 'fixed bottom-4 right-4 bg-white dark:bg-neutral-800 rounded-lg shadow-lg p-4 flex items-center gap-3 z-50 animate-fade-in';
      toast.innerHTML = `
          <div class="relative h-10 w-10 flex-shrink-0">
              <img alt="${notification.actor_username || 'User'}'s profile picture" 
                   class="rounded-full object-cover h-10 w-10" 
                   src="${notification.actor_profile_picture || './images/profile_placeholder.webp'}">
              <div class="absolute -bottom-1 -right-1 bg-white dark:bg-black rounded-full p-1">
                  ${getNotificationIcon(notification.type)}
              </div>
          </div>
          <div class="flex-1 min-w-0">
              <p class="text-sm">${message}</p>
          </div>
      `;
      
      document.body.appendChild(toast);
      
      // Remove toast after 5 seconds
      setTimeout(() => {
          toast.classList.add('animate-fade-out');
          setTimeout(() => toast.remove(), 300);
      }, 5000);
  }

  function createNotificationElement(notification) {
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
      
      switch (notification.type) {
          case 'like':
              message = count === 1
                  ? `${lastActor} a apreciat postarea ta`
                  : `${lastActor} si altii ${count - 1} au apreciat postarea ta`;
              break;
          case 'comment':
              message = count === 1
                  ? `${lastActor} a adaugat un comentariu la postarea ta`
                  : `${lastActor} si altii ${count - 1} au adaugat comentarii la postarea ta`;
              break;
          case 'story_like':
              message = count === 1
                  ? `${lastActor} a apreciat povestea ta`
                  : `${lastActor} si altii ${count - 1} au apreciat povestea ta`;
              break;
          case 'comment_reply':
              message = count === 1
                  ? `${lastActor} a raspuns la comentariul tau`
                  : `${lastActor} si altii ${count - 1} au raspuns la comentariul tau`;
              break;
          case 'event':
              message = `Un nou eveniment a fost postat! ${notification.event_name || ''}`;
              break;
          case 'follow':
              message = `${lastActor} a inceput sa te urmareasca`;
              break;
          case 'follow_request':
              message = `${lastActor} vrea să te urmărească`;
              break;
          case 'follow_request_accepted':
              message = `${lastActor} ți-a acceptat cererea de urmărire`;
              break;
          case 'new_follower':
              message = `${lastActor} a început să te urmărească`;
              break;
          default:
              console.error('Unknown notification type:', notification.type);
              message = 'Notificare nouă';
      }
      
      // Calculate time difference
      const createdDate = new Date(notification.created_at);
      const now = new Date();
      const timeDiff = Math.floor((now - createdDate) / 1000); // Convert to seconds
      
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

  function formatNotificationMessage(notification) {
      const count = parseInt(notification.count) || 1;
      const lastActor = notification.last_actor_username || notification.actor_username;
      let message = '';

      switch (notification.type) {
          case 'like':
              message = count === 1 
                  ? `${lastActor} a apreciat postarea ta`
                  : `${lastActor} si altii ${count - 1} au apreciat postarea ta`;
              break;
          case 'comment':
              message = count === 1
                  ? `${lastActor} a adaugat un comentariu la postarea ta`
                  : `${lastActor} si altii ${count - 1} au adaugat comentarii la postarea ta`;
              break;
          case 'story_like':
              message = count === 1
                  ? `${lastActor} a apreciat povestea ta`
                  : `${lastActor} si altii ${count - 1} au apreciat povestea ta`;
              break;
          case 'comment_reply':
              message = count === 1
                  ? `${lastActor} a raspuns la comentariul tau`
                  : `${lastActor} si altii ${count - 1} au raspuns la comentariul tau`;
              break;
          case 'event':
              message = `Un nou eveniment a fost postat! ${notification.event_name}`;
              break;
          case 'follow_request':
              message = `${lastActor} vrea să te urmărească`;
              break;
          case 'follow_request_accepted':
              message = `${lastActor} ți-a acceptat cererea de urmărire`;
              break;
          case 'new_follower':
              message = `${lastActor} a început să te urmărească`;
              break;
          default:
              console.error('Unknown notification type:', notification.type);
              message = 'Notificare nouă';
      }

      return message;
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

  function formatTimeAgo(timestamp) {
      // If timestamp is a string (from database), convert it to a Date object
      let date;
      if (typeof timestamp === 'string') {
          // Handle MySQL datetime format
          date = new Date(timestamp.replace(' ', 'T'));
      } else if (typeof timestamp === 'number') {
          // Handle Unix timestamp
          date = new Date(timestamp * 1000);
      } else {
          // Handle Date object
          date = new Date(timestamp);
      }

      // Check if date is valid
      if (isNaN(date.getTime())) {
          console.error('Invalid date:', timestamp);
          return 'Invalid date';
      }

      const now = new Date();
      const seconds = Math.floor((now - date) / 1000);
      
      if (seconds < 60) {
          return 'Acum';
      }
      
      const minutes = Math.floor(seconds / 60);
      if (minutes < 60) {
          return `Acum ${minutes} ${minutes === 1 ? 'minut' : 'minute'}`;
      }
      
      const hours = Math.floor(minutes / 60);
      if (hours < 24) {
          return `Acum ${hours} ${hours === 1 ? 'oră' : 'ore'}`;
      }
      
      const days = Math.floor(hours / 24);
      if (days < 7) {
          return `Acum ${days} ${days === 1 ? 'zi' : 'zile'}`;
      }
      
      // For older dates, show the actual date
      return date.toLocaleDateString('ro-RO', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
      });
  }

  function getNotificationIcon(type) {
      switch (type) {
          case 'like':
              return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                          <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                      </svg>`;
          case 'comment':
              return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                      </svg>`;
          case 'story_like':
              return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                          <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                      </svg>`;
          case 'comment_reply':
              return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
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

  function updateNotificationBadge() {
      const badge = document.querySelector('button[onclick="toggleNotifications()"] span');
      if (badge) {
          if (notificationCount > 0) {
              badge.textContent = notificationCount;
              badge.style.display = 'flex';
          } else {
              badge.style.display = 'none';
          }
      }
  }

  function toggleNotifications() {
      const notificationsSidebar = document.querySelector('[data-notifications-sidebar]');
      const searchSidebar = document.querySelector('[data-search-sidebar]');
      const navbar = document.querySelector('nav');
      const navTexts = document.querySelectorAll('.nav-text');
      const navItems = document.querySelectorAll('nav a, nav button');
      const fullLogo = document.querySelector('h1.nav-text');
      const shortLogo = document.querySelector('h1.collapsed-text');
      
      // If search is open, close it first
      if (searchSidebar && searchSidebar.classList.contains('translate-x-0')) {
          searchSidebar.classList.remove('translate-x-0');
          searchSidebar.classList.add('-translate-x-full');
          
          // Clear search input and results
          const searchInput = searchSidebar.querySelector('input');
          if (searchInput) {
              searchInput.value = '';
          }
          
          const searchResults = searchSidebar.querySelector('[data-search-results]');
          if (searchResults) {
              searchResults.style.display = 'none';
              searchResults.innerHTML = '';
          }
      }
      
      // Toggle notifications sidebar
      notificationsSidebar.classList.toggle('translate-x-0');
      notificationsSidebar.classList.toggle('-translate-x-full');
      
      // Always keep navbar collapsed when any sidebar is open
      if (notificationsSidebar.classList.contains('translate-x-0') || 
          (searchSidebar && searchSidebar.classList.contains('translate-x-0'))) {
          navbar.classList.remove('w-[240px]');
          navbar.classList.add('w-[72px]');
          
          // Hide text
          navTexts.forEach(text => {
              text.classList.add('hidden');
          });
          
          // Show short logo
          fullLogo.classList.add('hidden');
          shortLogo.classList.remove('hidden');
          
          // Center icons
          navItems.forEach(item => {
              item.classList.add('justify-center');
              item.classList.remove('justify-start');
          });
      } else {
          // Only expand navbar if no sidebars are open
          navbar.classList.remove('w-[72px]');
          navbar.classList.add('w-[240px]');
          
          // Show text
          navTexts.forEach(text => {
              text.classList.remove('hidden');
          });
          
          // Show full logo
          fullLogo.classList.remove('hidden');
          shortLogo.classList.add('hidden');
          
          // Align icons to start
          navItems.forEach(item => {
              item.classList.remove('justify-center');
              item.classList.add('justify-start');
          });
      }
      
      // If opening notifications, mark them as read and load initial notifications
      if (notificationsSidebar.classList.contains('translate-x-0')) {
          // Mark notifications as read
          fetch('api/notifications.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: 'action=mark_read'
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  notificationCount = 0;
                  updateNotificationBadge();
              }
          })
          .catch(error => console.error('Error marking notifications as read:', error));
          
          // Load initial notifications
          loadInitialNotifications();
      }
  }

  // ... rest of existing code ...

  // Load initial notifications
  async function loadInitialNotifications() {
      try {
          const response = await fetch('api/notifications.php');
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          const data = await response.json();
          
          const notificationsList = document.getElementById('notificationsList');
          if (!notificationsList) {
              return;
          }
          
          // Clear existing notifications
          notificationsList.innerHTML = '';
          
          if (data.notifications && data.notifications.length > 0) {
              data.notifications.forEach(notification => {
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

  // ... rest of the existing code ...
   </script>
   <script src="assets/js/create-modal.js"></script>
   <script>
      // Add theme switching functionality
      document.addEventListener('DOMContentLoaded', function() {
          const themeToggle = document.getElementById('theme-toggle');
          const themeIcon = document.getElementById('theme-icon');
          const themeLabel = document.getElementById('theme-label');
          
          // Set initial theme based on HTML class
          const isDark = document.documentElement.classList.contains('dark');
          updateThemeUI(isDark);
          
          themeToggle.addEventListener('click', function() {
              const isDark = document.documentElement.classList.contains('dark');
              const newTheme = isDark ? 'light' : 'dark';
              
              // Toggle theme class
              document.documentElement.classList.remove('light', 'dark');
              document.documentElement.classList.add(newTheme);
              
              // Update UI
              updateThemeUI(!isDark);
              
              // Save to server
              fetch('api/set-theme.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                  },
                  body: JSON.stringify({ theme: newTheme })
              });
          });
          
          function updateThemeUI(isDark) {
              if (isDark) {
                  themeIcon.innerHTML = `
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-500">
                          <circle cx="12" cy="12" r="4"></circle>
                          <path d="M12 2v2"></path>
                          <path d="M12 20v2"></path>
                          <path d="m4.93 4.93 1.41 1.41"></path>
                          <path d="m17.66 17.66 1.41 1.41"></path>
                          <path d="M2 12h2"></path>
                          <path d="M20 12h2"></path>
                          <path d="m6.34 17.66-1.41 1.41"></path>
                          <path d="m19.07 4.93-1.41 1.41"></path>
                      </svg>`;
                  themeLabel.textContent = 'Mod Întunecat';
              } else {
                  themeIcon.innerHTML = `
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                          <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
                      </svg>`;
                  themeLabel.textContent = 'Mod Luminos';
              }
          }
      });
   </script>
   </body>
</html>
