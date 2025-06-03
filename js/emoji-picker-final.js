/**
 * Simple Emoji Picker Implementation - Final Version
 * Fixed implementation with proper scoping and event handling
 */

// Global variables
let activeEmojiButton = null;
let activeInputField = null;
let emojiPickerProcessing = false;

// Wait for document to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Emoji] Initializing');
    setupEmojiPicker();
});

// Main initialization function
function setupEmojiPicker() {
    console.log('[Emoji] Setting up picker system');
    
    // Create container for emoji picker
    createContainer();
    
    // Initialize emoji buttons
    initButtons();
    
    // Set up mutation observer for dynamically added content
    setupObserver();
    
    // Set up document click handler to close picker when clicking outside
    setupClickOutsideHandler();
    
    console.log('[Emoji] Setup complete');
}

// Create container for emoji picker
function createContainer() {
    // Remove any existing container
    let existingContainer = document.getElementById('emoji-picker-container');
    if (existingContainer) {
        existingContainer.remove();
    }
    
    // Create new container
    const container = document.createElement('div');
    container.id = 'emoji-picker-container';
    container.style.position = 'fixed';
    container.style.zIndex = '9999';
    container.style.display = 'none';
    container.style.backgroundColor = 'white';
    container.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    container.style.borderRadius = '8px';
    document.body.appendChild(container);
    
    // Add styles for emoji picker
    addStyles();
    
    console.log('[Emoji] Container created');
}

// Add styles for emoji picker
function addStyles() {
    if (document.getElementById('emoji-picker-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'emoji-picker-styles';
    style.textContent = `
        emoji-picker {
            width: 338px;
            height: 435px;
        }
        
        body.dark emoji-picker,
        emoji-picker.dark {
            --background: #1f2937;
            --border-color: #374151;
            --input-border-color: #4b5563;
            --input-font-color: #e5e7eb;
            --input-placeholder-color: #9ca3af;
            --outline-color: #4b5563;
            --category-font-color: #d1d5db;
            --indicator-color: #60a5fa;
            --text-color: #e5e7eb;
        }
        
        .emoji-picker-arrow {
            position: absolute;
            width: 14px;
            height: 14px;
            transform: rotate(45deg);
            bottom: -7px;
            background-color: white;
            border-right: 1px solid #d9d9d9;
            border-bottom: 1px solid #d9d9d9;
        }
        
        body.dark .emoji-picker-arrow {
            background-color: #1f2937;
            border-right: 1px solid #374151;
            border-bottom: 1px solid #374151;
        }
    `;
    
    document.head.appendChild(style);
    console.log('[Emoji] Styles added');
}

// Initialize emoji buttons
function initButtons() {
    // Find all emoji buttons
    const emojiButtons = document.querySelectorAll('button[aria-label="Add emoji"]');
    console.log('[Emoji] Found', emojiButtons.length, 'emoji buttons');
    
    // Process each button
    emojiButtons.forEach((button, index) => {
        // Skip already processed buttons
        if (button.dataset.emojiProcessed === 'true') return;
        
        // Add class and ID if needed
        button.classList.add('emoji-button');
        if (!button.id) {
            button.id = 'emoji-button-' + Math.random().toString(36).substr(2, 9);
        }
        
        // Create a new button to remove old event listeners
        const newButton = button.cloneNode(true);
        newButton.dataset.emojiProcessed = 'true';
        
        // Replace the old button with the new one
        if (button.parentNode) {
            button.parentNode.replaceChild(newButton, button);
            
            // Add click event listener
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('[Emoji] Button clicked:', this.id);
                showEmojiPicker(this);
                return false;
            });
        }
    });
}

// Show emoji picker
function showEmojiPicker(button) {
    // Save reference to active button and input
    activeEmojiButton = button;
    
    // Find container
    const container = document.getElementById('emoji-picker-container');
    if (!container) {
        console.error('[Emoji] Container not found');
        return;
    }
    
    // Find the input field for this button
    const form = button.closest('form');
    if (!form) {
        console.error('[Emoji] No form found for button');
        return;
    }
    
    const input = form.querySelector('input[name="body"]');
    if (!input) {
        console.error('[Emoji] No input field found');
        return;
    }
    
    // Save reference to active input
    activeInputField = input;
    console.log('[Emoji] Found input field:', input);
    
    // Clear the container
    container.innerHTML = '';
    
    try {
        // Create the emoji picker
        const picker = document.createElement('emoji-picker');
        
        // Apply dark mode if needed
        if (document.body.classList.contains('dark')) {
            picker.classList.add('dark');
        }
        
        // Add the picker to the container first
        container.appendChild(picker);
        
        // Add emoji selection event listener
        picker.addEventListener('emoji-click', function(event) {
            try {
                const emoji = event.detail.unicode;
                insertEmoji(emoji);
                console.log('[Emoji] Selected:', emoji);
            } catch (err) {
                console.error('[Emoji] Error inserting emoji:', err);
            }
        });
        
        // Position the picker above the button
        positionPicker(button, container);
        
        console.log('[Emoji] Picker displayed');
    } catch (error) {
        console.error('[Emoji] Error creating picker:', error);
    }
}

// Position the picker above the button
function positionPicker(button, container) {
    // Get button position
    const rect = button.getBoundingClientRect();
    const pickerWidth = 338;
    
    // Calculate horizontal position (centered above button)
    let leftPos = rect.left + (rect.width / 2) - (pickerWidth / 2);
    
    // Make sure it stays on screen
    const viewportWidth = window.innerWidth;
    if (leftPos < 10) leftPos = 10;
    if (leftPos + pickerWidth > viewportWidth - 10) {
        leftPos = viewportWidth - pickerWidth - 10;
    }
    
    // Position vertically above button
    const topPos = rect.top - 450;
    
    // Set position
    container.style.left = Math.max(10, leftPos) + 'px';
    container.style.top = Math.max(10, topPos) + 'px';
    
    // Make sure it's visible
    container.style.display = 'block';
    container.style.visibility = 'visible';
    container.style.opacity = '1';
    
    // Add arrow pointing to button
    const arrow = document.createElement('div');
    arrow.className = 'emoji-picker-arrow';
    
    // Position arrow to point at button
    const arrowLeftPos = rect.left - leftPos + (rect.width / 2) - 7;
    arrow.style.left = arrowLeftPos + 'px';
    
    container.appendChild(arrow);
    
    console.log('[Emoji] Positioned at', container.style.left, container.style.top);
}

// Insert emoji into input field
function insertEmoji(emoji) {
    if (!activeInputField) {
        console.error('[Emoji] No active input field');
        return;
    }
    
    // Get cursor position
    const pos = activeInputField.selectionStart || 0;
    const text = activeInputField.value;
    
    // Insert emoji at cursor position
    activeInputField.value = text.slice(0, pos) + emoji + text.slice(pos);
    
    // Move cursor after inserted emoji
    activeInputField.focus();
    activeInputField.selectionStart = activeInputField.selectionEnd = pos + emoji.length;
    
    // Hide picker after insertion
    hideEmojiPicker();
}

// Hide emoji picker
function hideEmojiPicker() {
    const container = document.getElementById('emoji-picker-container');
    if (container) {
        container.style.display = 'none';
        console.log('[Emoji] Picker hidden');
    }
    
    // Clear active references
    activeEmojiButton = null;
    activeInputField = null;
}

// Set up observer for dynamically added content
function setupObserver() {
    // Keep track of processed buttons
    const processedButtons = new Set();
    
    // Create mutation observer
    const observer = new MutationObserver(mutations => {
        // Skip if we're currently processing
        if (emojiPickerProcessing) return;
        
        let newButtons = [];
        
        mutations.forEach(mutation => {
            if (mutation.addedNodes && mutation.addedNodes.length) {
                for (let i = 0; i < mutation.addedNodes.length; i++) {
                    const node = mutation.addedNodes[i];
                    if (node.nodeType !== 1) continue;
                    
                    // Check for emoji buttons
                    if (node.getAttribute && node.getAttribute('aria-label') === 'Add emoji') {
                        if (!processedButtons.has(node.id || '')) {
                            newButtons.push(node);
                        }
                    } else if (node.querySelectorAll) {
                        // Check children for emoji buttons
                        const buttons = Array.from(node.querySelectorAll('button[aria-label="Add emoji"]'));
                        buttons.forEach(button => {
                            if (!processedButtons.has(button.id || '')) {
                                newButtons.push(button);
                            }
                        });
                    }
                }
            }
        });
        
        // Process any new buttons
        if (newButtons.length > 0) {
            console.log('[Emoji] Found', newButtons.length, 'new buttons');
            emojiPickerProcessing = true;
            
            newButtons.forEach((button, index) => {
                // Add ID if needed
                if (!button.id) {
                    button.id = 'emoji-button-' + Math.random().toString(36).substr(2, 9);
                }
                
                // Mark as processed
                processedButtons.add(button.id);
                
                // Add class
                button.classList.add('emoji-button');
                
                // Clone to remove old event listeners
                const newButton = button.cloneNode(true);
                newButton.dataset.emojiProcessed = 'true';
                
                // Replace with new button
                if (button.parentNode) {
                    button.parentNode.replaceChild(newButton, button);
                    
                    // Add click handler
                    newButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('[Emoji] Button clicked:', this.id);
                        showEmojiPicker(this);
                        return false;
                    });
                }
            });
            
            emojiPickerProcessing = false;
        }
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('[Emoji] Observer set up');
}

// Set up click outside handler
function setupClickOutsideHandler() {
    document.addEventListener('click', function(event) {
        // Get container
        const container = document.getElementById('emoji-picker-container');
        
        // Skip if container is not visible
        if (!container || container.style.display !== 'block') {
            return;
        }
        
        // Don't close if clicking on an emoji button
        if (event.target.classList.contains('emoji-button') || 
            event.target.closest('button[aria-label="Add emoji"]')) {
            return;
        }
        
        // Don't close if clicking inside the picker
        if (container.contains(event.target)) {
            return;
        }
        
        // Close the picker
        console.log('[Emoji] Click outside detected, hiding picker');
        hideEmojiPicker();
    });
    
    console.log('[Emoji] Click outside handler set up');
}

// Initialize if DOM is already loaded
if (document.readyState === 'interactive' || document.readyState === 'complete') {
    console.log('[Emoji] Document already loaded, initializing immediately');
    setTimeout(setupEmojiPicker, 100);
}
