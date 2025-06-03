<?php
session_start();
require_once 'config/database.php';

// Initialize empty posts array
// Posts will be loaded via AJAX for infinite scrolling
$posts = [];
?>

<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <?php include 'includes/header.php'; ?>
      <script src="assets/js/post_functions.js"></script>
      <script src="assets/js/create-modal.js"></script>
   </head>
   <body class="bg-gray-50">
   <?php include 'navbar.php'; ?>
   <?php include 'postview.php'; ?>
   <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
      <div class="max-w-[1200px] mx-auto pt-4 md:pt-8 px-4">
         <!-- Header Section -->
         <div class="mb-8">
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Explorează</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Descoperă cele mai relevante postări din ultima săptămâna.</p>
         </div>

         <!-- Grid Layout -->
         <div id="explore-posts-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <!-- Posts will be loaded dynamically via JavaScript -->
         </div>
         
         <!-- Loading indicator -->
         <div id="loading-indicator" class="flex justify-center items-center py-8 hidden">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
         </div>
         
         <!-- No posts message (initially hidden) -->
         <div id="no-posts-message" class="col-span-3 text-center py-12 hidden">
            <p class="text-neutral-500 dark:text-neutral-400">Nu au fost găsite postări. Reveniți mai târziu!</p>
         </div>

         <!-- No "Load More" button needed - posts load automatically on scroll -->
      </div>
   </main>
   <script>
   // Global variables
   const postsGrid = document.getElementById('explore-posts-grid');
   const loadingIndicator = document.getElementById('loading-indicator');
   const noPostsMessage = document.getElementById('no-posts-message');
   let currentOffset = 0;
   const postsPerPage = 12;
   let isLoading = false;
   let hasMorePosts = true;

   // Function to create a post card element
   function createPostCard(post) {
       const postCard = document.createElement('div');
       postCard.classList.add(
           'group', 'relative', 'aspect-square', 'overflow-hidden', 'rounded-xl',
           'bg-white', 'dark:bg-neutral-900', 'border', 'border-neutral-200',
           'dark:border-neutral-800', 'cursor-pointer'
       );
       postCard.onclick = () => openPostModal(post.post_id);
       
       postCard.innerHTML = `
           <img src="${post.image_url}" alt="Post by ${post.username}" 
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
           <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
               <div class="absolute inset-0 flex items-center justify-center gap-6">
                   <div class="flex items-center gap-2 font-bold text-white">
                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart w-6 h-6 fill-white text-white">
                           <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"></path>
                       </svg>
                       <p class="text-base">${post.like_count}</p>
                   </div>
                   <div class="flex items-center gap-2 font-bold text-white">
                       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle w-6 h-6 fill-transparent text-white">
                           <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"></path>
                       </svg>
                       <p class="text-base">${post.comment_count}</p>
                   </div>
               </div>
           </div>
       `;
       
       return postCard;
   }

   // Function to load posts
   function loadPosts() {
       if (isLoading || !hasMorePosts) return;
       
       isLoading = true;
       loadingIndicator.classList.remove('hidden');
       
       fetch(`utils/get_explore_posts.php?offset=${currentOffset}&limit=${postsPerPage}`)
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   const posts = data.data;
                   
                   if (posts.length === 0 && currentOffset === 0) {
                       noPostsMessage.classList.remove('hidden');
                   } else {
                       posts.forEach(post => {
                           postsGrid.appendChild(createPostCard(post));
                       });
                       
                       currentOffset += posts.length;
                       hasMorePosts = data.pagination.has_more;
                   }
               } else {
                   console.error('Error loading posts:', data.error);
               }
           })
           .catch(error => {
               console.error('Error:', error);
           })
           .finally(() => {
               isLoading = false;
               loadingIndicator.classList.add('hidden');
           });
   }

   // Intersection Observer setup
   const observerOptions = {
       root: null,
       rootMargin: '200px',
       threshold: 0.1
   };

   const observer = new IntersectionObserver((entries) => {
       entries.forEach(entry => {
           if (entry.isIntersecting && !isLoading && hasMorePosts) {
               loadPosts();
           }
       });
   }, observerOptions);

   // Post modal functionality
   function openPostModal(postId) {
       const modal = document.getElementById('postModal');
       if (!modal) return;
       
       modal.classList.remove('hidden');
       document.body.style.overflow = 'hidden';
       window.currentPostId = postId;
       
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
                   
                   // Handle post options visibility
                   const editPostBtn = document.getElementById('modalEditPostBtn');
                   const deletePostBtn = document.getElementById('modalDeletePostBtn');
                   const reportPostBtn = document.getElementById('modalReportPostBtn');
                   
                   if (editPostBtn && deletePostBtn && reportPostBtn) {
                       if (post.user_id === window.currentUserId) {
                           // Show edit/delete for own posts
                           editPostBtn.classList.remove('hidden');
                           deletePostBtn.classList.remove('hidden');
                           reportPostBtn.classList.add('hidden');
                       } else {
                           // Show report for others' posts
                           editPostBtn.classList.add('hidden');
                           deletePostBtn.classList.add('hidden');
                           reportPostBtn.classList.remove('hidden');
                       }
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
                   
                   // Load comments
                   const modalCommentsContainer = document.getElementById('modalCommentsContainer');
                   if (modalCommentsContainer) {
                       if (post.deactivated_comments == 1) {
                           modalCommentsContainer.innerHTML = '<div class="p-4 text-center text-neutral-500 dark:text-neutral-400">Comentariile sunt dezactivate pentru această postare.</div>';
                       } else if (post.comments && post.comments.length > 0) {
                           modalCommentsContainer.innerHTML = '';
                           
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
                                                       ${comment.verifybadge === 'true' ? `
                                                       <svg aria-label="Verificat" class="inline-block text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 12px; height: 12px;">
                                                           <title>Verificat</title>
                                                           <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                                       </svg>` : ''}
                                                       <span class="text-sm whitespace-pre-line break-all overflow-wrap overflow-hidden text-pretty">${comment.comment_text}</span>
                                                   </span>
                                               </div>
                                               <div class="flex gap-3 mt-1 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                   <span>${comment.time_ago}</span>
                                                   <button class="hover:text-neutral-900 dark:hover:text-neutral-100" onclick="toggleReplies('${comment.comment_id}', this)">
                                                       <div>Vezi răspunsuri</div>
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
                           modalCommentsContainer.innerHTML = '<div class="p-4 text-center text-neutral-500 dark:text-neutral-400">Nu există comentarii încă. Fii primul care comentează!</div>';
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

   // Initialize the page
   document.addEventListener('DOMContentLoaded', function() {
       observer.observe(loadingIndicator);
       loadPosts();
   });
   </script>
</body>
</html>