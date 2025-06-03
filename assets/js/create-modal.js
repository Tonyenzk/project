// Initialize global variables for user tagging
window.selectedUsers = new Set();
window.selectedUsersData = new Map();

// Function to update the display of selected users
function updateSelectedUsersDisplay() {
    const container = document.getElementById('selectedUsersContainer');
    if (!container) return;

    container.innerHTML = '';
    
    window.selectedUsers.forEach(username => {
        const userData = window.selectedUsersData.get(username);
        if (!userData) return;

        const userElement = document.createElement('div');
        userElement.className = 'flex items-center gap-2 bg-neutral-100 dark:bg-neutral-800 rounded-full px-3 py-1';
        userElement.innerHTML = `
            <img src="${userData.profilePicture || 'images/profile_placeholder.webp'}" alt="${username}" class="w-6 h-6 rounded-full">
            <span class="text-sm">${username}</span>
            ${userData.isVerified ? '<svg class="w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-1.5 14.5l-4-4 1.5-1.5 2.5 2.5 5-5 1.5 1.5-6.5 6.5z"/></svg>' : ''}
            <button onclick="removeTaggedUser('${username}')" class="ml-2 text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        `;
        container.appendChild(userElement);
    });
}

// Function to remove a tagged user
function removeTaggedUser(username) {
    window.selectedUsers.delete(username);
    window.selectedUsersData.delete(username);
    updateSelectedUsersDisplay();
}

// Create Modal Functions
function openCreateModal() {
    // Load the create modal content
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
            
            // Initialize the modal
            initializeCreateModal();
            
            // Make sure file input is correctly set up
            const fileInput = document.getElementById('fileInput');
            const fileButton = document.querySelector('button[onclick="document.getElementById(\'fileInput\').click()"]');
            
            if (fileInput && fileButton) {
                // Ensure the button is visible with important flag
                fileButton.style.display = 'inline-flex';
                fileButton.style.visibility = 'visible';
                fileButton.style.opacity = '1';
                
                // Make sure the click handler works
                fileButton.onclick = function() {
                    fileInput.click();
                };
                
                // Set up file input change handlers
                fileInput.onchange = handleFileSelect;
                
                // Set up story file input change handler if it exists
                const storyFileInput = document.getElementById('storyFileInput');
                if (storyFileInput) {
                    storyFileInput.onchange = handleStoryFileSelect;
                }
            } else {
                // Try another selector approach if button not found
                const allButtons = document.querySelectorAll('button');
                allButtons.forEach((btn) => {
                    if (btn.innerText.includes('fotografie')) {
                        btn.style.display = 'inline-flex';
                        btn.style.visibility = 'visible';
                        btn.style.opacity = '1';
                    }
                });
            }
            
            // Lock background scrolling
            disableBackgroundScroll();
            
            // Display the modal
            const modal = document.getElementById('createModal');
            if (modal) {
                modal.style.display = 'flex';
            }
        })
        .catch(error => {
            console.error('Error loading create modal:', error);
            showToast('Error loading create modal', 'error');
        });
}

// New function to disable background scrolling
function disableBackgroundScroll() {
    // Apply to html and body for cross-browser compatibility
    document.documentElement.classList.add('overflow-hidden');
    document.body.classList.add('overflow-hidden');
    
    // Force hidden overflow with inline styles as well (belt and suspenders approach)
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    
    // Apply additional CSS properties to prevent any scroll
    document.body.style.position = 'fixed';
    document.body.style.width = '100%';
    document.body.style.top = `-${window.pageYOffset}px`;
    
    // Store the scroll position for later restoration
    window.createModalScrollY = window.pageYOffset;
}

// New function to enable background scrolling
function enableBackgroundScroll() {
    // Remove classes
    document.documentElement.classList.remove('overflow-hidden');
    document.body.classList.remove('overflow-hidden');
    
    // Remove inline styles
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    document.body.style.position = '';
    document.body.style.width = '';
    document.body.style.top = '';
    
    // Restore scroll position
    window.scrollTo(0, window.createModalScrollY || 0);
}

function closeCreateModal() {
    const modal = document.getElementById('createModal');
    if (modal) {
        // Hide the modal
        modal.style.display = 'none';
        
        // Enable background scrolling - this uses our dedicated function
        // to ensure consistency with how we disabled scrolling
        enableBackgroundScroll();
        
        // Also remove any other modal-open classes that might have been added
        document.documentElement.classList.remove('modal-open');
        document.body.classList.remove('modal-open');
        
        // Reset form if it exists
        const form = document.getElementById('createPostForm');
        if (form) {
            form.reset();
            form.removeAttribute('data-post-id');
            
            // Reset preview if elements exist
            const initialPostState = document.getElementById('initialPostState');
            const previewAndInputState = document.getElementById('previewAndInputState');
            if (initialPostState) initialPostState.style.display = 'flex';
            if (previewAndInputState) previewAndInputState.style.display = 'none';
            
            // Reset story states
            const initialStoryState = document.getElementById('initialStoryState');
            const previewAndInputStoryState = document.getElementById('previewAndInputStoryState');
            if (initialStoryState) initialStoryState.style.display = 'flex';
            if (previewAndInputStoryState) previewAndInputStoryState.style.display = 'none';
            
            // Reset modal title and button
            const titleElement = document.querySelector('#createModal h2.tracking-tight');
            const subtitleElement = document.querySelector('#createModal p.text-center.text-sm.text-neutral-500');
            const buttonElement = document.querySelector('#previewAndInputState button span');
            
            if (titleElement) titleElement.textContent = 'Creează postare nouă';
            if (subtitleElement) subtitleElement.textContent = 'Share a photo with your followers';
            if (buttonElement) buttonElement.textContent = 'Share Post';
            
            // Clear selected users if function exists
            if (typeof selectedUsers !== 'undefined' && typeof updateSelectedUsersDisplay === 'function') {
                selectedUsers.clear();
                updateSelectedUsersDisplay();
            }
            
            // Reset file inputs
            const fileInput = document.getElementById('fileInput');
            const storyFileInput = document.getElementById('storyFileInput');
            if (fileInput) fileInput.value = '';
            if (storyFileInput) storyFileInput.value = '';
        }
    }
}

function loadCreateModal() {
    // Load the create modal content
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
            
            // Initialize the modal
            initializeCreateModal();
            
            // Show the modal
            document.getElementById('createModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error loading create modal:', error);
            showToast('Error loading create modal');
        });
}

function initializeCreateModal() {
    // Get all required elements
    const fileInput = document.getElementById('fileInput');
    const postTabBtn = document.getElementById('postTabBtn');
    const storyTabBtn = document.getElementById('storyTabBtn');
    
    // Find tab contents
    const postContent = document.getElementById('postContent');
    const storyContent = document.getElementById('storyContent');
    
    // Story elements
    const storyFileInput = document.getElementById('storyFileInput');
    const storyPreviewImage = document.getElementById('storyPreviewImage');
    const initialStoryState = document.getElementById('initialStoryState');
    const previewAndInputStoryState = document.getElementById('previewAndInputStoryState');
    const submitStoryButton = document.getElementById('submitStoryButton');

    // Music elements
    const musicSearchInput = document.getElementById('storyMusicSearchInput');
    const musicSearchResults = document.getElementById('storyMusicSearchResults');
    const musicSearchLoading = document.getElementById('storyMusicSearchLoading');
    const musicSearchError = document.getElementById('storyMusicSearchError');
    const selectedMusicDisplay = document.getElementById('selectedMusic');
    const removeMusicBtn = document.getElementById('removeMusicBtn');

    // Initialize music search
    if (musicSearchInput) {
        musicSearchInput.addEventListener('input', function(e) {
            const searchQuery = e.target.value.trim();
            
            // Clear previous timeout
            clearTimeout(musicSearchTimeout);

            // Hide results and error, show loading
            musicSearchResults.classList.add('hidden');
            musicSearchError.classList.add('hidden');
            musicSearchLoading.classList.remove('hidden');

            // Set new timeout for search
            musicSearchTimeout = setTimeout(async () => {
                if (searchQuery.length < 2) {
                    musicSearchLoading.classList.add('hidden');
                    return;
                }

                try {
                    const response = await fetch(`/api/search_music.php?q=${encodeURIComponent(searchQuery)}`);
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to search music');
                    }

                    // Display results
                    musicSearchResults.innerHTML = data.items.map(item => `
                        <div class="flex items-center space-x-3 p-2 hover:bg-neutral-50 dark:hover:bg-neutral-800 rounded-lg cursor-pointer music-result" 
                             data-video-id="${item.id.videoId}"
                             data-title="${item.snippet.title}"
                             data-channel="${item.snippet.channelTitle}"
                             data-thumbnail="${item.snippet.thumbnails.default.url}">
                            <img src="${item.snippet.thumbnails.default.url}" alt="${item.snippet.title}" class="w-12 h-12 rounded object-cover">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">${item.snippet.title}</p>
                                <p class="text-xs text-neutral-500 truncate">${item.snippet.channelTitle}</p>
                            </div>
                        </div>
                    `).join('');

                    // Add click handlers to results
                    document.querySelectorAll('.music-result').forEach(result => {
                        result.addEventListener('click', () => {
                            selectedMusic = {
                                videoId: result.dataset.videoId,
                                title: result.dataset.title,
                                artist: result.dataset.channel,
                                thumbnail: result.dataset.thumbnail
                            };
                            displaySelectedMusic();
                            musicSearchResults.classList.add('hidden');
                        });
                    });

                    musicSearchResults.classList.remove('hidden');
                } catch (error) {
                    console.error('Music search error:', error);
                    musicSearchError.textContent = error.message;
                    musicSearchError.classList.remove('hidden');
                } finally {
                    musicSearchLoading.classList.add('hidden');
                }
            }, 500); // 500ms debounce
        });
    }

    // Initialize remove music button
    if (removeMusicBtn) {
        removeMusicBtn.addEventListener('click', () => {
            selectedMusic = null;
            displaySelectedMusic();
        });
    }

    // Set initial active tab styles
    if (postTabBtn) {
        postTabBtn.classList.add('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
        postTabBtn.classList.remove('text-neutral-600', 'dark:text-neutral-400');
    }
    
    if (storyTabBtn) {
        storyTabBtn.classList.add('text-neutral-600', 'dark:text-neutral-400');
        storyTabBtn.classList.remove('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
    }
    
    // Initialize tab
    switchTab('post');

    // Add file input change listener
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelect);
    }

    // Add event listener for the story submit button
    if (submitStoryButton) {
        submitStoryButton.addEventListener('click', function(event) {
            event.preventDefault();
            submitStory();
        });
    }

    // Character count for description
    if (postDescriptionInput && descriptionCharCount) {
        const maxDescriptionLength = postDescriptionInput.getAttribute('maxlength') || 250;
        postDescriptionInput.addEventListener('input', function() {
            const remaining = maxDescriptionLength - this.value.length;
            descriptionCharCount.textContent = `${remaining} characters remaining`;
        });
    }

    // Character count for location
    if (postLocationInput && locationCharCount) {
        const maxLocationLength = postLocationInput.getAttribute('maxlength') || 20;
        postLocationInput.addEventListener('input', function() {
            const remaining = maxLocationLength - this.value.length;
            locationCharCount.textContent = `${remaining} characters remaining`;
        });
    }
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    
    if (file) {
        // Only accept image files
        if (!file.type.match('image.*')) {
            showToast('Please select an image file', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const postPreviewImage = document.getElementById('postPreviewImage');
            const initialPostState = document.getElementById('initialPostState');
            const previewAndInputState = document.getElementById('previewAndInputState');
            
            if (postPreviewImage) {
                postPreviewImage.src = e.target.result;
            }
            
            if (initialPostState && previewAndInputState) {
                initialPostState.style.display = 'none';
                previewAndInputState.style.display = 'flex';
            }
        };
        reader.readAsDataURL(file);
    }
}

function handleStoryFileSelect(event) {
    const file = event.target.files[0];
    
    if (file) {
        // Only accept image files
        if (!file.type.match('image.*')) {
            showToast('Please select an image file', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const storyPreviewImage = document.getElementById('storyPreviewImage');
            const initialStoryState = document.getElementById('initialStoryState');
            const previewAndInputStoryState = document.getElementById('previewAndInputStoryState');
            
            if (storyPreviewImage) {
                storyPreviewImage.src = e.target.result;
            }
            
            if (initialStoryState && previewAndInputStoryState) {
                initialStoryState.style.display = 'none';
                previewAndInputStoryState.style.display = 'flex';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Make sure we have a global updateSelectedUsersDisplay function if it doesn't exist
if (typeof window.updateSelectedUsersDisplay === 'undefined') {
    window.updateSelectedUsersDisplay = function() {
        const selectedUsersContainer = document.getElementById('selectedUsersContainer');
        if (selectedUsersContainer) {
            selectedUsersContainer.innerHTML = '';
        }
    };
}

function submitPost() {
    const form = document.getElementById('createPostForm');
    const postId = form ? form.dataset.postId : null;
    const isUpdate = !!postId;
    
    // Get post data
    const description = document.getElementById('postDescriptionInput').value;
    const location = document.getElementById('postLocationInput').value;
    
    // Use the global helper function to get tags safely on any page
    let tags = window.getTagsFromSelectedUsers ? window.getTagsFromSelectedUsers() : '';
    
    const fileInput = document.getElementById('fileInput');
    
    // Handle case where commentsToggle might not exist on some pages
    let commentsDisabled = false;
    const commentsToggle = document.getElementById('commentsToggle');
    if (commentsToggle) {
        commentsDisabled = commentsToggle.checked;
    }

    const formData = new FormData();
    
    // Add common data
    formData.append('description', description);
    formData.append('location', location);
    formData.append('tags', tags);
    formData.append('deactivated_comments', commentsDisabled ? '1' : '0');
    
    // Add post ID if we're updating
    if (isUpdate) {
        console.log('Updating post:', postId);
        formData.append('post_id', postId);
    } else {
        // Only require image for new posts
        if (!fileInput.files[0]) {
            showToast('Please select an image to upload');
            return;
        }
        formData.append('image', fileInput.files[0]);
    }

    // Show loading state
    const submitButton = document.getElementById('submitPostButton');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Se încarcă...';
    }

    fetch('utils/create_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store success message in sessionStorage before reloading
            const message = isUpdate ? 'Post updated successfully' : 'Post created successfully';
            sessionStorage.setItem('toastMessage', message);
            sessionStorage.setItem('toastType', 'success');
            closeCreateModal();
            // Reload the page to show the new post
            window.location.reload();
        } else {
            showToast(data.error || 'An error occurred while saving the post', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while saving the post');
    })
    .finally(() => {
        // Reset button state
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = `<span>${isUpdate ? 'Update Post' : 'Partajează'}</span>`;
        }
    });
}

// Music Search Functionality
let musicSearchTimeout;
let selectedMusic = null;
let ytPlayer = null;
let isPlayerReady = false;
let songDuration = 0;
let startTime = 0;

// Load YouTube API
function loadYouTubeAPI() {
    if (window.YT) return Promise.resolve();
    
    return new Promise((resolve) => {
        // Create script tag if not already present
        if (!document.getElementById('youtube-api')) {
            const tag = document.createElement('script');
            tag.id = 'youtube-api';
            tag.src = 'https://www.youtube.com/iframe_api';
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
        
        // Setup callback for when API is ready
        window.onYouTubeIframeAPIReady = function() {
            resolve();
        };
        
        // If API is already loaded, resolve immediately
        if (window.YT && window.YT.Player) {
            resolve();
        }
    });
}

// Format time in seconds to MM:SS format
function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
}

// Initialize the YouTube player
function initYouTubePlayer(videoId) {
    if (!videoId) return;
    
    const container = document.getElementById('youtubePlayerContainer');
    if (!container) return;
    
    // Clear any existing player
    container.innerHTML = '<div id="youtubePlayer"></div>';
    
    loadYouTubeAPI().then(() => {
        ytPlayer = new YT.Player('youtubePlayer', {
            height: '0',
            width: '0',
            videoId: videoId,
            playerVars: {
                autoplay: 0,
                controls: 0,
                disablekb: 1,
                fs: 0,
                modestbranding: 1
            },
            events: {
                onReady: onPlayerReady,
                onStateChange: onPlayerStateChange
            }
        });
    }).catch(error => {
        console.error('Error loading YouTube API:', error);
    });
}

// When player is ready
function onPlayerReady(event) {
    isPlayerReady = true;
    songDuration = event.target.getDuration();
    updateTimeDisplay(0);
    updateSelectionDisplay();
    
    // Setup slider and time display
    const timeSlider = document.getElementById('timeSlider');
    if (timeSlider) {
        timeSlider.max = songDuration;
        timeSlider.value = 0;
        
        timeSlider.addEventListener('input', function() {
            const time = parseFloat(this.value);
            if (isPlayerReady && ytPlayer) {
                // Update the YouTube player position
                ytPlayer.seekTo(time, true);
                updateTimeDisplay(time);
                updateProgressBar(time);
                
                // Also move the 15-second selector to this position
                startTime = time;
                document.getElementById('musicStartTime').value = startTime;
                document.getElementById('startTimeDisplay').textContent = formatTime(startTime);
                
                // Update the selection overlay position
                updateSelectionDisplay();
                
                console.log('Timeline clicked, moved selector to:', time);
            }
        });
        
        // Also add a click handler to the timeline container for better interaction
        const timelineContainer = timeSlider.parentElement;
        if (timelineContainer) {
            timelineContainer.addEventListener('click', function(e) {
                if (e.target === timeSlider) return; // Avoid duplicate events
                
                // Calculate the click position as a percentage of the timeline width
                const rect = timelineContainer.getBoundingClientRect();
                const clickPosition = (e.clientX - rect.left) / rect.width;
                const time = clickPosition * songDuration;
                
                // Update everything
                if (isPlayerReady && ytPlayer) {
                    ytPlayer.seekTo(time, true);
                    updateTimeDisplay(time);
                    updateProgressBar(time);
                    
                    // Move the 15-second selector to this position
                    startTime = time;
                    document.getElementById('musicStartTime').value = startTime;
                    document.getElementById('startTimeDisplay').textContent = formatTime(startTime);
                    updateSelectionDisplay();
                    
                    console.log('Timeline container clicked, moved selector to:', time);
                }
            });
        }
    }
    
    // Setup draggable 15-second segment selector
    setupDraggableSelector();
    
    // Setup preview selection button
    const previewSelectionBtn = document.getElementById('previewSelectionBtn');
    if (previewSelectionBtn) {
        previewSelectionBtn.addEventListener('click', function() {
            if (isPlayerReady && ytPlayer) {
                // Seek to the start time and play
                ytPlayer.seekTo(startTime, true);
                ytPlayer.playVideo();
                
                // Schedule pause after 15 seconds or at song end
                const endTime = Math.min(startTime + 15, songDuration);
                const previewDuration = (endTime - startTime) * 1000; // Convert to milliseconds
                
                setTimeout(() => {
                    if (ytPlayer && isPlayerReady) {
                        ytPlayer.pauseVideo();
                    }
                }, previewDuration);
            }
        });
    }
}

// When player state changes
function onPlayerStateChange(event) {
    const playPauseBtn = document.getElementById('playPauseBtn');
    
    if (event.data === YT.PlayerState.PLAYING) {
        // Update progress bar while playing
        updatePlayButton(true);
        startProgressUpdater();
        
        // Start monitoring for end of the 15-second segment to loop
        startLoopMonitor();
    } else if (event.data === YT.PlayerState.ENDED || event.data === YT.PlayerState.PAUSED) {
        updatePlayButton(false);
        stopProgressUpdater();
        stopLoopMonitor();
    }
}

// Global event handler references for cleanup
let dragStartHandler = null;
let dragMoveHandler = null;
let dragEndHandler = null;
let touchStartHandler = null;
let touchMoveHandler = null;
let touchEndHandler = null;

// Setup the draggable 15-second segment selector
function setupDraggableSelector() {
    const selectionOverlay = document.getElementById('selectionOverlay');
    const timeline = selectionOverlay?.parentElement;
    if (!selectionOverlay || !timeline || songDuration <= 0) return;
    
    // Clean up any existing event listeners to prevent duplicates
    cleanupDragListeners();
    
    let isDragging = false;
    let startX = 0;
    let startLeft = 0;
    
    // Calculate the width of the 15-second segment as a percentage of total song duration
    // Ensure it's at least 25% of the timeline width for better visibility
    const segmentWidth = Math.max(Math.min((15 / songDuration) * 100, 100), 25);
    selectionOverlay.style.width = `${segmentWidth}%`;
    
    // Initialize position to 0 (start of song) - making it immediately visible and draggable
    selectionOverlay.style.left = '0%';
    selectionOverlay.style.display = 'flex';
    selectionOverlay.style.opacity = '1';
    startTime = 0;
    document.getElementById('musicStartTime').value = startTime;
    document.getElementById('startTimeDisplay').textContent = formatTime(startTime);
    document.getElementById('endTimeDisplay').textContent = formatTime(Math.min(15, songDuration));
    
    // Add a highlight animation to indicate it's draggable
    selectionOverlay.style.boxShadow = '0 0 8px rgba(59, 130, 246, 0.8)';
    selectionOverlay.classList.add('animate-pulse');
    setTimeout(() => {
        selectionOverlay.classList.remove('animate-pulse');
        selectionOverlay.style.boxShadow = '';
    }, 2000);
    
    // Maximum position (prevent dragging beyond the end of the song)
    const maxPosition = 100 - segmentWidth;
    
    // Define drag handlers
    dragStartHandler = function(e) {
        e.preventDefault();
        isDragging = true;
        
        // Get initial position
        const touch = e.type === 'touchstart' ? e.touches[0] : e;
        startX = touch.clientX;
        startLeft = parseFloat(selectionOverlay.style.left || '0');
        
        // Change appearance to indicate active dragging
        selectionOverlay.style.opacity = '0.8';
        selectionOverlay.style.cursor = 'grabbing';
        document.body.style.cursor = 'grabbing'; // Change cursor for the entire page during drag
        
        console.log('Drag started at position:', startLeft);
    };
    
    dragMoveHandler = function(e) {
        if (!isDragging) return;
        e.preventDefault();
        
        const touch = e.type === 'touchmove' ? e.touches[0] : e;
        const timelineRect = timeline.getBoundingClientRect();
        
        // Calculate drag distance as percentage of timeline width
        const deltaX = touch.clientX - startX;
        const deltaPercent = (deltaX / timelineRect.width) * 100;
        
        // Update position with bounds checking
        let newPosition = startLeft + deltaPercent;
        newPosition = Math.max(0, Math.min(newPosition, maxPosition));
        selectionOverlay.style.left = `${newPosition}%`;
        
        // Calculate and update the start time based on the new position
        startTime = (newPosition / 100) * songDuration;
        document.getElementById('musicStartTime').value = startTime;
        document.getElementById('startTimeDisplay').textContent = formatTime(startTime);
        
        // Update end time display
        const endTime = Math.min(startTime + 15, songDuration);
        document.getElementById('endTimeDisplay').textContent = formatTime(endTime);
        
        // Update the YouTube player position if it's playing
        if (ytPlayer && ytPlayer.getPlayerState() === YT.PlayerState.PLAYING) {
            ytPlayer.seekTo(startTime, true);
        }
        
        console.log('Dragging to position:', newPosition);
    };
    
    dragEndHandler = function(e) {
        if (!isDragging) return;
        
        // Optional: prevent default only for touch events
        if (e.type === 'touchend') e.preventDefault();
        
        isDragging = false;
        
        // Restore normal appearance
        selectionOverlay.style.opacity = '1';
        selectionOverlay.style.cursor = 'move';
        document.body.style.cursor = ''; // Restore default cursor
        
        console.log('Drag ended, final position:', selectionOverlay.style.left);
    };
    
    // Attach mouse event listeners
    selectionOverlay.addEventListener('mousedown', dragStartHandler);
    document.addEventListener('mousemove', dragMoveHandler);
    document.addEventListener('mouseup', dragEndHandler);
    
    // Attach touch event listeners for mobile
    touchStartHandler = dragStartHandler; // Reuse the same handler
    touchMoveHandler = dragMoveHandler;
    touchEndHandler = dragEndHandler;
    
    selectionOverlay.addEventListener('touchstart', touchStartHandler, { passive: false });
    document.addEventListener('touchmove', touchMoveHandler, { passive: false });
    document.addEventListener('touchend', touchEndHandler);
    
    console.log('Drag handlers attached to selection overlay');
}

// Clean up event listeners to prevent memory leaks and duplicate handlers
function cleanupDragListeners() {
    const selectionOverlay = document.getElementById('selectionOverlay');
    if (!selectionOverlay) return;
    
    // Remove existing mouse event listeners if they exist
    if (dragStartHandler) {
        selectionOverlay.removeEventListener('mousedown', dragStartHandler);
        document.removeEventListener('mousemove', dragMoveHandler);
        document.removeEventListener('mouseup', dragEndHandler);
    }
    
    // Remove existing touch event listeners if they exist
    if (touchStartHandler) {
        selectionOverlay.removeEventListener('touchstart', touchStartHandler);
        document.removeEventListener('touchmove', touchMoveHandler);
        document.removeEventListener('touchend', touchEndHandler);
    }
    
    console.log('Cleaned up previous drag event listeners');
}

// Update play/pause button appearance
function updatePlayButton(isPlaying) {
    const playPauseBtn = document.getElementById('playPauseBtn');
    if (!playPauseBtn) return;
    
    if (isPlaying) {
        playPauseBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>`;
    } else {
        playPauseBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
            </svg>`;
    }
}

// Update time display
function updateTimeDisplay(time) {
    const timeDisplay = document.getElementById('timeDisplay');
    if (timeDisplay) {
        timeDisplay.textContent = formatTime(time);
    }
}

// Update progress bar
function updateProgressBar(time) {
    const progressBar = document.getElementById('progressBar');
    if (progressBar && songDuration > 0) {
        const percent = (time / songDuration) * 100;
        progressBar.style.width = `${percent}%`;
    }
}

// Update the 15-second selection overlay
function updateSelectionDisplay() {
    const selectionOverlay = document.getElementById('selectionOverlay');
    const endTimeDisplay = document.getElementById('endTimeDisplay');
    if (!selectionOverlay || songDuration <= 0) return;
    
    // Calculate width and position for the 15-second selection
    // Ensure the width is at least 25% of the timeline for better visibility
    // but accurately represents 15 seconds of the song duration
    const selectionWidth = Math.max(Math.min((15 / songDuration) * 100, 100), 25);
    const selectionPosition = (startTime / songDuration) * 100;
    
    // Make sure the selection doesn't go beyond the song duration
    const maxPosition = 100 - selectionWidth;
    const adjustedPosition = Math.min(selectionPosition, maxPosition);
    
    // Update the UI with clear visibility
    selectionOverlay.style.width = `${selectionWidth}%`;
    selectionOverlay.style.left = `${adjustedPosition}%`;
    selectionOverlay.style.display = 'flex'; // Ensure it's visible
    selectionOverlay.style.opacity = '1'; // Full opacity
    
    // Update the end time display (start time + 15 seconds)
    const endTime = Math.min(startTime + 15, songDuration);
    if (endTimeDisplay) {
        endTimeDisplay.textContent = formatTime(endTime);
    }
}

// Intervals for updating progress and monitoring loop
let progressInterval;
let loopMonitorInterval;

function startProgressUpdater() {
    stopProgressUpdater();
    progressInterval = setInterval(() => {
        if (isPlayerReady && ytPlayer) {
            const currentTime = ytPlayer.getCurrentTime();
            updateTimeDisplay(currentTime);
            updateProgressBar(currentTime);
        }
    }, 500);
}

function stopProgressUpdater() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
}

// Monitor and loop the 15-second segment
function startLoopMonitor() {
    stopLoopMonitor();
    loopMonitorInterval = setInterval(() => {
        if (isPlayerReady && ytPlayer && ytPlayer.getPlayerState() === YT.PlayerState.PLAYING) {
            const currentTime = ytPlayer.getCurrentTime();
            const endTime = Math.min(startTime + 15, songDuration);
            
            // If we've reached the end of our 15-second segment, loop back to start
            if (currentTime >= endTime) {
                console.log('Reached end of 15-second segment, looping back to:', startTime);
                ytPlayer.seekTo(startTime, true);
                updateTimeDisplay(startTime);
                updateProgressBar(startTime);
            }
        }
    }, 200); // Check more frequently for more precise looping
}

function stopLoopMonitor() {
    if (loopMonitorInterval) {
        clearInterval(loopMonitorInterval);
        loopMonitorInterval = null;
    }
}

function displaySelectedMusic() {
    const selectedMusicElement = document.getElementById('selectedMusic');
    const thumbnailElement = document.getElementById('selectedMusicThumbnail');
    const titleElement = document.getElementById('selectedMusicTitle');
    const artistElement = document.getElementById('selectedMusicArtist');
    const playerContainer = document.getElementById('musicPlayerContainer');
    const removeMusicBtn = document.getElementById('removeMusicBtn');

    if (selectedMusic) {
        thumbnailElement.src = selectedMusic.thumbnail;
        titleElement.textContent = selectedMusic.title;
        artistElement.textContent = selectedMusic.artist;
        selectedMusicElement.classList.remove('hidden');
        
        // Make sure the selection overlay is immediately visible
        const selectionOverlay = document.getElementById('selectionOverlay');
        if (selectionOverlay) {
            // Make it prominent with default styling before YouTube player is ready
            selectionOverlay.style.display = 'flex';
            selectionOverlay.style.opacity = '1';
            selectionOverlay.style.width = '25%';
            selectionOverlay.style.left = '0%';
            
            // Set initial values for the time displays
            document.getElementById('startTimeDisplay').textContent = '0:00';
            document.getElementById('endTimeDisplay').textContent = '0:15';
            document.getElementById('musicStartTime').value = '0';
        }
        
        // Setup remove music button to just clear the selection without submitting
        if (removeMusicBtn) {
            // Remove any existing event listeners
            removeMusicBtn.replaceWith(removeMusicBtn.cloneNode(true));
            const newRemoveBtn = document.getElementById('removeMusicBtn');
            
            // Add new event listener that just removes the song
            newRemoveBtn.addEventListener('click', function(e) {
                // Prevent form submission or any default behavior
                e.preventDefault();
                e.stopPropagation();
                
                // Clean up the player
                if (ytPlayer) {
                    ytPlayer.stopVideo();
                    ytPlayer.destroy();
                    ytPlayer = null;
                    isPlayerReady = false;
                }
                
                // Reset music selection
                selectedMusic = null;
                startTime = 0;
                stopProgressUpdater();
                stopLoopMonitor();
                
                // Hide the music selection
                selectedMusicElement.classList.add('hidden');
                
                // Show the music search again
                const musicSearchSection = document.getElementById('musicSearchSection');
                if (musicSearchSection) {
                    musicSearchSection.classList.remove('hidden');
                }
                
                console.log('Song removed, ready for new selection');
                return false;
            });
        }
        
        // Initialize the YouTube player when music is selected
        if (playerContainer) {
            initYouTubePlayer(selectedMusic.videoId);
            
            // Setup play/pause button
            const playPauseBtn = document.getElementById('playPauseBtn');
            if (playPauseBtn) {
                playPauseBtn.addEventListener('click', function() {
                    if (!isPlayerReady || !ytPlayer) return;
                    
                    const state = ytPlayer.getPlayerState();
                    if (state === YT.PlayerState.PLAYING) {
                        ytPlayer.pauseVideo();
                    } else {
                        ytPlayer.playVideo();
                    }
                });
            }
        }
    } else {
        selectedMusicElement.classList.add('hidden');
        
        // Cleanup player when music is removed
        if (ytPlayer) {
            ytPlayer.stopVideo();
            isPlayerReady = false;
            stopProgressUpdater();
        }
    }
}

// Function to handle modal close and cleanup resources
function setupModalCloseHandlers() {
    // Find close buttons for the modal
    const modal = document.getElementById('createModal');
    if (!modal) return;
    
    // Add listeners to all close buttons in the modal
    const closeButtons = modal.querySelectorAll('[data-action="close-modal"], .modal-close, .close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', cleanupMusicPlayer);
    });
    
    // Add listener to the modal backdrop for clicks outside
    modal.addEventListener('click', function(e) {
        // If clicked on the backdrop (modal itself, not its children)
        if (e.target === modal) {
            cleanupMusicPlayer();
        }
    });
    
    // Also listen for the Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            cleanupMusicPlayer();
        }
    });
    
    console.log('Modal close handlers set up');
}

// Function to stop music and clean up resources when modal is closed
function cleanupMusicPlayer() {
    console.log('Modal closing, cleaning up music player');
    
    // Stop all intervals
    stopProgressUpdater();
    stopLoopMonitor();
    
    // Stop the YouTube player if it exists
    if (ytPlayer && isPlayerReady) {
        ytPlayer.stopVideo();
        ytPlayer.destroy();
        ytPlayer = null;
        isPlayerReady = false;
    }
    
    // Reset the selection state
    selectedMusic = null;
    startTime = 0;
}

// Add back the submitStory function
async function submitStory() {
    // Stop any currently playing music immediately
    if (ytPlayer && typeof ytPlayer.pauseVideo === 'function') {
        ytPlayer.pauseVideo();
    }
    stopProgressUpdater();
    stopLoopMonitor();
    
    // Get file input
    const fileInput = document.getElementById('storyFileInput');
    
    // Check if a file is selected
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        showToast('Te rog selectează o imagine pentru a o încărca', 'error');
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('storyImage', fileInput.files[0]);
    
    // Add music data if selected
    if (selectedMusic) {
        // Get the start time from the hidden input field
        const musicStartTime = document.getElementById('musicStartTime');
        if (musicStartTime) {
            formData.append('music_start_time', musicStartTime.value);
        }
        
        formData.append('music_data', JSON.stringify(selectedMusic));
    }
    
    // Show loading state on button
    const submitButton = document.getElementById('submitStoryButton');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Se încarcă...';
    }
    
    // Disable all form inputs during submission
    const formInputs = document.querySelectorAll('#previewAndInputStoryState input, #previewAndInputStoryState button, #previewAndInputStoryState textarea');
    formInputs.forEach(input => {
        input.disabled = true;
    });
    
    // Submit form
    fetch('includes/submit_story.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store success message
            sessionStorage.setItem('toastMessage', 'Povestea a fost creată cu succes');
            sessionStorage.setItem('toastType', 'success');
            
            // Clean up music player resources
            cleanupMusicPlayer();
            
            // Close the modal
            closeCreateModal();
            
            // Show success toast
            showToast('Povestea a fost creată cu succes', 'success');
            
            // Reload page to show new story
            window.location.reload();
        } else {
            showToast(data.message || 'A apărut o eroare la crearea poveștii', 'error');
            
            // Re-enable form inputs on error
            formInputs.forEach(input => {
                input.disabled = false;
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('A apărut o eroare la trimiterea poveștii', 'error');
        
        // Re-enable form inputs on error
        formInputs.forEach(input => {
            input.disabled = false;
        });
    })
    .finally(() => {
        // Reset button state if still on the page
        if (submitButton && document.body.contains(submitButton)) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<span>Partajează</span>';
        }
    });
}

function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Remove active styles from all tab buttons
    document.querySelectorAll('[id$="TabBtn"]').forEach(btn => {
        btn.classList.remove('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
        btn.classList.add('text-neutral-600', 'dark:text-neutral-400');
    });
    
    // Show selected tab content
    document.getElementById(tabName + 'Content').style.display = 'block';
    
    // Add active styles to clicked tab button
    const activeBtn = document.getElementById(tabName + 'TabBtn');
    activeBtn.classList.add('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
    activeBtn.classList.remove('text-neutral-600', 'dark:text-neutral-400');
    
    // Set the correct submit button handler based on tab
    const submitButton = document.querySelector('#previewAndInputStoryState button');
    if (submitButton && tabName === 'story') {
        submitButton.onclick = submitStory;
    }
    
    // Reset states when switching tabs
    if (tabName === 'post') {
        const initialStoryState = document.getElementById('initialStoryState');
        const previewAndInputStoryState = document.getElementById('previewAndInputStoryState');
        
        if (initialStoryState && previewAndInputStoryState) {
            initialStoryState.style.display = 'flex';
            previewAndInputStoryState.style.display = 'none';
        }
    } else if (tabName === 'story') {
        const initialPostState = document.getElementById('initialPostState');
        const previewAndInputState = document.getElementById('previewAndInputState');
        
        if (initialPostState && previewAndInputState) {
            initialPostState.style.display = 'flex';
            previewAndInputState.style.display = 'none';
        }
    }
}

function updateCommentsStatus() {
    const commentsToggle = document.getElementById('commentsToggle');
    const commentsStatus = document.getElementById('commentsStatus');
    if (!commentsToggle || !commentsStatus) return;

    if (commentsToggle.checked) {
        commentsStatus.textContent = 'Comentarii dezactivate';
        commentsStatus.classList.remove('text-green-600', 'dark:text-green-400');
        commentsStatus.classList.add('text-red-600', 'dark:text-red-400');
    } else {
        commentsStatus.textContent = 'Comentarii active';
        commentsStatus.classList.remove('text-red-600', 'dark:text-red-400');
        commentsStatus.classList.add('text-green-600', 'dark:text-green-400');
    }
}

function preventSpecialChars(event) {
    // Allow: backspace, delete, tab, escape, enter
    if ([8, 9, 13, 27, 46].indexOf(event.keyCode) !== -1 ||
        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        (event.keyCode >= 35 && event.keyCode <= 39) ||
        (event.ctrlKey && [65, 67, 86, 88].indexOf(event.keyCode) !== -1)) {
        return true;
    }
    
    // Prevent line breaks
    if (event.keyCode === 13) {
        event.preventDefault();
        return false;
    }
    
    // Allow only letters, numbers, spaces, and basic punctuation
    const regex = /^[a-zA-Z0-9\s.,!?-]*$/;
    if (!regex.test(event.key)) {
        event.preventDefault();
        return false;
    }
    
    return true;
}

function editPost(postId) {
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
            showToast('Error loading edit form', 'error');
        });
} 