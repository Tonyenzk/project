<!-- Create Modal Container -->
<div id="createModal" onclick="if(event.target === this) closeCreateModal()" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; overflow: hidden; backdrop-filter: blur(4px);">
   <div role="dialog" aria-describedby="dialog-description" aria-labelledby="dialog-title" data-state="open" class="fixed left-[50%] top-[50%] z-50 w-full translate-x-[-50%] translate-y-[-50%] gap-4 animate-none transition-none duration-0 max-w-5xl h-[90vh] flex flex-col p-0 border border-neutral-200 dark:border-neutral-800 rounded-xl overflow-hidden shadow-xl bg-white dark:bg-neutral-900" onclick="event.stopPropagation()">
      <h2 id="dialog-title" class="sr-only">Dialog</h2>
      <p id="dialog-description" class="sr-only">This dialog window contains interactive content. Press Escape to close the dialog.</p>
      
      <div class="flex flex-col space-y-1.5 text-center sm:text-left px-6 py-4 border-b border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
         <h2 class="tracking-tight text-center text-lg font-semibold text-neutral-900 dark:text-white">Creează postare nouă</h2>
         <p class="text-center text-sm text-neutral-500 dark:text-neutral-400">Share a photo with your followers</p>
         <button onclick="closeCreateModal()" class="absolute right-4 top-4 p-2 rounded-full hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-neutral-900 dark:text-white">
               <path d="M18 6 6 18"></path>
               <path d="m6 6 12 12"></path>
            </svg>
         </button>
      </div>

      <div dir="ltr" data-orientation="horizontal" class="flex-1 flex flex-col overflow-hidden">
         <!-- Tabs -->
         <div role="tablist" aria-orientation="horizontal" class="items-center justify-center text-muted-foreground bg-neutral-100 dark:bg-neutral-900 p-1 mx-6 mt-4 mb-2 grid w-auto rounded-lg h-auto" style="grid-template-columns: repeat(2, 1fr);">
            <button id="postTabBtn" onclick="switchTab('post')" type="button" role="tab" aria-selected="true" class="justify-center whitespace-nowrap px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 rounded-md h-10 flex items-center gap-1.5 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white" data-state="active">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                  <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                  <circle cx="9" cy="9" r="2"></circle>
                  <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
               </svg>
               <span>Post</span>
            </button>
            <button id="storyTabBtn" onclick="switchTab('story')" type="button" role="tab" aria-selected="false" class="justify-center whitespace-nowrap px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 rounded-md h-10 flex items-center gap-1.5 text-neutral-600 dark:text-neutral-400">
               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
               </svg>
               <span>Story</span>
            </button>
         </div>

         <div class="h-[calc(90vh-120px)] overflow-hidden">
            <!-- Post Content -->
            <div id="postContent" class="tab-content h-full">
               <!-- Initial Upload State -->
               <div id="initialPostState" class="flex w-full h-full">
                  <div class="flex-1 flex items-center justify-center bg-white dark:bg-neutral-900">
                      <div class="flex flex-col items-center justify-center w-full h-full gap-4 transition-colors p-6">
                         <div class="flex flex-col items-center justify-center gap-4 max-w-md text-center p-8 bg-white dark:bg-neutral-900 rounded-2xl shadow-sm border border-neutral-200 dark:border-neutral-800">
                             <div class="w-24 h-24 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-2">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image h-10 w-10 text-neutral-500 dark:text-neutral-400">
                                     <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                                     <circle cx="9" cy="9" r="2"></circle>
                                     <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                                 </svg>
                             </div>
                             <h3 class="text-xl font-semibold text-neutral-900 dark:text-white">Creează o postare nouă</h3>
                             <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Distribuie fotografiile tale cu urmăritorii tăi</p>
                             <div class="w-full border-t border-neutral-200 dark:border-neutral-800 my-4"></div>
                             <p class="text-sm font-medium text-neutral-900 dark:text-white">Trage fotografii și videoclipuri aici</p>
                             <p class="text-xs text-neutral-500 dark:text-neutral-400">Sau apasă pentru a încărca</p>
                             <input id="fileInput" class="h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 hidden" accept="image/*,video/*" type="file">
                             <button onclick="document.getElementById('fileInput').click()" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-black dark:bg-neutral-800 text-white dark:text-white hover:bg-neutral-800 dark:hover:bg-neutral-700 h-10 px-4 py-2 mt-2">
                                Adaugă fotografie
                             </button>
                         </div>
                      </div>
                  </div>
               </div>

               <!-- Preview and Input State -->
               <div id="previewAndInputState" class="flex w-full h-full" style="display: none;">
                  <div class="flex-1 flex items-center justify-center bg-white dark:bg-neutral-900">
                     <div class="relative w-full h-full flex items-center justify-center p-6">
                        <div class="relative overflow-hidden rounded-lg shadow-lg bg-white dark:bg-neutral-900 max-w-3xl w-full">
                           <div style="position: relative; width: 100%; padding-bottom: 56.25%;">
                              <div class="w-full bg-black overflow-hidden" style="position: absolute; inset: 0px;">
                                 <div class="relative w-full h-full">
                                    <img id="postPreviewImage" alt="Preview" decoding="async" data-nimg="fill" class="object-contain" src="" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent; transform: scale(1);">
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="w-[350px] h-full border-l border-neutral-200 dark:border-neutral-800 flex flex-col bg-white dark:bg-neutral-900 overflow-hidden">
                     <div class="flex-1 p-6 space-y-6 overflow-y-auto">
                        <form id="createPostForm" class="space-y-6">
                           <div>
                              <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Scrie o descriere</h3>
                              <div class="relative flex items-start rounded-sm border border-neutral-200 dark:border-neutral-800 px-3 py-2 focus-within:outline-none">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info h-4 w-4 text-neutral-500 dark:text-neutral-400 mr-2 mt-[5px]">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                 </svg>
                                 <textarea id="postDescriptionInput" name="description" class="flex w-full rounded-lg border border-input ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 min-h-[120px] resize-none bg-transparent border-none text-sm text-neutral-800 dark:text-neutral-200 placeholder:text-neutral-500 dark:placeholder:text-neutral-400 focus-visible:ring-0 focus-visible:ring-offset-0 focus:outline-none p-0" placeholder="Scrie o descriere" maxlength="250" onkeydown="return preventSpecialChars(event)" onpaste="return false" ondrop="return false"></textarea>
                              </div>
                              <div class="flex justify-end mt-1">
                                 <span id="descriptionCharCount" class="text-xs text-neutral-500 dark:text-neutral-400">250 characters remaining</span>
                              </div>
                           </div>
                           <div>
                              <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Locație</h3>
                              <div class="relative flex items-center rounded-sm border border-neutral-200 dark:border-neutral-800 px-3 py-2">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin h-4 w-4 text-neutral-500 dark:text-neutral-400 mr-2">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                 </svg>
                                 <input id="postLocationInput" name="location" class="flex w-full rounded-lg border ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 h-9 border-none bg-transparent text-sm text-neutral-800 dark:text-neutral-200 placeholder:text-neutral-500 dark:placeholder:text-neutral-400 focus-visible:ring-0 focus-visible:ring-offset-0 p-0" placeholder="Adaugă locație" maxlength="20" value="">
                              </div>
                              <div class="flex justify-end mt-1">
                                 <span id="locationCharCount" class="text-xs text-neutral-500 dark:text-neutral-400">20 characters remaining</span>
                              </div>
                           </div>
                           <div>
                              <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Etichetează persoane</h3>
                              <div class="w-full relative">
                                 <div class="flex items-center rounded-sm border border-neutral-200 dark:border-neutral-800 px-3 py-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-round h-4 w-4 text-neutral-500 dark:text-neutral-400 mr-2">
                                       <path d="M18 21a8 8 0 0 0-16 0"></path>
                                       <circle cx="10" cy="8" r="5"></circle>
                                       <path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"></path>
                                    </svg>
                                    <input id="postTaggingInput" name="tags" class="flex h-10 rounded-lg border ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 w-full bg-transparent border-none text-sm text-neutral-800 dark:text-neutral-200 focus-visible:ring-0 focus-visible:ring-offset-0 p-0" placeholder="Tag people (use @ to search)" type="text" value="" onkeyup="handleTaggingInput(event)">
                                 </div>
                                 <!-- User Search Dropdown -->
                                 <div id="userSearchDropdown" class="absolute z-50 w-full mt-1 bg-white dark:bg-neutral-900 rounded-lg shadow-lg border border-neutral-200 dark:border-neutral-800 max-h-60 overflow-y-auto hidden">
                                    <div class="p-2">
                                       <div id="userSearchResults" class="space-y-1">
                                          <!-- User results will be populated here -->
                                       </div>
                                    </div>
                                 </div>
                                 <!-- Selected Users Display -->
                                 <div id="selectedUsersContainer" class="mt-2 flex flex-wrap gap-2">
                                    <!-- Selected users will be displayed here -->
                                 </div>
                              </div>
                           </div>
                           <div>
                              <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-2">Dezactiveaza comentarii</h3>
                              <div class="flex items-center justify-between">
                                 <span id="commentsStatus" class="text-sm text-green-600 dark:text-green-400">Comentarii active</span>
                                 <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="commentsToggle" class="sr-only peer" onchange="updateCommentsStatus()">
                                    <div class="w-11 h-6 bg-neutral-200 rounded-full peer dark:bg-neutral-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-blue-600"></div>
                                 </label>
                              </div>
                           </div>
                        </form>
                     </div>
                     <div class="p-6 border-t border-neutral-200 dark:border-neutral-800">
                         <button id="submitPostButton" onclick="submitPost()" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-black dark:bg-neutral-800 text-white dark:text-white hover:bg-neutral-800 dark:hover:bg-neutral-700 h-10 px-4 py-2 w-full rounded-lg">
                            <span>Partajează</span>
                         </button>
                      </div>
                  </div>
               </div>
            </div>

            <!-- Story Content -->
            <div id="storyContent" class="tab-content h-full" style="display: none;">
               <div id="initialStoryState" class="flex w-full h-full">
                  <div class="flex-1 flex items-center justify-center bg-white dark:bg-neutral-900">
                      <div class="flex flex-col items-center justify-center w-full h-full gap-4 transition-colors p-6">
                         <div class="flex flex-col items-center justify-center gap-4 max-w-md text-center p-8 bg-white dark:bg-neutral-900 rounded-2xl shadow-sm border border-neutral-200 dark:border-neutral-800">
                             <div class="w-24 h-24 rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mb-2">
                                 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock h-10 w-10 text-neutral-500 dark:text-neutral-400">
                                     <circle cx="12" cy="12" r="10"></circle>
                                     <polyline points="12 6 12 12 16 14"></polyline>
                                 </svg>
                             </div>
                             <h3 class="text-xl font-semibold text-neutral-900 dark:text-white">Creează o poveste nouă</h3>
                             <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">Distribuie un moment care dispare în 24 de ore</p>
                             <div class="w-full border-t border-neutral-200 dark:border-neutral-800 my-4"></div>
                             <p class="text-sm font-medium text-neutral-900 dark:text-white">Trage fotografii și videoclipuri aici</p>
                             <p class="text-xs text-neutral-500 dark:text-neutral-400">Sau apasă pentru a încărca</p>
                             <input id="storyFileInput" class="h-10 w-full rounded-lg border bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 border-neutral-200 dark:border-neutral-800 hidden" accept="image/*" type="file">
                             <button onclick="document.getElementById('storyFileInput').click()" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-black dark:bg-neutral-800 text-white dark:text-white hover:bg-neutral-800 dark:hover:bg-neutral-700 h-10 px-4 py-2 mt-2">
                                Adaugă fotografie
                             </button>
                         </div>
                      </div>
                  </div>
               </div>

               <!-- Preview and Info State for Story -->
               <div id="previewAndInputStoryState" class="flex w-full h-full" style="display: none;">
                  <div class="flex-1 flex items-center justify-center bg-white dark:bg-neutral-900">
                      <div class="relative w-full h-full flex items-center justify-center p-6">
                          <div class="relative overflow-hidden rounded-lg shadow-lg bg-white dark:bg-neutral-900 max-w-3xl w-full">
                              <div style="position: relative; width: 100%; padding-bottom: 56.25%;">
                                  <div class="w-full bg-black overflow-hidden" style="position: absolute; inset: 0px;">
                                      <div class="relative w-full h-full">
                                          <img id="storyPreviewImage" alt="Preview" decoding="async" data-nimg="fill" class="object-contain" src="" style="position: absolute; height: 100%; width: 100%; inset: 0px; color: transparent; transform: scale(1);">
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="w-[350px] h-full border-l border-neutral-200 dark:border-neutral-800 flex flex-col bg-white dark:bg-neutral-900 overflow-hidden">
                      <div class="flex-1 p-6 space-y-6 overflow-y-auto">
                          <div class="bg-neutral-50 dark:bg-neutral-800 p-4 rounded-lg">
                              <div class="flex items-center mb-4">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info w-4 h-4 mr-2 text-neutral-500 dark:text-neutral-400">
                                      <circle cx="12" cy="12" r="10"></circle>
                                      <path d="M12 16v-4"></path>
                                      <path d="M12 8h.01"></path>
                                  </svg>
                                  <h3 class="text-sm font-medium text-neutral-900 dark:text-white">Poveștile dispar după 24 de ore</h3>
                              </div>
                              <p class="text-xs text-neutral-500 dark:text-neutral-400">Poveștile sunt vizibile pentru urmăritorii tăi timp de 24 de ore înainte de a dispărea.</p>
                          </div>

                          <!-- Music Search Section -->
                          <div class="space-y-4">
                              <div class="flex items-center justify-between">
                                  <h3 class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Adaugă muzică</h3>
                              </div>
                              
                              <!-- Music Search Input -->
                              <div id="musicSearchSection" class="relative">
                                  <div class="relative flex items-center">
                                      <!-- Search icon positioned with flex for better alignment -->
                                      <div class="absolute left-3 flex items-center pointer-events-none">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                                              class="w-4 h-4 text-neutral-500 dark:text-neutral-400">
                                              <circle cx="11" cy="11" r="8"></circle>
                                              <path d="m21 21-4.3-4.3"></path>
                                          </svg>
                                      </div>
                                      <input type="text" id="storyMusicSearchInput" placeholder="Caută melodii sau artiști..." 
                                          class="w-full px-4 py-2 pl-10 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white">
                                  </div>
                              </div>

                              <!-- Music Search Results -->
                              <div id="storyMusicSearchResults" class="space-y-2 max-h-[200px] overflow-y-auto hidden">
                                  <!-- Results will be populated here -->
                              </div>

                              <!-- Loading State -->
                              <div id="storyMusicSearchLoading" class="hidden p-2 text-center">
                                  <div class="inline-block animate-spin rounded-full h-6 w-6 border-4 border-blue-500 border-t-transparent"></div>
                              </div>

                              <!-- Error State -->
                              <div id="storyMusicSearchError" class="hidden p-2 text-center text-red-500 text-sm">
                                  A apărut o eroare. Vă rugăm să încercați din nou.
                              </div>
                              
                              <!-- Selected Music Display -->
                              <div id="selectedMusic" class="hidden p-3 bg-neutral-50 dark:bg-neutral-800 rounded-lg">
                                  <div class="flex items-center space-x-3">
                                      <div class="flex-shrink-0">
                                          <img id="selectedMusicThumbnail" src="" alt="Music thumbnail" class="w-12 h-12 rounded object-cover">
                                      </div>
                                      <div class="flex-1 min-w-0">
                                          <p id="selectedMusicTitle" class="text-sm font-medium text-neutral-900 dark:text-white"></p>
                                          <p id="selectedMusicArtist" class="text-xs text-neutral-500 dark:text-neutral-400"></p>
                                      </div>
                                      <button id="removeMusicBtn" type="button" class="p-1 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                          </svg>
                                      </button>
                                  </div>
                                  
                                  <!-- Music Player for 15-second selection -->
                                  <div id="musicPlayerContainer" class="mt-3">
                                      <div class="text-xs text-neutral-500 dark:text-neutral-400 mb-1">
                                          Stories with music will be 15 seconds long. Select which part of the song to use:
                                      </div>
                                      
                                      <!-- Hidden YouTube iframe (for API access) -->
                                      <div id="youtubePlayerContainer" class="hidden"></div>
                                      
                                      <!-- Custom player controls -->
                                      <div class="flex items-center space-x-2 mb-2">
                                          <button id="playPauseBtn" type="button" class="p-1.5 bg-neutral-200 dark:bg-neutral-700 rounded-full">
                                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-neutral-900 dark:text-white" viewBox="0 0 20 20" fill="currentColor">
                                                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                              </svg>
                                          </button>
                                          
                                          <!-- Timeline container -->
                                          <div class="relative flex-1 h-10 bg-gray-200 dark:bg-gray-700 rounded-md overflow-visible">
                                              <!-- Full song duration bar (gray background) -->
                                              <div class="absolute inset-0 rounded-md"></div>
                                              
                                              <!-- Current playback position indicator -->
                                              <div id="progressBar" class="absolute h-full bg-blue-400 dark:bg-blue-500 opacity-30 rounded-md" style="width: 0%"></div>
                                              
                                              <!-- Draggable 15-second selection window (blue bar) -->
                                              <div id="selectionOverlay" class="absolute top-0 h-full bg-blue-500 dark:bg-blue-600 cursor-move rounded-md flex items-center justify-center shadow-md border border-blue-600" 
                                                  style="width: 25%; left: 0%">
                                                  <!-- 15s Label -->
                                                  <div class="text-[12px] text-white font-bold select-none pointer-events-none px-2 py-1 bg-blue-600 rounded-sm">15s</div>
                                                  
                                                  <!-- Drag handles - more visible -->
                                                  <div class="absolute inset-y-0 left-0 w-2 flex items-center justify-center cursor-ew-resize">
                                                    <div class="w-1 h-6 bg-white rounded-full"></div>
                                                  </div>
                                                  <div class="absolute inset-y-0 right-0 w-2 flex items-center justify-center cursor-ew-resize">
                                                    <div class="w-1 h-6 bg-white rounded-full"></div>
                                                  </div>
                                              </div>
                                              
                                              <!-- Seek slider (invisible but interactive) -->
                                              <input id="timeSlider" type="range" min="0" max="100" value="0" 
                                                  class="absolute w-full h-full opacity-0 cursor-pointer">
                                          </div>
                                          <span id="timeDisplay" class="text-xs tabular-nums w-10 text-right text-neutral-900 dark:text-white">0:00</span>
                                      </div>
                                      
                                      <!-- Time selection info -->
                                      <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400 mb-2">
                                          <span>Selection: <span id="startTimeDisplay">0:00</span> - <span id="endTimeDisplay">0:15</span></span>
                                          <input type="hidden" id="musicStartTime" name="music_start_time" value="0">
                                          <button id="previewSelectionBtn" type="button" class="px-2 py-0.5 bg-neutral-200 dark:bg-neutral-700 rounded text-xs text-neutral-900 dark:text-white">
                                              Preview
                                          </button>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                      <div class="p-6 border-t border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900">
                          <button id="submitStoryButton" class="inline-flex items-center justify-center whitespace-nowrap text-sm font-medium ring-offset-background transition-colors disabled:pointer-events-none disabled:opacity-50 bg-black dark:bg-neutral-800 text-white dark:text-white hover:bg-neutral-800 dark:hover:bg-neutral-700 h-10 px-4 py-2 w-full rounded-lg">
                              <span>Partajează</span>
                          </button>
                      </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- Load tagging system script -->
<script src="/assets/js/tagging.js"></script>
<script>
// Initialize tagging system when document is ready
document.addEventListener('DOMContentLoaded', function() {
   console.log('Initializing tagging system...');
   // Reset the global tagging variables to ensure a clean state
   window.selectedUsers = new Set();
   window.selectedUsersData = new Map();
   console.log('Tagging system initialized with empty sets');
});
</script>
<script>
          function switchTab(tabName) {
         // Hide all tab contents
         document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
         });

         // Remove active styles from all tab buttons
         document.querySelectorAll('[id$="TabBtn"]').forEach(btn => {
            btn.classList.remove('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
            btn.classList.add('text-neutral-600', 'dark:text-neutral-400');
         });

         // Show selected tab content
         document.getElementById(tabName + 'Content').style.display = 'block';

         // Add active styles to clicked tab button
         const activeBtn = document.getElementById(tabName + 'TabBtn');
         activeBtn.classList.add('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
         activeBtn.classList.remove('text-neutral-600', 'dark:text-neutral-400');
         
         // Initialize story submit button if in story tab
         if (tabName === 'story') {
            const submitStoryButton = document.getElementById('submitStoryButton');
            if (submitStoryButton) {
               submitStoryButton.onclick = function() {
                  if (typeof submitStory === 'function') {
                     submitStory();
                  }
               };
            }
         }
      }
          // Initialize the default tab
          document.addEventListener('DOMContentLoaded', function() {
         // Check for stored toast message
         const toastMessage = sessionStorage.getItem('toastMessage');
         const toastType = sessionStorage.getItem('toastType');
         
         if (toastMessage) {
            showToast(toastMessage, toastType);
            // Clear the stored message
            sessionStorage.removeItem('toastMessage');
            sessionStorage.removeItem('toastType');
         }

         // Set initial active tab styles
         const postTabBtn = document.getElementById('postTabBtn');
         const storyTabBtn = document.getElementById('storyTabBtn');
         
         if (postTabBtn) {
            postTabBtn.classList.add('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
            postTabBtn.classList.remove('text-neutral-600', 'dark:text-neutral-400');
         }
         
         if (storyTabBtn) {
            storyTabBtn.classList.add('text-neutral-600', 'dark:text-neutral-400');
            storyTabBtn.classList.remove('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
         }
         
         switchTab('post');
         
         // Highlight current page in navigation
         const currentPath = window.location.pathname.split('/').pop() || 'dashboard.php';
         const navLinks = document.querySelectorAll('nav a[href]');
         
         navLinks.forEach(link => {
            const linkPath = link.getAttribute('href').split('/').pop();
            if (currentPath === linkPath || 
                (currentPath === '' && linkPath === 'dashboard.php')) {
               // Remove active classes from all nav items
               document.querySelectorAll('nav a').forEach(navLink => {
                  navLink.classList.remove('bg-neutral-100', 'dark:bg-neutral-800/50');
               });
               
               // Add active classes to current nav item
               link.classList.add('bg-neutral-100', 'dark:bg-neutral-800/50');
            }
         });

         // Get elements for post preview and input
         const fileInput = document.getElementById('fileInput');
         const postPreviewImage = document.getElementById('postPreviewImage');
         const initialPostState = document.getElementById('initialPostState');
         const previewAndInputState = document.getElementById('previewAndInputState');
         const postDescriptionInput = document.getElementById('postDescriptionInput');
         const descriptionCharCount = document.getElementById('descriptionCharCount');
         const postLocationInput = document.getElementById('postLocationInput');
         const locationCharCount = document.getElementById('locationCharCount');

         // File input change listener
         fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
               const reader = new FileReader();
               reader.onload = function(e) {
                  postPreviewImage.src = e.target.result;
                  initialPostState.style.display = 'none';
                  previewAndInputState.style.display = 'flex'; // Use flex since the div has flex class
               }
               reader.readAsDataURL(file);
            }
         });
               // Add this to your JavaScript section
      function updateCommentsStatus() {
         const toggle = document.getElementById('commentsToggle');
         const statusText = document.getElementById('commentsStatus');
         
         if (toggle.checked) {
            statusText.textContent = 'Comentarii dezactivate';
            statusText.classList.remove('text-green-600', 'dark:text-green-400');
            statusText.classList.add('text-red-600', 'dark:text-red-400');
         } else {
            statusText.textContent = 'Comentarii active';
            statusText.classList.remove('text-red-600', 'dark:text-red-400');
            statusText.classList.add('text-green-600', 'dark:text-green-400');
         }
      }
            // Initialize the status text when the page loads
            document.addEventListener('DOMContentLoaded', function() {
         updateCommentsStatus();
         // ... rest of your existing DOMContentLoaded code ...
      });
            // Add this JavaScript function
            function preventSpecialChars(event) {
         // Allow: backspace, delete, tab, escape, enter
         if ([8, 9, 13, 27, 46].indexOf(event.keyCode) !== -1 ||
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (event.keyCode >= 35 && event.keyCode <= 39) ||
            (event.ctrlKey && [65, 67, 86, 88].indexOf(event.keyCode) !== -1)) {
            return true;
         }
         
         // Prevent line breaks
         if (event.keyCode === 13) {
            event.preventDefault();
            return false;
         }
         
         // Allow only letters, numbers, spaces, and basic punctuation
         const regex = /^[a-zA-Z0-9\s.,!?-]*$/;
         if (!regex.test(event.key)) {
            event.preventDefault();
            return false;
         }
         
         return true;
      }

            // Update the character count function
            document.addEventListener('DOMContentLoaded', function() {
         const postDescriptionInput = document.getElementById('postDescriptionInput');
         const descriptionCharCount = document.getElementById('descriptionCharCount');
         
         if (postDescriptionInput && descriptionCharCount) {
            postDescriptionInput.addEventListener('input', function() {
               // Remove any special characters that might have been pasted
               this.value = this.value.replace(/[^a-zA-Z0-9\s.,!?-]/g, '');
               // Remove line breaks
               this.value = this.value.replace(/[\r\n]+/g, ' ');
               
               const remaining = 250 - this.value.length;
               descriptionCharCount.textContent = `${remaining} characters remaining`;
            });
         }
         // ... rest of your existing DOMContentLoaded code ...
      });
      


         // Character count for description
         if (postDescriptionInput && descriptionCharCount) {
             const maxDescriptionLength = postDescriptionInput.getAttribute('maxlength') || 250;
             postDescriptionInput.addEventListener('input', function() {
                 const remaining = maxDescriptionLength - this.value.length;
                 descriptionCharCount.textContent = `${remaining} characters remaining`;
             });
         }

         // Character count for location
         if (postLocationInput && locationCharCount) {
             const maxLocationLength = postLocationInput.getAttribute('maxlength') || 20;
             postLocationInput.addEventListener('input', function() {
                 const remaining = maxLocationLength - this.value.length;
                 locationCharCount.textContent = `${remaining} characters remaining`;
             });
         }

         // Get elements for story preview and input
         const storyFileInput = document.getElementById('storyFileInput');
         const storyPreviewImage = document.getElementById('storyPreviewImage');
         const initialStoryState = document.getElementById('initialStoryState');
         const previewAndInputStoryState = document.getElementById('previewAndInputStoryState');

         // Story file input change listener
         if (storyFileInput && storyPreviewImage && initialStoryState && previewAndInputStoryState) {
            storyFileInput.addEventListener('change', function(event) {
               const file = event.target.files[0];
               if (file) {
                  const reader = new FileReader();
                  reader.onload = function(e) {
                     storyPreviewImage.src = e.target.result;
                     initialStoryState.style.display = 'none';
                     previewAndInputStoryState.style.display = 'flex'; // Use flex since the div has flex class
                  }
                  reader.readAsDataURL(file);
               }
            });
         }
      });

          function initializeCreateModal() {
         // Get all required elements
         const fileInput = document.getElementById('fileInput');
         const postPreviewImage = document.getElementById('postPreviewImage');
         const initialPostState = document.getElementById('initialPostState');
         const previewAndInputState = document.getElementById('previewAndInputState');
         const postDescriptionInput = document.getElementById('postDescriptionInput');
         const descriptionCharCount = document.getElementById('descriptionCharCount');
         const postLocationInput = document.getElementById('postLocationInput');
         const locationCharCount = document.getElementById('locationCharCount');
         const postTabBtn = document.getElementById('postTabBtn');
         const storyTabBtn = document.getElementById('storyTabBtn');

         // Set initial active tab styles
         if (postTabBtn) {
            postTabBtn.classList.add('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
            postTabBtn.classList.remove('text-neutral-600', 'dark:text-neutral-400');
         }
         
         if (storyTabBtn) {
            storyTabBtn.classList.add('text-neutral-600', 'dark:text-neutral-400');
            storyTabBtn.classList.remove('bg-white', 'dark:bg-neutral-800', 'text-neutral-900', 'dark:text-white');
         }
         
         // Initialize tab
         switchTab('post');

         // Add file input change listener
         if (fileInput) {
            fileInput.addEventListener('change', handleFileSelect);
         }

         // Character count for description
         if (postDescriptionInput && descriptionCharCount) {
            const maxDescriptionLength = postDescriptionInput.getAttribute('maxlength') || 250;
            postDescriptionInput.addEventListener('input', function() {
               const remaining = maxDescriptionLength - this.value.length;
               descriptionCharCount.textContent = `${remaining} characters remaining`;
            });
         }

         // Character count for location
         if (postLocationInput && locationCharCount) {
            const maxLocationLength = postLocationInput.getAttribute('maxlength') || 20;
            postLocationInput.addEventListener('input', function() {
               const remaining = maxLocationLength - this.value.length;
               locationCharCount.textContent = `${remaining} characters remaining`;
            });
         }
      }

      // closeCreateModal is now defined in create-modal.js
         
      function handleFileSelect(event) {
         const file = event.target.files[0];
         if (file) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
               showToast('Invalid file type. Only JPEG, PNG, and WebP are allowed.', 'error');
               event.target.value = '';
               return;
            }

            // Validate file size (5MB)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
               showToast('File too large. Maximum size is 5MB.', 'error');
               event.target.value = '';
               return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
               const postPreviewImage = document.getElementById('postPreviewImage');
               const initialPostState = document.getElementById('initialPostState');
               const previewAndInputState = document.getElementById('previewAndInputState');
               
               if (postPreviewImage && initialPostState && previewAndInputState) {
                  postPreviewImage.src = e.target.result;
                  initialPostState.style.display = 'none';
                  previewAndInputState.style.display = 'flex';
               }
            }
            reader.readAsDataURL(file);
         }
      }

      function submitPost() {
         console.log('submitPost called');
         console.log('window.selectedUsers at submit:', window.selectedUsers);
         console.log('window.selectedUsersData at submit:', window.selectedUsersData);
         
         const form = document.getElementById('createPostForm');
         const postId = form.dataset.postId;
         const isUpdate = !!postId;
         
         const formData = new FormData();
         
         // Add post data
         formData.append('description', document.getElementById('postDescriptionInput').value);
         formData.append('location', document.getElementById('postLocationInput').value);
         formData.append('deactivated_comments', document.getElementById('commentsToggle').checked ? 1 : 0);
         
         // Add tags - both as text format and as individual usernames
         // Debug window objects
         console.log('DEBUG window.selectedUsers exists:', typeof window.selectedUsers !== 'undefined');
         console.log('DEBUG window.selectedUsers instanceof Set:', window.selectedUsers instanceof Set);
         console.log('DEBUG window.selectedUsers size:', window.selectedUsers?.size);
         console.log('DEBUG window global scope:', Object.keys(window).filter(key => key.includes('select')));
         
         // Make sure we're accessing the correct variable from tagging.js
         if (typeof window.selectedUsers !== 'undefined' && window.selectedUsers instanceof Set) {
             console.log('DEBUG selectedUsers type:', Object.prototype.toString.call(window.selectedUsers));
             console.log('DEBUG selectedUsers contents:', Array.from(window.selectedUsers));
             
             const tags = Array.from(window.selectedUsers).map(username => '@' + username).join(' ');
             formData.append('tags', tags);
             
             // Also send each username separately for more reliable processing
             window.selectedUsers.forEach(username => {
                console.log('DEBUG appending tagged username:', username);
                formData.append('tagged_usernames[]', username);
             });
             
             console.log('Submitting tags:', tags);
             console.log('Selected users count:', window.selectedUsers.size);
         } else {
             console.warn('ERROR: selectedUsers is undefined or not a valid Set');
             formData.append('tags', '');
         }
         
         // Add post ID if updating
         if (isUpdate) {
             formData.append('post_id', postId);
         } else {
             // Add image file for new posts only
             const fileInput = document.getElementById('fileInput');
             if (!fileInput.files[0]) {
                 showToast('Please select an image to upload', 'error');
                 return;
             }
             formData.append('image', fileInput.files[0]);
         }
         
         // Show loading state
         const submitButton = document.getElementById('submitPostButton');
         if (submitButton) {
             submitButton.disabled = true;
             submitButton.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Se încarcă...';
         }
         
         // Debug FormData contents before sending
         console.log('Form data being submitted:');
         for (let pair of formData.entries()) {
             console.log(pair[0] + ': ' + pair[1]);
         }
         
         // Submit the form
         fetch('utils/create_post.php', {
             method: 'POST',
             body: formData,
             credentials: 'same-origin' // Ensure cookies are sent with the request
         })
         .then(response => response.json())
         .then(data => {
             console.log('Server response:', data);
             if (data.success) {
                 showToast(isUpdate ? 'Post updated successfully' : 'Post created successfully', 'success');
                 closeCreateModal();
                 // Reload the page to show changes
                 setTimeout(() => {
                     window.location.reload();
                 }, 500);
             } else {
                 showToast(data.error || 'Error saving post', 'error');
             }
         })
         .catch(error => {
             console.error('Error:', error);
             showToast('Error saving post', 'error');
         })
         .finally(() => {
             // Reset button state
             if (submitButton) {
                 submitButton.disabled = false;
                 submitButton.innerHTML = `<span>${isUpdate ? 'Update Post' : 'Partajează'}</span>`;
             }
         });
      }

      // closeCreateModal is now defined in create-modal.js
</script> 