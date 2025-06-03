<?php
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();
require_once 'config/database.php';

// Get filter parameter from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Set timezone to Europe/Bucharest
date_default_timezone_set('Europe/Bucharest');

// Log for debugging
file_put_contents('filter_log.txt', date('Y-m-d H:i:s') . " - Filter: {$filter}\n", FILE_APPEND);

// Get events based on filter
$sql = "SELECT e.*, u.username, u.profile_picture, 
        (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as registration_count,
        (SELECT GROUP_CONCAT(amount SEPARATOR ',') FROM event_prizes WHERE event_id = e.event_id) as prizes
        FROM events e 
        JOIN users u ON e.user_id = u.user_id";

// Only filter for today or tomorrow - 'all' shows everything
if ($filter === 'today') {
    $sql .= " WHERE DATE(e.event_date) = CURDATE()";
} elseif ($filter === 'tomorrow') {
    $sql .= " WHERE DATE(e.event_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)";
}

$sql .= " ORDER BY e.event_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$events = $stmt->fetchAll();

// Log the number of events found
file_put_contents('filter_log.txt', date('Y-m-d H:i:s') . " - Found " . count($events) . " events\n", FILE_APPEND);

if (count($events) > 0) {
    foreach ($events as $event): 
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
        
        // Set badge text and class based on date difference
        if ($daysDiff === 0) {
            $badgeText = 'Azi';
            $badgeClass = 'badge-today';
        } elseif ($daysDiff === 1) {
            $badgeText = 'Mâine';
            $badgeClass = 'badge-tomorrow'; // Using the upcoming class for tomorrow
        } elseif ($daysDiff > 1 && $daysDiff <= 7) {
            $badgeText = 'Această săptămână';
            $badgeClass = 'badge-this_week';
        } elseif ($daysDiff > 7) {
            $badgeText = 'Upcoming';
            $badgeClass = 'badge-upcoming';
        } else {
            $badgeText = 'Trecut';
            $badgeClass = 'badge-past';
        }
        
        // Parse prizes
        $prizeAmounts = $event['prizes'] ? explode(',', $event['prizes']) : [];
        $totalPrize = 0;
        foreach ($prizeAmounts as $amount) {
            $totalPrize += floatval($amount);
        }
    ?>
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
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
                <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="px-4 py-2 rounded-lg bg-blue-500 text-white text-sm font-medium hover:bg-blue-600 transition-colors">
                    Vezi detalii
                </a>
            </div>
        </div>
    </div>
    <?php endforeach;
} else {
    ?>
    <!-- Empty state message for AJAX responses -->
    <div class="col-span-1 md:col-span-2 lg:col-span-3 flex flex-col items-center justify-center py-16">
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
    <?php
}
?> 