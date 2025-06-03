// Prevent multiple initializations
if (window.eventModalInitialized) {
    console.log('Event modal already initialized');
} else {
    window.eventModalInitialized = true;

    // Global variables
    let isSubmitting = false;
    let currentEventId = null;

    // Initialize event modal functionality
    function initEventModal() {
        console.log('Event modal JS initialized');
        setupImageUpload();
        setupEventRegistration();
    }

    // Handle event registration
    function setupEventRegistration() {
        document.addEventListener('submit', function(e) {
            if (e.target.id === 'eventRegistrationForm') {
                e.preventDefault();
                handleRegistration(e);
            }
        });
    }

    function handleRegistration(e) {
        if (isSubmitting) return;
        
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Disable the button and show loading state
        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Se procesează...
        `;
        
        // Ensure the FormData has the register parameter
        const formData = new FormData(form);
        if (!formData.has('register')) {
            formData.append('register', 'true');
        }
        
        fetch(`modals/event_details.php?id=${currentEventId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            }
            // If not JSON, get the text and try to parse it
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Server returned invalid response format');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Update participant count in the modal
                const participantCount = document.getElementById('participantCount');
                if (participantCount) {
                    participantCount.textContent = `${data.newCount} persoane înscrise`;
                }
                
                // Also update the participant count on the main page for this event
                const eventCards = document.querySelectorAll('.bg-white.rounded-xl.border');
                eventCards.forEach(card => {
                    // Check if this is the event card for the current event
                    const onclickAttr = card.getAttribute('onclick');
                    if (onclickAttr) {
                        const match = onclickAttr.match(/openEventModal\(([0-9]+)\)/);
                        if (match && match[1] && match[1] == currentEventId) {
                            // This is the right card, update the participant count
                            const participantSpan = card.querySelector('.text-sm.font-medium.text-purple-600');
                            if (participantSpan) {
                                participantSpan.textContent = `${data.newCount} participanți`;
                            }
                        }
                    }
                });

                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'bg-green-50 text-green-700 rounded-lg p-4 flex items-center gap-3';
                successDiv.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span>${data.message}</span>
                `;
                form.parentElement.replaceChild(successDiv, form);
            } else {
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-50 text-red-700 rounded-lg p-4 flex items-center gap-3';
                errorDiv.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>${data.message}</span>
                `;
                form.parentElement.replaceChild(errorDiv, form);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-50 text-red-700 rounded-lg p-4 flex items-center gap-3';
            errorDiv.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>A apărut o eroare. Vă rugăm să încercați din nou.</span>
            `;
            form.parentElement.replaceChild(errorDiv, form);
        })
        .finally(() => {
            isSubmitting = false;
        });
    }

    // Function to open event modal
    function openEventModal(eventId) {
        currentEventId = eventId;
        
        // Create a container for the modal if it doesn't exist
        let modalContainer = document.getElementById('event-modal-container');
        if (!modalContainer) {
            modalContainer = document.createElement('div');
            modalContainer.id = 'event-modal-container';
            document.body.appendChild(modalContainer);
        }

        // Prevent body scrolling when modal is open
        document.body.style.overflow = 'hidden';

        // Load the modal content
        fetch(`modals/event_details.php?id=${eventId}`)
            .then(response => response.text())
            .then(html => {
                modalContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading event modal:', error);
                document.body.style.overflow = ''; // Restore scrolling on error
            });
    }

    // Function to close event modal
    function closeEventModal() {
        const modal = document.querySelector('.fixed.inset-0.z-50');
        if (modal) {
            // Remove modal immediately without animation
            modal.remove();
            document.body.style.overflow = '';
            currentEventId = null;
        }
    }

    // Setup image upload functionality
    function setupImageUpload() {
        console.log('Setting up image upload functionality');
        const imageInput = document.getElementById('eventImage');
        const imagePreview = document.getElementById('imagePreview');
        
        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', initEventModal);

    // Add event listener for escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEventModal();
        }
    });
}
