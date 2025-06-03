<?php
if (!defined('INCLUDED_IN_PAGE')) {
    require_once 'includes/image_helper.php';
}

// Enable image caching for this page
$imageUrlCachingEnabled = true;
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <link rel="stylesheet" href="css/common.css">
      <link rel="stylesheet" href="css/toast.css">
      <?php include 'includes/header.php'; ?>
      <script src="utils/likes_modal.js"></script>
      <script src="utils/comment_likes_modal.js"></script>
      <style>
         /* Remove fade-in animation */
      </style>
   </head>
   <body>
<?php
// Initialize post variable to prevent warnings
$post = [
    'post_id' => 0,
    'like_count' => 0,
    'is_saved' => false,
    'caption' => '',
    'username' => '',
    'profile_picture' => '',
    'verifybadge' => 0,
    'created_at' => '',
    'is_liked' => false,
    'comments' => [],
    'deactivated_comments' => false
];
?>

<div id="postModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closePostModal()"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center" onclick="closePostModal()">
        <div class="bg-white dark:bg-black max-w-[1400px] w-[95%] max-h-[95vh] h-[95vh] flex rounded-lg overflow-hidden" onclick="event.stopPropagation()">
            <div class="relative flex-1 bg-black flex items-center justify-center h-full w-auto min-h-0 max-h-full">
                <div class="relative w-full h-full flex items-center justify-center bg-black">
                    <div class="w-full h-full flex items-center justify-center bg-black">
                        <img id="modalPostImage" alt="Post Image" loading="eager" width="1200" height="1200" decoding="sync" data-nimg="1" class="w-full h-full select-none object-contain">
                    </div>
                </div>
            </div>
            <div class="flex flex-col w-[400px] h-full bg-white dark:bg-black border-l border-neutral-200 dark:border-neutral-800 overflow-hidden">
                <div class="flex flex-col text-center sm:text-left flex-shrink-0 p-0 border-b border-neutral-200 dark:border-neutral-800 space-y-0">
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center gap-3">
                            <div><span class="relative flex shrink-0 overflow-hidden rounded-full w-8 h-8">
                                    <div class="relative aspect-square h-full w-full rounded-full">
                                        <div class="relative rounded-full overflow-hidden h-full w-full bg-background"><img id="modalUserProfilePic" alt="Profile picture" referrerpolicy="no-referrer" loading="lazy" decoding="async" data-nimg="fill" class="object-cover rounded-full w-8 h-8"></div>
                                    </div>
                                </span></div>
                            <div class="flex flex-col">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <a id="modalUsername" class="text-sm font-semibold" href="#"></a>
                                    <div id="modalVerifiedBadge" class="relative inline-block hidden">
                                        <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                            <title>Verified</title>
                                            <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <span class="text-neutral-500 dark:text-neutral-400 text-xs">•</span>
                                    <span id="modalTimeAgo" class="font-medium text-neutral-500 dark:text-neutral-400 text-xs"></span>
                                </div>
                                <div id="modalPostLocation" class="hidden"></div>
                            </div>
                        </div>
                        <div class="relative">
                            <button id="modalPostOptionsButton" class="hover:bg-gray-100 dark:hover:bg-neutral-800 p-2 rounded-full" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="modal-post-options" data-state="closed">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-more-horizontal h-5 w-5">
                                    <circle cx="12" cy="12" r="1"></circle>
                                    <circle cx="19" cy="12" r="1"></circle>
                                    <circle cx="5" cy="12" r="1"></circle>
                                </svg>
                            </button>
                            <div id="modal-post-options" class="hidden absolute top-full right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-neutral-900 ring-1 ring-black ring-opacity-5 z-50">
                                <div class="py-1" role="menu" aria-orientation="vertical">
                                    <button id="modalEditPostBtn" class="w-full text-left px-4 py-2 text-sm text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-800" role="menuitem">
                                        Edit
                                    </button>
                                    <button id="modalDeletePostBtn" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-neutral-100 dark:hover:bg-neutral-800" role="menuitem">
                                        Delete
                                    </button>
                                    <button id="modalReportPostBtn" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-neutral-100 dark:hover:bg-neutral-800 hidden" role="menuitem">
                                        Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div dir="ltr" class="relative overflow-hidden flex-1">
                    <div data-radix-scroll-area-viewport="" class="h-full w-full rounded-[inherit]">
                        <div class="flex flex-col h-full">
                            <div class="flex-shrink-0 px-4 py-3 border-b border-neutral-200 dark:border-neutral-800">
                                <div class="flex gap-3">
                                    <div class="flex-1 min-w-0 w-full">
                                        <div class="text-sm w-full">
                                            <div>
                                                <span>
                                                    <a id="modalPostUsername" class="font-semibold hover:underline mr-1" href="#"></a>
                                                    <span id="modalPostVerifiedBadge" class="inline-flex flex-shrink-0 mr-1 hidden">
                                                        <div class="relative inline-block h-3.5 w-3.5">
                                                            <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                                                <title>Verified</title>
                                                                <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    </span>
                                                    <span id="modalPostCaption" class="text-sm whitespace-pre-line break-all overflow-wrap overflow-hidden text-pretty"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="modalComments" class="flex-1 px-4 py-2 overflow-y-auto min-h-0">
                                <!-- Comments will be dynamically inserted here -->
                            </div>
                            <div id="commentsLoading" class="hidden px-4 py-2 text-center">
                                <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-neutral-300 border-t-neutral-600"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0 border-t border-neutral-200 dark:border-neutral-800 bg-white dark:bg-black">
                    <div class="px-4 py-2 flex flex-col gap-2">
                        <div class="relative flex flex-col w-full gap-y-1 pb-2">
                            <div class="flex items-start w-full gap-x-2">
                                <button id="modalLikeButton" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9" type="button" data-post-id="">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart h-6 w-6 transition-colors duration-200">
                                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                    </svg>
                                </button>
                                <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle h-6 w-6">
                                        <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                                    </svg>
                                </button>
                                <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-send h-6 w-6">
                                        <path d="m22 2-7 20-4-9-9-4Z"></path>
                                        <path d="M22 2 11 13"></path>
                                    </svg>
                                </button>
                                <div class="ml-auto">
                                    <button id="modalSaveButton" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-9 w-9" type="button" data-save-button-id="save-${post.post_id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark h-6 w-6 ${post.is_saved ? 'fill-current text-black dark:text-white' : ''}">
                                            <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <div id="modalLikeCount" class="font-normal text-sm text-neutral-500 dark:text-neutral-400" data-like-count-id="like-count-<?php echo $post['post_id']; ?>">
                                    <?php if ($post['like_count'] === 0): ?>
                                        <div class="font-normal text-sm text-neutral-500 dark:text-neutral-400">Be the first to like this</div>
                                    <?php else: ?>
                                        <button onclick="openLikesModal('<?php echo $post['post_id']; ?>', event)" class="font-semibold text-sm text-left hover:underline text-neutral-900 dark:text-neutral-100 likes-count-button" data-post-id="<?php echo $post['post_id']; ?>"><?php echo $post['like_count']; ?> like<?php echo $post['like_count'] === 1 ? '' : 's'; ?></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <form id="modalCommentForm" class="relative py-3 flex w-full px-3 pt-3 mt-2 border-t border-neutral-200 dark:border-neutral-800">
                            <div class="flex items-center space-x-2 w-full">
                                <?php if ($post['deactivated_comments']): ?>
                                <div class="w-full flex justify-center text-sm py-1 px-3 text-neutral-500 dark:text-neutral-400">
                                    Comentariile sunt dezactivate
                                </div>
                                <?php else: ?>
                                <button type="button" aria-label="Add emoji" class="rounded-full p-1.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-smile w-5 h-5 text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200 cursor-pointer transition-colors">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                        <line x1="9" x2="9.01" y1="9" y2="9"></line>
                                        <line x1="15" x2="15.01" y1="9" y2="9"></line>
                                    </svg>
                                </button>
                                <div class="space-y-2 w-full flex-1">
                                    <input placeholder="Add a comment..." class="bg-transparent text-sm border-none focus:outline-none w-full dark:text-neutral-200 placeholder-neutral-500 disabled:opacity-30" maxlength="150" type="text" value="" name="body" onkeypress="return preventSpecialChars(event)" onpaste="return false">
                                </div>
                                <button type="submit" class="text-neutral-500 text-sm font-semibold hover:text-neutral-700 dark:hover:text-neutral-300 disabled:cursor-not-allowed disabled:opacity-50 transition-colors">Post</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize current user ID
window.currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;

// Post view likes system initialization
window.initializePostviewLikes = function() {
    // Check if likes modal exists
    const likesModal = document.getElementById('likesModal');
    
    // Initialize likes modal system
    if (typeof initializeLikesModalHandler === 'function') {
        initializeLikesModalHandler();
    }
}

window.currentPostId = null;

<?php
// Pass the current user ID to JavaScript
if (isset($_SESSION['user_id'])) {
    echo "window.currentUserId = " . $_SESSION['user_id'] . ";\n";
} else {
    echo "window.currentUserId = null;\n";
}
?>

// Add these variables at the top of your script section
let currentPage = 1;
let isCommentsLoading = false;
let hasMoreComments = true;

// Function to update like counts with fresh data from server
function updateLikeCounts(postId) {
    // Fetch fresh post data
    fetch(`utils/get_post_data.php?post_id=${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const post = data.data;
                const likeCount = post.like_count;
                const isLiked = post.is_liked;

                // Update modal if it's open for this post
                if (window.currentPostId === postId) {
                    const modal = document.getElementById('postModal');
                    const modalLikeButton = modal.querySelector('#modalLikeButton');
                    const modalHeartIcon = modalLikeButton.querySelector('svg');
                    const modalLikeCount = modal.querySelector('#modalLikeCount');

                    if (modalHeartIcon) {
                        modalHeartIcon.classList.toggle('text-red-500', isLiked);
                        modalHeartIcon.classList.toggle('fill-red-500', isLiked);
                    }

                    if (modalLikeCount) {
                        if (likeCount === 0) {
                            modalLikeCount.innerHTML = '<div class="font-normal text-sm text-neutral-500 dark:text-neutral-400">Be the first to like this</div>';
                        } else {
                            modalLikeCount.innerHTML = `<button onclick="openLikesModal('${postId}', event)" class="font-semibold text-sm text-left hover:underline text-neutral-900 dark:text-neutral-100 likes-count-button" data-post-id="${postId}">${likeCount} like${likeCount === 1 ? '' : 's'}</button>`;
                        }
                    }
                }

                // Update dashboard
                const dashboardLikeButton = document.querySelector(`button[data-like-button-id="like-${postId}"]`);
                if (dashboardLikeButton) {
                    const dashboardHeartIcon = dashboardLikeButton.querySelector('svg');
                    if (dashboardHeartIcon) {
                        dashboardHeartIcon.classList.toggle('text-red-500', isLiked);
                        dashboardHeartIcon.classList.toggle('fill-red-500', isLiked);
                    }

                    const likeContainer = dashboardLikeButton.closest('.flex-col');
                    if (likeContainer) {
                        const buttonsContainer = likeContainer.querySelector('.flex.items-start');
                        if (buttonsContainer) {
                            let likeCountContainer = buttonsContainer.nextElementSibling;
                            if (!likeCountContainer) {
                                likeCountContainer = document.createElement('div');
                                likeContainer.appendChild(likeCountContainer);
                            }

                            if (likeCount === 0) {
                                likeCountContainer.innerHTML = '<div class="font-normal text-sm text-neutral-500 dark:text-neutral-400">Be the first to like this</div>';
                            } else {
                                likeCountContainer.innerHTML = `<button class="font-semibold text-sm text-left hover:underline text-neutral-900 dark:text-neutral-100">${likeCount} like${likeCount === 1 ? '' : 's'}</button>`;
                            }
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error fetching post data:', error);
        });
}

// Modify the updateComments function to handle pagination
function updateComments(postId, page = 1, append = false) {
    if (isCommentsLoading || !hasMoreComments) return;
    
    isCommentsLoading = true;
    const loadingIndicator = document.getElementById('commentsLoading');
    if (loadingIndicator) {
        loadingIndicator.classList.remove('hidden');
        // Add a small delay before showing the loading indicator to prevent flickering
        setTimeout(() => {
            if (isCommentsLoading) {
                loadingIndicator.classList.remove('hidden');
            }
        }, 300);
    }
    
    fetch(`utils/get_post_data.php?post_id=${postId}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const post = data.data;
                const commentsContainer = document.getElementById('modalComments');
                
                // Clear existing comments only if not appending
                if (!append) {
                    commentsContainer.innerHTML = '';
                }
                
                // Check if we have any new comments
                if (post.comments && post.comments.length > 0) {
                    // Add each comment
                    post.comments.forEach(comment => {
                        // Check if comment already exists to prevent duplicates
                        const existingComment = commentsContainer.querySelector(`[data-comment-id="${comment.comment_id}"]`);
                        if (!existingComment) {
                            const commentElement = document.createElement('div');
                            commentElement.className = 'flex gap-3 mb-4 group';
                            commentElement.setAttribute('data-comment-id', comment.comment_id);
                            
                            // Comment element generation
                            
                            commentElement.innerHTML = `
                                <div class="flex-shrink-0">
                                    <a href="user.php?username=${comment.username}" class="block">
                                        <div class="relative flex shrink-0 overflow-hidden rounded-full w-8 h-8">
                                            <div class="relative aspect-square h-full w-full rounded-full">
                                                <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                    <img src="${comment.profile_picture || './images/profile_placeholder.webp'}" 
                                                         alt="${comment.username}'s profile picture" 
                                                         class="object-cover rounded-full w-8 h-8">
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm">
                                        <div>
                                            <span>
                                                <a href="user.php?username=${comment.username}" class="font-semibold hover:underline mr-1">${comment.username}</a>
                                                ${comment.verifybadge ? `
                                                <span class="inline-flex flex-shrink-0 mr-1">
                                                    <div class="relative inline-block h-3.5 w-3.5">
                                                        <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                                            <title>Verified</title>
                                                            <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                        </svg>
                                                    </div>
                                                </span>
                                                ` : ''}
                                                <span class="text-sm whitespace-pre-line break-all overflow-wrap overflow-hidden text-pretty">${comment.content}</span>
                                            </span>
                                        </div>
                                        <div class="flex items-center mt-1 space-x-3 text-neutral-500 text-[11px]">
                                            <span class="font-medium text-neutral-500 dark:text-neutral-400 text-[11px]">
                                                <time datetime="${comment.created_at}" title="${comment.created_at}">${comment.time_ago}</time>
                                            </span>
                                            ${comment.like_count > 0 ? `
                                            <button class="font-semibold hover:underline text-[11px]">${comment.like_count} like${comment.like_count === 1 ? '' : 's'}</button>
                                            ` : ''}
                                            <button class="font-semibold text-[11px]" onclick="handleCommentReply('${comment.comment_id}', '${comment.username}')">Reply</button>
                                            <div class="relative flex items-center">
                                                <button class="hover:text-neutral-600 dark:hover:text-neutral-300 opacity-0 group-hover:opacity-100 transition-opacity comment-options-btn" type="button" aria-haspopup="dialog" aria-expanded="false" data-state="closed" data-comment-id="${comment.comment_id}" data-comment-user-id="${comment.user_id}" onclick="openCommentOptionsModal(this, event)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-more-horizontal h-4 w-4">
                                                        <circle cx="12" cy="12" r="1"></circle>
                                                        <circle cx="19" cy="12" r="1"></circle>
                                                        <circle cx="5" cy="12" r="1"></circle>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        ${comment.reply_count > 0 ? `
                                        <div class="mt-2">
                                            <button class="flex items-center space-x-1 text-neutral-500 text-[11px] font-semibold hover:text-neutral-700 dark:hover:text-neutral-300" onclick="toggleReplies('${comment.comment_id}', this)">
                                                <div class="flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down h-3 w-3 mr-1">
                                                        <path d="m6 9 6 6 6-6"></path>
                                                    </svg>
                                                    View ${comment.reply_count} repl${comment.reply_count === 1 ? 'y' : 'ies'}
                                                </div>
                                            </button>
                                            <div id="replies-${comment.comment_id}" class="hidden mt-2 ml-2 pl-3 border-l border-neutral-200 dark:border-neutral-700 w-full">
                                                <!-- Replies will be loaded here -->
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <button class="p-1 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-full transition-colors" data-comment-id="${comment.comment_id}" onclick="handleCommentLike('${comment.comment_id}', event)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart h-4 w-4 transition-colors duration-200 ${comment.is_liked ? 'text-red-500 fill-red-500' : 'dark:text-white'}">
                                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                        </svg>
                                    </button>
                                </div>
                            `;
                            commentsContainer.appendChild(commentElement);
                        }
                    });
                    
                    // Update pagination state
                    hasMoreComments = post.comments.length > 0;
                    currentPage = page;
                } else {
                    // No more comments to load
                    hasMoreComments = false;
                }
                
                // Remove existing load more button if any
                const existingLoadMoreBtn = document.getElementById('loadMoreCommentsBtn');
                if (existingLoadMoreBtn) {
                    existingLoadMoreBtn.remove();
                }
                
                // Add Load More button if there are more comments to load
                if (hasMoreComments && post.comments && post.comments.length > 0 && post.has_more_comments) {
                    const loadMoreBtn = document.createElement('div');
                    loadMoreBtn.id = 'loadMoreCommentsBtn';
                    loadMoreBtn.className = 'flex items-center justify-center my-3';
                    loadMoreBtn.innerHTML = `
                        <button onclick="loadMoreComments()" class="flex items-center justify-center space-x-1 px-3 py-1.5 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 rounded-full transition-colors text-xs font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                            Load More
                        </button>
                    `;
                    commentsContainer.appendChild(loadMoreBtn);
                } else if (page > 1) {
                    // Add a "No more comments" message if we're at the end and not on the first page
                    const endMessage = document.createElement('div');
                    endMessage.className = 'text-center text-neutral-500 dark:text-neutral-400 text-xs py-3';
                    endMessage.textContent = 'No more comments';
                    commentsContainer.appendChild(endMessage);
                }
            }
        })
        .catch(error => {
            console.error('Error fetching comments:', error);
        })
        .finally(() => {
            isCommentsLoading = false;
            if (loadingIndicator) {
                loadingIndicator.classList.add('hidden');
            }
        });
}

// Add this CSS to the head section of your document
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out forwards;
    }
`;
document.head.appendChild(style);

// Function to load more comments when button is clicked
function loadMoreComments() {
    if (isCommentsLoading || !hasMoreComments) return;
    
    currentPage++;
    updateComments(window.currentPostId, currentPage, true);
}

// We're using a Load More button instead of infinite scrolling
// No scroll listener needed

// Add time formatting function
function formatTimeAgo(timeDiff) {
    // Handle NaN or invalid values
    if (!timeDiff || isNaN(timeDiff)) {
        return 'Just now';
    }
    
    // Convert to number if it's a string
    timeDiff = Number(timeDiff);
    
    if (timeDiff < 60) {
        return 'Just now';
    } else if (timeDiff < 3600) {
        const minutes = Math.floor(timeDiff / 60);
        return `${minutes}m`;
    } else if (timeDiff < 86400) {
        const hours = Math.floor(timeDiff / 3600);
        return `${hours}h`;
    } else if (timeDiff < 604800) {
        const days = Math.floor(timeDiff / 86400);
        return `${days}d`;
    } else if (timeDiff < 2592000) {
        const weeks = Math.floor(timeDiff / 604800);
        return `${weeks}w`;
    } else if (timeDiff < 31536000) {
        const months = Math.floor(timeDiff / 2592000);
        return `${months}mo`;
    } else {
        const years = Math.floor(timeDiff / 31536000);
        return `${years}y`;
    }
}

// Modify the openPostModal function to reset pagination
function openPostModal(postId) {
    window.currentPostId = postId;
    currentPage = 1;
    hasMoreComments = true;
    isCommentsLoading = false;
    
    const modal = document.getElementById('postModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Set the post ID on the like button
    const modalLikeButton = document.getElementById('modalLikeButton');
    modalLikeButton.dataset.postId = postId;
    
    // Fetch post data and update both views
    updateLikeCounts(postId);
    updateComments(postId);
    
    // Fetch other post data
    fetch(`utils/get_post_data.php?post_id=${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const post = data.data;
                
                // Update post options based on ownership
                updatePostOptions(post);
                
                // Update post image with cached URL
                <?php if (isset($imageUrlCachingEnabled) && $imageUrlCachingEnabled): ?>
                // Use server-side cached URL (PHP generates this part)
                document.getElementById('modalPostImage').src = post.cached_image_url || post.image_url;
                <?php else: ?>
                // Fallback to standard URL if caching system not available
                document.getElementById('modalPostImage').src = post.image_url;
                <?php endif; ?>
                
                // Update user info
                document.getElementById('modalUserProfilePic').src = post.profile_picture || './images/profile_placeholder.webp';
                // Construct the header content based on tagged users
                const userInfoContainer = document.querySelector('.flex.items-center.gap-3 > div.flex.flex-col > div.flex.items-center.gap-1');
                if (userInfoContainer) {
                    let usernameHtml = `<a id="modalUsername" class="text-sm font-semibold" href="user.php?username=${post.username}">${post.username}</a>`;
                    
                    // Add verified badge if present
                    if (post.verifybadge === 'true') {
                        usernameHtml += `
                            <div id="modalVerifiedBadge" class="relative inline-block">
                                <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                    <title>Verified</title>
                                    <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                </svg>
                            </div>
                        `;
                    }
                    
                    // Add tagged users info
                    if (post.tagged_users && post.tagged_users.length > 0) {
                        if (post.tagged_users.length === 1) {
                            usernameHtml += ` <span class="text-sm text-neutral-500 dark:text-neutral-400">with</span> <a href="user.php?username=${post.tagged_users[0].username}" class="text-sm font-semibold hover:underline">${post.tagged_users[0].username}</a>`;
                        } else {
                            usernameHtml += ` <span class="text-sm font-semibold">x others</span>`;
                        }
                    }
                    
                    // Add separator and time ago
                    usernameHtml += `
                        <span class="text-neutral-500 dark:text-neutral-400 text-xs">•</span>
                        <span id="modalTimeAgo" class="font-medium text-neutral-500 dark:text-neutral-400 text-xs">${post.time_ago}</span>
                    `;
                    
                    // Update the container's inner HTML
                    userInfoContainer.innerHTML = usernameHtml;
                    
                    // Update the username link in the header to use user.php or profile.php
                    const usernameLink = userInfoContainer.querySelector('a#modalUsername');
                    if (usernameLink) {
                        usernameLink.href = post.is_owner ? 'profile.php' : `user.php?username=${post.username}`;
                    }
                }
                
                // Update post content
                document.getElementById('modalPostUsername').textContent = post.username;
                // Redirect to profile.php if it's the current user's post, otherwise to user.php
                document.getElementById('modalPostUsername').href = post.is_owner ? 'profile.php' : `user.php?username=${post.username}`;
                document.getElementById('modalPostVerifiedBadge').style.display = post.verifybadge === 'true' ? 'inline-flex' : 'none';
                
                // Set caption and add tagged users if present
                let captionText = post.caption || '';
                
                // Set caption with tagged users HTML
                document.getElementById('modalPostCaption').textContent = captionText;
                
                // Display location if available
                const locationContainer = document.getElementById('modalPostLocation');
                if (locationContainer) {
                    if (post.location) {
                        locationContainer.innerHTML = `
                            <a href="location.php?location=${encodeURIComponent(post.location)}" class="text-xs text-neutral-500 dark:text-neutral-400 hover:underline block mt-0.5 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline-block align-middle mr-1">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span class="align-middle">${post.location}</span>
                            </a>
                        `;
                        locationContainer.style.display = 'block';
                    } else {
                        locationContainer.style.display = 'none';
                    }
                }

                // Update save button state
                const saveButton = document.getElementById('modalSaveButton');
                const bookmarkIcon = saveButton.querySelector('svg');
                if (post.is_saved) {
                    bookmarkIcon.classList.add('fill-current', 'text-black', 'dark:text-white');
                } else {
                    bookmarkIcon.classList.remove('fill-current', 'text-black', 'dark:text-white');
                }

                // Update comment form based on deactivated_comments state
                const commentForm = document.getElementById('modalCommentForm');
                const commentFormContent = commentForm.querySelector('.flex.items-center.space-x-2.w-full');
                const commentsContainer = document.getElementById('modalComments');
                
                if (post.deactivated_comments) {
                    // Hide comment form and show message
                    commentFormContent.innerHTML = `
                        <div class="w-full flex justify-center text-sm py-1 px-3 text-neutral-500 dark:text-neutral-400">
                            Comentariile sunt dezactivate
                        </div>
                    `;
                    
                    // Clear and update comments container with message
                    commentsContainer.innerHTML = `
                        <div class="flex items-center justify-center h-32 text-neutral-500 dark:text-neutral-400 text-sm">
                            Comentariile au fost dezactivate pentru aceasta postare
                        </div>
                    `;
                } else {
                    // Show normal comment form
                    commentFormContent.innerHTML = `
                        <button type="button" aria-label="Add emoji" class="rounded-full p-1.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-smile w-5 h-5 text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200 cursor-pointer transition-colors">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" x2="9.01" y1="9" y2="9"></line>
                                <line x1="15" x2="15.01" y1="9" y2="9"></line>
                            </svg>
                        </button>
                        <div class="space-y-2 w-full flex-1">
                            <input placeholder="Add a comment..." class="bg-transparent text-sm border-none focus:outline-none w-full dark:text-neutral-200 placeholder-neutral-500 disabled:opacity-30" maxlength="150" type="text" value="" name="body" onkeypress="return preventSpecialChars(event)" onpaste="return false">
                        </div>
                        <button type="submit" class="text-neutral-500 text-sm font-semibold hover:text-neutral-700 dark:hover:text-neutral-300 disabled:cursor-not-allowed disabled:opacity-50 transition-colors">Post</button>
                    `;
                    
                    // Load comments if they're not deactivated
                    updateComments(postId);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function closePostModal() {
    const modal = document.getElementById('postModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    window.currentPostId = null;
    
    // We don't need to do anything else when closing the modal
    // The like state is already properly synchronized in real-time when the user clicks the like button
    // If we can't find the elements after closing, it means the page has been refreshed or navigated
    // which will automatically load the correct state from the server
}

// Close modal when pressing Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePostModal();
    }
});

// Handle comment form submission
document.getElementById('modalCommentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const input = this.querySelector('input[name="body"]');
    const comment = input.value.trim();
    const submitButton = this.querySelector('button[type="submit"]');
    const parentCommentId = this.dataset.parentCommentId;
    
    if (comment && window.currentPostId) {
        // Disable the form while submitting
        input.disabled = true;
        submitButton.disabled = true;
        
        // Build request body
        const requestBody = `post_id=${window.currentPostId}&body=${encodeURIComponent(comment)}${parentCommentId ? `&parent_comment_id=${parentCommentId}` : ''}`;
        
        // Send the comment to the server
        fetch('utils/comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: requestBody
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Clear the input and parent comment ID
                input.value = '';
                this.dataset.parentCommentId = '';
                
                if (parentCommentId) {
                    // After submitting a reply, load all replies for this comment
                    // This ensures all existing replies are displayed along with the new one
                    const repliesContainer = document.getElementById(`replies-${parentCommentId}`);
                    const repliesButton = document.querySelector(`button[onclick="toggleReplies('${parentCommentId}', this)"]`);
                    
                    if (repliesContainer && repliesButton) {
                        // Load all replies to ensure the new one is included with all existing ones
                        loadReplies(parentCommentId);
                        
                        // Always open the replies section when a new reply is added
                        repliesContainer.classList.remove('hidden');
                        
                        // Update the button to show "Hide replies"
                        const chevronIcon = repliesButton.querySelector('svg');
                        if (chevronIcon) {
                            chevronIcon.classList.replace('lucide-chevron-down', 'lucide-chevron-up');
                        }
                        
                        const textDiv = repliesButton.querySelector('div');
                        if (textDiv) {
                            textDiv.innerHTML = `
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-up h-3 w-3 mr-1">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                    Hide replies
                                </div>
                            `;
                        }
                    }
                }
                else {
                    // If this is a top-level comment, add it to the comments container
                    const commentsContainer = document.getElementById('modalComments');
                    const commentElement = document.createElement('div');
                    commentElement.className = 'flex gap-3 mb-4 group animate-fade-in';
                    commentElement.setAttribute('data-comment-id', data.comment.comment_id);
                    
                    commentElement.innerHTML = `
                        <div class="flex-shrink-0">
                            <a href="user.php?username=${data.comment.username}" class="block">
                                <div class="relative flex shrink-0 overflow-hidden rounded-full w-8 h-8">
                                    <div class="relative aspect-square h-full w-full rounded-full">
                                        <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                            <img src="${data.comment.profile_picture || './images/profile_placeholder.webp'}" 
                                                 alt="${data.comment.username}'s profile picture" 
                                                 class="object-cover rounded-full w-8 h-8">
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm">
                                <div>
                                    <span>
                                        <a href="user.php?username=${data.comment.username}" class="font-semibold hover:underline mr-1">${data.comment.username}</a>
                                        ${data.comment.verifybadge ? `
                                        <span class="inline-flex flex-shrink-0 mr-1">
                                            <div class="relative inline-block h-3.5 w-3.5">
                                                <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                                    <title>Verified</title>
                                                    <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                        </span>
                                        ` : ''}
                                        <span class="text-sm whitespace-pre-line break-all overflow-wrap overflow-hidden text-pretty">${data.comment.content}</span>
                                    </span>
                                </div>
                                <div class="flex items-center mt-1 space-x-3 text-neutral-500 text-[11px]">
                                    <span class="font-medium text-neutral-500 dark:text-neutral-400 text-[11px]">
                                        <time datetime="${data.comment.created_at}" title="${data.comment.created_at}">${data.comment.time_ago}</time>
                                    </span>
                                    <button class="font-semibold text-[11px]" onclick="handleCommentReply('${data.comment.comment_id}', '${data.comment.username}')">Reply</button>
                                    <div class="relative flex items-center">
                                        <button class="hover:text-neutral-600 dark:hover:text-neutral-300 opacity-0 group-hover:opacity-100 transition-opacity comment-options-btn" type="button" aria-haspopup="dialog" aria-expanded="false" data-state="closed" data-comment-id="${data.comment.comment_id}" data-comment-user-id="${data.comment.user_id}" onclick="openCommentOptionsModal(this, event)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-more-horizontal h-4 w-4">
                                                <circle cx="12" cy="12" r="1"></circle>
                                                <circle cx="19" cy="12" r="1"></circle>
                                                <circle cx="5" cy="12" r="1"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <button class="p-1 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-full transition-colors" data-comment-id="${data.comment.comment_id}" onclick="handleCommentLike('${data.comment.comment_id}', event)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart h-4 w-4 transition-colors duration-200 dark:text-white">
                                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                    
                    // Insert the new comment at the top
                    if (commentsContainer.firstChild) {
                        commentsContainer.insertBefore(commentElement, commentsContainer.firstChild);
                    } else {
                        commentsContainer.appendChild(commentElement);
                    }
                }
                
                // Update comment count in the dashboard
                const dashboardCommentCount = document.querySelector(`[data-comment-count-id="comment-count-${window.currentPostId}"]`);
                if (dashboardCommentCount) {
                    if (data.comment_count === 0) {
                        dashboardCommentCount.innerHTML = '';
                    } else {
                        dashboardCommentCount.innerHTML = `
                            <a class="text-sm text-neutral-500 dark:text-neutral-400 px-3 sm:px-0 hover:underline mt-1" href="javascript:void(0)" onclick="openPostModal('${window.currentPostId}')">
                                View all ${data.comment_count} comment${data.comment_count === 1 ? '' : 's'}
                            </a>
                        `;
                    }
                }
                
                // Show success message
                showToast('Comment added successfully', 'success');
            } else {
                showToast(data.message || 'Failed to add comment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred while adding the comment', 'error');
        })
        .finally(() => {
            // Re-enable the form
            input.disabled = false;
            submitButton.disabled = false;
        });
    }
});

// Add input event listener to enable/disable submit button
document.querySelector('#modalCommentForm input[name="body"]').addEventListener('input', function() {
    const submitButton = this.closest('form').querySelector('button[type="submit"]');
    const currentLength = this.value.length;
    
    // Enable/disable submit button and update its style
    const hasText = currentLength > 0;
    if (hasText) {
        submitButton.classList.remove('text-neutral-500', 'dark:hover:text-neutral-300');
        submitButton.classList.add('text-sky-500', 'hover:text-sky-700', 'dark:hover:text-sky-400');
    } else {
        submitButton.classList.remove('text-sky-500', 'hover:text-sky-700', 'dark:hover:text-sky-400');
        submitButton.classList.add('text-neutral-500', 'dark:hover:text-neutral-300');
    }
    
    submitButton.disabled = !hasText;
});

// Add event listener for the modal like button
document.getElementById('modalLikeButton').addEventListener('click', function(event) {
    const button = event.currentTarget;
    const postId = button.dataset.postId;
    
    // Send the like/unlike request to the server
    fetch('utils/like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId + '&action=' + (button.querySelector('svg').classList.contains('text-red-500') ? 'unlike' : 'like')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update both views with fresh data
            updateLikeCounts(postId);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});

// Add event listener for dashboard like buttons
document.addEventListener('click', function(event) {
    const likeButton = event.target.closest('button[data-like-button-id]');
    if (likeButton) {
        const postId = likeButton.dataset.likeButtonId.replace('like-', '');
        
        // Send the like/unlike request to the server
        fetch('utils/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'post_id=' + postId + '&action=' + (likeButton.querySelector('svg').classList.contains('text-red-500') ? 'unlike' : 'like')
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update both views with fresh data
                updateLikeCounts(postId);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
});

// Remove the old like button event listeners since they're now handled by the LikeSystem class
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any other post view specific functionality here
    const modal = document.getElementById('postModal');
    if (modal) {
        modal.dataset.postId = '<?php echo $post['post_id']; ?>';
    }
});

// Add these functions after the existing JavaScript code
function handleCommentLike(commentId, event) {
    if (!event || !event.currentTarget) return;
    
    event.preventDefault();
    event.stopPropagation();
    
    const likeButton = event.currentTarget;
    const heartIcon = likeButton.querySelector('svg');
    const isLiked = heartIcon.classList.contains('text-red-500');
    
    fetch('utils/like_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `comment_id=${commentId}&action=${isLiked ? 'unlike' : 'like'}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the heart icon
            if (heartIcon) {
                if (data.is_liked) {
                    heartIcon.classList.add('text-red-500', 'fill-red-500');
                    heartIcon.classList.remove('dark:text-white');
                } else {
                    heartIcon.classList.remove('text-red-500', 'fill-red-500');
                    heartIcon.classList.add('dark:text-white');
                }
            }
            
            // Find the comment container
            const commentContainer = likeButton.closest('.flex.gap-3.mb-4');
            if (!commentContainer) return;
            
            // Find the actions container
            const actionsContainer = commentContainer.querySelector('.flex.items-center.mt-1.space-x-3');
            if (!actionsContainer) return;
            
            // Find or create the like count button
            let likeCountButton = actionsContainer.querySelector('.font-semibold.hover\\:underline');
            
            if (data.like_count === 0) {
                if (likeCountButton) {
                    likeCountButton.remove();
                }
            } else {
                if (!likeCountButton) {
                    likeCountButton = document.createElement('button');
                    likeCountButton.className = 'font-semibold hover:underline text-[11px] comment-likes-button';
                    likeCountButton.setAttribute('data-comment-id', commentId);
                    likeCountButton.onclick = function(e) { openCommentLikesModal(commentId, e); };
                    const timeElement = actionsContainer.querySelector('time');
                    if (timeElement && timeElement.parentElement) {
                        timeElement.parentElement.insertAdjacentElement('afterend', likeCountButton);
                    }
                }
                likeCountButton.textContent = `${data.like_count} like${data.like_count === 1 ? '' : 's'}`;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function handleCommentReply(commentId, username) {
    const commentForm = document.getElementById('modalCommentForm');
    const commentInput = commentForm.querySelector('input[name="body"]');
    
    // Set the input value to @username
    commentInput.value = `@${username} `;
    commentInput.focus();
    
    // Store the parent comment ID for the reply
    commentForm.dataset.parentCommentId = commentId;
    
    // Enable the submit button
    const submitButton = commentForm.querySelector('button[type="submit"]');
    submitButton.classList.remove('text-neutral-500', 'dark:hover:text-neutral-300');
    submitButton.classList.add('text-sky-500', 'hover:text-sky-700', 'dark:hover:text-sky-400');
    submitButton.disabled = false;

    // Scroll the comment form into view
    commentForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function toggleReplies(commentId, button) {
    const repliesContainer = document.getElementById(`replies-${commentId}`);
    
    if (!repliesContainer) {
        return;
    }
    
    const isHidden = repliesContainer.classList.contains('hidden');
    
    const chevronIcon = button.querySelector('svg');
    const textDiv = button.querySelector('div');
    
    if (isHidden) {
        // Check if there are actual reply elements (not just whitespace)
        const hasReplies = repliesContainer.querySelector('.group') !== null;
        
        if (!hasReplies) {
            loadReplies(commentId);
        } else {
            // If replies are already loaded, just show them
            repliesContainer.classList.remove('hidden');
            if (chevronIcon) {
                chevronIcon.classList.replace('lucide-chevron-down', 'lucide-chevron-up');
            }
            if (textDiv) {
                textDiv.textContent = 'Hide replies';
            }
        }
    } else {
        repliesContainer.classList.add('hidden');
        if (chevronIcon) {
            chevronIcon.classList.replace('lucide-chevron-up', 'lucide-chevron-down');
        }
        if (textDiv) {
            // Get the actual reply count from the container
            const replyCount = repliesContainer.querySelectorAll('.reply-container').length;
            textDiv.innerHTML = `
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down h-3 w-3 mr-1">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                    View ${replyCount} repl${replyCount === 1 ? 'y' : 'ies'}
                </div>
            `;
        }
    }
}

function loadReplies(commentId) {
    fetch(`utils/get_comment_replies.php?comment_id=${commentId}`)
        .then(response => {
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const repliesContainer = document.getElementById(`replies-${commentId}`);
                
                if (!repliesContainer) {
                    return;
                }
                
                // Clear any existing replies
                repliesContainer.innerHTML = '';
                
                if (data.replies && data.replies.length > 0) {
                    // Create the replies container with proper styling
                    repliesContainer.innerHTML = '';
                    repliesContainer.className = 'mt-2 ml-2 pl-3 border-l border-neutral-200 dark:border-neutral-700 w-full';
                    
                    // Add replies in reverse order (newest first)
                    data.replies.reverse().forEach((reply, index) => {
                        const replyElement = document.createElement('div');
                        replyElement.className = 'flex gap-3 mb-4 group reply-container hover:bg-neutral-50 dark:hover:bg-neutral-900/50 rounded-lg p-1 transition-colors';
                        replyElement.setAttribute('data-comment-id', reply.comment_id);
                        replyElement.innerHTML = `
                            <div class="flex-shrink-0">
                                <a href="user.php?username=${reply.username}" class="block">
                                    <div class="relative flex shrink-0 overflow-hidden rounded-full w-8 h-8">
                                        <div class="relative aspect-square h-full w-full rounded-full">
                                            <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                <img src="${reply.profile_picture || './images/profile_placeholder.webp'}" 
                                                     alt="${reply.username}'s profile picture" 
                                                     class="object-cover rounded-full w-8 h-8">
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">
                                    <div>
                                        <span>
                                            <a href="user.php?username=${reply.username}" class="font-semibold hover:underline mr-1">${reply.username}</a>
                                            ${reply.verifybadge ? `
                                            <span class="inline-flex flex-shrink-0 mr-1">
                                                <div class="relative inline-block h-3.5 w-3.5">
                                                    <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                                                        <title>Verified</title>
                                                        <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                    </svg>
                                                </div>
                                            </span>
                                            ` : ''}
                                            <span class="text-sm whitespace-pre-line break-all overflow-wrap overflow-hidden text-pretty">${reply.content}</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center mt-1 space-x-3 text-neutral-500 text-[11px]">
                                        <span class="font-medium text-neutral-500 dark:text-neutral-400 text-[11px]">
                                             <time datetime="${reply.created_at}" title="${reply.created_at}">${reply.time_ago}</time>
                                        </span>
                                        ${reply.like_count > 0 ? `
                                        <button class="font-semibold hover:underline text-[11px]">${reply.like_count} like${reply.like_count === 1 ? '' : 's'}</button>
                                        ` : ''}
                                        <button class="font-semibold text-[11px]" onclick="handleCommentReply('${commentId}', '${reply.username}')">Reply</button>
                                        <div class="relative flex items-center">
                                            <button class="hover:text-neutral-600 dark:hover:text-neutral-300 opacity-0 group-hover:opacity-100 transition-opacity comment-options-btn" type="button" aria-haspopup="dialog" aria-expanded="false" data-state="closed" data-comment-id="${reply.comment_id}" data-comment-user-id="${reply.user_id}" onclick="openCommentOptionsModal(this, event)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-more-horizontal h-4 w-4">
                                                    <circle cx="12" cy="12" r="1"></circle>
                                                    <circle cx="19" cy="12" r="1"></circle>
                                                    <circle cx="5" cy="12" r="1"></circle>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <button class="p-1 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-full transition-colors" data-comment-id="${reply.comment_id}" onclick="handleCommentLike('${reply.comment_id}', event)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart h-4 w-4 transition-colors duration-200 ${reply.is_liked ? 'text-red-500 fill-red-500' : ''}">
                                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                    </svg>
                                </button>
                            </div>
                        `;
                        repliesContainer.appendChild(replyElement);
                    });
                    
                    // Show the replies container
                    repliesContainer.classList.remove('hidden');
                    
                    // Update the button text and icon with the correct count
                    const button = document.querySelector(`button[onclick="toggleReplies('${commentId}', this)"]`);
                    if (button) {
                        const chevronIcon = button.querySelector('svg');
                        const textDiv = button.querySelector('div');
                        
                        if (chevronIcon) {
                            chevronIcon.classList.replace('lucide-chevron-down', 'lucide-chevron-up');
                        }
                        if (textDiv) {
                            textDiv.innerHTML = `
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-up h-3 w-3 mr-1">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                    Hide replies
                                </div>
                            `;
                        }
                    }
                } else {
                    // No replies found
                    repliesContainer.innerHTML = '<div class="text-sm text-neutral-500 dark:text-neutral-400">No replies yet</div>';
                    repliesContainer.classList.remove('hidden');
                }
            } else {
                const repliesContainer = document.getElementById(`replies-${commentId}`);
                if (repliesContainer) {
                    repliesContainer.innerHTML = '<div class="text-sm text-red-500">Error loading replies</div>';
                    repliesContainer.classList.remove('hidden');
                }
            }
        })
        .catch(error => {
            const repliesContainer = document.getElementById(`replies-${commentId}`);
            if (repliesContainer) {
                repliesContainer.innerHTML = '<div class="text-sm text-red-500">Error loading replies</div>';
                repliesContainer.classList.remove('hidden');
            }
        });
}

document.getElementById('modalSaveButton').addEventListener('click', function(event) {
    const postId = window.currentPostId;
    const bookmarkIcon = this.querySelector('svg');
    const isSaved = bookmarkIcon.classList.contains('fill-current');
    
    // Send the save/unsave request to the server
    fetch('utils/save_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}&action=${isSaved ? 'unsave' : 'save'}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Toggle the bookmark icon in modal
            bookmarkIcon.classList.toggle('fill-current');
            bookmarkIcon.classList.toggle('text-black');
            bookmarkIcon.classList.toggle('dark:text-white');
            
            // Update the dashboard save button if it exists
            const dashboardSaveButton = document.querySelector(`button[data-save-button-id="save-${postId}"]`);
            if (dashboardSaveButton) {
                const dashboardBookmarkIcon = dashboardSaveButton.querySelector('svg');
                if (dashboardBookmarkIcon) {
                    dashboardBookmarkIcon.classList.toggle('fill-current');
                    dashboardBookmarkIcon.classList.toggle('text-black');
                    dashboardBookmarkIcon.classList.toggle('dark:text-white');
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});

// Modal post options dropdown logic
function toggleModalPostOptions() {
    const dropdown = document.getElementById('modal-post-options');
    // Close all other dropdowns (not needed in modal, but for consistency)
    // Toggle current dropdown
    dropdown.classList.toggle('hidden');
    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!dropdown.contains(e.target) && !e.target.closest('#modalPostOptionsButton')) {
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeDropdown);
        }
    });
}
document.getElementById('modalPostOptionsButton').onclick = function(e) {
    e.stopPropagation();
    toggleModalPostOptions();
};

document.getElementById('modalEditPostBtn').onclick = function() {
    if (typeof editPost === 'function') {
        editPost(window.currentPostId);
    }
    closePostModal();
};

document.getElementById('modalDeletePostBtn').onclick = function() {
    if (typeof deletePost === 'function') {
        deletePost(window.currentPostId);
    }
    closePostModal();
};

// Update post options based on post ownership
function updatePostOptions(post) {
    const editBtn = document.getElementById('modalEditPostBtn');
    const deleteBtn = document.getElementById('modalDeletePostBtn');
    const reportBtn = document.getElementById('modalReportPostBtn');
    
    if (post.user_id === window.currentUserId) {
        // User owns the post
        editBtn.classList.remove('hidden');
        deleteBtn.classList.remove('hidden');
        reportBtn.classList.add('hidden');
    } else {
        // User doesn't own the post
        editBtn.classList.add('hidden');
        deleteBtn.classList.add('hidden');
        reportBtn.classList.remove('hidden');
    }
}

// Add report functionality
document.getElementById('modalReportPostBtn').onclick = function() {
    // Close the dropdown menu
    document.getElementById('modal-post-options').classList.add('hidden');
    
    // Open report modal with post ID
    openReportModal(window.currentPostId, 'post');
};

// Update the report form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        // Remove any existing event listeners
        const newForm = reportForm.cloneNode(true);
        reportForm.parentNode.replaceChild(newForm, reportForm);
        
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const reportId = document.getElementById('reportId').value;
            const reportType = document.getElementById('reportType').value;
            const description = document.getElementById('reportDescription').value.trim();
            
            // Validate inputs
            if (!reportId || reportId === 'undefined' || reportId === 'null') {
                showToast('Unable to report this item. Please try again.', 'error');
                return false;
            }
            
            if (!description) {
                showToast('Please provide a reason for your report', 'error');
                return false;
            }
            
            // Disable the form while submitting
            const submitButton = newForm.querySelector('button[type="submit"]');
            if (submitButton) submitButton.disabled = true;
            
            // Determine the endpoint based on report type
            const endpoint = reportType === 'comment' ? 'utils/report_comment.php' : 'utils/report_post.php';
            const paramName = reportType === 'comment' ? 'comment_id' : 'post_id';
            
            // Create form data
            const formData = new FormData();
            formData.append(paramName, reportId);
            formData.append('description', description);
            
            // Send the request
            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Close the report modal regardless of success/failure
                closeReportModal();
                
                if (data.success) {
                    showToast(data.message || `${reportType.charAt(0).toUpperCase() + reportType.slice(1)} reported successfully`, 'success');
                    // Close post modal only if reporting a post
                    if (reportType === 'post') {
                        closePostModal();
                    }
                } else {
                    // Only show error toast if there's a specific error message
                    if (data.message && data.message !== 'Post not found') {
                        showToast(data.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast(`Network error when reporting ${reportType}`, 'error');
            })
            .finally(() => {
                // Re-enable the form
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit Report';
                }
            });
            
            return false;
        });
    }
});

// Add toast notification function
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        // Create toast container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('data-type', type);
    
    const icon = type === 'success' ? 
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>' :
        '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    
    toast.innerHTML = `
        <div data-icon>${icon}</div>
        <div data-content>
            <div data-title>${type === 'success' ? 'Success' : 'Error'}</div>
            ${message}
        </div>
        <button onclick="this.parentElement.remove()">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
        </button>
    `;

    document.getElementById('toast-container').appendChild(toast);
    
    // Remove toast after animation
    setTimeout(() => {
        toast.remove();
    }, 5000);
}
</script>
   <?php include 'modals/likes_modal.php'; ?>
   <?php include 'modals/report_modal.php'; ?>

   <!-- Comment Options Modal -->
   <div id="commentOptionsModal" class="fixed inset-0 z-50 hidden">
       <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCommentOptionsModal()"></div>
       <div class="fixed inset-0 z-10 flex items-center justify-center" onclick="closeCommentOptionsModal()">
           <div class="bg-white dark:bg-neutral-900 rounded-lg shadow-lg w-72 overflow-hidden" onclick="event.stopPropagation()">
               <div class="p-4 space-y-3">
                   <!-- Options for user's own comment -->
                   <div id="ownCommentOptions">
                       <button id="deleteCommentBtn" class="w-full text-left text-red-500 font-medium py-2 px-3 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
                           Delete
                       </button>
                       <button onclick="closeCommentOptionsModal()" class="w-full text-left text-neutral-700 dark:text-neutral-300 py-2 px-3 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
                           Cancel
                       </button>
                   </div>
                   <!-- Options for other users' comments -->
                   <div id="otherCommentOptions" class="hidden">
                       <button id="reportCommentBtn" class="w-full text-left text-neutral-700 dark:text-neutral-300 py-2 px-3 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
                           Report
                       </button>
                       <button onclick="closeCommentOptionsModal()" class="w-full text-left text-neutral-700 dark:text-neutral-300 py-2 px-3 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
                           Close
                       </button>
                   </div>
               </div>
           </div>
       </div>
   </div>

   <script>
   // Initialize after modal HTML is included
   initializePostviewLikes();
   
   // Global variables to track current comment
   let currentCommentId = null;
   let currentCommentUserId = null;
   
   // Function to open comment options modal
   function openCommentOptionsModal(button, event) {
       event.preventDefault();
       event.stopPropagation();
       
       // Get comment data
       currentCommentId = button.getAttribute('data-comment-id');
       currentCommentUserId = button.getAttribute('data-comment-user-id');
       
       const modal = document.getElementById('commentOptionsModal');
       const ownOptions = document.getElementById('ownCommentOptions');
       const otherOptions = document.getElementById('otherCommentOptions');
       
       // Determine if this is the user's own comment
       const isOwnComment = currentCommentUserId == window.currentUserId;
       
       // Show appropriate options
       if (isOwnComment) {
           ownOptions.classList.remove('hidden');
           otherOptions.classList.add('hidden');
       } else {
           ownOptions.classList.add('hidden');
           otherOptions.classList.remove('hidden');
       }
       
       // Show modal
       modal.classList.remove('hidden');
   }
   
   // Function to close comment options modal
   function closeCommentOptionsModal() {
       document.getElementById('commentOptionsModal').classList.add('hidden');
   }
   
   // Handle delete comment button click
   document.getElementById('deleteCommentBtn').addEventListener('click', function() {
       if (currentCommentId) {
           // Call API to delete comment
           fetch('utils/delete_comment.php', {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/x-www-form-urlencoded',
               },
               body: `comment_id=${currentCommentId}`
           })
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   // Remove comment from DOM
                   const commentElement = document.querySelector(`[data-comment-id="${currentCommentId}"]`);
                   if (commentElement) {
                       commentElement.remove();
                   }
                   
                   // Show success message
                   showToast('Comment deleted successfully', 'success');
               } else {
                   // Show error message
                   showToast(data.message || 'Failed to delete comment', 'error');
               }
           })
           .catch(error => {
               showToast('An error occurred while deleting the comment', 'error');
           })
           .finally(() => {
               // Close modal
               closeCommentOptionsModal();
           });
       } else {
           showToast('Error: No comment ID found', 'error');
       }
   });
   
   // Handle report comment button click
   document.getElementById('reportCommentBtn').addEventListener('click', function() {
       // Get the current comment ID (set in openCommentOptionsModal)
       const commentId = currentCommentId;
       console.log('REPORT: Opening report modal for comment ID:', commentId);
       
       if (!commentId) {
           showToast('Error: No comment ID found', 'error');
           return;
       }
       
       // Close the options modal
       closeCommentOptionsModal();
       
       // Set up the form data
       const reportTypeInput = document.getElementById('reportType');
       const reportIdInput = document.getElementById('reportId');
       const reportTypeLabel = document.getElementById('reportTypeLabel');
       const reportTypeTitle = document.getElementById('reportTypeTitle');
       
       if (!reportTypeInput || !reportIdInput || !reportTypeLabel || !reportTypeTitle) {
           console.error('Report modal elements not found');
           showToast('Error: Report modal not properly initialized', 'error');
           return;
       }
       
       reportTypeInput.value = 'comment';
       reportIdInput.value = commentId;
       reportTypeLabel.textContent = 'comment';
       reportTypeTitle.textContent = 'Comment';
       
       // Reset the description field
       const textareaEl = document.getElementById('reportDescription');
       if (textareaEl) {
           textareaEl.value = '';
           const charCount = document.getElementById('charCount');
           if (charCount) charCount.textContent = '0';
       }
       
       // Show the modal
       const reportModal = document.getElementById('reportModal');
       if (reportModal) {
           reportModal.classList.remove('hidden');
       } else {
           console.error('Report modal not found');
           showToast('Error: Report modal not found', 'error');
       }
   });
   </script>

   <script>
   // Report Modal Functions
   function openReportModal(id, type = 'post') {
       console.log('Opening report modal with:', id, type);
       
       if (!id) {
           console.error('No ID provided for report');
           showToast('Error: No ID provided for report', 'error');
           return;
       }
       
       const reportIdInput = document.getElementById('reportId');
       const reportTypeInput = document.getElementById('reportType');
       const reportTypeLabel = document.getElementById('reportTypeLabel');
       const reportTypeTitle = document.getElementById('reportTypeTitle');
       const modal = document.getElementById('reportModal');
       const charCountEl = document.getElementById('charCount');
       const textareaEl = document.getElementById('reportDescription');
       
       if (!reportIdInput || !reportTypeInput || !reportTypeLabel || !reportTypeTitle || !modal) {
           console.error('Report modal elements not found');
           showToast('Error: Report modal not properly initialized', 'error');
           return;
       }
       
       // Reset the form
       if (textareaEl) textareaEl.value = '';
       if (charCountEl) charCountEl.textContent = '0';
       
       // Set values and log them for debugging
       reportIdInput.value = id;
       reportTypeInput.value = type;
       reportTypeLabel.textContent = type;
       reportTypeTitle.textContent = type.charAt(0).toUpperCase() + type.slice(1);
       
       console.log('Report modal values set:', {
           id: reportIdInput.value,
           type: reportTypeInput.value,
           label: reportTypeLabel.textContent,
           title: reportTypeTitle.textContent
       });
       
       modal.classList.remove('hidden');
   }

   function closeReportModal() {
       const modal = document.getElementById('reportModal');
       if (modal) modal.classList.add('hidden');
   }

   // Character counter for report description
   document.getElementById('reportDescription')?.addEventListener('input', function() {
       const charCount = document.getElementById('charCount');
       if (charCount) {
           charCount.textContent = this.value.length;
       }
   });
   </script>
   </body>
   </html>
