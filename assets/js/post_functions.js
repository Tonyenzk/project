// Function to handle post editing
function editPost(postId) {
    console.log('[editPost] Called with postId:', postId);
    // First, close the dropdown menu if it's open
    const dropdown = document.getElementById(`post-options-${postId}`);
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
    
    // Load the create modal content first to ensure we have the latest version
    fetch('modals/create.php')
        .then(response => response.text())
        .then(html => {
            // Create a temporary container
            const temp = document.createElement('div');
            temp.innerHTML = html;
            
            // Get the modal content
            const modalContent = temp.querySelector('#createModal');
            
            // Remove any existing modal
            const existingModal = document.getElementById('createModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add the modal to the body
            document.body.appendChild(modalContent);
            
            // Now that the modal is in the DOM, continue with loading post data
            loadPostDataForEdit(postId);
        })
        .catch(error => {
            console.error('Error loading create modal:', error);
            showToast('Error loading create modal', 'error');
        });
}

// Helper function to load post data for editing
function loadPostDataForEdit(postId) {
    console.log('[loadPostDataForEdit] Called with postId:', postId);
    // First show the modal
    const modal = document.getElementById('createModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Hide the "Post" and "Story" tabs since this is edit mode
        const tabList = modal.querySelector('[role="tablist"]');
        if (tabList) {
            tabList.style.display = 'none';
        }
        
        // Update modal title to indicate edit mode
        const modalTitle = modal.querySelector('h2.tracking-tight');
        if (modalTitle) {
            modalTitle.textContent = 'Editează postare';
        }
    }
    
    // Fetch post data
    console.log('[loadPostDataForEdit] Fetching: utils/get_post_data.php?post_id=' + postId);
    fetch(`utils/get_post_data.php?post_id=${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const post = data.data;
                
                // Show preview state and hide initial state
                const initialPostState = document.getElementById('initialPostState');
                const previewAndInputState = document.getElementById('previewAndInputState');
                
                if (initialPostState && previewAndInputState) {
                    initialPostState.style.display = 'none';
                    previewAndInputState.style.display = 'flex';
                }
                
                // Set post data in the form
                const descriptionInput = document.getElementById('postDescriptionInput');
                const locationInput = document.getElementById('postLocationInput');
                const commentsToggle = document.getElementById('commentsToggle');
                
                if (descriptionInput) descriptionInput.value = post.caption || '';
                if (locationInput) locationInput.value = post.location || '';
                if (commentsToggle) {
                    commentsToggle.checked = post.deactivated_comments === 1;
                    updateCommentsStatus(); // Update the comments status text
                }

                // Set the image preview
                const previewImage = document.getElementById('postPreviewImage');
                if (previewImage) {
                    previewImage.src = post.image_url;
                }
                
                // Store post ID for update
                const form = document.getElementById('createPostForm');
                if (form) {
                    form.dataset.postId = postId;
                }
                
                // Clear existing selected users and populate with tagged users from the post
                if (typeof selectedUsers !== 'undefined') {
                    selectedUsers.clear();
                    selectedUsersData.clear(); // Clear user data as well
                    
                    // Add tagged users with their full data
                    if (post.tagged_users && post.tagged_users.length > 0) {
                        post.tagged_users.forEach(user => {
                            // Add to selectedUsers set
                            selectedUsers.add(user.username);
                            
                            // Store complete user data
                            selectedUsersData.set(user.username, {
                                username: user.username,
                                fullName: user.full_name,
                                profilePicture: user.profile_picture,
                                isVerified: user.is_verified === '1'
                            });
                        });
                    } else if (post.tagged_usernames) {
                        // Fallback to using just usernames if full data isn't available
                        const taggedUsernamesArray = post.tagged_usernames.split(',');
                        taggedUsernamesArray.forEach(username => {
                            if (username.trim()) {
                                selectedUsers.add(username);
                                // Add minimal data
                                selectedUsersData.set(username, {
                                    username: username,
                                    fullName: '',
                                    profilePicture: '',
                                    isVerified: false
                                });
                            }
                        });
                    }
                    
                    // Update the display to show tagged users
                    updateSelectedUsersDisplay();
                    
                    // Log for debugging
                    console.log('Loaded tagged users:', Array.from(selectedUsers));
                }

                // Update modal title
                const modalTitle = document.querySelector('#createModal h2.tracking-tight');
                const modalSubtitle = document.querySelector('#createModal p.text-center.text-sm.text-neutral-500');
                
                if (modalTitle) modalTitle.textContent = 'Edit Post';
                if (modalSubtitle) modalSubtitle.textContent = '';
                
                // Find and update the submit button
                const submitButton = document.getElementById('submitPostButton');
                if (submitButton) {
                    // Make sure the button container is visible
                    const buttonContainer = submitButton.parentElement;
                    if (buttonContainer) {
                        buttonContainer.style.display = 'block';
                    }
                    
                    // Ensure button is visible and styled correctly
                    submitButton.style.display = 'inline-flex';
                    submitButton.classList.add('bg-primary');
                    
                    // Update the button text
                    const buttonSpan = submitButton.querySelector('span');
                    if (buttonSpan) {
                        buttonSpan.textContent = 'Update Post';
                    } else {
                        submitButton.innerHTML = '<span>Update Post</span>';
                    }
                }
            } else {
                showToast(data.error || 'Error loading post data', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error loading post data', 'error');
        });
}

// Function to delete a post
function deletePost(postId) {
    if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
        fetch('utils/delete_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove post from the feed
                const postElement = document.querySelector(`[data-post-id="post-${postId}"]`);
                if (postElement) {
                    postElement.remove();
                }
                // Show success toast
                showToast('Post deleted successfully', 'success');
            } else {
                showToast(data.error || 'Error deleting post. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error deleting post. Please try again.', 'error');
        });
    }
}

// Function to toggle post options dropdown
function togglePostOptions(postId) {
    const dropdown = document.getElementById(`post-options-${postId}`);
    const allDropdowns = document.querySelectorAll('[id^="post-options-"]');
    
    // Close all other dropdowns
    allDropdowns.forEach(d => {
        if (d.id !== `post-options-${postId}`) {
            d.classList.add('hidden');
        }
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('hidden');
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function closeDropdown(e) {
        if (!dropdown.contains(e.target) && !e.target.closest('button[onclick*="togglePostOptions"]')) {
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeDropdown);
        }
    });
}

// This function was renamed to avoid conflicts with create-modal.js
// IMPORTANT: Do not use this function directly - use openCreateModal from create-modal.js instead
function openEditModal() {
    console.log('WARNING: Using deprecated openEditModal from post_functions.js');
    // Use the proper implementation from create-modal.js
    if (typeof window.openCreateModal === 'function') {
        window.openCreateModal();
        return;
    }
    
    // Fallback implementation (should never be used)
    const modal = document.getElementById('createModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Ensure the preview and input state is visible if we're editing
        const form = document.getElementById('createPostForm');
        if (form && form.dataset.postId) {
            const initialPostState = document.getElementById('initialPostState');
            const previewAndInputState = document.getElementById('previewAndInputState');
            if (initialPostState) initialPostState.style.display = 'none';
            if (previewAndInputState) previewAndInputState.style.display = 'flex';
            
            // Show the submit button and change its text
            const buttonContainer = document.querySelector('.p-6.border-t');
            if (buttonContainer) {
                buttonContainer.style.display = 'block';
                const buttonSpan = buttonContainer.querySelector('button span');
                if (buttonSpan) {
                    buttonSpan.textContent = 'Update Post';
                }
            }
        }
    }
}

// Function to handle post reporting
function reportPost(postId) {
    // Close the post options dropdown
    const dropdown = document.getElementById(`post-options-${postId}`);
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
    
    // Open report modal with post ID
    openReportModal(postId, 'post');
}