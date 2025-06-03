<!-- Followers/Following Modal -->
<div id="followers-modal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none; background-color: rgba(0, 0, 0, 0.5);">
    <div class="bg-white dark:bg-neutral-900 rounded-2xl overflow-hidden flex flex-col w-full max-w-lg shadow-2xl border border-neutral-200 dark:border-neutral-800">
        <div class="flex justify-between items-center p-4 border-b border-neutral-200 dark:border-neutral-700">
            <h3 class="text-xl font-semibold text-neutral-900 dark:text-white" id="modal-title">Followers</h3>
            <button id="close-followers-modal" class="text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 focus:outline-none p-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-4 max-h-[60vh] overflow-y-auto">
            <div id="followers-list" class="space-y-4">
                <!-- Followers/Following items will be loaded here dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Add Toast Container -->
<ol id="toast-container" class="fixed bottom-8 right-8 z-50 flex flex-col gap-3.5 w-[356px] m-0 p-0 list-none" style="--front-toast-height: 53.5px; --width: 356px; --gap: 14px; --offset-top: 32px; --offset-right: 32px; --offset-bottom: 32px; --offset-left: 32px;"></ol>

<link rel="stylesheet" href="../css/toast.css">
<script>
// Wrap in self-executing function to avoid polluting global scope
(function() {
    // Define follower modal functions in global scope so they can be called elsewhere
    window.openFollowersModal = function(type, userId) {
        const modal = document.getElementById('followers-modal');
        const modalTitle = document.getElementById('modal-title');
        const followersList = document.getElementById('followers-list');
        
        // Store the modal type for later use
        modal.dataset.type = type;
        
        // Set modal title
        modalTitle.textContent = type === 'followers' ? 'Followers' : 'Following';
        
        // Show loading state
        followersList.innerHTML = `
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>
        `;
        
        // Show modal
        modal.style.display = 'flex';
        
        // Fetch followers/following data
        fetch(`../utils/get_followers.php?type=${type}&user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    followersList.innerHTML = data.users.map(user => `
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <a href="user.php?username=${user.username}" class="flex-shrink-0">
                                    <img src="${user.profile_picture}" alt="${user.username}" class="w-10 h-10 rounded-full object-cover">
                                </a>
                                <div>
                                    <a href="user.php?username=${user.username}" class="font-semibold text-sm hover:underline flex items-center gap-1">
                                        <span>${user.username}</span>
                                        ${user.isVerified ? `<span class="relative inline-flex items-center"><svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="14" role="img" style="width:14px;height:14px" viewBox="0 0 40 40" width="14"><title>Verified</title><path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path></svg></span>` : ''}
                                    </a>
                                    ${user.full_name ? `<p class="text-xs text-neutral-500 dark:text-neutral-400">${user.full_name}</p>` : ''}
                                </div>
                            </div>
                            ${user.user_id !== window.currentUserId ? `
                                <button class="follow-button px-4 py-1.5 text-sm font-semibold rounded-md transition-colors duration-200 w-[100px] ${getFollowButtonClass(user)}" 
                                        data-user-id="${user.user_id}" 
                                        data-is-following="${user.is_following}"
                                        data-is-private="${user.isPrivate}"
                                        data-has-pending-request="${user.has_pending_request}"
                                        data-last-action-time="0">
                                    ${getFollowButtonText(user)}
                                </button>
                            ` : ''}
                        </div>
                    `).join('') || '<p class="text-center text-neutral-500 dark:text-neutral-400 py-4">No users found</p>';
                    
                    // Add event listeners to follow buttons
                    document.querySelectorAll('.follow-button').forEach(button => {
                        button.addEventListener('click', handleFollowButtonClick);
                        button.addEventListener('mouseenter', function() {
                            if (this.textContent.trim() === 'Following') {
                                this.textContent = 'Unfollow';
                            } else if (this.textContent.trim() === 'Requested') {
                                this.textContent = 'Cancel';
                            }
                        });
                        button.addEventListener('mouseleave', function() {
                            if (this.textContent.trim() === 'Unfollow') {
                                this.textContent = 'Following';
                            } else if (this.textContent.trim() === 'Cancel') {
                                this.textContent = 'Requested';
                            }
                        });
                    });
                } else {
                    let errorMessage = 'Error loading users';
                    if (data.debug) {
                        console.error('Debug info:', data.debug);
                        errorMessage += `: ${data.debug.error}`;
                    }
                    followersList.innerHTML = `
                        <div class="text-center py-4">
                            <p class="text-red-500 mb-2">${errorMessage}</p>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Please try again later</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                followersList.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-red-500 mb-2">Network error occurred</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Please check your connection and try again</p>
                    </div>
                `;
            });
    }

    // Add helper functions for button state
    function getFollowButtonClass(user) {
        if (user.is_following) {
            return 'bg-neutral-100 text-neutral-900 hover:bg-neutral-200 hover:text-neutral-900 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700 dark:border dark:border-neutral-600';
        } else if (user.has_pending_request) {
            return 'bg-neutral-100 text-neutral-900 hover:bg-neutral-200 hover:text-neutral-900 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700 dark:border dark:border-neutral-600';
        } else {
            return 'bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700';
        }
    }

    function getFollowButtonText(user) {
        if (user.is_following) {
            return 'Following';
        } else if (user.has_pending_request) {
            return 'Requested';
        } else {
            return 'Follow';
        }
    }

    function handleFollowButtonClick(event) {
        const button = event.target;
        const userId = button.dataset.userId;
        const isFollowing = button.textContent.trim() === 'Following' || button.textContent.trim() === 'Unfollow';
        const isPrivate = button.dataset.isPrivate === '1';
        const hasPendingRequest = button.dataset.hasPendingRequest === '1';
        const modal = document.getElementById('followers-modal');
        const modalType = modal.dataset.type;

        // Validate userId
        if (!userId) {
            console.error('Missing user ID');
            return;
        }

        // Check cooldown
        const currentTime = Date.now();
        const lastActionTime = parseInt(button.dataset.lastActionTime) || 0;
        const COOLDOWN = 1000; // 1 second cooldown
        const timeSinceLastAction = currentTime - lastActionTime;

        if (timeSinceLastAction < COOLDOWN) {
            const remainingTime = Math.ceil((COOLDOWN - timeSinceLastAction) / 1000);
            showToast(`Trebuie să aștepți ${remainingTime} secunde înainte de a putea urmări din nou`);
            return;
        }
        
        // Handle cancel request
        if (button.textContent.trim() === 'Cancel') {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', 'cancel_request');
            
            fetch('../utils/follow.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.textContent = 'Follow';
                    button.classList.remove('bg-secondary', 'text-secondary-foreground');
                    button.classList.add('bg-blue-500', 'text-white', 'hover:bg-blue-600');
                    button.dataset.hasPendingRequest = 'false';
                    
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
        
        fetch('../utils/follow.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update last action time
                button.dataset.lastActionTime = Date.now().toString();

                if (isFollowing) {
                    // Unfollowing
                    button.textContent = 'Follow';
                    button.className = 'follow-button px-4 py-1.5 text-sm font-semibold rounded-md transition-colors duration-200 w-[100px] bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700';
                    button.dataset.isFollowing = 'false';
                    button.dataset.hasPendingRequest = 'false';
                    
                    // Update the button text on hover
                    button.onmouseenter = null;
                    button.onmouseleave = null;
                } else {
                    // Following
                    if (isPrivate) {
                        button.textContent = 'Requested';
                        button.className = 'follow-button px-4 py-1.5 text-sm font-semibold rounded-md transition-colors duration-200 w-[100px] bg-neutral-100 text-neutral-900 hover:bg-neutral-200 hover:text-neutral-900 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700 dark:border dark:border-neutral-600';
                        button.dataset.hasPendingRequest = 'true';
                    } else {
                        button.textContent = 'Following';
                        button.className = 'follow-button px-4 py-1.5 text-sm font-semibold rounded-md transition-colors duration-200 w-[100px] bg-neutral-100 text-neutral-900 hover:bg-neutral-200 hover:text-neutral-900 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700 dark:border dark:border-neutral-600';
                        button.dataset.isFollowing = 'true';
                        
                        // Add hover effect for unfollow
                        button.onmouseenter = function() {
                            this.textContent = 'Unfollow';
                        };
                        button.onmouseleave = function() {
                            this.textContent = 'Following';
                        };
                    }
                }
                
                // Update the following count only if:
                // 1. We're in the following list, or
                // 2. We're following a public account
                const followingButton = document.querySelector('.flex.items-center.justify-around button:nth-child(3)');
                if (followingButton) {
                    const followingCount = followingButton.querySelector('span:first-child');
                    if (followingCount) {
                        const currentCount = parseInt(followingCount.textContent);
                        if (isFollowing) {
                            // Always decrease count when unfollowing
                            followingCount.textContent = currentCount - 1;
                        } else if (!isPrivate) {
                            // Only increase count when following a public account
                            followingCount.textContent = currentCount + 1;
                        }
                    }
                }

                // If we're in the following list, also update the list
                if (modalType === 'following') {
                    if (isFollowing) {
                        // Remove the user from the list if unfollowing
                        button.closest('.flex.items-center.justify-between').remove();
                    }
                }
            } else if (data.error) {
                showToast(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Close modal when clicking outside
    document.getElementById('followers-modal').addEventListener('click', function(event) {
        if (event.target === this) {
            this.style.display = 'none';
        }
    });

    // Close modal when clicking close button
    document.getElementById('close-followers-modal').addEventListener('click', function() {
        document.getElementById('followers-modal').style.display = 'none';
    });

    // Add toast function
    function showToast(message) {
        // Remove any existing toasts
        const container = document.getElementById('toast-container');
        if (!container) {
            const newContainer = document.createElement('ol');
            newContainer.id = 'toast-container';
            newContainer.className = 'fixed bottom-8 right-8 z-50 flex flex-col gap-3.5 w-[356px] m-0 p-0 list-none';
            newContainer.style.cssText = '--front-toast-height: 53.5px; --width: 356px; --gap: 14px; --offset-top: 32px; --offset-right: 32px; --offset-bottom: 32px; --offset-left: 32px;';
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

    // Add toast styles only if they haven't been added yet
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            #toast-container {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                z-index: 50;
                display: flex;
                flex-direction: column;
                gap: 0.875rem;
                width: 356px;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .toast {
                background-color: #fef2f2;
                color: #dc2626;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                animation: slideIn 0.3s ease-out, fadeOut 0.3s ease-out 4.7s;
                box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.1), 0 2px 4px -1px rgba(220, 38, 38, 0.06);
                border: 1px solid #fee2e2;
                position: relative;
                width: 100%;
                overflow: hidden;
            }

            .toast[data-type="error"] {
                background-color: #fef2f2;
                border-color: #fee2e2;
            }

            .toast[data-type="error"] svg {
                color: #dc2626;
            }

            .toast button {
                position: absolute;
                right: 0.5rem;
                top: 0.5rem;
                padding: 0;
                border: none;
                background: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
            }

            .toast button > div {
                width: 20px;
                height: 20px;
                border-radius: 9999px;
                background-color: #fee2e2;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.2s ease-in-out;
            }

            .toast button:hover > div {
                background-color: #fecaca;
            }

            .toast button svg {
                width: 12px;
                height: 12px;
                color: #dc2626;
                transition: color 0.2s ease-in-out;
            }

            .toast button:hover svg {
                color: #b91c1c;
            }

            .toast [data-icon] svg {
                width: 20px;
                height: 20px;
                flex-shrink: 0;
            }

            .toast [data-content] {
                flex: 1;
                font-size: 0.875rem;
                line-height: 1.25rem;
            }

            .toast [data-content] [data-title] {
                font-weight: 600;
                line-height: 1.5rem;
            }

            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes fadeOut {
                from {
                    opacity: 1;
                }
                to {
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    window.handleFollowAction = function(button, userId) {
        const isFollowing = button.textContent.trim() === 'Following' || button.textContent.trim() === 'Unfollow';
        const isRequested = button.textContent.trim() === 'Requested' || button.textContent.trim() === 'Cancel';
        
        // Handle cancel request
        if (isRequested && button.textContent.trim() === 'Cancel') {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', 'cancel_request');
            
            fetch('../utils/follow.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.textContent = 'Follow';
                    button.classList.remove('bg-secondary', 'text-secondary-foreground');
                    button.classList.add('bg-blue-500', 'text-white', 'hover:bg-blue-600');
                } else {
                    showToast(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred');
            });
            return;
        }

        // Don't allow action if request is pending
        if (isRequested) {
            return;
        }

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('action', isFollowing ? 'unfollow' : 'follow');
        
        fetch('../utils/follow.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isFollowing) {
                    button.textContent = 'Follow';
                    button.classList.remove('bg-secondary', 'text-secondary-foreground');
                    button.classList.add('bg-blue-500', 'text-white', 'hover:bg-blue-600');
                } else {
                    if (data.is_private) {
                        button.textContent = 'Requested';
                        button.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600');
                        button.classList.add('bg-secondary', 'text-secondary-foreground');
                    } else {
                        button.textContent = 'Following';
                        button.classList.remove('bg-blue-500', 'text-white', 'hover:bg-blue-600');
                        button.classList.add('bg-secondary', 'text-secondary-foreground');
                    }
                }
            } else {
                showToast(data.error || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred');
        });
    }

    // Add hover effects for follow buttons
    document.addEventListener('DOMContentLoaded', function() {
        const followButtons = document.querySelectorAll('.follow-button');
        followButtons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                if (this.textContent.trim() === 'Following') {
                    this.textContent = 'Unfollow';
                } else if (this.textContent.trim() === 'Requested') {
                    this.textContent = 'Cancel';
                }
            });

            button.addEventListener('mouseleave', function() {
                if (this.textContent.trim() === 'Unfollow') {
                    this.textContent = 'Following';
                } else if (this.textContent.trim() === 'Cancel') {
                    this.textContent = 'Requested';
                }
            });

            button.addEventListener('click', function(event) {
                const userId = this.dataset.userId;
                if (this.textContent.trim() === 'Cancel') {
                    event.preventDefault();
                    event.stopPropagation();
                    handleFollowButtonClick(event);
                }
            });
        });
    });
})();
</script> 