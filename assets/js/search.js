// Search functionality for the navbar search sidebar
const RECENT_SEARCHES_KEY = 'recent_searches';
const MAX_RECENT_SEARCHES = 5;

// Initialize the search functionality
document.addEventListener('DOMContentLoaded', () => {
    initSearchFunctionality();
});

function initSearchFunctionality() {
    const searchInput = document.querySelector('[data-search-sidebar] input');
    const searchResultsContainer = document.querySelector('[data-search-results]');
    const recentSearchesContainer = document.querySelector('[data-recent-searches-container]');
    const noRecentSearchesMessage = document.querySelector('[data-no-recent-searches]');
    const clearAllButton = document.querySelector('[data-clear-all-searches]');

    if (!searchInput || !searchResultsContainer || !recentSearchesContainer) {
        console.error('Search elements not found');
        return;
    }

    // Display recent searches on load
    displayRecentSearches();

    // Clear all recent searches
    if (clearAllButton) {
        clearAllButton.addEventListener('click', (e) => {
            e.preventDefault();
            localStorage.removeItem(RECENT_SEARCHES_KEY);
            displayRecentSearches();
        });
    }

    // Handle search input
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Clear previous results if query is empty
        if (!query) {
            searchResultsContainer.innerHTML = '';
            searchResultsContainer.style.display = 'none';
            recentSearchesContainer.style.display = 'block';
            return;
        }

        // Show loading indicator
        searchResultsContainer.innerHTML = '<div class="p-4 text-center">Se încarcă...</div>';
        searchResultsContainer.style.display = 'block';
        recentSearchesContainer.style.display = 'none';

        // Wait a bit before sending the request to avoid too many requests
        searchTimeout = setTimeout(() => {
            fetchSearchResults(query);
        }, 300);
    });

    // Handle click on search input to show recent searches
    searchInput.addEventListener('focus', () => {
        if (!searchInput.value.trim()) {
            searchResultsContainer.style.display = 'none';
            recentSearchesContainer.style.display = 'block';
        }
    });
}

// Fetch search results from the API
async function fetchSearchResults(query) {
    const searchResultsContainer = document.querySelector('[data-search-results]');
    
    try {
        const response = await fetch(`/api/search-users.php?q=${encodeURIComponent(query)}`);
        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.length === 0) {
            searchResultsContainer.innerHTML = '<div class="p-4 text-center text-neutral-500">Niciun rezultat găsit</div>';
            return;
        }
        
        displaySearchResults(data, query);
    } catch (error) {
        console.error('Error fetching search results:', error);
        searchResultsContainer.innerHTML = '<div class="p-4 text-center text-red-500">Eroare la căutare</div>';
    }
}

// Display search results in the sidebar
function displaySearchResults(users, query) {
    const searchResultsContainer = document.querySelector('[data-search-results]');
    
    searchResultsContainer.innerHTML = '';
    
    const resultsHeader = document.createElement('div');
    resultsHeader.className = 'mb-4';
    resultsHeader.innerHTML = `<h3 class="font-semibold">Rezultate pentru "${escapeHtml(query)}"</h3>`;
    searchResultsContainer.appendChild(resultsHeader);
    
    const resultsList = document.createElement('div');
    resultsList.className = 'space-y-4';
    
    users.forEach(user => {
        const userElement = createUserElement(user, () => {
            addToRecentSearches(user);
            window.location.href = `/user.php?username=${user.username}`;
        });
        
        resultsList.appendChild(userElement);
    });
    
    searchResultsContainer.appendChild(resultsList);
}

// Get recent searches from localStorage
function getRecentSearches() {
    const recentSearches = localStorage.getItem(RECENT_SEARCHES_KEY);
    return recentSearches ? JSON.parse(recentSearches) : [];
}

// Add a user to recent searches
function addToRecentSearches(user) {
    const recentSearches = getRecentSearches();
    
    // Check if user already exists in recent searches
    const existingIndex = recentSearches.findIndex(item => item.username === user.username);
    
    // Remove existing entry if found
    if (existingIndex !== -1) {
        recentSearches.splice(existingIndex, 1);
    }
    
    // Add user to the beginning of the array
    recentSearches.unshift(user);
    
    // Limit the number of recent searches
    if (recentSearches.length > MAX_RECENT_SEARCHES) {
        recentSearches.pop();
    }
    
    // Save to localStorage
    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(recentSearches));
    
    // Update the display
    displayRecentSearches();
}

// Remove a user from recent searches
function removeFromRecentSearches(username) {
    const recentSearches = getRecentSearches();
    
    // Filter out the user
    const updatedSearches = recentSearches.filter(user => user.username !== username);
    
    // Save to localStorage
    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(updatedSearches));
    
    // Update the display
    displayRecentSearches();
}

// Display recent searches in the sidebar
function displayRecentSearches() {
    const recentSearchesContainer = document.querySelector('[data-recent-searches-container]');
    const recentSearchesList = document.querySelector('[data-recent-searches-list]');
    const noRecentSearchesMessage = document.querySelector('[data-no-recent-searches]');
    const clearAllButton = document.querySelector('[data-clear-all-searches]');
    
    if (!recentSearchesContainer || !recentSearchesList) {
        return;
    }
    
    const recentSearches = getRecentSearches();
    
    // Show/hide no recent searches message
    if (noRecentSearchesMessage) {
        noRecentSearchesMessage.style.display = recentSearches.length === 0 ? 'block' : 'none';
    }
    
    // Show/hide clear all button
    if (clearAllButton) {
        clearAllButton.style.display = recentSearches.length === 0 ? 'none' : 'inline-block';
    }
    
    // Clear the list
    recentSearchesList.innerHTML = '';
    
    // Add each recent search to the list
    recentSearches.forEach(user => {
        const userElement = createUserElement(user, () => {
            window.location.href = `/user.php?username=${user.username}`;
        }, true);
        
        recentSearchesList.appendChild(userElement);
    });
}

// Create a user element for search results or recent searches
function createUserElement(user, onClick, isRecent = false) {
    const element = document.createElement('div');
    element.className = 'flex items-center justify-between';
    
    const userInfo = document.createElement('div');
    userInfo.className = 'flex items-center gap-3 cursor-pointer flex-1';
    userInfo.addEventListener('click', onClick);
    
    userInfo.innerHTML = `
        <div class="relative flex-shrink-0">
            <img alt="${escapeHtml(user.username)}" loading="lazy" decoding="async" class="h-12 w-12 rounded-full object-cover" src="${user.profile_picture || './images/profile_placeholder.webp'}">
        </div>
        <div class="min-w-0">
            <div class="flex items-center gap-1">
                <p class="font-semibold truncate">${escapeHtml(user.username)}</p>
                ${user.is_verified ? `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500 flex-shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>` : ''}
            </div>
            <p class="text-sm text-neutral-600 dark:text-neutral-400 truncate">${escapeHtml(user.full_name)}</p>
        </div>
    `;
    
    element.appendChild(userInfo);
    
    // Add remove button for recent searches
    if (isRecent) {
        const removeButton = document.createElement('button');
        removeButton.className = 'p-2 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-full';
        removeButton.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x w-4 h-4">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        `;
        removeButton.addEventListener('click', (e) => {
            e.stopPropagation();
            removeFromRecentSearches(user.username);
        });
        
        element.appendChild(removeButton);
    }
    
    return element;
}

// Escape HTML special characters to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
