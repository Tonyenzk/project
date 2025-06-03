<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Check if location parameter exists
$location = $_GET['location'] ?? null;

if (!$location) {
    header("Location: dashboard.php");
    exit();
}

// Fetch location posts
try {
    // Fetch total post count for this location
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM posts WHERE location = ?");
    $countStmt->execute([$location]);
    $totalPosts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Fetch location photo
    $photoStmt = $pdo->prepare("SELECT location_photo FROM posts WHERE location = ? AND location_photo IS NOT NULL LIMIT 1");
    $photoStmt->execute([$location]);
    $photoResult = $photoStmt->fetch(PDO::FETCH_ASSOC);
    $locationPhoto = $photoResult ? $photoResult['location_photo'] : null;
    
    // Fetch posts by location
    $stmt = $pdo->prepare("SELECT 
                            p.post_id,
                            p.user_id,
                            p.image_url,
                            p.caption,
                            p.created_at,
                            u.username,
                            u.profile_picture,
                            u.verifybadge,
                            (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as like_count,
                            (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comment_count
                        FROM posts p
                        JOIN users u ON p.user_id = u.user_id
                        WHERE p.location = ? AND u.is_banned = 0
                        ORDER BY p.created_at DESC");
    $stmt->execute([$location]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
    $posts = [];
    $totalPosts = 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/toast.css">
    <script src="assets/js/toast-handler.js"></script>
    <title><?php echo htmlspecialchars($location); ?> - Locație</title>
    <style>
        .location-header {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 1rem;
            text-align: center;
            margin-bottom: 2rem;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            min-height: 300px;
        }
        
        .location-icon {
            margin-bottom: 1.5rem;
            font-size: 2rem;
            padding: 1rem;
            border-radius: 50%;
            backdrop-filter: blur(8px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }
        
        .location-icon:hover {
            transform: scale(1.05);
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 0 auto;
            max-width: 1200px;
            padding: 1rem;
        }
        
        .photo-grid-item {
            position: relative;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .photo-grid-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .photo-grid-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .photo-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0) 100%);
            opacity: 0;
            transition: opacity 0.2s ease;
            display: flex;
            align-items: flex-end;
            padding: 1rem;
        }
        
        .photo-grid-item:hover .photo-overlay {
            opacity: 1;
        }
        
        .photo-grid-item:hover img {
            transform: scale(1.05);
        }
        
        .photo-stats {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
        }
        
        .photo-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: bold;
            color: white;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 6rem 1rem;
            text-align: center;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin: 2rem auto;
            max-width: 600px;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #9CA3AF;
        }
        
        .location-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .location-stats {
            font-size: 1.1rem;
            opacity: 0.9;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .location-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
            z-index: 20;
        }

        .action-button {
            padding: 0.5rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: inherit;
            transition: all 0.2s ease;
        }

        .action-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .photo-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
            color: white;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .photo-grid-item:hover .photo-info {
            opacity: 1;
        }

        .photo-username {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .photo-caption {
            font-size: 0.875rem;
            opacity: 0.9;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        @media (max-width: 1024px) {
            .photo-grid {
                grid-template-columns: repeat(2, 1fr);
                padding: 0.5rem;
            }
            
            .location-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 640px) {
            .photo-grid {
                grid-template-columns: repeat(1, 1fr);
            }
            
            .location-header {
                padding: 3rem 1rem;
            }
            
            .location-title {
                font-size: 1.75rem;
            }
            
            .photo-stats {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php include 'postview.php'; ?>
    <?php include 'modals/report_modal.php'; ?>
    
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="max-w-[1200px] mx-auto pt-4 md:pt-8 px-4">
            <!-- Location Header -->
            <div class="location-header relative <?php echo $locationPhoto ? 'min-h-[400px]' : 'bg-white dark:bg-neutral-900'; ?>">
                <?php if ($locationPhoto): ?>
                <div class="absolute inset-0 z-0">
                    <img src="<?php echo htmlspecialchars($locationPhoto); ?>" alt="<?php echo htmlspecialchars($location); ?>" 
                         class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/50 to-black/30"></div>
                </div>
                <?php endif; ?>
                <div class="location-actions">
                    <button onclick="openReportModal('<?php echo htmlspecialchars($location); ?>', 'location')" class="action-button text-red-500 hover:text-red-600" title="Report Location">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </button>
                </div>
                <div class="relative z-10 flex flex-col items-center justify-center <?php echo $locationPhoto ? 'text-white' : 'text-black dark:text-white'; ?>">
                    <div class="location-icon <?php echo $locationPhoto ? 'bg-white/10' : 'bg-gray-100'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h1 class="location-title"><?php echo htmlspecialchars($location); ?></h1>
                    <p class="location-stats">
                        <?php echo $totalPosts; ?> <?php echo $totalPosts == 1 ? 'postare' : 'postări'; ?>
                    </p>
                </div>
            </div>
            
            <!-- Photo Grid -->
            <?php if (count($posts) > 0): ?>
                <div class="photo-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="photo-grid-item">
                            <a href="#" onclick="openPost('<?php echo $post['post_id']; ?>'); return false;">
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post de la <?php echo htmlspecialchars($post['username']); ?>">
                                <div class="photo-overlay">
                                    <div class="photo-stats">
                                        <div class="photo-stat">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-6 h-6 fill-white text-white">
                                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                                            </svg>
                                            <span class="text-base"><?php echo number_format($post['like_count']); ?></span>
                                        </div>
                                        <div class="photo-stat">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle w-6 h-6 fill-transparent text-white">
                                                <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                                            </svg>
                                            <span class="text-base"><?php echo number_format($post['comment_count']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="photo-info">
                                    <div class="photo-username"><?php echo htmlspecialchars($post['username']); ?></div>
                                    <?php if (!empty($post['caption'])): ?>
                                    <div class="photo-caption"><?php echo htmlspecialchars($post['caption']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0118.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-semibold mb-3 text-gray-800">Nicio postare încă</h2>
                    <p class="text-gray-600">Nu există postări pentru această locație.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Function to open post modal
        function openPost(postId) {
            // Get the modal element
            const modal = document.getElementById('postModal');
            if (!modal) return;
            
            // Display the modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Store the current post ID for later use
            window.currentPostId = postId;
            
            // Fetch post data
            fetch(`utils/get_post_data.php?post_id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const post = data.data;
                        
                        // Set post image
                        const modalPostImage = document.getElementById('modalPostImage');
                        if (modalPostImage) {
                            modalPostImage.src = post.image_url;
                        }
                        
                        // Set user profile info
                        const modalUsername = document.getElementById('modalUsername');
                        const modalPostUsername = document.getElementById('modalPostUsername');
                        const modalUserProfilePic = document.getElementById('modalUserProfilePic');
                        const modalVerifiedBadge = document.getElementById('modalVerifiedBadge');
                        const modalPostCaption = document.getElementById('modalPostCaption');
                        const modalTimeAgo = document.getElementById('modalTimeAgo');
                        
                        if (modalUsername) {
                            modalUsername.textContent = post.username;
                            modalUsername.href = post.is_owner ? 'profile.php' : `user.php?username=${post.username}`;
                        }
                        
                        if (modalPostUsername) {
                            modalPostUsername.textContent = post.username;
                            modalPostUsername.href = post.is_owner ? 'profile.php' : `user.php?username=${post.username}`;
                        }
                        
                        if (modalUserProfilePic) {
                            modalUserProfilePic.src = post.profile_picture || 'images/profile_placeholder.webp';
                        }
                        
                        if (modalVerifiedBadge) {
                            if (post.verifybadge === 'true') {
                                modalVerifiedBadge.classList.remove('hidden');
                            } else {
                                modalVerifiedBadge.classList.add('hidden');
                            }
                        }
                        
                        if (modalPostCaption) {
                            modalPostCaption.textContent = post.caption || '';
                        }
                        
                        if (modalTimeAgo) {
                            modalTimeAgo.textContent = post.time_ago || '';
                        }
                        
                        // Set like status
                        const modalLikeButton = document.getElementById('modalLikeButton');
                        if (modalLikeButton) {
                            const heartIcon = modalLikeButton.querySelector('svg');
                            if (heartIcon) {
                                if (post.is_liked) {
                                    heartIcon.classList.add('fill-red-500', 'text-red-500');
                                } else {
                                    heartIcon.classList.remove('fill-red-500', 'text-red-500');
                                }
                            }
                        }
                        
                        // Set save status
                        const modalSaveButton = document.getElementById('modalSaveButton');
                        if (modalSaveButton) {
                            const bookmarkIcon = modalSaveButton.querySelector('svg');
                            if (bookmarkIcon) {
                                if (post.is_saved) {
                                    bookmarkIcon.classList.add('fill-current');
                                } else {
                                    bookmarkIcon.classList.remove('fill-current');
                                }
                            }
                        }
                        
                        // Add verified badge for post username
                        const modalPostVerifiedBadge = document.getElementById('modalPostVerifiedBadge');
                        if (modalPostVerifiedBadge) {
                            if (post.verifybadge === 'true') {
                                modalPostVerifiedBadge.classList.remove('hidden');
                            } else {
                                modalPostVerifiedBadge.classList.add('hidden');
                            }
                        }
                        
                        // Load comments
                        const modalCommentsContainer = document.getElementById('modalCommentsContainer');
                        if (modalCommentsContainer) {
                            if (post.deactivated_comments == 1) {
                                modalCommentsContainer.innerHTML = '<div class="p-4 text-center text-neutral-500 dark:text-neutral-400">Comments are disabled for this post.</div>';
                            } else if (post.comments && post.comments.length > 0) {
                                // Clear previous comments
                                modalCommentsContainer.innerHTML = '';
                                
                                // Add each comment
                                post.comments.forEach(comment => {
                                    const commentElement = document.createElement('div');
                                    commentElement.className = 'px-4 py-3';
                                    commentElement.setAttribute('data-comment-id', comment.comment_id);
                                    
                                    commentElement.innerHTML = `
                                        <div class="flex gap-3">
                                            <div class="relative w-8 h-8 rounded-full overflow-hidden">
                                                <a href="profile_view.php?username=${comment.username}">
                                                    <img src="${comment.profile_picture || 'images/profile_placeholder.webp'}" alt="${comment.username}" class="w-full h-full object-cover">
                                                </a>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm">
                                                    <div>
                                                        <span>
                                                            <a href="profile_view.php?username=${comment.username}" class="font-semibold hover:underline mr-1">${comment.username}</a>
                                                            ${comment.verifybadge == 1 ? `
                                                            <svg aria-label="Verified" class="inline-block text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 12px; height: 12px;">
                                                                <title>Verified</title>
                                                                <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                            </svg>` : ''}
                                                            <span class="text-sm whitespace-pre-line break-all overflow-wrap overflow-hidden text-pretty">${comment.comment_text}</span>
                                                        </span>
                                                    </div>
                                                    <div class="flex gap-3 mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                        <span>${comment.time_ago}</span>
                                                        <button class="hover:text-neutral-900 dark:hover:text-neutral-100" onclick="toggleReplies('${comment.comment_id}', this)">
                                                            <div>View replies</div>
                                                        </button>
                                                    </div>
                                                    <div id="replies-${comment.comment_id}" class="mt-2 pl-4 border-l border-neutral-200 dark:border-neutral-800 hidden">
                                                        <!-- Replies will be loaded here -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                    
                                    modalCommentsContainer.appendChild(commentElement);
                                });
                            } else {
                                modalCommentsContainer.innerHTML = '<div class="p-4 text-center text-neutral-500 dark:text-neutral-400">No comments yet. Be the first to comment!</div>';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching post data:', error);
                });
        }

        function closePostModal() {
            const modal = document.getElementById('postModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

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

        // Report form submission handler
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
                    let endpoint;
                    let formData = new FormData();
                    
                    switch (reportType) {
                        case 'location':
                            endpoint = 'utils/report_location.php';
                            formData.append('location', reportId);
                            break;
                        case 'comment':
                            endpoint = 'utils/report_comment.php';
                            formData.append('comment_id', reportId);
                            break;
                        default:
                            endpoint = 'utils/report_post.php';
                            formData.append('post_id', reportId);
                    }
                    
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
                        } else {
                            // Only show error toast if there's a specific error message
                            if (data.message) {
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
    </script>
</body>
</html>