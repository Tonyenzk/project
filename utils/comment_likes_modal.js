// Comment Likes Modal functionality
// This extends the likes_modal.js to support comment likes

// Initialize state for comment likes
window.commentLikesState = {
    initialized: false,
    modalCommentId: null,
    offset: 0,
    loading: false,
    hasMore: false,
    cachedLikes: {}, // Cache likes data by comment ID
    userSeen: {} // Track which users have already been seen in the current modal
};

// Initialize the comment likes modal system
function initializeCommentLikesModal() {
    try {
        // Prevent multiple initializations
        if (window.commentLikesState.initialized) {
            return;
        }
        
        // Check if modal exists - we'll reuse the same likes modal
        const modal = document.getElementById('likesModal');
        if (!modal) {
            console.error('[Comment Likes Modal] Modal element not found');
            return;
        }
        
        window.commentLikesState.initialized = true;
        
        // Define global function for opening comment likes modal
        window.openCommentLikesModal = function(commentId, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Reset state for new modal opening
            window.commentLikesState.modalCommentId = commentId;
            window.commentLikesState.offset = 0;
            window.commentLikesState.loading = false;
            window.commentLikesState.hasMore = true;
            window.commentLikesState.userSeen = {};
            
            // Update modal title to indicate these are comment likes
            const modalTitle = modal.querySelector('h2');
            if (modalTitle) {
                modalTitle.textContent = 'Comment Likes';
            }
            
            // Clear and show loading state
            const likesContainer = document.getElementById('likesContainer');
            if (likesContainer) {
                likesContainer.innerHTML = '<div class="flex justify-center p-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-neutral-900 dark:border-neutral-100"></div></div>';
            } else {
                console.error('[Comment Likes Modal] Likes container not found');
            }
            
            // Show modal
            modal.classList.remove('hidden');
            document.body.classList.add('no-scroll');
            
            // Set up scroll event for infinite scrolling
            if (likesContainer) {
                likesContainer.addEventListener('scroll', handleCommentLikesScroll);
            }
            
            // Load initial likes
            loadCommentLikes();
        };
        
        // Define function to load comment likes
        window.loadCommentLikes = loadCommentLikes;
        window.handleCommentLikesScroll = handleCommentLikesScroll;
        
    } catch (error) {
        console.error('[Comment Likes Modal] Error during initialization:', error);
    }
}

// Load comment likes
function loadCommentLikes() {
    try {
        // Prevent multiple simultaneous requests
        if (window.commentLikesState.loading) {
            return;
        }
        
        const commentId = window.commentLikesState.modalCommentId;
        const offset = window.commentLikesState.offset;
        
        if (!commentId) {
            console.error('[Comment Likes Modal] No comment ID set');
            return;
        }
        
        // Set loading state
        window.commentLikesState.loading = true;
        const loaderElement = document.getElementById('likesLoader');
        if (loaderElement) {
            loaderElement.classList.remove('hidden');
        }
        
        // Check cache first
        const cacheKey = `${commentId}-${offset}`;
        if (window.commentLikesState.cachedLikes[cacheKey]) {
            renderCommentLikes(window.commentLikesState.cachedLikes[cacheKey]);
            return;
        }
        
        // Fetch likes from server
        fetch(`utils/get_comment_likes.php?comment_id=${commentId}&offset=${offset}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cache the results
                    window.commentLikesState.cachedLikes[cacheKey] = data;
                    
                    // Render the likes
                    renderCommentLikes(data);
                } else {
                    console.error('[Comment Likes Modal] Error loading likes:', data.error);
                    const likesContainer = document.getElementById('likesContainer');
                    if (likesContainer && offset === 0) {
                        likesContainer.innerHTML = '<div class="p-4 text-center text-neutral-500">Failed to load likes. Please try again.</div>';
                    }
                }
            })
            .catch(error => {
                console.error('[Comment Likes Modal] Fetch error:', error);
                const likesContainer = document.getElementById('likesContainer');
                if (likesContainer && offset === 0) {
                    likesContainer.innerHTML = '<div class="p-4 text-center text-neutral-500">Failed to load likes. Please try again.</div>';
                }
            })
            .finally(() => {
                // Reset loading state
                window.commentLikesState.loading = false;
                const loaderElement = document.getElementById('likesLoader');
                if (loaderElement) {
                    loaderElement.classList.add('hidden');
                }
            });
    } catch (error) {
        console.error('[Comment Likes Modal] Error in loadCommentLikes:', error);
        window.commentLikesState.loading = false;
    }
}

// Render comment likes in the modal
function renderCommentLikes(data) {
    try {
        const likesContainer = document.getElementById('likesContainer');
        if (!likesContainer) {
            console.error('[Comment Likes Modal] Likes container not found');
            return;
        }
        
        // Clear container if this is the first batch
        if (window.commentLikesState.offset === 0) {
            likesContainer.innerHTML = '';
        }
        
        // If no likes, show message
        if (data.likes.length === 0 && window.commentLikesState.offset === 0) {
            likesContainer.innerHTML = '<div class="p-4 text-center text-neutral-500">No likes yet.</div>';
            return;
        }
        
        // Process and render each like
        data.likes.forEach(user => {
            // Skip users we've already shown
            if (window.commentLikesState.userSeen[user.user_id]) {
                return;
            }
            
            // Mark user as seen
            window.commentLikesState.userSeen[user.user_id] = true;
            
            // Create the user element
            const userElement = document.createElement('div');
            userElement.className = 'flex items-center justify-between p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-lg';
            
            userElement.innerHTML = `
                <div class="flex items-center gap-3">
                    <a href="user.php?username=${user.username}" class="block">
                        <div class="relative flex shrink-0 overflow-hidden rounded-full w-10 h-10">
                            <div class="relative aspect-square h-full w-full rounded-full">
                                <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                    <img src="${user.profile_picture || './images/profile_placeholder.webp'}" 
                                         alt="${user.username}'s profile picture" 
                                         class="object-cover rounded-full w-10 h-10">
                                </div>
                            </div>
                        </div>
                    </a>
                    <div class="flex flex-col">
                        <a href="user.php?username=${user.username}" class="text-sm font-semibold flex items-center">
                            ${user.username}
                            ${user.verifybadge ? `
                            <span class="inline-flex flex-shrink-0 ml-1">
                                <div class="relative inline-block h-3.5 w-3.5">
                                    <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                        <title>Verified</title>
                                        <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </span>
                            ` : ''}
                        </a>
                    </div>
                </div>
                ${user.user_id !== window.currentUserId ? `
                <button class="follow-button px-2.5 py-1 rounded-lg text-sm font-semibold ${user.is_following ? 'bg-neutral-200 dark:bg-neutral-800 text-neutral-900 dark:text-neutral-100' : 'bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900'}" 
                        data-user-id="${user.user_id}" 
                        data-following="${user.is_following ? '1' : '0'}" 
                        onclick="toggleFollow(this, ${user.user_id})">
                    ${user.is_following ? 'Following' : 'Follow'}
                </button>
                ` : ''}
            `;
            
            likesContainer.appendChild(userElement);
        });
        
        // Update state
        window.commentLikesState.hasMore = data.hasMore;
        window.commentLikesState.offset += data.likes.length;
        
    } catch (error) {
        console.error('[Comment Likes Modal] Error rendering likes:', error);
    }
}

// Handle scroll event for infinite loading
function handleCommentLikesScroll() {
    try {
        const likesContainer = document.getElementById('likesContainer');
        if (!likesContainer) return;
        
        // Check if we're near the bottom
        const isNearBottom = likesContainer.scrollTop + likesContainer.clientHeight >= likesContainer.scrollHeight - 100;
        
        // Load more if we're near bottom, have more to load, and not currently loading
        if (isNearBottom && window.commentLikesState.hasMore && !window.commentLikesState.loading) {
            loadCommentLikes();
        }
    } catch (error) {
        console.error('[Comment Likes Modal] Error in scroll handler:', error);
    }
}

// Initialize when the DOM is loaded
document.addEventListener('DOMContentLoaded', initializeCommentLikesModal);
