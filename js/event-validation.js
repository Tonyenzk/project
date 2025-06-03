// Character counter and input validation for event creation form
document.addEventListener('DOMContentLoaded', function() {
    // Character counter
    const textInputs = document.querySelectorAll('input[maxlength], textarea[maxlength]');
    textInputs.forEach(input => {
        const maxLength = input.getAttribute('maxlength');
        const countDisplay = input.parentElement.querySelector('.char-count');
        
        // Initial count display
        if (countDisplay) {
            countDisplay.textContent = `(0/${maxLength})`;
        }
        
        // Update count on input
        input.addEventListener('input', function() {
            if (countDisplay) {
                countDisplay.textContent = `(${this.value.length}/${maxLength})`;
            }
        });
    });
    
    // Validate text and emoji inputs (block special characters)
    const validateInputs = document.querySelectorAll('[data-validate="text-emoji"]');
    validateInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Regex that blocks most special characters but allows letters, numbers, basic punctuation, and emojis
            const invalidChars = /[\u0000-\u001F\u007F-\u009F\u2000-\u200F\u2028-\u202F\u205F-\u206F\uFFF0-\uFFFF]/gu;
            
            // Save cursor position
            const cursorPos = this.selectionStart;
            const oldValue = this.value;
            
            // Filter out invalid characters
            const newValue = oldValue.replace(invalidChars, '');
            
            // Only update if there's a change to avoid cursor jumping
            if (newValue !== oldValue) {
                this.value = newValue;
                // Restore cursor position adjusted for removed characters
                this.setSelectionRange(
                    cursorPos - (oldValue.length - newValue.length),
                    cursorPos - (oldValue.length - newValue.length)
                );
            }
        });
    });
    
    // Add $ symbol to all prize inputs and handle formatting
    function setupPrizeInput(input) {
        // Add $ prefix if it doesn't exist
        const wrapper = input.parentElement;
        if (!wrapper.querySelector('.prize-prefix')) {
            // Create a wrapper for input with $ prefix
            const inputContainer = document.createElement('div');
            inputContainer.className = 'relative flex-1';
            
            // Create the $ prefix with improved styling
            const prefix = document.createElement('div');
            prefix.className = 'prize-prefix absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-600 font-medium';
            prefix.style.fontSize = '0.95rem';
            prefix.style.pointerEvents = 'none';
            prefix.textContent = '$';
            
            // Move the input into the container and adjust its padding
            input.parentNode.insertBefore(inputContainer, input);
            inputContainer.appendChild(prefix);
            inputContainer.appendChild(input);
            input.style.paddingLeft = '1.5rem';
        }
        
        // Add input handler for formatting
        input.addEventListener('input', function() {
            // Remove non-digits
            let value = this.value.replace(/[^0-9]/g, '');
            
            // Format number with commas for thousands
            if (value) {
                const num = parseInt(value, 10);
                this.value = num.toLocaleString('en-US');
            }
        });
        
        // Trigger input event to format existing value
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
    }
    
    // Setup all existing prize inputs
    const prizeInputs = document.querySelectorAll('.prize-input, [data-validate="prize"]');
    prizeInputs.forEach(input => setupPrizeInput(input));
    
    // Add prize button functionality
    const addPrizeBtn = document.getElementById('addPrizeBtn');
    if (addPrizeBtn) {
        addPrizeBtn.addEventListener('click', function() {
            const prizesContainer = document.getElementById('prizesContainer');
            // Get current count of prize inputs
            const prizeCount = prizesContainer.querySelectorAll('.flex.gap-2').length + 1;
            
            // Create new prize input container
            const newPrizeDiv = document.createElement('div');
            newPrizeDiv.className = 'flex gap-2';
            newPrizeDiv.innerHTML = `
                <div class="relative flex-1">
                    <div class="prize-prefix absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-600 font-medium" style="font-size: 0.95rem; pointer-events: none;">$</div>
                    <input type="text" name="prizes[]" 
                        class="flex h-10 w-full rounded-md border px-3 py-2 pl-7 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent prize-input" 
                        placeholder="Premiu ${prizeCount} (e.g., 1,000,000)" data-validate="prize">
                </div>
                <button type="button" class="remove-prize-btn inline-flex items-center justify-center h-10 w-10 rounded-md border border-neutral-200 hover:bg-red-50 text-red-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            `;
            
            // Add to container
            prizesContainer.appendChild(newPrizeDiv);
            
            // Setup the new prize input
            const newInput = newPrizeDiv.querySelector('.prize-input');
            setupPrizeInput(newInput);
            
            // Add remove button functionality
            const removeBtn = newPrizeDiv.querySelector('.remove-prize-btn');
            removeBtn.addEventListener('click', function() {
                newPrizeDiv.remove();
                
                // Update all prize numbers after removal
                updatePrizeNumbers();
            });
        });
    }
    
    // Function to update prize numbers after a prize is removed
    function updatePrizeNumbers() {
        const prizesContainer = document.getElementById('prizesContainer');
        const prizeInputs = prizesContainer.querySelectorAll('.prize-input');
        
        prizeInputs.forEach((input, index) => {
            // Update placeholder with new index
            const newPlaceholder = `Premiu ${index + 1} (e.g., 1,000,000)`;
            input.setAttribute('placeholder', newPlaceholder);
        });
    }
    
    // Process prize inputs before form is submitted
    // We use the event-modal.js for actual form submission
    const formatPrizeInputs = function() {
        const prizeInputs = document.querySelectorAll('.prize-input');
        prizeInputs.forEach(input => {
            // Convert to plain number for server processing
            const formattedValue = input.value;
            input.value = input.value.replace(/[^0-9]/g, '');
            // Store the formatted version for reference
            input.dataset.formatted = formattedValue;
        });
    };
    
    // Make this function available globally
    window.formatPrizeInputs = formatPrizeInputs;
});
