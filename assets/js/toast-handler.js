// Function to check and display stored toast messages
function checkStoredToast() {
    const toastMessage = sessionStorage.getItem('toastMessage');
    const toastType = sessionStorage.getItem('toastType');
    
    if (toastMessage) {
        showToast(toastMessage, toastType);
        // Clear the stored message
        sessionStorage.removeItem('toastMessage');
        sessionStorage.removeItem('toastType');
    }
}

// Check for toast messages when the page loads
document.addEventListener('DOMContentLoaded', function() {
    checkStoredToast();
}); 