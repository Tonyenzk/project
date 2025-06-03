<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

// We'll fetch the story owner's data dynamically through JavaScript
// This section just ensures we're logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to format time difference
function getTimeAgo($timestamp) {
    $time_difference = time() - strtotime($timestamp);
    
    if ($time_difference < 60) {
        return '1s';
    } elseif ($time_difference < 3600) {
        $minutes = floor($time_difference / 60);
        return $minutes . 'm';
    } elseif ($time_difference < 86400) {
        $hours = floor($time_difference / 3600);
        return $hours . 'h';
    } else {
        $days = floor($time_difference / 86400);
        return $days . 'd';
    }
}
?>

<div id="story-modal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none; background-color: rgba(0, 0, 0, 0.7);">
    <div class="relative w-full max-w-md md:max-w-3xl lg:max-w-6xl h-[85vh] bg-black rounded-lg overflow-hidden flex flex-col">
        <!-- Progress Bar Container -->
        <div id="story-progress-bars" class="absolute top-4 left-0 right-0 z-20 px-6 pb-4 flex space-x-1">
             <!-- Progress bars will be added here by JavaScript -->
        </div>
        
        <!-- Spacer element -->
        <div class="w-full h-20"></div>

        <!-- Story Header -->
        <div class="absolute top-36 left-0 right-0 z-10 px-6 py-8 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="relative flex shrink-0 overflow-hidden rounded-full h-10 w-10 bg-gray-300">
                    <img id="story-owner-avatar" class="absolute inset-0 w-full h-full object-cover rounded-full" src="../images/profile_placeholder.webp" alt="Story Owner Avatar">
                </div>
                <div class="flex flex-col justify-center min-w-0">
                    <div class="flex items-center space-x-1 text-white">
                        <span id="story-owner-username" class="font-semibold text-base truncate">Loading...</span>
                        <svg id="story-owner-verified" style="display: none;" aria-label="Verified" class="flex-shrink-0 text-blue-500" fill="currentColor" height="16" width="16" viewBox="0 0 40 40">
                            <title>Verified</title>
                            <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                        </svg>
                        <span class="text-white mx-1">•</span>
                        <span id="story-timestamp" class="font-semibold text-base text-white">Loading...</span>
                    </div>
                    
                    <!-- Song information (hidden by default, shown when a story has music) -->
                    <div id="story-music-info" class="flex items-center mt-1" style="display: none;">
                        <div class="flex items-center space-x-1">
                            <!-- Music icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-white opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                            </svg>
                            <!-- Song title and artist -->
                            <div class="text-xs text-white opacity-90 truncate max-w-[150px]">
                                <span id="story-music-title" class="font-medium">Song Title</span>
                                <span class="mx-1">•</span>
                                <span id="story-music-artist" class="text-white opacity-75">Artist</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button id="pause-story" class="text-white hover:text-gray-300 focus:outline-none p-1 transition-colors duration-200">
                    <svg id="pause-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg id="play-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
                <button id="toggle-volume" class="text-white hover:text-gray-300 focus:outline-none p-1 transition-colors duration-200">
                    <svg id="volume-on-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                    </svg>
                    <svg id="volume-off-icon" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                    </svg>
                </button>
                <button id="delete-story" class="text-white hover:text-red-500 focus:outline-none p-1 transition-colors duration-200" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <button id="report-story" class="text-white hover:text-red-500 focus:outline-none p-1 transition-colors duration-200" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </button>
                <button id="close-story-modal" class="text-white hover:text-gray-300 focus:outline-none p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Story Content Container -->
        <!-- Hidden YouTube player container -->
        <div id="youtube-player-container" class="absolute opacity-0 pointer-events-none" style="width:1px; height:1px; overflow:hidden; position:absolute; top:-9999px; left:-9999px;">
            <div id="youtube-player"></div>
        </div>
        
        <div class="flex-1 relative flex items-center justify-center">
            <div class="relative w-full h-full">
                <img id="story-image" src="" alt="Story Image" class="absolute inset-0 w-full h-full object-cover">
                
                <!-- Music Player -->
                <div id="story-music-player" class="absolute bottom-4 left-4 right-4 bg-black/50 backdrop-blur-sm rounded-lg p-3 hidden">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <img id="music-thumbnail" src="" alt="Music thumbnail" class="w-10 h-10 rounded">
                            <div>
                                <p id="music-title" class="text-sm font-medium text-white"></p>
                                <p id="music-artist" class="text-xs text-neutral-300"></p>
                            </div>
                        </div>
                        <button id="toggle-music" class="text-white hover:text-neutral-300">
                            <svg id="play-music-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                            </svg>
                            <svg id="pause-music-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 hidden">
                                <rect x="6" y="4" width="4" height="16"></rect>
                                <rect x="14" y="4" width="4" height="16"></rect>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation Arrows -->
            <button id="prev-story-btn" class="absolute left-4 z-20 text-white p-2 rounded-full bg-black/30 hover:bg-black/50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button id="next-story-btn" class="absolute right-4 z-20 text-white p-2 rounded-full bg-black/30 hover:bg-black/50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Viewers and Likes Icon -->
        <div class="absolute bottom-0 left-0 z-10 p-6">
            <div id="viewers-likes-icon" class="flex items-center text-white cursor-pointer hover:opacity-80 transition-opacity" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span id="viewer-count" class="text-base font-medium">0</span>
            </div>
        </div>

        <!-- Heart Button for Other People's Stories -->
        <div id="story-heart-button" class="absolute bottom-0 right-0 z-10 p-6" style="display: none;">
            <button class="text-white hover:scale-110 transition-transform duration-200 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        </div>
        <!-- Red Heart Icon (Displayed when story is liked) -->
        <div id="story-heart-liked" class="absolute bottom-0 right-0 z-10 p-6" style="display: none;">
            <button class="text-red-500 hover:scale-110 transition-transform duration-200 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        </div>
    </div>
</div> 

<!-- Viewers and Likes Modal -->
<div id="viewers-likes-modal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none; background-color: rgba(10, 10, 10, 0.1);">
    <div class="relative w-full max-w-md md:max-w-lg bg-white dark:bg-neutral-900 rounded-lg overflow-hidden flex flex-col p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-neutral-900 dark:text-white">Viewers and Likes</h3>
            <button id="close-viewers-likes-modal" class="text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 focus:outline-none p-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <!-- Viewers list will be populated dynamically -->
        <div id="story-viewers-container" class="space-y-5">
            <div class="flex items-center justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-gray-900 dark:border-white"></div>
                <span class="ml-3 text-gray-700 dark:text-gray-300">Loading viewers...</span>
            </div>
        </div>
        </div>
    </div>
</div>

<!-- Story Delete Confirmation Modal -->
<div id="delete-story-confirmation-modal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none; background-color: rgba(0, 0, 0, 0.7);">
    <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-neutral-900 text-left shadow-xl transition-all p-6">
        <div class="flex items-center justify-center mb-4 text-red-600">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"></path>
                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-center mb-2 text-neutral-900 dark:text-white">Delete Story</h3>
        <p class="text-sm text-neutral-600 dark:text-neutral-400 text-center mb-4">Are you sure you want to delete this story? This action cannot be undone.</p>
        
        <div class="flex justify-center gap-3 mt-6">
            <button type="button" id="cancel-delete-story-btn" class="px-4 py-2 bg-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 dark:bg-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-600 dark:focus:ring-neutral-700">
                Cancel
            </button>
            <button type="button" id="confirm-delete-story-btn" class="px-4 py-2 rounded-lg" style="background-color: red !important; color: white !important; display: inline-block !important;">
                Sterge
            </button>
        </div>
    </div>
</div>

<!-- Story Report Modal -->
<div id="storyReportModal" class="fixed inset-0 z-50 hidden" style="z-index: 9999;">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeStoryReportModal()"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center" onclick="closeStoryReportModal()">
        <div class="bg-white dark:bg-black w-[400px] max-h-[70vh] flex flex-col rounded-xl overflow-hidden" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center p-4 border-b border-neutral-200 dark:border-neutral-800">
                <div class="w-10"></div>
                <h2 class="font-semibold text-center">Report Story</h2>
                <button onclick="closeStoryReportModal()" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-neutral-100 dark:hover:bg-neutral-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-5 w-5">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <form id="storyReportForm" class="space-y-4">
                    <input type="hidden" id="reportStoryId" name="story_id">
                    <div>
                        <label for="storyReportDescription" class="block text-sm font-medium mb-2">Why are you reporting this story? (max 150 characters)</label>
                        <textarea id="storyReportDescription" name="description" maxlength="150" rows="3" class="w-full px-3 py-2 border border-neutral-200 dark:border-neutral-800 rounded-lg bg-white dark:bg-black focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Please provide details about your report..."></textarea>
                        <div class="text-right text-sm text-neutral-500 mt-1">
                            <span id="storyCharCount">0</span>/150
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                        Submit Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add YouTube IFrame API -->
<script src="https://www.youtube.com/iframe_api"></script>

<script>
// Global function to ensure all music playback is completely stopped
window.stopAllMusicPlayback = function() {
    // Clear any loop interval first
    if (window.storyMusicLoopInterval) {
        clearInterval(window.storyMusicLoopInterval);
        window.storyMusicLoopInterval = null;
    }
    
    // Only attempt to access player methods if the player exists and is in a valid state
    if (window.storyMusicPlayer && typeof window.storyMusicPlayer === 'object') {
        // Safely check if methods exist before calling them
        if (window.storyMusicPlayer.getPlayerState && 
            typeof window.storyMusicPlayer.getPlayerState === 'function') {
            
            // Only try to stop if the player is actually playing
            if (window.storyMusicPlayer.stopVideo && 
                typeof window.storyMusicPlayer.stopVideo === 'function') {
                try {
                    window.storyMusicPlayer.stopVideo();
                } catch (e) {
                    // Silent error handling
                }
            }
            
            // Try to destroy the player if the method exists
            if (window.storyMusicPlayer.destroy && 
                typeof window.storyMusicPlayer.destroy === 'function') {
                try {
                    window.storyMusicPlayer.destroy();
                } catch (e) {
                    // Silent error handling
                }
            }
        }
    }
    
    // Always set the player reference to null after cleanup
    window.storyMusicPlayer = null;
    
    // For safety, also stop any legacy player
    if (typeof musicPlayer !== 'undefined' && musicPlayer) {
        try {
            if (musicPlayer.stopVideo && typeof musicPlayer.stopVideo === 'function') {
                musicPlayer.stopVideo();
            }
            if (musicPlayer.destroy && typeof musicPlayer.destroy === 'function') {
                musicPlayer.destroy();
            }
            musicPlayer = null;
        } catch (e) {
            // Silent error handling
        }
    }
    
    // As a final failsafe, clear the player container
    const playerContainer = document.getElementById('youtube-player-container');
    if (playerContainer) {
        playerContainer.innerHTML = '<div id="youtube-player"></div>';
    }
};

// Define openStoryModal in global scope before any usage
window.openStoryModal = function(userId = null) {
    const storyModalElement = document.getElementById('story-modal');
    if (!storyModalElement) {
        return;
    }
    
    storyModalElement.style.display = 'flex';
    document.body.classList.add('no-scroll');
    window.currentStoryIndex = 0; // Start from the first story
    window.loadStories(userId); // Load stories from server
};

// Function to format timestamp like Instagram
function formatTimestamp(timestamp) {
    const now = new Date();
    const storyTime = new Date(timestamp);
    const diff = Math.floor((now - storyTime) / 1000); // difference in seconds

    if (diff < 60) return '1s';
    if (diff < 3600) return Math.floor(diff / 60) + 'm';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    return Math.floor(diff / 86400) + 'd';
}

// Function to update story owner information
function updateStoryOwnerInfo(story) {
    const avatar = document.getElementById('story-owner-avatar');
    const username = document.getElementById('story-owner-username');
    const verified = document.getElementById('story-owner-verified');
    const timestamp = document.getElementById('story-timestamp');
    const deleteStoryBtn = document.getElementById('delete-story');
    const reportStoryBtn = document.getElementById('report-story');

    if (!story) return;

    // Update avatar with proper URL handling
    if (avatar && story.profile_picture) {
        const avatarUrl = story.profile_picture.startsWith('../') ? story.profile_picture : '../' + story.profile_picture;
        avatar.src = avatarUrl || '../images/profile_placeholder.webp';
    }
    
    // Update username
    if (username) {
        username.textContent = story.username || 'Unknown User';
    }
    
    // Update verified badge
    if (verified) {
        verified.style.display = story.verifybadge === 'true' ? 'inline-block' : 'none';
    }
    
    // Update timestamp
    if (timestamp) {
        timestamp.textContent = formatTimestamp(story.created_at);
    }

    // Show/hide delete or report button based on ownership
    if (deleteStoryBtn && reportStoryBtn) {
        const isOwner = story.user_id == window.currentUserID;
        deleteStoryBtn.style.display = isOwner ? 'block' : 'none';
        reportStoryBtn.style.display = isOwner ? 'none' : 'block';
    }
}

// Function to close the delete confirmation modal for stories - in global scope
function closeDeleteStoryConfirmation() {
    const deleteConfirmationModal = document.getElementById('delete-story-confirmation-modal');
    if (deleteConfirmationModal) {
        deleteConfirmationModal.style.display = 'none';
    }
}

// Initialize global variables
window.stories = [];
window.storyIds = [];
window.storyUsers = [];
window.currentStoryIndex = 0;
window.isStoryAnimationPaused = false;
window.progressInterval = null;
window.storyPauseProgressData = null;
window.storyDuration = 5000; // Default: 5 seconds per story without music
window.storyDurationWithMusic = 15000; // 15 seconds for stories with music
window.volumeFadeInterval = null;

let musicPlayer = null;
let isMusicPlaying = false;

// Story modal initialization - wrapped in IIFE to avoid scope issues
(function() {
    // Check if the story modal code has already been initialized
    if (typeof window.storyModalInitialized === 'undefined') {
        window.storyModalInitialized = true;
        
        const storyModalElement = document.getElementById('story-modal');
        const viewersLikesIcon = document.getElementById('viewers-likes-icon');
        const viewersLikesModal = document.getElementById('viewers-likes-modal');
        const closeViewersLikesModal = document.getElementById('close-viewers-likes-modal');
        const storyProgressBar = document.getElementById('story-progress-bar');
        const storyImage = document.getElementById('story-image');
        const prevStoryBtn = document.getElementById('prev-story-btn');
        const nextStoryBtn = document.getElementById('next-story-btn');
        const storyProgressBarsContainer = document.getElementById('story-progress-bars');
        const deleteStoryBtn = document.getElementById('delete-story');
        const pauseStoryBtn = document.getElementById('pause-story');
        const pauseIcon = document.getElementById('pause-icon');
        const playIcon = document.getElementById('play-icon');

        // Get current user ID
        window.currentUserID = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
        
        // Function to create and add progress bars based on the number of stories
        function createProgressBars() {
            storyProgressBarsContainer.innerHTML = ''; // Clear existing bars
            window.stories.forEach((_, index) => {
                const barContainer = document.createElement('div');
                barContainer.className = 'w-0 flex-1 h-1.5 bg-gray-400/30 rounded-full'; // flex-1 distributes space
                const progressBar = document.createElement('div');
                progressBar.id = `story-progress-bar-${index}`;
                progressBar.className = 'h-full bg-white rounded-full';
                progressBar.style.width = '0%';
                progressBar.style.transition = 'none'; // Initially no transition

                barContainer.appendChild(progressBar);
                storyProgressBarsContainer.appendChild(barContainer);
            });
        }

        function updateProgressBarsVisuals() {
            const progressBars = storyProgressBarsContainer.querySelectorAll('.h-full');
            progressBars.forEach((bar, index) => {
                bar.style.transition = 'none'; // Remove transition for instant update
                if (index < window.currentStoryIndex) {
                    bar.style.width = '100%'; // Fill previous bars
                    bar.style.backgroundColor = 'white';
                } else if (index === window.currentStoryIndex) {
                    // Current bar will be animated by startProgressBar
                    bar.style.width = '0%';
                    bar.style.backgroundColor = 'white';
                } else {
                    bar.style.width = '0%'; // Ensure future bars are empty
                    bar.style.backgroundColor = ''; // Revert to container background
                }
            });
        }

        function startProgressBar(index) {
            clearTimeout(window.progressInterval);
            const progressBar = document.getElementById(`story-progress-bar-${index}`);
            if (!progressBar) return;
            
            // Check if current story has music to determine duration
            const currentStory = window.stories[window.currentStoryIndex];
            const hasMusic = currentStory && currentStory.music_data;
            
            // Use appropriate duration based on whether the story has music
            const duration = hasMusic ? window.storyDurationWithMusic : window.storyDuration;

            // Reset pause state
            window.isStoryAnimationPaused = false;
            pauseIcon.classList.remove('hidden');
            playIcon.classList.add('hidden');

            // Force reflow to apply initial state before transition
            progressBar.offsetHeight;

            progressBar.style.transition = `width ${duration}ms linear`;
            progressBar.style.width = '100%';

            window.progressInterval = setTimeout(() => {
                nextStory(); // Move to the next story after duration
            }, duration);
        }

        function updateStory() {
            // Ensure index is within bounds before updating visuals and starting timer
            if (window.currentStoryIndex < 0) {
                window.currentStoryIndex = 0;
                return;
            }

            if (window.currentStoryIndex >= window.stories.length) {
                closeStoryModal();
                return;
            }

            // Update story content
            const story = window.stories[window.currentStoryIndex];
            if (!story) {
                return;
            }

            // Update story image
            if (storyImage && story.image_url) {
                const imageUrl = '../' + story.image_url;
                const cacheBuster = `?t=${new Date().getTime()}`;
                storyImage.src = imageUrl + cacheBuster;
                storyImage.alt = `Story by ${story.username}`;
            }

            // Update music player if story has music
            if (story.music_data) {
                // Show music info UI
                const musicInfoElement = document.getElementById('story-music-info');
                if (musicInfoElement) {
                    musicInfoElement.style.display = 'flex';
                }
                
                try {
                    const musicData = JSON.parse(story.music_data);
                    
                    // Correctly get the start time from the musicData object
                    // It could be in either of these locations based on how it was saved
                    const musicStartTime = musicData.startTime || 0;
                    
                    // Update music info display
                    const musicTitleElement = document.getElementById('story-music-title');
                    const musicArtistElement = document.getElementById('story-music-artist');
                    
                    if (musicTitleElement && musicData.title) {
                        musicTitleElement.textContent = musicData.title;
                    }
                    
                    if (musicArtistElement && musicData.artist) {
                        musicArtistElement.textContent = musicData.artist;
                    }
                    
                    // Initialize YouTube player if API is loaded
                    if (window.YT && window.YT.Player) {
                        // Use our global function to initialize the player
                        window.initializeYouTubePlayer(musicData.videoId, musicStartTime);
                    } else {
                        // Store the video info for later initialization
                        window.pendingMusicVideo = {
                            videoId: musicData.videoId,
                            startTime: musicStartTime
                        };
                        
                        // Load YouTube API if not already loaded
                        loadYouTubeAPI();
                    }
                } catch (error) {
                    // Silent error handling for music data parsing
                }
            } else {
                // Hide music info if no music data
                const musicInfoElement = document.getElementById('story-music-info');
                if (musicInfoElement) {
                    musicInfoElement.style.display = 'none';
                }
                
                // Stop and destroy player if it exists
                if (window.storyMusicPlayer) {
                    try {
                        // Clear any loop interval
                        if (window.storyMusicLoopInterval) {
                            clearInterval(window.storyMusicLoopInterval);
                            window.storyMusicLoopInterval = null;
                        }
                        
                        window.storyMusicPlayer.stopVideo();
                        window.storyMusicPlayer.destroy();
                        window.storyMusicPlayer = null;
                    } catch (e) {
                        // Silent error handling for music player stopping
                    }
                }
            }

            // Update owner info
            updateStoryOwnerInfo(story);

            // Show/hide views count and heart button based on whether the current user is the story owner
            const viewersLikesIcon = document.getElementById('viewers-likes-icon');
            const heartButton = document.getElementById('story-heart-button');
            const heartLiked = document.getElementById('story-heart-liked');
            
            if (viewersLikesIcon && heartButton && heartLiked && deleteStoryBtn) {
                const isOwner = story.user_id == window.currentUserID;
                
                viewersLikesIcon.style.display = isOwner ? 'flex' : 'none';
                deleteStoryBtn.style.display = isOwner ? 'block' : 'none';
                
                // Hide both heart buttons initially
                heartButton.style.display = 'none';
                heartLiked.style.display = 'none';
                
                // Always track story views regardless of owner status
                trackStoryView(story.story_id);
                
                // If not owner, check like status
                if (!isOwner) {
                    // Check if the current user has liked this story
                    checkStoryLikeStatus(story.story_id);
                } else {
                    // If owner, update the viewer count
                    updateViewerCount(story.story_id);
                }
            }    

            // Update progress bars visual state
            updateProgressBarsVisuals();

            // Show/hide navigation arrows based on current story index
            if (window.stories.length > 1) {
                if (window.currentStoryIndex === 0) {
                    prevStoryBtn.style.display = 'none';
                    nextStoryBtn.style.display = 'block';
                } else if (window.currentStoryIndex === window.stories.length - 1) {
                    prevStoryBtn.style.display = 'block';
                    nextStoryBtn.style.display = 'none';
                } else {
                    prevStoryBtn.style.display = 'block';
                    nextStoryBtn.style.display = 'block';
                }
            } else {
                prevStoryBtn.style.display = 'none';
                nextStoryBtn.style.display = 'none';
            }

            // Start progress animation for current story
            startProgressBar(window.currentStoryIndex);
        }

        // Make togglePausePlay globally accessible
        window.togglePausePlay = function() {
            const currentBar = document.getElementById(`story-progress-bar-${window.currentStoryIndex}`);
            if (!currentBar) return;
            
            // Get current story to check if it has music
            const currentStory = window.stories[window.currentStoryIndex];
            const hasMusic = currentStory && currentStory.music_data;
            
            // Check which duration we're using (with or without music)
            const currentDuration = hasMusic ? window.storyDurationWithMusic : window.storyDuration;

            if (window.isStoryAnimationPaused) {
                // Resume animation
                window.isStoryAnimationPaused = false;
                pauseIcon.classList.remove('hidden');
                playIcon.classList.add('hidden');

                // Calculate remaining time and continue from where it was paused
                const elapsedTime = Date.now() - window.storyPauseProgressData.pauseStartTime;
                const remainingTime = Math.max(0, currentDuration - elapsedTime);

                // Force reflow to apply initial state before transition
                currentBar.offsetHeight;

                // Set the transition duration to the remaining time
                currentBar.style.transition = `width ${remainingTime}ms linear`;
                currentBar.style.width = '100%';
                
                // Resume music if story has music
                if (hasMusic && window.storyMusicPlayer) {
                    try {
                        window.storyMusicPlayer.playVideo();
                        
                        // Also resume the loop interval if needed
                        if (!window.storyMusicLoopInterval) {
                            const musicData = JSON.parse(currentStory.music_data);
                            const startTimeInSeconds = parseFloat(musicData.startTime) || 0;
                            const endTimeInSeconds = startTimeInSeconds + 15;
                            
                            window.storyMusicLoopInterval = setInterval(function() {
                                try {
                                    const currentTime = window.storyMusicPlayer.getCurrentTime();
                                    if (currentTime >= endTimeInSeconds) {
                                        window.storyMusicPlayer.seekTo(startTimeInSeconds);
                                    }
                                } catch (e) {
                                    clearInterval(window.storyMusicLoopInterval);
                                }
                            }, 1000);
                        }
                    } catch (e) {
                        // Silent error handling for music resuming
                    }
                }

                // Set timeout for the remaining duration
                window.progressInterval = setTimeout(() => {
                    nextStory();
                }, remainingTime);
            } else {
                // Pause animation
                window.isStoryAnimationPaused = true;
                pauseIcon.classList.add('hidden');
                playIcon.classList.remove('hidden');

                // Store the current progress and pause time
                const computedStyle = getComputedStyle(currentBar);
                const currentWidth = parseFloat(computedStyle.width);
                const containerWidth = currentBar.parentElement.offsetWidth;
                const progressPercentage = (currentWidth / containerWidth) * 100;
                
                window.storyPauseProgressData = {
                    progressPercentage,
                    pauseStartTime: Date.now() - (progressPercentage / 100) * currentDuration
                };
                
                // Pause music if story has music
                if (hasMusic && window.storyMusicPlayer) {
                    try {
                        window.storyMusicPlayer.pauseVideo();
                        
                        // Pause the loop interval
                        if (window.storyMusicLoopInterval) {
                            clearInterval(window.storyMusicLoopInterval);
                            // Don't set to null so we know to restart it when resuming
                        }
                    } catch (e) {
                        // Silent error handling for music pausing
                    }
                }
                
                // Clear the timeout and remove the transition
                clearTimeout(window.progressInterval);
                currentBar.style.transition = 'none';
                currentBar.style.width = `${progressPercentage}%`;
            }
        };

        function closeStoryModal() {
            // First, make sure all music is completely stopped before doing anything else
            // This ensures that even if the modal is closed quickly, music won't continue playing
            window.stopAllMusicPlayback();
            
            // Now continue with normal modal closing operations
            storyModalElement.style.display = 'none';
            document.body.classList.remove('no-scroll');
            clearTimeout(window.progressInterval);
            
            // Refresh story rings after viewing
            if (window.stories && window.stories.length > 0 && window.currentStoryIndex >= window.stories.length - 1) {
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        }

        function prevStory() {
            // Clear current progress animation before moving
            clearTimeout(window.progressInterval);
            const currentBar = document.getElementById(`story-progress-bar-${window.currentStoryIndex}`);
            if (currentBar) {
                currentBar.style.transition = 'none';
                currentBar.style.width = '0%'; // Reset current bar
            }

            window.currentStoryIndex--;
            updateStory();
        }

        function nextStory() {
            // Clear current progress animation before moving
            clearTimeout(window.progressInterval);
            const currentBar = document.getElementById(`story-progress-bar-${window.currentStoryIndex}`);
            if (currentBar) {
                currentBar.style.transition = 'none';
                currentBar.style.width = '100%'; // Fill current bar before moving
            }

            window.currentStoryIndex++;
            updateStory();
        }

        // Function to load stories - make it globally accessible
        window.loadStories = function(userId = null) {
            // Reset progress bars
            const progressBarsContainer = document.getElementById('story-progress-bars');
            progressBarsContainer.innerHTML = '';

            // Fetch stories from server
            const url = userId ? `../includes/get_user_stories.php?user_id=${userId}` : '../includes/get_stories.php';
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load stories');
                    }
                    window.stories = data.stories;
                    if (window.stories && window.stories.length > 0) {
                        // Create progress bars
                        createProgressBars();
                        
                        // Show first story
                        updateStory();
                    } else {
                        closeStoryModal(); // Close modal if no stories
                    }
                })
                .catch(error => {
                    console.error('Error loading stories:', error);
                    closeStoryModal();
                });
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation button handling
            if (prevStoryBtn) {
                prevStoryBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    prevStory();
                });
            }

            if (nextStoryBtn) {
                nextStoryBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    nextStory();
                });
            }

            // Delete story modal handling
            const deleteStoryBtn = document.getElementById('delete-story');
            const deleteConfirmationModal = document.getElementById('delete-story-confirmation-modal');
            const confirmDeleteBtn = document.getElementById('confirm-delete-story-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete-story-btn');

            if (deleteStoryBtn) {
                deleteStoryBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (!window.isStoryAnimationPaused) {
                        togglePausePlay(); // Pause the story
                    }
                    if (deleteConfirmationModal) {
                        deleteConfirmationModal.style.display = 'flex';
                    }
                });
            }

            if (cancelDeleteBtn) {
                cancelDeleteBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (deleteConfirmationModal) {
                        deleteConfirmationModal.style.display = 'none';
                    }
                    if (window.isStoryAnimationPaused) {
                        togglePausePlay(); // Resume the story
                    }
                });
            }
            
            // Add event listener for confirm delete button
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Get the current story ID
                    const currentStory = window.stories[window.currentStoryIndex];
                    if (currentStory && currentStory.story_id) {
                        deleteStory(currentStory.story_id);
                    }
                });
            }

            // Close delete confirmation modal when clicking outside
            if (deleteConfirmationModal) {
                deleteConfirmationModal.addEventListener('click', function(event) {
                    if (event.target === deleteConfirmationModal) {
                        event.preventDefault();
                        event.stopPropagation();
                        deleteConfirmationModal.style.display = 'none';
                        if (window.isStoryAnimationPaused) {
                            togglePausePlay(); // Resume the story
                        }
                    }
                });
            }

            const closeStoryModalButton = document.getElementById('close-story-modal');
            if (closeStoryModalButton) {
                closeStoryModalButton.addEventListener('click', closeStoryModal);
            }

            // Only close story modal when clicking directly on the modal background
            storyModalElement.addEventListener('click', function(event) {
                if (event.target === storyModalElement) {
                    closeStoryModal();
                }
            });

            // Get the viewers and likes modal elements
            const viewersLikesModal = document.getElementById('viewers-likes-modal');
            const viewersLikesIcon = document.getElementById('viewers-likes-icon');
            const closeViewersLikesModal = document.getElementById('close-viewers-likes-modal');

            if (viewersLikesIcon && viewersLikesModal) {
                viewersLikesIcon.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (!window.isStoryAnimationPaused) {
                        togglePausePlay(); // Pause the story
                    }
                    
                    // Load actual viewers data for the current story
                    const currentStory = window.stories[window.currentStoryIndex];
                    if (currentStory) {
                        loadStoryViewers(currentStory.story_id);
                    }
                    
                    viewersLikesModal.style.display = 'flex';
                });

                if (closeViewersLikesModal) {
                    closeViewersLikesModal.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        viewersLikesModal.style.display = 'none';
                        if (window.isStoryAnimationPaused) {
                            togglePausePlay(); // Resume the story
                        }
                    });
                }

                // Close viewers modal when clicking outside
                viewersLikesModal.addEventListener('click', (event) => {
                    if (event.target === viewersLikesModal) {
                        event.preventDefault();
                        event.stopPropagation();
                        viewersLikesModal.style.display = 'none';
                        if (window.isStoryAnimationPaused) {
                            togglePausePlay(); // Resume the story
                        }
                    }
                });
            }

            // Add event listener for pause/play button
            if (pauseStoryBtn) {
                pauseStoryBtn.addEventListener('click', (event) => {
                    event.stopPropagation(); // Prevent modal closing
                    togglePausePlay();
                });
            }
            
            // Add event listener for volume toggle button
            const volumeToggleBtn = document.getElementById('toggle-volume');
            const volumeOnIcon = document.getElementById('volume-on-icon');
            const volumeOffIcon = document.getElementById('volume-off-icon');
            
            // Initialize a global variable to track the mute state
            window.isMuted = false;
            
            if (volumeToggleBtn) {
                volumeToggleBtn.addEventListener('click', (event) => {
                    event.stopPropagation(); // Prevent modal closing
                    
                    // Toggle mute state
                    window.isMuted = !window.isMuted;
                    
                    // Update icon visibility
                    if (window.isMuted) {
                        volumeOnIcon.classList.add('hidden');
                        volumeOffIcon.classList.remove('hidden');
                    } else {
                        volumeOffIcon.classList.add('hidden');
                        volumeOnIcon.classList.remove('hidden');
                    }
                    
                    // Apply mute/unmute to the YouTube player if it exists
                    if (window.storyMusicPlayer && typeof window.storyMusicPlayer === 'object') {
                        try {
                            if (window.isMuted) {
                                if (window.storyMusicPlayer.mute && typeof window.storyMusicPlayer.mute === 'function') {
                                    window.storyMusicPlayer.mute();
                                }
                            } else {
                                if (window.storyMusicPlayer.unMute && typeof window.storyMusicPlayer.unMute === 'function') {
                                    window.storyMusicPlayer.unMute();
                                    // Fade in volume over 1 second
                                    fadeInVolume();
                                }
                            }
                        } catch (e) {
                            // Silent error handling
                        }
                    }
                });
            }
            
            // Add event listener for the heart button (like story)
            const heartButton = document.getElementById('story-heart-button');
            const heartLiked = document.getElementById('story-heart-liked');
            
            if (heartButton) {
                heartButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const currentStory = window.stories[window.currentStoryIndex];
                    if (currentStory) {
                        likeStory(currentStory.story_id);
                    }
                });
            }
            
            // Add event listener for the liked heart button (unlike story)
            if (heartLiked) {
                heartLiked.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const currentStory = window.stories[window.currentStoryIndex];
                    if (currentStory) {
                        likeStory(currentStory.story_id);
                    }
                });
            }
        });
        
        // Function to track when a user views a story
        function trackStoryView(storyId) {
            if (!storyId) {
                return;
            }
            
            const formData = new FormData();
            formData.append('story_id', storyId);
            
            // Use the direct URL for better tracking success
            const fetchUrl = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
                ? '/includes/track_story_view.php'
                : '/includes/track_story_view.php';
                
            fetch(fetchUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Create a fallback iframe to ensure the view is recorded
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = `/includes/debug_story_view.php?story_id=${storyId}`;
                    document.body.appendChild(iframe);
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 2000);
                }
            })
            .catch(() => {
                // On error, try the debug version as a fallback
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = `/includes/debug_story_view.php?story_id=${storyId}`;
                document.body.appendChild(iframe);
                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 2000);
            });
        }
        
        // Function to delete a story
        function deleteStory(storyId) {
            if (!storyId) return;
            
            const formData = new FormData();
            formData.append('story_id', storyId);
            
            fetch('../utils/delete_story.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close the delete confirmation modal
                    const deleteConfirmationModal = document.getElementById('delete-story-confirmation-modal');
                    if (deleteConfirmationModal) {
                        deleteConfirmationModal.style.display = 'none';
                    }
                    
                    // If there are more stories, show the next one
                    if (window.stories.length > 1) {
                        // Remove the deleted story from the array
                        window.stories.splice(window.currentStoryIndex, 1);
                        
                        // If we deleted the last story, go to the previous one
                        if (window.currentStoryIndex >= window.stories.length) {
                            window.currentStoryIndex = window.stories.length - 1;
                        }
                        
                        // Update progress bars and show the next/previous story
                        createProgressBars();
                        updateStory();
                    } else {
                        // If it was the only story, close the modal
                        closeStoryModal();
                    }
                    
                    // Show success toast notification
                    if (typeof showToast === 'function') {
                        showToast('Story deleted successfully!', 'success');
                    }
                } else {
                    // Show error toast notification
                    if (typeof showToast === 'function') {
                        showToast('Error deleting story: ' + (data.message || 'Unknown error'));
                    }
                    
                    // Resume the story animation if it was paused
                    if (window.isStoryAnimationPaused) {
                        togglePausePlay();
                    }
                }
            })
            .catch(error => {
                console.error('Error deleting story:', error);
                if (typeof showToast === 'function') {
                    showToast('Error deleting story. Please try again.');
                }
                
                // Resume the story animation if it was paused
                if (window.isStoryAnimationPaused) {
                    togglePausePlay();
                }
            });
        }
        
        // Function to check if the current user has liked the story
        function checkStoryLikeStatus(storyId) {
            if (!storyId) return;
            
            const heartButton = document.getElementById('story-heart-button');
            const heartLiked = document.getElementById('story-heart-liked');
            if (!heartButton || !heartLiked) return;
            
            // Fix the URL to use the correct path
            const url = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
                ? '/includes/check_story_like.php'
                : '/includes/check_story_like.php';
            
            fetch(`${url}?story_id=${storyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // If story is liked, show the filled heart
                    if (data.liked) {
                        heartLiked.style.display = 'block';
                        heartButton.style.display = 'none';
                    } else {
                        // If not liked, show the outline heart
                        heartLiked.style.display = 'none';
                        heartButton.style.display = 'block';
                    }
                } else {
                    // On error, default to showing the outline heart
                    heartLiked.style.display = 'none';
                    heartButton.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error checking story like status:', error);
                // On error, default to showing the outline heart
                heartLiked.style.display = 'none';
                heartButton.style.display = 'block';
            });
        }
        
        // Function to update the viewer count
        function updateViewerCount(storyId) {
            if (!storyId) return;
            
            fetch(`../includes/get_story_viewers.php?story_id=${storyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const viewerCount = document.getElementById('viewer-count');
                    if (viewerCount) {
                        viewerCount.textContent = data.viewers.length;
                    }
                }
            })
            .catch(error => {
                console.error('Error updating viewer count:', error);
            });
        }
        
        // Function to like or unlike a story
        function likeStory(storyId) {
            if (!storyId) return;
            
            // Get heart button elements
            const heartButton = document.getElementById('story-heart-button');
            const heartLiked = document.getElementById('story-heart-liked');
            
            // Check if story is currently liked (if liked heart is visible)
            const currentlyLiked = heartLiked.style.display === 'block';
            
            // Toggle visibility of heart icons
            if (!currentlyLiked) {
                // Show liked heart, hide unlike heart
                heartButton.style.display = 'none';
                heartLiked.style.display = 'block';
            } else {
                // Show unlike heart, hide liked heart
                heartButton.style.display = 'block';
                heartLiked.style.display = 'none';
            }
            
            // Send like request to server
            const formData = new FormData();
            formData.append('story_id', storyId);
            
            fetch('../includes/like_story.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error liking story:', data.message);
                    // Revert visual change if server request failed
                    if (currentlyLiked) {
                        // Revert to showing liked heart
                        heartButton.style.display = 'none';
                        heartLiked.style.display = 'block';
                    } else {
                        // Revert to showing outline heart
                        heartButton.style.display = 'block';
                        heartLiked.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error liking story:', error);
                // Revert the UI state on error
                if (currentlyLiked) {
                    heartButton.style.display = 'none';
                    heartLiked.style.display = 'block';
                } else {
                    heartButton.style.display = 'block';
                    heartLiked.style.display = 'none';
                }
            });
        }
        
        // Function to load story viewers when the modal is opened
        function loadStoryViewers(storyId) {
            if (!storyId) return;
            
            const viewersContainer = document.getElementById('story-viewers-container');
            if (!viewersContainer) return;
            
            // Show loading indicator
            viewersContainer.innerHTML = `
                <div class="flex items-center justify-center py-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-gray-900 dark:border-white"></div>
                    <span class="ml-3 text-gray-700 dark:text-gray-300">Loading viewers...</span>
                </div>
            `;
            
            fetch(`../includes/get_story_viewers.php?story_id=${storyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.viewers.length > 0) {
                    // Clear loading indicator
                    viewersContainer.innerHTML = '';
                    
                    // Add each viewer to the list
                    data.viewers.forEach(viewer => {
                        const viewerItem = document.createElement('div');
                        viewerItem.className = 'flex items-center justify-between';
                        
                        // Format profile picture URL
                        let profilePic = viewer.profile_picture || '../images/profile_placeholder.webp';
                        if (!profilePic.startsWith('../') && !profilePic.startsWith('http')) {
                            profilePic = '../' + profilePic;
                        }
                        
                        // Create the HTML for the viewer item
                        viewerItem.innerHTML = `
                            <div class="flex items-center space-x-3">
                                <a href="../profile.php?username=${encodeURIComponent(viewer.username)}" class="relative flex shrink-0 overflow-hidden rounded-full h-10 w-10 bg-gray-300 hover:opacity-90 transition-opacity">
                                    <img src="${profilePic}" class="absolute inset-0 w-full h-full object-cover rounded-full" alt="${viewer.username}">
                                </a>
                                <a href="../profile.php?username=${encodeURIComponent(viewer.username)}" class="font-semibold text-neutral-900 dark:text-white text-base hover:underline">${viewer.username}</a>
                                ${viewer.verifybadge == 1 ? `<svg aria-label="Verified" class="flex-shrink-0 text-blue-500" fill="currentColor" height="12" width="12" viewBox="0 0 40 40"><title>Verified</title><path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path></svg>` : ''}
                            </div>
                            ${viewer.has_liked ? '<span class="text-red-500 text-xl">❤️</span>' : ''}
                        `;
                        
                        viewersContainer.appendChild(viewerItem);
                    });
                } else {
                    // No viewers yet
                    viewersContainer.innerHTML = `
                        <div class="text-center py-6 text-neutral-500 dark:text-neutral-400">
                            <p>Nimeni nu a văzut încă această poveste.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading story viewers:', error);
                viewersContainer.innerHTML = `
                    <div class="text-center py-6 text-neutral-500 dark:text-neutral-400">
                        <p>A apărut o eroare la încărcarea vizualizărilor.</p>
                    </div>
                `;
            });
        }

        function onPlayerReady(event) {
            // Player is ready
            console.log('Music player ready');
        }

        function onPlayerStateChange(event) {
            // Handle player state changes
            if (event.data === YT.PlayerState.ENDED) {
                // Restart music when it ends
                event.target.playVideo();
            }
        }

        function toggleMusic() {
            if (!musicPlayer) return;
            
            const playIcon = document.getElementById('play-music-icon');
            const pauseIcon = document.getElementById('pause-music-icon');
            
            if (isMusicPlaying) {
                musicPlayer.pauseVideo();
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
            } else {
                musicPlayer.playVideo();
                playIcon.classList.add('hidden');
                pauseIcon.classList.remove('hidden');
            }
            
            isMusicPlaying = !isMusicPlaying;
        }

        // Add event listener for music toggle button
        document.getElementById('toggle-music').addEventListener('click', toggleMusic);
    }
    
    // Add YouTube API loading function
    function loadYouTubeAPI() {
        if (!document.getElementById('youtube-api')) {
            const tag = document.createElement('script');
            tag.id = 'youtube-api';
            tag.src = 'https://www.youtube.com/iframe_api';
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
    }
    
    // Function to fade in the volume over 1 second
    function fadeInVolume(startVolume = 0, targetVolume = 70, duration = 1000) {
        // Clear any existing volume fade interval
        if (window.volumeFadeInterval) {
            clearInterval(window.volumeFadeInterval);
        }
        
        // Make sure we have a valid player with the setVolume method
        if (!window.storyMusicPlayer) {
            return;
        }
        
        try {
            // Check if player is ready and has setVolume method
            if (typeof window.storyMusicPlayer !== 'object' || 
                typeof window.storyMusicPlayer.setVolume !== 'function' || 
                typeof window.storyMusicPlayer.getPlayerState !== 'function') {
                return;
            }
            
            // Try to get the player state to ensure it's fully initialized
            const playerState = window.storyMusicPlayer.getPlayerState();
            if (playerState === undefined) {
                return; // Player not fully initialized
            }
            
            // Start with volume at 0
            window.storyMusicPlayer.setVolume(startVolume);
            
            // Calculate steps for smooth fade
            const steps = 20; // 20 steps for smooth transition
            const stepDuration = duration / steps;
            const volumeStep = (targetVolume - startVolume) / steps;
            
            let currentStep = 0;
            let currentVolume = startVolume;
            
            // Create the interval for fading
            window.volumeFadeInterval = setInterval(() => {
                // Check again if player is still valid before each step
                if (!window.storyMusicPlayer || typeof window.storyMusicPlayer.setVolume !== 'function') {
                    clearInterval(window.volumeFadeInterval);
                    window.volumeFadeInterval = null;
                    return;
                }
                
                try {
                    currentStep++;
                    currentVolume += volumeStep;
                    
                    if (currentStep >= steps) {
                        window.storyMusicPlayer.setVolume(targetVolume); // Ensure we reach exactly the target
                        clearInterval(window.volumeFadeInterval);
                        window.volumeFadeInterval = null;
                        return;
                    }
                    
                    window.storyMusicPlayer.setVolume(currentVolume);
                } catch (e) {
                    // If any error occurs during the fade, stop the interval
                    clearInterval(window.volumeFadeInterval);
                    window.volumeFadeInterval = null;
                }
            }, stepDuration);
        } catch (e) {
            // Silent error handling if any check fails
        }
    }
    
    // Initialize YouTube player with autoplay functionality
    window.initializeYouTubePlayer = function(videoId, startTime) {
        // Convert startTime to a number and ensure it's valid
        const startTimeInSeconds = parseFloat(startTime) || 0;
        
        // Clean up existing player if any
        if (window.storyMusicPlayer) {
            try {
                clearInterval(window.storyMusicLoopInterval);
                window.storyMusicPlayer.destroy();
                window.storyMusicPlayer = null;
            } catch (e) {
                console.error('Error destroying previous player:', e);
            }
        }
        
        // Create new player
        window.storyMusicPlayer = new YT.Player('youtube-player', {
            videoId: videoId,
            playerVars: {
                autoplay: 1,        // Auto-play when ready
                controls: 0,        // Hide controls
                disablekb: 1,       // Disable keyboard controls
                enablejsapi: 1,     // Enable JavaScript API
                fs: 0,              // Disable fullscreen
                modestbranding: 1,  // Minimal branding
                rel: 0,             // Don't show related videos
                start: Math.floor(startTimeInSeconds),  // Start at specified time, convert to integer
                loop: 1,            // Enable looping
                playsinline: 1,     // Play inline on mobile
                mute: 0             // Not muted
            },
            events: {
                'onReady': function(event) {
                    try {
                        // Make sure player is valid before attempting to control volume
                        if (event && event.target && typeof event.target.setVolume === 'function') {
                            // Start with volume at 0 for fade-in effect
                            event.target.setVolume(0);  
                        }
                        
                        // Start playing the video
                        if (event && event.target && typeof event.target.playVideo === 'function') {
                            event.target.playVideo();
                        }
                        
                        // Apply mute state if needed
                        if (window.isMuted) {
                            if (event && event.target && typeof event.target.mute === 'function') {
                                event.target.mute();
                            }
                        } else {
                            // Apply fade-in effect with additional safety checks
                            setTimeout(() => {
                                // Add a small delay to ensure player is fully initialized
                                fadeInVolume(0, 70, 1000);
                            }, 100);
                        }
                    } catch (e) {
                        // Silent error handling for player initialization
                    }
                    
                    // Create a 15-second loop
                    const startTimeInSeconds = parseFloat(startTime) || 0;
                    const endTimeInSeconds = startTimeInSeconds + 15;
                    
                    // Set up loop monitoring
                    window.storyMusicLoopInterval = setInterval(function() {
                        try {
                            const currentTime = event.target.getCurrentTime();
                            if (currentTime >= endTimeInSeconds) {
                                // Reset to start time when reaching end of segment
                                event.target.seekTo(startTimeInSeconds);
                            }
                        } catch (e) {
                            clearInterval(window.storyMusicLoopInterval);
                        }
                    }, 1000); // Check every second
                },
                'onStateChange': function(event) {
                    // If video ends, restart it (for added loop reliability)
                    if (event.data === YT.PlayerState.ENDED) {
                        const startTimeInSeconds = parseInt(startTime, 10) || 0;
                        event.target.seekTo(startTimeInSeconds);
                        event.target.playVideo();
                    }
                },
                'onError': function(event) {
                    // Silent handling of YouTube player errors
                }
            }
        });
    };
    
    // Override the window.onYouTubeIframeAPIReady callback
    window.onYouTubeIframeAPIReady = function() {
        // Check if there's a pending video to play
        if (window.pendingMusicVideo) {
            initializeYouTubePlayer(window.pendingMusicVideo.videoId, window.pendingMusicVideo.startTime);
            window.pendingMusicVideo = null;
        }
    };
    
    // Load YouTube API when the document is ready
    document.addEventListener('DOMContentLoaded', loadYouTubeAPI);
})();

// Story Report Modal Functions
function closeStoryReportModal() {
    const modal = document.getElementById('storyReportModal');
    const form = document.getElementById('storyReportForm');
    const charCount = document.getElementById('storyCharCount');
    
    if (modal) modal.classList.add('hidden');
    if (form) form.reset();
    if (charCount) charCount.textContent = '0';
}

function openStoryReportModal(storyId) {
    const storyIdInput = document.getElementById('reportStoryId');
    const modal = document.getElementById('storyReportModal');
    
    if (storyIdInput) storyIdInput.value = storyId;
    if (modal) modal.classList.remove('hidden');
}

// Initialize story report modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const storyReportDescription = document.getElementById('storyReportDescription');
    const storyReportForm = document.getElementById('storyReportForm');
    const reportStoryBtn = document.getElementById('report-story');
    const storyCharCount = document.getElementById('storyCharCount');
    
    if (storyReportDescription) {
        storyReportDescription.addEventListener('input', function() {
            if (storyCharCount) {
                storyCharCount.textContent = this.value.length;
            }
        });
    }
    
    if (reportStoryBtn) {
        reportStoryBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (!window.isStoryAnimationPaused) {
                togglePausePlay(); // Pause the story
            }
            const currentStory = window.stories[window.currentStoryIndex];
            if (currentStory) {
                openStoryReportModal(currentStory.story_id);
            }
        });
    }
    
    if (storyReportForm) {
        storyReportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const storyId = document.getElementById('reportStoryId')?.value;
            const description = document.getElementById('storyReportDescription')?.value.trim();
            
            if (!description) {
                alert('Please provide a reason for your report.');
                return;
            }

            // Disable the form while submitting
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) submitButton.disabled = true;

            // Close modal immediately
            closeStoryReportModal();

            // Send the report to the server
            fetch('utils/report_story.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `story_id=${storyId}&description=${encodeURIComponent(description)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Story reported successfully', 'success');
                } else {
                    showToast(data.message || 'Error reporting story', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error reporting story. Please try again.', 'error');
            })
            .finally(() => {
                if (submitButton) submitButton.disabled = false;
                if (window.isStoryAnimationPaused) {
                    togglePausePlay(); // Resume the story
                }
            });
        });
    }
});
</script>