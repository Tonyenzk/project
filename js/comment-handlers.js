/**
 * Enhanced comment count updater for dashboard.
 * This script doesn't add duplicate form handlers - it just enhances the existing ones.
 */
document.addEventListener('DOMContentLoaded', function() {
    // We'll override the original fetch response handler to also update the count
    // This is done by intercepting the Fetch API
    const originalFetch = window.fetch;
    
    window.fetch = function(url, options) {
        // Call the original fetch
        const fetchPromise = originalFetch.apply(this, arguments);
        
        // If this is a comment submission
        if (typeof url === 'string' && url.includes('utils/comment.php') && 
            options && options.method === 'POST' && options.body) {
            
            // Extract the post ID from the request body
            const bodyParams = new URLSearchParams(options.body);
            const postId = bodyParams.get('post_id');
            
            if (postId) {
                // Clone the response to process it twice
                return fetchPromise.then(response => {
                    const clone = response.clone();
                    
                    // Process the cloned response to update the comment count
                    clone.json().then(data => {
                        if (data.success && typeof data.comment_count !== 'undefined') {
                            updateCommentCountDisplay(postId, data.comment_count);
                        }
                    }).catch(err => {
                        console.error('Error processing comment response:', err);
                    });
                    
                    // Return the original response for the existing handlers
                    return response;
                });
            }
        }
        
        // If not a comment submission, just return the original promise
        return fetchPromise;
    };
});

/**
 * Updates the comment count display for a post
 */
function updateCommentCountDisplay(postId, commentCount) {
    // Find the comment count container by its data attribute
    const commentCountContainer = document.querySelector(`[data-comment-count-id="comment-count-${postId}"]`);
    
    if (commentCountContainer) {
        // Update the container based on the comment count
        if (commentCount === 0) {
            commentCountContainer.innerHTML = '';
        } else {
            commentCountContainer.innerHTML = `
                <a class="text-sm text-neutral-500 dark:text-neutral-400 px-3 sm:px-0 hover:underline mt-1" 
                   href="javascript:void(0)" 
                   onclick="openPostModal('${postId}')">
                    View all ${commentCount} comment${commentCount === 1 ? '' : 's'}
                </a>
            `;
        }
        console.log(`Updated comment count for post ${postId} to ${commentCount}`);
    }
}
