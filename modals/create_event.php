<!-- Event Creation Modal -->
<div id="createEventModal" class="modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; overflow: hidden; backdrop-filter: blur(4px);">
   <div role="dialog" aria-describedby="dialog-description" aria-labelledby="dialog-title" data-state="open" class="fixed left-[50%] top-[50%] z-50 w-full translate-x-[-50%] translate-y-[-50%] gap-4 animate-none transition-none duration-0 max-w-2xl h-[90vh] flex flex-col p-0 border border-neutral-200 dark:border-neutral-800 rounded-xl overflow-hidden shadow-xl bg-white dark:bg-neutral-900" onclick="event.stopPropagation()">
      <h2 id="dialog-title" class="sr-only">Dialog</h2>
      <p id="dialog-description" class="sr-only">This dialog window contains interactive content. Press Escape to close the dialog.</p>
      
      <div class="flex flex-col space-y-1.5 text-center sm:text-left px-6 py-4 border-b border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
         <h2 class="tracking-tight text-center text-lg font-semibold">Creează eveniment</h2>
         <p class="text-center text-sm text-neutral-500 dark:text-neutral-400">Creează un eveniment nou pentru comunitatea ta</p>
         <button id="closeEventModal" class="absolute right-4 top-4 p-2 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
               <path d="M18 6 6 18"></path>
               <path d="m6 6 12 12"></path>
            </svg>
         </button>
      </div>

      <div class="flex-1 overflow-y-auto">
         <div class="p-6">
            <form id="createEventForm" method="post" action="../includes/create_event.php" enctype="multipart/form-data" class="space-y-6">
               <!-- Image Upload Area -->
               <div class="space-y-4">
                  <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Fotografie eveniment</h3>
                  <div id="imageUploadContainer" class="relative w-full aspect-[16/9] min-h-[200px] rounded-md border-blue-200 hover:border-blue-300 border-2 border-dashed bg-blue-50/50 cursor-pointer transition-colors flex items-center justify-center overflow-hidden">
                     <!-- Image upload placeholder -->
                     <div id="imageUploadPlaceholder" class="absolute inset-0 flex flex-col items-center justify-center h-full gap-3 z-10 p-4 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-10 h-10 text-blue-500">
                           <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                           <circle cx="9" cy="9" r="2"></circle>
                           <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-700">Apasă pentru a încărca o fotografie</span>
                        <span class="text-xs text-neutral-500">Imaginea va apărea în pagina evenimentului</span>
                     </div>
                     
                     <!-- Image preview -->
                     <img id="eventImagePreview" class="hidden absolute inset-0 w-full h-full object-cover z-0" src="#" alt="Event preview">
                     
                     <!-- Change image overlay -->
                     <div id="changeImageOverlay" class="hidden absolute inset-0 bg-black/60 flex items-center justify-center text-white z-20">
                        <div class="flex flex-col items-center gap-2">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                           </svg>
                           <span class="text-sm">Schimbă imaginea</span>
                        </div>
                     </div>
                  </div>
                  <!-- File input outside the container to prevent event issues -->
                  <input type="file" id="eventImage" name="event_image" class="hidden" accept="image/jpeg,image/png,image/gif" data-already-initialized="false">
               </div>

               <!-- Event Details -->
               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                     <label for="eventName" class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Numele evenimentului <span class="char-count">(0/40)</span></label>
                     <input type="text" id="eventName" name="event_name" maxlength="40" 
                        class="flex h-10 w-full rounded-md border px-3 py-2 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        placeholder="Introdu numele evenimentului" required data-validate="text-emoji">
                  </div>

                  <div class="space-y-2">
                     <label for="eventType" class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Tipul evenimentului <span class="char-count">(0/40)</span></label>
                     <input type="text" id="eventType" name="event_type" maxlength="40" 
                        class="flex h-10 w-full rounded-md border px-3 py-2 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        placeholder="ex., Car Meet, Rap Battle, etc." required data-validate="text-emoji">
                  </div>

                  <div class="space-y-2">
                     <label for="eventOrganizer" class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Organizator <span class="char-count">(0/40)</span></label>
                     <input type="text" id="eventOrganizer" name="event_organizer" maxlength="40" 
                        class="flex h-10 w-full rounded-md border px-3 py-2 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        placeholder="Numele organizatorului" required data-validate="text-emoji">
                  </div>

                  <div class="space-y-2">
                     <label for="eventLocation" class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Locație <span class="char-count">(0/40)</span></label>
                     <input type="text" id="eventLocation" name="event_location" maxlength="40" 
                        class="flex h-10 w-full rounded-md border px-3 py-2 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                        placeholder="Locația evenimentului" required data-validate="text-emoji">
                  </div>

                  <div class="space-y-2">
                     <label for="eventDate" class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Data evenimentului</label>
                     <input type="date" id="eventDate" name="event_date" 
                        class="flex h-10 w-full rounded-md border px-3 py-2 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required
                        min="<?php echo date('Y-m-d'); ?>">
                  </div>

                  <div class="space-y-2">
                     <label for="eventTime" class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Ora evenimentului</label>
                     <select id="eventTime" name="event_time" 
                        class="flex h-10 w-full rounded-md border px-3 py-2 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="00:00">00:00</option>
                        <option value="01:00">01:00</option>
                        <option value="02:00">02:00</option>
                        <option value="03:00">03:00</option>
                        <option value="04:00">04:00</option>
                        <option value="05:00">05:00</option>
                        <option value="06:00">06:00</option>
                        <option value="07:00">07:00</option>
                        <option value="08:00">08:00</option>
                        <option value="09:00">09:00</option>
                        <option value="10:00">10:00</option>
                        <option value="11:00">11:00</option>
                        <option value="12:00">12:00</option>
                        <option value="13:00">13:00</option>
                        <option value="14:00">14:00</option>
                        <option value="15:00">15:00</option>
                        <option value="16:00">16:00</option>
                        <option value="17:00">17:00</option>
                        <option value="18:00">18:00</option>
                        <option value="19:00">19:00</option>
                        <option value="20:00">20:00</option>
                        <option value="21:00">21:00</option>
                        <option value="22:00">22:00</option>
                        <option value="23:00">23:00</option>
                     </select>
                  </div>
               </div>

               <!-- Description and Rules -->
               <div class="space-y-4">
                  <div class="space-y-2">
                     <label for="eventDescription" class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Descrierea și regulile evenimentului <span class="char-count">(0/2000)</span></label>
                     <textarea id="eventDescription" name="event_description" rows="6" maxlength="2000" 
                        class="flex w-full rounded-md border px-3 py-2 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none min-h-[150px]" 
                        placeholder="Descrie evenimentul tău și include regulile aici..." required></textarea>
                  </div>

                  <div class="space-y-2">
                     <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Premii (Opțional)</label>
                        <button type="button" id="addPrizeBtn" class="inline-flex items-center justify-center text-sm font-medium h-9 rounded-md px-3 text-blue-500 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                           <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2">
                              <path d="M5 12h14"></path>
                              <path d="M12 5v14"></path>
                           </svg>
                           Adaugă premiu
                        </button>
                     </div>
                     <div id="prizesContainer" class="space-y-2">
                        <div class="flex gap-2">
                           <div class="relative flex-1">
                              <div class="prize-prefix absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-600 font-medium" style="font-size: 0.95rem; pointer-events: none; z-index: 10; line-height: 1;">$</div>
                              <input type="text" name="prizes[]" 
                                 class="flex h-10 w-full rounded-md border px-3 py-2 pl-8 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent prize-input" 
                                 placeholder="Premiu 1 (e.g., 1,000,000)" data-validate="prize">
                           </div>
                           <!-- Initial prize doesn't have a remove button -->
                        </div>
                     </div>
                  </div>
               </div>
            </form>
         </div>
      </div>

      <div class="p-6 border-t border-neutral-200 dark:border-neutral-800">
         <button type="button" id="submitEventBtn" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-black text-white hover:bg-neutral-800 h-10 px-4 py-2 w-full rounded-lg">
            <span>Crează eveniment</span>
         </button>
      </div>
   </div>
</div>

<style>
.char-count {
    font-size: 0.8em;
    color: #666;
    font-weight: normal;
}

/* Ensure the prize input displays the $ correctly */
.prize-input {
    padding-left: 1.75rem !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set min date for event date picker to today
    const eventDatePicker = document.getElementById('eventDate');
    if (eventDatePicker) {
        const today = new Date().toISOString().split('T')[0];
        eventDatePicker.setAttribute('min', today);
        eventDatePicker.value = today; // Default to today
    }
    
    // Handle prize input - ensure the $ is visible
    const prizeInputs = document.querySelectorAll('.prize-input');
    prizeInputs.forEach(input => {
        input.addEventListener('focus', function() {
            const prefix = this.parentElement.querySelector('.prize-prefix');
            if (prefix) {
                prefix.style.color = '#1a73e8'; // Change color on focus
            }
        });
        
        input.addEventListener('blur', function() {
            const prefix = this.parentElement.querySelector('.prize-prefix');
            if (prefix) {
                prefix.style.color = '#666'; // Restore color on blur
            }
        });
    });
    
    // Handle add prize button
    const addPrizeBtn = document.getElementById('addPrizeBtn');
    if (addPrizeBtn) {
        addPrizeBtn.addEventListener('click', function() {
            const prizesContainer = document.getElementById('prizesContainer');
            const prizeCount = prizesContainer.querySelectorAll('.flex.gap-2').length + 1;
            
            const newPrizeDiv = document.createElement('div');
            newPrizeDiv.className = 'flex gap-2';
            newPrizeDiv.innerHTML = `
                <div class="relative flex-1">
                    <div class="prize-prefix absolute left-3 top-1/2 transform -translate-y-1/2 text-neutral-600 font-medium" style="font-size: 0.95rem; pointer-events: none; z-index: 10; line-height: 1;">$</div>
                    <input type="text" name="prizes[]" 
                        class="flex h-10 w-full rounded-md border px-3 py-2 pl-8 text-sm bg-white border-neutral-200 text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent prize-input" 
                        placeholder="Premiu ${prizeCount} (e.g., 1,000,000)" data-validate="prize">
                </div>
                <button type="button" class="remove-prize-btn h-10 px-3 rounded-md bg-neutral-100 hover:bg-neutral-200 text-neutral-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            `;
            
            prizesContainer.appendChild(newPrizeDiv);
            
            // Add event listener to the new remove button
            const removeBtn = newPrizeDiv.querySelector('.remove-prize-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    newPrizeDiv.remove();
                });
            }
            
            // Add event listeners to the new prize input
            const newInput = newPrizeDiv.querySelector('.prize-input');
            if (newInput) {
                newInput.addEventListener('focus', function() {
                    const prefix = this.parentElement.querySelector('.prize-prefix');
                    if (prefix) {
                        prefix.style.color = '#1a73e8';
                    }
                });
                
                newInput.addEventListener('blur', function() {
                    const prefix = this.parentElement.querySelector('.prize-prefix');
                    if (prefix) {
                        prefix.style.color = '#666';
                    }
                });
            }
        });
    }
});
</script>

<script src="../js/event-modal.js"></script>
<script src="../js/event-validation.js"></script>
