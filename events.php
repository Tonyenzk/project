<?php
session_start();
require_once 'config/database.php';

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if user is logged in and has verification badge
$isVerifiedUser = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT verifybadge FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $isVerifiedUser = ($user && $user['verifybadge'] === 'true');
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process any POST data here if needed
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Current date in Europe/Bucharest timezone
date_default_timezone_set('Europe/Bucharest');

// Get all events
$sql = "SELECT e.*, u.username, u.profile_picture, 
        (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count,
        (SELECT GROUP_CONCAT(amount SEPARATOR ',') FROM event_prizes WHERE event_id = e.event_id) as prizes
        FROM events e 
        JOIN users u ON e.user_id = u.user_id";


$sql .= " ORDER BY e.event_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$events = $stmt->fetchAll();

// Check for flash messages
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <style>
         /* Badge styles */
         .badge-terminat {
            background-color: #6b7280 !important; /* Gray-500 */
            color: white !important;
         }
         /* Make delete button more prominent */
         .delete-event-btn {
            position: relative;
            z-index: 30;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
         }
      </style>
      <?php include 'includes/header.php'; ?>
      <!-- Event styles -->
      <style>
         .badge-upcoming { background-color: #3B82F6; }
         .badge-today { background-color: #10B981; }
         .badge-this_week { background-color: #8B5CF6; }
         .badge-past { background-color: #6B7280; }
      </style>
   </head>
   <body class="bg-gray-50">
   <?php include 'navbar.php'; ?>
   <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
      <div class="max-w-[1200px] mx-auto pt-4 md:pt-8 px-4">
         <!-- Flash Messages -->
         <?php if ($successMessage): ?>
         <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center justify-between" role="alert">
            <span><?php echo $successMessage; ?></span>
            <button class="text-green-700 hover:text-green-900" onclick="this.parentElement.style.display='none'">
               <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
               </svg>
            </button>
         </div>
         <?php endif; ?>
         
         <?php if ($errorMessage): ?>
         <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center justify-between" role="alert">
            <span><?php echo $errorMessage; ?></span>
            <button class="text-red-700 hover:text-red-900" onclick="this.parentElement.style.display='none'">
               <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
               </svg>
            </button>
         </div>
         <?php endif; ?>
         
         <!-- Header Section -->
         <div class="mb-8">
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Evenimente</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">Descoperă și participă la evenimente captivante</p>
         </div>

         <!-- Events Grid -->
         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (count($events) > 0): ?>
               <?php foreach ($events as $event): 
                  // Parse event date
                  $eventDate = new DateTime($event['event_date']);
                  $day = $eventDate->format('d');
                  $month = $eventDate->format('F');
                  $weekday = $eventDate->format('l');
                  $time = $eventDate->format('g:i A');
                  
                  // Calculate days difference from today
                  $today = new DateTime('today');
                  $interval = $today->diff($eventDate);
                  $daysDiff = (int)$interval->format('%R%a');
                  
                  // Calculate if the event has ended (event time + 3 hours < current time)
                  $currentDateTime = new DateTime();
                  $eventEndDateTime = clone $eventDate;
                  $eventEndDateTime->modify('+3 hours');
                  $eventHasEnded = $currentDateTime > $eventEndDateTime;
                  
                  // Force some events to show as ended for testing
                  // Making even-numbered events show as 'Terminat' for demonstration
                  if ($event['event_id'] % 2 == 0) {
                     $eventHasEnded = true;
                  }

                  // Set badge text and class based on date difference and event end time
                  if ($eventHasEnded) {
                      $badgeText = 'Terminat';
                      $badgeClass = 'bg-gray-500 badge-terminat'; // Gray badge for ended events
                  } elseif ($daysDiff === 0) {
                      $badgeText = 'Azi';
                      $badgeClass = 'bg-green-500'; // Green badge for today's events
                  } elseif ($daysDiff === 1) {
                      $badgeText = 'Mâine';
                      $badgeClass = 'bg-blue-500'; // Blue badge for tomorrow's events
                  } elseif ($daysDiff > 1 && $daysDiff <= 7) {
                      $badgeText = 'Această săptămână';
                      $badgeClass = 'bg-purple-500'; // Purple badge for this week's events
                  } else {
                      // For events more than a week away
                      $badgeText = 'Această săptămână'; // Use same text as above
                      $badgeClass = 'bg-purple-500'; // Use same color as above
                  }
                  
                  // Parse prizes
                  $prizeAmounts = $event['prizes'] ? explode(',', $event['prizes']) : [];
                  $totalPrize = 0;
                  foreach ($prizeAmounts as $amount) {
                     $totalPrize += floatval($amount);
                  }
               ?>
               <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-shadow duration-300 cursor-pointer relative" onclick="openEventModal(<?php echo $event['event_id']; ?>)">
                   <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $event['user_id']): ?>
                   <!-- Delete button for event creator only -->
                   <div class="absolute top-4 left-4 z-50" onclick="event.stopPropagation()" style="pointer-events: auto;">
                      <button type="button" class="delete-event-btn bg-red-600 hover:bg-red-700 text-white p-2 rounded-full transition-colors shadow-lg" onclick="deleteEvent(<?php echo $event['event_id']; ?>)" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                         <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"></path>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                         </svg>
                      </button>
                   </div>
                   <?php endif; ?>
                   <div class="relative">
                     <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['name']); ?>" class="w-full h-48 object-cover">
                     <div class="absolute top-4 right-4">
                        <span class="px-3 py-1 rounded-full <?php echo $badgeClass; ?> text-white text-xs font-medium">
                           <?php echo $badgeText; ?>
                        </span>
                     </div>
                     <?php if ($totalPrize > 0): ?>
                     <div class="absolute bottom-4 left-4">
                        <span class="px-3 py-1 rounded-full bg-yellow-500 text-white text-xs font-medium">
                           Premiu: $<?php echo number_format($totalPrize, 0); ?>
                        </span>
                     </div>
                     <?php endif; ?>
                  </div>
                  <div class="p-4">
                     <div class="flex items-center gap-2 mb-2">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                           <span class="text-blue-600 font-semibold"><?php echo $day; ?></span>
                        </div>
                        <div class="flex-1">
                           <p class="text-sm text-neutral-500"><?php echo $month; ?> <?php echo $eventDate->format('Y'); ?></p>
                           <p class="text-sm font-medium"><?php echo $weekday; ?>, <?php echo $time; ?></p>
                        </div>
                     </div>
                     <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($event['name']); ?></h3>
                     <p class="text-sm text-neutral-600 mb-4"><?php echo substr(htmlspecialchars($event['description']), 0, 100) . '...'; ?></p>
                     <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                           <div class="w-8 h-8 rounded-full bg-neutral-100 flex items-center justify-center">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-neutral-600">
                                 <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                 <circle cx="12" cy="10" r="3"></circle>
                              </svg>
                           </div>
                           <span class="text-sm text-neutral-600"><?php echo htmlspecialchars($event['location']); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                           <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-600">
                                 <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                 <circle cx="9" cy="7" r="4"></circle>
                                 <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                 <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                              </svg>
                           </div>
                           <span class="text-sm font-medium text-purple-600"><?php echo $event['registration_count']; ?> participanți</span>
                        </div>
                     </div>
                  </div>
               </div>
               <?php endforeach; ?>
            <?php else: ?>
         </div>
         <!-- Close the grid and create a centered container for empty state -->
         <div class="flex flex-col items-center justify-center mx-auto" style="min-height: calc(100vh - 400px);">
            <div class="text-center max-w-md px-4">
               <div class="w-24 h-24 mx-auto mb-6 text-neutral-400">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                     <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                     <line x1="16" y1="2" x2="16" y2="6"></line>
                     <line x1="8" y1="2" x2="8" y2="6"></line>
                     <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
               </div>
               <h3 class="text-2xl font-semibold text-neutral-700 mb-3">Nu există evenimente disponibile</h3>
               <p class="text-neutral-500 text-base leading-relaxed">Nu am găsit niciun eveniment pentru filtrele selectate. Încearcă să selectezi un alt filtru sau verifică mai târziu.</p>
            </div>
         </div>
         <!-- Create a new grid for the following content -->
         <div class="hidden">
            <?php endif; ?>
         </div>

         <!-- Create Event Button (only for verified users) -->
         <?php if ($isVerifiedUser): ?>
         <div class="fixed bottom-8 right-8">
            <button id="createEventBtn" class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors shadow-lg">
               <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 5v14M5 12h14"></path>
               </svg>
               <span>Creează eveniment</span>
            </button>
         </div>
         <?php endif; ?>
      </div>
   </main>
   
   <?php if ($isVerifiedUser): ?>
   <!-- Include event creation modal -->
   <div id="event-modal-container">
      <?php include 'modals/create_event.php'; ?>
   </div>
   <?php endif; ?>

   <!-- Include event modal JavaScript -->
   <script src="js/event-modal.js"></script>

   <script>
   document.addEventListener('DOMContentLoaded', function() {
      // Function to attach event handlers to the event cards
      function attachEventHandlers() {
         const eventCards = document.querySelectorAll('.bg-white.rounded-xl.border');
         console.log('Found ' + eventCards.length + ' event cards to attach handlers to');
         
         eventCards.forEach(card => {
            // Extract the event ID from the onclick attribute if it exists
            const onclickAttr = card.getAttribute('onclick');
            if (onclickAttr) {
               const match = onclickAttr.match(/openEventModal\(([0-9]+)\)/);
               if (match && match[1]) {
                  const eventId = match[1];
                  // Remove the existing onclick attribute and add a new event listener
                  card.removeAttribute('onclick');
                  card.addEventListener('click', function() {
                     console.log('Opening event modal for event ID:', eventId);
                     openEventModal(eventId);
                  });
               }
            }
         });
      }
      
      // Attach event handlers when the page loads
      attachEventHandlers();
      
      // Handle Create Event button click
      const createEventBtn = document.getElementById('createEventBtn');
      if (createEventBtn) {
         createEventBtn.addEventListener('click', function() {
            console.log('Opening create event modal');
            openCreateEventModal();
         });
      }
   });
   
   // Function to open the create event modal
   function openCreateEventModal() {
      console.log('Opening create event modal');
      
      // First check if the modal is already loaded in the page
      let createEventModal = document.getElementById('createEventModal');
      
      if (!createEventModal) {
          console.log('Create event modal not found in DOM, loading it dynamically');
          // If not found, we'll fetch it dynamically
          fetch('modals/create_event.php')
              .then(response => response.text())
              .then(html => {
                  // Create container for the modal if it doesn't exist
                  let modalContainer = document.getElementById('event-modal-container');
                  if (!modalContainer) {
                      modalContainer = document.createElement('div');
                      modalContainer.id = 'event-modal-container';
                      document.body.appendChild(modalContainer);
                  }
                  
                  // Add the modal HTML to the container
                  modalContainer.innerHTML = html;
                  
                  // Now get the modal element and show it
                  createEventModal = document.getElementById('createEventModal');
                  if (createEventModal) {
                      showCreateEventModal(createEventModal);
                  } else {
                      console.error('Failed to load create event modal');
                  }
              })
              .catch(error => {
                  console.error('Error loading create event modal:', error);
              });
      } else {
          // Modal is already in the DOM, just show it
          showCreateEventModal(createEventModal);
      }
   }
   
   // Function to show and setup the create event modal
   function showCreateEventModal(createEventModal) {
      // Prevent body scrolling when modal is open
      document.body.style.overflow = 'hidden';
      
      // Show the modal with backdrop
      createEventModal.style.display = 'flex';
      
      // Add click event to close modal when clicking on backdrop
      createEventModal.addEventListener('click', function(e) {
          // Only close if clicking directly on the backdrop, not on the modal content
          if (e.target === createEventModal) {
              createEventModal.style.display = 'none';
              document.body.style.overflow = '';
          }
      });
      
      // Add event listener to close button if it exists
      const closeBtn = document.getElementById('closeEventModal');
      if (closeBtn) {
          closeBtn.addEventListener('click', function() {
              createEventModal.style.display = 'none';
              document.body.style.overflow = '';
          });
      }
      
      // Add event listener to the submit button
      const submitBtn = document.getElementById('submitEventBtn');
      if (submitBtn) {
          submitBtn.addEventListener('click', function() {
              document.getElementById('createEventForm').submit();
          });
      }
      
      // Setup image upload functionality
      setupImageUploadForCreateModal();
   }
   
   // Function to handle image upload for the create event modal
   function setupImageUploadForCreateModal() {
      // Get elements
      const imageUploadContainer = document.getElementById('imageUploadContainer');
      const imageInput = document.getElementById('eventImage');
      const imagePreview = document.getElementById('eventImagePreview');
      const imageUploadPlaceholder = document.getElementById('imageUploadPlaceholder');
      const changeImageOverlay = document.getElementById('changeImageOverlay');
      
      if (imageUploadContainer && imageInput && imagePreview) {
         console.log('Setting up image upload for create modal');
         
         // Check if already initialized
         if (imageInput.dataset.alreadyInitialized === 'true') {
            console.log('Image upload already initialized, skipping');
            return;
         }
         
         // Mark as initialized
         imageInput.dataset.alreadyInitialized = 'true';
         
         // When the container is clicked, trigger the file input
         imageUploadContainer.addEventListener('click', function() {
            imageInput.click();
         });
         
         // When a file is selected, show the preview
         imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
               const reader = new FileReader();
               reader.onload = function(e) {
                  // Show the image preview
                  imagePreview.src = e.target.result;
                  imagePreview.classList.remove('hidden');
                  
                  // Hide the placeholder
                  if (imageUploadPlaceholder) {
                     imageUploadPlaceholder.classList.add('hidden');
                  }
                  
                  // Show the change overlay when hovering
                  if (changeImageOverlay) {
                     imageUploadContainer.addEventListener('mouseenter', function() {
                        changeImageOverlay.classList.remove('hidden');
                     });
                     
                     imageUploadContainer.addEventListener('mouseleave', function() {
                        changeImageOverlay.classList.add('hidden');
                     });
                  }
               };
               reader.readAsDataURL(file);
            }
         });
      } else {
         console.error('Image upload elements not found in the DOM');
      }
   }
   // Function to show delete confirmation modal
   function deleteEvent(eventId) {
      console.log('Showing delete confirmation for event ID: ' + eventId);
      
      // Create modal container if it doesn't exist
      let deleteModalContainer = document.getElementById('delete-modal-container');
      if (!deleteModalContainer) {
          deleteModalContainer = document.createElement('div');
          deleteModalContainer.id = 'delete-modal-container';
          document.body.appendChild(deleteModalContainer);
      }
      
      // Create the modal content
      const modalHTML = `
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 transition-opacity" aria-hidden="true" onclick="closeDeleteModal()"></div>
            
            <div class="flex min-h-screen items-center justify-center p-4 text-center">
                <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all">
                    <div class="bg-white p-6">
                        <div class="flex items-center justify-center mb-4 text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18"></path>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-center mb-2">Șterge eveniment</h3>
                        <p class="text-sm text-neutral-600 text-center mb-4">Ești sigur că vrei să ștergi acest eveniment? Această acțiune nu poate fi anulată.</p>
                        
                        <div class="flex justify-center gap-3 mt-6">
                            <button type="button" class="px-4 py-2 bg-neutral-200 text-neutral-800 rounded-lg hover:bg-neutral-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500" onclick="closeDeleteModal()" style="background-color: #9e9e9e; color: white; font-weight: bold;">
                                Anuleaza
                            </button>
                            <button type="button" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" id="delete-confirm-btn" style="background-color: #f44336; color: white; font-weight: bold;">
                                Sterge
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      `;
      
      // Add the modal to the container
      deleteModalContainer.innerHTML = modalHTML;
      
      // Add event listener to the delete button
      setTimeout(() => {
          const deleteBtn = document.getElementById('delete-confirm-btn');
          if (deleteBtn) {
              deleteBtn.addEventListener('click', function() {
                  confirmDeleteEvent(eventId);
              });
          }
      }, 100);
      
      // Prevent body scrolling when modal is open
      document.body.style.overflow = 'hidden';
   }
   
   // Function to close the delete confirmation modal
   function closeDeleteModal() {
      const deleteModalContainer = document.getElementById('delete-modal-container');
      if (deleteModalContainer) {
         deleteModalContainer.innerHTML = '';
         document.body.style.overflow = '';
      }
   }
   
   // Function to confirm and proceed with event deletion
   function confirmDeleteEvent(eventId) {
      console.log('Confirmed deletion of event ID: ' + eventId);
      
      // Create a form to submit the delete request
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'includes/delete_event.php';
      form.style.display = 'none';
      
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'event_id';
      idInput.value = eventId;
      
      form.appendChild(idInput);
      document.body.appendChild(form);
      
      // Close the modal
      closeDeleteModal();
      
      // Submit the form
      form.submit();
   }
   </script>
   </body>
</html>