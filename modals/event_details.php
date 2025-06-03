<?php
session_start();
require_once '../config/database.php';

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Get event ID from URL
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$event_id) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    } else {
        exit('Invalid event ID');
    }
    exit;
}

// Get event details
$stmt = $pdo->prepare("
    SELECT e.*, u.username, u.profile_picture, 
    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count
    FROM events e 
    JOIN users u ON e.user_id = u.user_id
    WHERE e.event_id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Event not found']);
    } else {
        exit('Event not found');
    }
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register']) && isset($_SESSION['user_id'])) {
    // Always set the content type for AJAX requests
    if ($isAjax) {
        header('Content-Type: application/json');
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id) VALUES (?, ?)");
        $stmt->execute([$event_id, $_SESSION['user_id']]);
        
        // Get updated registration count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $newCount = $stmt->fetch()['count'];
        
        if ($isAjax) {
            echo json_encode([
                'success' => true, 
                'message' => 'Te-ai înregistrat cu succes la acest eveniment!',
                'newCount' => $newCount
            ]);
            exit;
        } else {
            // For non-AJAX requests, redirect back to the event page
            header('Location: ../events.php?success=registered');
            exit;
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry error
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'Ești deja înregistrat la acest eveniment.']);
                exit;
            } else {
                // For non-AJAX requests
                header('Location: ../events.php?error=already_registered');
                exit;
            }
        } else {
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => 'A apărut o eroare la înregistrare: ' . $e->getMessage()]);
                exit;
            } else {
                // For non-AJAX requests
                header('Location: ../events.php?error=registration_failed');
                exit;
            }
        }
    }
}

// Only return 'Invalid request' for AJAX requests that aren't registration submissions
if ($isAjax && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get event prizes
$stmt = $pdo->prepare("SELECT * FROM event_prizes WHERE event_id = ? ORDER BY amount DESC");
$stmt->execute([$event_id]);
$prizes = $stmt->fetchAll();

// Check if user is registered for this event
$isRegistered = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    $isRegistered = $stmt->rowCount() > 0;
}

// Parse event date
$eventDate = new DateTime($event['event_date']);
$formattedDate = $eventDate->format('d F Y, H:i');

// Calculate days difference from today (same as in events.php)
$today = new DateTime('today');
$interval = $today->diff($eventDate);
$daysDiff = (int)$interval->format('%R%a');

// Calculate if the event has ended (event time + 3 hours < current time)
$currentDateTime = new DateTime();
$eventEndDateTime = clone $eventDate;
$eventEndDateTime->modify('+3 hours');
$eventHasEnded = $currentDateTime > $eventEndDateTime;

// Set badge text and class based on date difference and event end time
if ($eventHasEnded) {
    $badgeText = 'Terminat';
    $badgeClass = 'bg-gray-500 badge-terminat';
} elseif ($daysDiff === 0) {
    $badgeText = 'Azi';
    $badgeClass = 'bg-green-500';
} elseif ($daysDiff === 1) {
    $badgeText = 'Mâine';
    $badgeClass = 'bg-blue-500';
} elseif ($daysDiff > 1 && $daysDiff <= 7) {
    $badgeText = 'Această săptămână';
    $badgeClass = 'bg-purple-500';
} else {
    // For events more than a week away
    $badgeText = 'Această săptămână';
    $badgeClass = 'bg-purple-500';
}
?>

<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop without blur effect -->
    <div class="fixed inset-0 bg-black/60 transition-opacity" aria-hidden="true" onclick="closeEventModal()"></div>

    <!-- Modal container -->
    <div class="flex min-h-screen items-center justify-center p-4 text-center" onclick="event.stopPropagation()">
        <!-- Modal panel -->
        <div class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all modal-content">
            <!-- Close button removed as requested -->

            <!-- Modal content -->
            <div class="bg-white">
                <!-- Event Hero Section -->
                <div class="relative h-[300px]">
                    <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['name']); ?>" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-6 text-white">
                        <div class="mb-2">
                            <span class="px-3 py-1 rounded-full <?php echo $badgeClass; ?> text-white text-xs font-medium">
                                <?php echo $badgeText; ?>
                            </span>
                        </div>
                        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($event['name']); ?></h1>
                        <p class="text-white/90 mt-2"><?php echo htmlspecialchars($event['organizer']); ?></p>
                    </div>
                </div>
                
                <!-- Event Info -->
                <div class="p-6 border-b border-neutral-200">
                    <div class="flex flex-wrap gap-6">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-neutral-500">Data</p>
                                <p class="font-medium"><?php echo $formattedDate; ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-neutral-500">Locație</p>
                                <p class="font-medium"><?php echo htmlspecialchars($event['location']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-600">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-neutral-500">Participanți</p>
                                <p class="font-medium" id="participantCount"><?php echo $event['registration_count']; ?> persoane înscrise</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-600">
                                    <circle cx="12" cy="8" r="7"></circle>
                                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-neutral-500">Tip</p>
                                <p class="font-medium"><?php echo htmlspecialchars($event['type']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Event Description -->
                <div class="p-6 border-b border-neutral-200">
                    <h2 class="text-xl font-semibold mb-4">Despre eveniment</h2>
                    <p class="text-neutral-600"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>

                <!-- Event Prizes Section -->
                <?php if (count($prizes) > 0): ?>
                <div class="p-6 border-b border-neutral-200">
                    <h2 class="text-xl font-semibold mb-4">Premii</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php foreach ($prizes as $index => $prize): ?>
                            <div class="bg-white border border-neutral-200 rounded-lg p-4 flex flex-col items-center text-center shadow-sm hover:shadow-md transition-shadow">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3 <?php echo $index === 0 ? 'bg-yellow-100 text-yellow-700' : ($index === 1 ? 'bg-gray-100 text-gray-700' : 'bg-amber-100 text-amber-700'); ?>">
                                    <span class="text-xl font-bold"><?php echo $index + 1; ?></span>
                                </div>
                                <h3 class="font-semibold">Premiul <?php echo $index + 1; ?></h3>
                                <p class="text-green-600 font-medium mt-1">$<?php echo number_format($prize['amount'], 0, ',', '.'); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Registration Section -->
                <div class="p-6">
                    <?php if ($event['status'] !== 'past'): ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($isRegistered): ?>
                                <div class="bg-green-50 text-green-700 rounded-lg p-4 flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span>Ești deja înregistrat la acest eveniment</span>
                                </div>
                            <?php else: ?>
                                <div class="flex justify-center">
                                    <form method="post" action="" id="eventRegistrationForm" class="w-full max-w-xs">
                                        <button type="submit" name="register" class="w-full px-8 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 shadow-lg">
                                            Particip
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="bg-blue-50 text-blue-700 rounded-lg p-4 flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="16"></line>
                                    <line x1="8" y1="12" x2="16" y2="12"></line>
                                </svg>
                                <span>Trebuie să fii autentificat pentru a te înregistra la acest eveniment</span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="bg-neutral-100 text-neutral-700 rounded-lg p-4 flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-neutral-500">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span>Acest eveniment s-a încheiat</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modal-content {
    opacity: 1;
    transform: scale(1) translateY(0);
}

@keyframes modalFadeOut {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
}

/* Add smooth scrollbar for the modal content */
.modal-content {
    scrollbar-width: thin;
    scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.modal-content::-webkit-scrollbar {
    width: 8px;
}

.modal-content::-webkit-scrollbar-track {
    background: transparent;
}

.modal-content::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 20px;
    border: 2px solid transparent;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.7);
}
</style>

<!-- Include event modal JavaScript -->
<script src="../js/event-modal.js"></script> 