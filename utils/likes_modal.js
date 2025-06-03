// Centralized likes modal handler - Completely revised version

// Force console to be available
if (typeof console === 'undefined') {
    window.console = {
        log: function() {},
        error: function() {},
        warn: function() {},
        info: function() {}
    };
}

// Global state variables
window.likesState = {
    initialized: false,
    modalPostId: null,
    offset: 0,
    loading: false,
    hasMore: false,
    cachedLikes: {}, // Cache likes data by post ID
    userSeen: {} // Track which users have already been seen in the current modal
};

// Initialize the likes modal system
function initializeLikesModalHandler() {
    try {
        // Prevent multiple initializations
        if (window.likesState.initialized) {
            return;
        }
        
        // Check if modal exists
        const modal = document.getElementById('likesModal');
        if (!modal) {
            console.error('[Likes Modal] Modal element not found');
            return;
        }
        
        window.likesState.initialized = true;
        
        // Define global functions
        window.openLikesModal = function(postId, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Reset state for new modal opening
            window.likesState.modalPostId = postId;
            window.likesState.offset = 0;
            window.likesState.loading = false;
            window.likesState.hasMore = true;
            window.likesState.userSeen = {};
            
            // Clear and show loading state
            const likesContainer = document.getElementById('likesContainer');
            if (likesContainer) {
                likesContainer.innerHTML = '<div class="flex justify-center p-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-neutral-900 dark:border-neutral-100"></div></div>';
            } else {
                console.error('[Likes Modal] Likes container not found');
            }
            
            // Show modal
            modal.classList.remove('hidden');
            document.body.classList.add('no-scroll');
            
            // Set up scroll event for infinite scrolling
            if (likesContainer) {
                likesContainer.addEventListener('scroll', handleLikesScroll);
            }
            
            // Load initial likes
            loadLikes();
        };
        
        window.closeLikesModal = closeLikesModal;
        window.loadLikes = loadLikes;
        window.handleLikesScroll = handleLikesScroll;
        
        // Set up global click handler for likes count buttons
        setupLikesButtonHandlers();
    } catch (error) {
        console.error('[Likes Modal] Error during initialization:', error);
    }
}

// Set up click handlers for likes count buttons
function setupLikesButtonHandlers() {
    document.addEventListener('click', function(event) {
        // Find if the click was on a like count button or its children
        const likeButton = event.target.closest('button');
        if (!likeButton) return;
        
        // Method 1: Check for our specific class
        if (likeButton.classList.contains('likes-count-button')) {
            const postId = likeButton.dataset.postId;
            if (postId) {
                openLikesModal(postId, event);
                return;
            }
        }
        
        // Method 2: Check button text content as fallback
        const buttonText = likeButton.textContent.trim().toLowerCase();
        if (buttonText.includes('like') && 
            !buttonText.includes('first') && 
            !likeButton.classList.contains('inline-flex') && 
            !likeButton.hasAttribute('data-like-button-id')) {
            
            // Extract post ID from container
            let postId = null;
            const postContainer = likeButton.closest('[data-post-id]');
            const likeCountContainer = likeButton.closest('[data-like-count-id]');
            
            if (postContainer) {
                postId = postContainer.dataset.postId.replace('post-', '');
            } else if (likeCountContainer) {
                postId = likeCountContainer.dataset.likeCountId.replace('like-count-', '');
            }
            
            if (postId) {
                openLikesModal(postId, event);
            }
        }
    });
}

// Close the likes modal
function closeLikesModal() {
    const modal = document.getElementById('likesModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('no-scroll');
    } else {
        console.error('[Likes Modal] Modal element not found when trying to close');
    }
    
    // Remove scroll listener
    const container = document.getElementById('likesContainer');
    if (container) {
        container.removeEventListener('scroll', handleLikesScroll);
    }
    
    // Reset state
    window.likesState.modalPostId = null;
}

// Handle scrolling in the likes container
function handleLikesScroll() {
    if (window.likesState.loading || !window.likesState.hasMore) return;
    
    const container = document.getElementById('likesContainer');
    if (!container) {
        console.error('[Likes Modal] Container not found during scroll');
        return;
    }
    
    const scrollPosition = container.scrollTop + container.clientHeight;
    const scrollHeight = container.scrollHeight;
    
    // Load more when user scrolls to bottom (with a small threshold)
    if (scrollPosition >= scrollHeight - 100) {
        loadLikes();
    }
}

// Load likes from the server
function loadLikes() {
    if (window.likesState.loading || !window.likesState.modalPostId) {
        return;
    }
    
    window.likesState.loading = true;
    
    // Show loader at bottom if not first load
    if (window.likesState.offset > 0) {
        document.getElementById('likesLoader').classList.remove('hidden');
    }
    
    // Clear cache if this is first load
    if (window.likesState.offset === 0) {
        window.likesState.userSeen = {};
    }
    
    const apiUrl = `/utils/get_post_likes.php?post_id=${window.likesState.modalPostId}&offset=${window.likesState.offset}`;
    
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const likesContainer = document.getElementById('likesContainer');
                
                // Clear container if first load
                if (window.likesState.offset === 0) {
                    likesContainer.innerHTML = '';
                }
                
                // Update likes state
                window.likesState.hasMore = data.data.has_more;
                window.likesState.offset = data.data.next_offset;
                
                // Add likes to container, avoiding duplicates
                data.data.likes.forEach(like => {
                    // Skip if we've already shown this user
                    if (window.likesState.userSeen[like.user_id]) {
                        return;
                    }
                    
                    // Mark this user as seen
                    window.likesState.userSeen[like.user_id] = true;
                    
                    // Create and add the like element
                    const likeElement = createLikeElement(like);
                    likesContainer.appendChild(likeElement);
                });
                
                // Update title with count
                document.querySelector('#likesModal h2').textContent = 
                    `Likes${data.data.total_likes > 0 ? ` (${data.data.total_likes})` : ''}`;
                
                // Show empty state if needed
                if (data.data.total_likes === 0 && window.likesState.offset === 0) {
                    likesContainer.innerHTML = '<div class="text-center p-4 text-neutral-500 dark:text-neutral-400">No likes yet</div>';
                }
            } else {
                console.error('[Likes Modal] Failed to load likes:', data.error || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('[Likes Modal] Network error loading likes:', error);
        })
        .finally(() => {
            window.likesState.loading = false;
            document.getElementById('likesLoader').classList.add('hidden');
        });
}

// Create a like list item element
function createLikeElement(user) {
    const isCurrentUser = user.user_id === window.currentUserId;
    
    // Create the main container div
    const container = document.createElement('div');
    container.className = 'flex items-center justify-between px-4 py-2';
    
    // Create the user link
    const userLink = document.createElement('a');
    userLink.className = 'flex items-center gap-x-3 flex-1 min-w-0';
    userLink.href = `user.php?username=${user.username}`;
    
    // Create profile picture container
    const profilePicContainer = document.createElement('span');
    profilePicContainer.className = 'relative flex shrink-0 overflow-hidden rounded-full h-8 w-8';
    
    const profilePicInner = document.createElement('div');
    profilePicInner.className = 'relative aspect-square h-full w-full rounded-full';
    
    const profilePicWrapper = document.createElement('div');
    profilePicWrapper.className = 'relative rounded-full overflow-hidden h-full w-full bg-background';
    
    const profilePic = document.createElement('img');
    profilePic.alt = `${user.username}'s profile picture`;
    profilePic.referrerPolicy = 'no-referrer';
    profilePic.loading = 'lazy';
    profilePic.decoding = 'async';
    profilePic.className = 'object-cover rounded-full h-8 w-8';
    profilePic.src = user.profile_picture || './images/profile_placeholder.webp';
    
    profilePicWrapper.appendChild(profilePic);
    profilePicInner.appendChild(profilePicWrapper);
    profilePicContainer.appendChild(profilePicInner);
    
    // Create user info container
    const userInfoContainer = document.createElement('div');
    userInfoContainer.className = 'flex flex-col flex-1 min-w-0';
    
    const usernameContainer = document.createElement('div');
    usernameContainer.className = 'flex items-center gap-1';
    
    const username = document.createElement('p');
    username.className = 'text-sm font-semibold truncate';
    username.textContent = user.username;
    
    usernameContainer.appendChild(username);
    
    // Add verified badge only if user is verified (check both verifybadge and verifyBadge for compatibility)
    if ((user.verifybadge && user.verifybadge === 'true') || (user.verifyBadge && user.verifyBadge === 'true')) {
        const verifiedBadge = document.createElement('div');
        verifiedBadge.className = 'relative inline-block h-3.5 w-3.5';
        verifiedBadge.innerHTML = `
            <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                <title>Verified</title>
                <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
            </svg>
        `;
        usernameContainer.appendChild(verifiedBadge);
    }
    
    const name = document.createElement('p');
    name.className = 'text-xs text-neutral-500 dark:text-neutral-400 truncate';
    name.textContent = user.name;
    
    userInfoContainer.appendChild(usernameContainer);
    userInfoContainer.appendChild(name);
    
    userLink.appendChild(profilePicContainer);
    userLink.appendChild(userInfoContainer);
    
    container.appendChild(userLink);
    
    return container;
}

// Initialize the likes modal system when the script loads
document.addEventListener('DOMContentLoaded', function() {
    if (!window.likesState.initialized) {
        initializeLikesModalHandler();
    }
});
