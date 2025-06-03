<script>
    function handleFollowAction(button, userId) {
        console.log('=== Follow Action Started ===');
        console.log('Button clicked:', {
            userId: userId,
            buttonText: button.textContent.trim(),
            dataset: button.dataset
        });

        // Validate userId first
        if (!userId || userId === 'undefined' || userId === 'null') {
            console.error('Invalid user ID:', userId);
            return;
        }

        const isFollowing = button.textContent.trim() === 'Following' || button.textContent.trim() === 'Unfollow';
        const isPrivate = button.dataset.isPrivate === '1';
        const hasPendingRequest = button.dataset.hasPendingRequest === '1';

        console.log('Action state:', {
            isFollowing,
            isPrivate,
            hasPendingRequest
        });

        // Check cooldown
        const currentTime = Date.now();
        const lastActionTime = parseInt(button.dataset.lastActionTime) || 0;
        const COOLDOWN = 1000; // 1 second cooldown
        const timeSinceLastAction = currentTime - lastActionTime;

        console.log('Cooldown check:', {
            currentTime,
            lastActionTime,
            timeSinceLastAction,
            COOLDOWN
        });

        if (timeSinceLastAction < COOLDOWN) {
            const remainingTime = Math.ceil((COOLDOWN - timeSinceLastAction) / 1000);
            console.log('Cooldown active, remaining time:', remainingTime);
            showToast(`Trebuie să aștepți ${remainingTime} secunde înainte de a putea urmări din nou`);
            return;
        }
        
        // Handle cancel request
        if (button.textContent.trim() === 'Cancel') {
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
        
        fetch('utils/follow.php', {
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

                // Update the following count only if following a public account
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
                if (isFollowing) {
                    // Remove the user from the list if unfollowing
                    button.closest('.flex.items-center.justify-between').remove();
                }
            } else if (data.error) {
                showToast(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
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

    function openFollowingModal(userId) {
        console.log('=== Opening Following Modal ===');
        console.log('Opening modal for user ID:', userId);
        
        const modal = document.getElementById('following-modal');
        const modalTitle = document.getElementById('modal-title');
        const followingList = document.getElementById('following-list');
        
        console.log('Modal elements found:', {
            modal: !!modal,
            modalTitle: !!modalTitle,
            followingList: !!followingList
        });
        
        // Set modal title
        modalTitle.textContent = 'Following';
        
        // Show loading state
        followingList.innerHTML = `
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            </div>
        `;
        
        // Show modal
        modal.style.display = 'flex';
        console.log('Modal displayed with loading state');
        
        // Fetch following data
        console.log('Fetching following data from:', `../utils/get_followers.php?type=following&user_id=${userId}`);
        fetch(`../utils/get_followers.php?type=following&user_id=${userId}`)
            .then(response => {
                console.log('Received response:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                
                if (data.success) {
                    console.log('Number of users found:', data.users.length);
                    console.log('Users data:', data.users);
                    
                    followingList.innerHTML = data.users.map(user => {
                        console.log('Processing user:', user);
                        return `
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <a href="user.php?username=${user.username}" class="flex-shrink-0">
                                        <img src="${user.profile_picture}" alt="${user.username}" class="w-10 h-10 rounded-full object-cover">
                                    </a>
                                    <div>
                                        <a href="user.php?username=${user.username}" class="font-semibold text-sm hover:underline">${user.username}</a>
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
                        `;
                    }).join('') || '<p class="text-center text-neutral-500 dark:text-neutral-400 py-4">No users found</p>';
                    
                    console.log('Rendered user list HTML');
                    
                    // Add event listeners for follow buttons
                    document.querySelectorAll('.follow-button').forEach(button => {
                        // Add click handler
                        button.addEventListener('click', function(event) {
                            event.preventDefault();
                            event.stopPropagation();
                            const userId = this.dataset.userId;
                            if (userId) {
                                handleFollowAction(this, userId);
                            }
                        });

                        // Add hover effects
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
                    console.error('Error in data:', data);
                    let errorMessage = 'Error loading users';
                    if (data.debug) {
                        console.error('Debug info:', data.debug);
                        errorMessage += `: ${data.debug.error}`;
                    }
                    followingList.innerHTML = `
                        <div class="text-center py-4">
                            <p class="text-red-500 mb-2">${errorMessage}</p>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Please try again later</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                followingList.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-red-500 mb-2">Network error occurred</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Please check your connection and try again</p>
                    </div>
                `;
            });
    }
</script>

<link rel="stylesheet" href="../css/toast.css">

<!-- Add Toast Container -->
<ol id="toast-container" class="fixed bottom-8 right-8 z-50 flex flex-col gap-3.5 w-[356px] m-0 p-0 list-none" style="--front-toast-height: 53.5px; --width: 356px; --gap: 14px; --offset-top: 32px; --offset-right: 32px; --offset-bottom: 32px; --offset-left: 32px;"></ol>

<script>
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
</script> 