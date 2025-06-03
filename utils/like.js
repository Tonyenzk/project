// Centralized like handling system
class LikeSystem {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Add click event listeners to all like buttons
        document.addEventListener('click', (event) => {
            const likeButton = event.target.closest('button[data-like-button-id]');
            if (likeButton) {
                this.handleLike(likeButton);
            }
        });

        // Add click event listener for modal like button
        const modalLikeButton = document.getElementById('modalLikeButton');
        if (modalLikeButton) {
            modalLikeButton.addEventListener('click', (event) => {
                this.handleLike(event.currentTarget);
            });
        }
    }

    handleLike(button) {
        // Get the heart icon and its current state
        const heartIcon = button.querySelector('svg');
        const isLiked = heartIcon.classList.contains('text-red-500');
        const newLikeState = !isLiked;
        
        // INSTANT UI UPDATE - First priority
        if (heartIcon) {
            // Remove any existing transitions and force immediate update
            heartIcon.style.transition = 'none';
            
            // Update classes immediately
            if (newLikeState) {
                heartIcon.classList.add('text-red-500', 'fill-red-500');
            } else {
                heartIcon.classList.remove('text-red-500', 'fill-red-500');
            }
            
            // Force a reflow to ensure class changes take effect
            heartIcon.getBoundingClientRect();
            
            // Add scale animation in the next frame for smoother transition
            if (newLikeState) {
                requestAnimationFrame(() => {
                    heartIcon.style.transform = 'scale(1.25)';
                    requestAnimationFrame(() => {
                        heartIcon.style.transform = 'scale(1)';
                    });
                });
            }
        }
        
        // Get the post ID for other operations
        const postId = String(button.dataset.likeButtonId ? 
            button.dataset.likeButtonId.replace('like-', '') : 
            button.dataset.postId);
        
        // Update any other instances of this post in the UI
        requestAnimationFrame(() => {
            this.updateLikeUI(postId, newLikeState);
        });
        
        // Process all background tasks after visual feedback
        requestAnimationFrame(() => {
            // Server communication
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);
            
            fetch('../utils/like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}&action=${isLiked ? 'unlike' : 'like'}`,
                signal: controller.signal
            })
            .then(response => response.json())
            .then(data => {
                clearTimeout(timeoutId);
                if (data.success) {
                    const serverLikeState = data.action === 'liked';
                    const newLikeCount = data.like_count;
                    this.updateLikeState(postId, newLikeCount, serverLikeState);
                } else {
                    console.error('Error:', data.message);
                    this.updateLikeUI(postId, isLiked);
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                console.error('Error:', error);
                this.updateLikeUI(postId, isLiked);
            });
        });
    }

    // Update just the UI for like button (heart color) - for optimistic updates
    updateLikeUI(postId, isLiked) {
        // Update all like buttons for this post
        const likeButtons = document.querySelectorAll(`button[data-like-button-id="like-${postId}"], button[data-post-id="${postId}"]`);
        
        likeButtons.forEach(button => {
            const heartIcon = button.querySelector('svg');
            if (heartIcon) {
                heartIcon.classList.toggle('text-red-500', isLiked);
                heartIcon.classList.toggle('fill-red-500', isLiked);
            }
        });
    }
    
    updateLikeState(postId, likeCount, isLiked) {
        // First update the like UI (heart icon)
        this.updateLikeUI(postId, isLiked);
        
        // Then update like counts

        // Update like count displays
        const likeCountElements = document.querySelectorAll(`[data-like-count-id="like-count-${postId}"]`);
        likeCountElements.forEach(element => {
            if (likeCount === 0) {
                element.innerHTML = '<div class="font-normal text-sm text-neutral-500 dark:text-neutral-400">Be the first to like this</div>';
            } else {
                element.innerHTML = `<button onclick="openLikesModal('${postId}', event)" class="font-semibold text-sm text-left hover:underline text-neutral-900 dark:text-neutral-100">${likeCount} like${likeCount === 1 ? '' : 's'}</button>`;
            }
        });

        // Update modal if it's open for this post
        const modal = document.getElementById('postModal');
        if (modal && modal.dataset.postId === postId) {
            const modalLikeCount = document.getElementById('modalLikeCount');
            if (modalLikeCount) {
                if (likeCount === 0) {
                    modalLikeCount.innerHTML = '<div class="font-normal text-sm text-neutral-500 dark:text-neutral-400">Be the first to like this</div>';
                } else {
                    modalLikeCount.innerHTML = `<button onclick="openLikesModal('${postId}', event)" class="font-semibold text-sm text-left hover:underline text-neutral-900 dark:text-neutral-100">${likeCount} like${likeCount === 1 ? '' : 's'}</button>`;
                }
            }
        }

        // Update grid view like counts
        const gridLikeCounts = document.querySelectorAll(`[data-grid-like-count="${postId}"]`);
        gridLikeCounts.forEach(element => {
            element.textContent = likeCount;
        });
        
        // If likes modal is open for this post, refresh the likes data
        if (window.likesModalPostId === postId) {
            // Reset likes modal and reload likes
            window.likesState.offset = 0;
            window.likesState.userSeen = {};
            loadLikes();
        }
    }
}

// Initialize the like system when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.likeSystem = new LikeSystem();
}); 