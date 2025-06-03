// Explicitly make these variables global by attaching them to window
window.selectedUsers = new Set();
window.selectedUsersData = new Map(); // Store user data for selected users
let currentSearchTimeout = null; // This can remain local

function handleTaggingInput(event) {
    const input = event.target;
    const dropdown = document.getElementById('userSearchDropdown');
    const resultsContainer = document.getElementById('userSearchResults');
    
    if (!dropdown || !resultsContainer) return;
    
    // Get the current word being typed
    const cursorPosition = input.selectionStart;
    const text = input.value;
    const lastAtIndex = text.lastIndexOf('@', cursorPosition);
    
    if (lastAtIndex !== -1) {
        const searchTerm = text.slice(lastAtIndex + 1, cursorPosition).trim();
        
        if (searchTerm.length > 0) {
            // Clear previous timeout
            if (currentSearchTimeout) {
                clearTimeout(currentSearchTimeout);
            }
            
            // Show loading state
            resultsContainer.innerHTML = '<div class="text-sm text-neutral-500 dark:text-neutral-400 px-2 py-1">Searching users...</div>';
            dropdown.classList.remove('hidden');
            
            // Debounce search
            currentSearchTimeout = setTimeout(() => {
                searchUsers(searchTerm);
            }, 300);
        } else {
            dropdown.classList.add('hidden');
        }
    } else {
        dropdown.classList.add('hidden');
    }
}

function searchUsers(searchTerm) {
    const dropdown = document.getElementById('userSearchDropdown');
    const resultsContainer = document.getElementById('userSearchResults');
    
    if (!dropdown || !resultsContainer) return;
    
    // Make an AJAX call to your backend to search users
    fetch(`/api/search-users.php?q=${encodeURIComponent(searchTerm)}`, {
        credentials: 'same-origin' // Include cookies in the request
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(users => {
            // Filter out already selected users
            const filteredUsers = users.filter(user => !window.selectedUsers.has(user.username));
            
            if (filteredUsers.length === 0) {
                resultsContainer.innerHTML = '<div class="text-sm text-neutral-500 dark:text-neutral-400 px-2 py-1">No users found</div>';
                return;
            }
            
            resultsContainer.innerHTML = filteredUsers.map(user => `
                <div class="flex items-center px-2 py-1.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 rounded-md cursor-pointer transition-colors" 
                     onclick="selectUser('${user.username}', '${user.full_name}', '${user.profile_picture}', ${user.is_verified})">
                    <div class="w-8 h-8 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center mr-2">
                        ${user.profile_picture ? 
                            `<img src="${user.profile_picture}" alt="${user.username}" class="w-8 h-8 rounded-full object-cover">` :
                            `<span class="text-sm font-medium">${user.username.charAt(0).toUpperCase()}</span>`
                        }
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">${user.username}</span>
                        <span class="text-xs text-neutral-500 dark:text-neutral-400">${user.full_name}</span>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            resultsContainer.innerHTML = '<div class="text-sm text-red-500 px-2 py-1">Error searching users</div>';
        });
}

function selectUser(username, fullName, profilePicture, isVerified) {
    const input = document.getElementById('postTaggingInput');
    const dropdown = document.getElementById('userSearchDropdown');
    const selectedUsersContainer = document.getElementById('selectedUsersContainer');
    
    if (!input || !dropdown || !selectedUsersContainer) {
        return;
    }
    
    // Check if maximum number of tags (50) has been reached
    if (window.selectedUsers.size >= 50) {
        showToast('Maximum number of tags (50) reached');
        return;
    }
    
    // Add user to selected users set and store their data
    window.selectedUsers.add(username);
    window.selectedUsersData.set(username, {
        username,
        fullName,
        profilePicture,
        isVerified
    });
    
    // Dispatch a custom event so other scripts can react to user selection
    document.dispatchEvent(new CustomEvent('userTagged', { 
        detail: { username, fullName, profilePicture, isVerified } 
    }));
    
    // Replace the @search with @username
    const text = input.value;
    const cursorPosition = input.selectionStart;
    const lastAtIndex = text.lastIndexOf('@', cursorPosition);
    
    if (lastAtIndex !== -1) {
        const newText = text.slice(0, lastAtIndex) + `@${username} ` + text.slice(cursorPosition);
        input.value = newText;
        
        // Move cursor after the inserted username
        const newCursorPosition = lastAtIndex + username.length + 2; // +2 for @ and space
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    }
    
    // Update selected users display
    updateSelectedUsersDisplay();
    
    // Hide dropdown
    dropdown.classList.add('hidden');
}

function removeSelectedUser(username) {
    window.selectedUsers.delete(username);
    window.selectedUsersData.delete(username);
    updateSelectedUsersDisplay();
}

function updateSelectedUsersDisplay() {
    const selectedUsersContainer = document.getElementById('selectedUsersContainer');
    if (!selectedUsersContainer) return;
    
    selectedUsersContainer.innerHTML = Array.from(window.selectedUsersData.values()).map(user => `
        <div class="flex items-center gap-2 bg-neutral-100 dark:bg-neutral-800 rounded-full px-3 py-1.5">
            <div class="w-6 h-6 rounded-full bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center">
                ${user.profilePicture ? 
                    `<img src="${user.profilePicture}" alt="${user.username}" class="w-6 h-6 rounded-full object-cover">` :
                    `<span class="text-xs font-medium">${user.username.charAt(0).toUpperCase()}</span>`
                }
            </div>
            <div class="flex items-center gap-1">
                <span class="text-sm font-medium">${user.username}</span>
                ${user.isVerified ? `
                    <div class="relative inline-block h-3.5 w-3.5">
                        <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 13px; height: 16px;">
                            <title>Verified</title>
                            <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                        </svg>
                    </div>
                ` : ''}
            </div>
            <button onclick="removeSelectedUser('${user.username}')" class="ml-1 text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
    `).join('');
}

// Close dropdown when clicking outside
document.addEventListener('click', (event) => {
    const dropdown = document.getElementById('userSearchDropdown');
    const input = document.getElementById('postTaggingInput');
    
    if (!dropdown || !input) return;
    
    if (!input.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
}); 