// Global initialization file to ensure essential variables exist
// This file should be loaded BEFORE any other JS files that use these variables

// Initialize essential global variables
window.selectedUsers = window.selectedUsers || new Set();
window.selectedUsersData = window.selectedUsersData || new Map();

// Create placeholder functions if they don't exist
window.updateSelectedUsersDisplay = window.updateSelectedUsersDisplay || function() {
    // Silent fallback
};

// Create a proper tagging fallback
window.getTagsFromSelectedUsers = function() {
    try {
        if (window.selectedUsers instanceof Set && window.selectedUsers.size > 0) {
            return Array.from(window.selectedUsers).map(user => '@' + user).join(' ');
        }
    } catch(e) {
        // Silent error handling
    }
    return '';
};
