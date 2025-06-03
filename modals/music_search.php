<!-- Music Search Modal -->
<div id="musicSearchModal" class="fixed inset-0 z-50 flex items-center justify-center" style="display: none; background-color: rgba(0, 0, 0, 0.7);">
    <div class="relative w-full max-w-2xl bg-white dark:bg-neutral-900 rounded-lg shadow-xl">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-4 border-b border-neutral-200 dark:border-neutral-800">
            <h3 class="text-lg font-semibold">Caută muzică</h3>
            <button id="closeMusicSearchModal" class="text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Search Input -->
        <div class="p-4">
            <div class="relative">
                <input type="text" id="musicSearchInput" placeholder="Caută melodii sau artiști..." 
                    class="w-full px-4 py-2 pl-10 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                    class="absolute left-3 top-2.5 w-4 h-4 text-neutral-500">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
            </div>
        </div>

        <!-- Search Results -->
        <div class="p-4 border-t border-neutral-200 dark:border-neutral-800">
            <div id="musicSearchResults" class="space-y-2 max-h-[400px] overflow-y-auto">
                <!-- Results will be populated here -->
            </div>
        </div>

        <!-- Loading State -->
        <div id="musicSearchLoading" class="hidden p-4 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
        </div>

        <!-- Error State -->
        <div id="musicSearchError" class="hidden p-4 text-center text-red-500">
            A apărut o eroare. Vă rugăm să încercați din nou.
        </div>
    </div>
</div>

<script>
// Music search functionality
let selectedMusic = null;

function openMusicSearchModal() {
    document.getElementById('musicSearchModal').style.display = 'flex';
    document.getElementById('musicSearchInput').focus();
}

function closeMusicSearchModal() {
    document.getElementById('musicSearchModal').style.display = 'none';
    document.getElementById('musicSearchInput').value = '';
    document.getElementById('musicSearchResults').innerHTML = '';
}

function displaySelectedMusic(music) {
    const selectedMusicDiv = document.getElementById('selectedMusic');
    const thumbnail = document.getElementById('selectedMusicThumbnail');
    const title = document.getElementById('selectedMusicTitle');
    const artist = document.getElementById('selectedMusicArtist');

    selectedMusicDiv.classList.remove('hidden');
    thumbnail.src = music.thumbnail;
    title.textContent = music.title;
    artist.textContent = music.artist;
}

function removeSelectedMusic() {
    const selectedMusicDiv = document.getElementById('selectedMusic');
    selectedMusicDiv.classList.add('hidden');
    selectedMusic = null;
}

async function searchMusic(query) {
    if (!query.trim()) return;

    const loadingDiv = document.getElementById('musicSearchLoading');
    const errorDiv = document.getElementById('musicSearchError');
    const resultsDiv = document.getElementById('musicSearchResults');

    loadingDiv.classList.remove('hidden');
    errorDiv.classList.add('hidden');
    resultsDiv.innerHTML = '';

    try {
        const response = await fetch(`/api/search_music.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data.success) {
            resultsDiv.innerHTML = data.results.map(music => `
                <div class="flex items-center justify-between p-3 hover:bg-neutral-50 dark:hover:bg-neutral-800 rounded-lg cursor-pointer"
                     onclick="selectMusic(${JSON.stringify(music).replace(/"/g, '&quot;')})">
                    <div class="flex items-center space-x-3">
                        <img src="${music.thumbnail}" alt="${music.title}" class="w-12 h-12 rounded object-cover">
                        <div>
                            <p class="text-sm font-medium">${music.title}</p>
                            <p class="text-xs text-neutral-500">${music.artist}</p>
                        </div>
                    </div>
                    <button class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <path d="M5 12h14"></path>
                            <path d="m12 5 7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            `).join('');
        } else {
            throw new Error(data.message || 'Failed to search music');
        }
    } catch (error) {
        console.error('Error searching music:', error);
        errorDiv.classList.remove('hidden');
        errorDiv.textContent = error.message || 'A apărut o eroare. Vă rugăm să încercați din nou.';
    } finally {
        loadingDiv.classList.add('hidden');
    }
}

function selectMusic(music) {
    selectedMusic = music;
    displaySelectedMusic(music);
    closeMusicSearchModal();
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    const searchMusicBtn = document.getElementById('searchMusicBtn');
    const closeMusicSearchModalBtn = document.getElementById('closeMusicSearchModal');
    const musicSearchInput = document.getElementById('musicSearchInput');
    const removeMusicBtn = document.getElementById('removeMusicBtn');

    if (searchMusicBtn) {
        searchMusicBtn.addEventListener('click', openMusicSearchModal);
    }

    if (closeMusicSearchModalBtn) {
        closeMusicSearchModalBtn.addEventListener('click', closeMusicSearchModal);
    }

    if (musicSearchInput) {
        let searchTimeout;
        musicSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchMusic(this.value);
            }, 500);
        });
    }

    if (removeMusicBtn) {
        removeMusicBtn.addEventListener('click', removeSelectedMusic);
    }

    // Close modal when clicking outside
    document.getElementById('musicSearchModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeMusicSearchModal();
        }
    });
});
</script> 