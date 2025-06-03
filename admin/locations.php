<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../config/database.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verify user role
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Check if user has admin privileges
    if (!$user || !in_array($user['role'], ['Moderator', 'Admin', 'Master_Admin'])) {
        header("Location: ../dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    // If there's an error, redirect to login
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Set flag for admin section for proper navigation paths
$isAdminSection = true;

// Handle location deletion
if (isset($_POST['delete_location'])) {
    try {
        $locationId = $_POST['location_id'];
        $location = $_POST['location_name'];
        
        // Start transaction to ensure data integrity
        $pdo->beginTransaction();
        
        // First, update all posts with this location to have NULL location
        $updateStmt = $pdo->prepare("UPDATE posts SET location = NULL WHERE location = ?");
        $updateStmt->execute([$location]);
        
        // Commit the transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Locația '" . htmlspecialchars($location) . "' a fost ștearsă cu succes și eliminată din toate postările.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        // Rollback the transaction if there's an error
        $pdo->rollBack();
        $_SESSION['error'] = "A apărut o eroare la ștergerea locației: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle location photo upload
if (isset($_POST['upload_location_photo'])) {
    try {
        $location = $_POST['location_name'];
        $photo = $_FILES['location_photo'];
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($photo['type'], $allowedTypes)) {
            throw new Exception("Tip de fișier neacceptat. Sunt acceptate doar JPEG, PNG și WebP.");
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = '../uploads/locations/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
        $filename = uniqid('location_') . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($photo['tmp_name'], $filepath)) {
            // Update location photo in database
            $updateStmt = $pdo->prepare("UPDATE posts SET location_photo = ? WHERE location = ?");
            $updateStmt->execute(['uploads/locations/' . $filename, $location]);
            
            $_SESSION['success'] = "Fotografia locației a fost încărcată cu succes.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            throw new Exception("Eroare la încărcarea fișierului.");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get flash messages
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Pagination
$itemsPerPage = 15;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = " WHERE p.location LIKE ? ";
    $searchParams[] = "%$search%";
}

// Fetch total count of unique locations
try {
    $countQuery = "SELECT COUNT(DISTINCT p.location) as total FROM posts p" . $searchCondition . " WHERE p.location IS NOT NULL AND TRIM(p.location) != ''";
    $countStmt = $pdo->prepare($countQuery);
    if (!empty($searchParams)) {
        $countStmt->execute($searchParams);
    } else {
        $countStmt->execute();
    }
    $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $errorMessage = "Eroare la numărarea locațiilor: " . $e->getMessage();
    $totalItems = 0;
}

$totalPages = ceil($totalItems / $itemsPerPage);

// Fetch locations with post counts
try {
    $query = "
        SELECT 
            p.location, 
            COUNT(p.post_id) as post_count
        FROM posts p 
        " . $searchCondition . "
        WHERE p.location IS NOT NULL AND TRIM(p.location) != '' 
        GROUP BY p.location 
        ORDER BY post_count DESC, p.location ASC 
        LIMIT $itemsPerPage OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    if (!empty($searchParams)) {
        $stmt->execute($searchParams);
    } else {
        $stmt->execute();
    }
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Eroare la încărcarea locațiilor: " . $e->getMessage();
    $locations = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
    <title>Administrare Locații</title>
    <base href="../" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/toast.css">
    <script src="assets/js/toast-handler.js"></script>
</head>
<body>
    <?php 
    // Define a flag to let navbar know we're in admin section
    $isAdminSection = true;
    include '../navbar.php';
    ?>
    
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="flex flex-col min-h-screen bg-white dark:bg-black transition-all duration-300 ease-in-out">
            <div class="flex-1 container max-w-7xl mx-auto px-4 py-8 lg:px-8">
                <div class="border-b border-neutral-200 dark:border-neutral-700 bg-white dark:bg-black rounded-lg p-6 mb-8 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Administrare Locații</h1>
                    </div>
                    <?php include 'tabs.php'; ?>
                </div>
            
            <!-- Search and Stats -->
            <div class="bg-white dark:bg-black rounded-xl p-4 shadow-sm mb-4">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-4">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="text-neutral-700 dark:text-white">Total locații: <span class="font-semibold"><?php echo $totalItems; ?></span></span>
                    </div>
                    
                    <!-- Search Form -->
                    <form action="" method="GET" class="w-full lg:w-auto">
                        <div class="flex">
                            <input type="text" name="search" placeholder="Caută locații..." value="<?php echo htmlspecialchars($search); ?>" class="flex-grow px-4 py-2 border border-neutral-300 dark:border-neutral-700 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-black text-neutral-700 dark:text-white">
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-r-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Locations Table -->
            <div class="bg-white dark:bg-black rounded-xl shadow-sm overflow-hidden">
                <?php if (empty($locations)): ?>
                <div class="p-6 text-center">
                    <div class="inline-flex rounded-full bg-yellow-100 p-4 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white mb-2">Nu s-au găsit locații</h3>
                    <p class="text-neutral-500 dark:text-white">
                        <?php echo !empty($search) ? "Nu s-au găsit locații care să corespundă căutării tale." : "Nu există încă locații în baza de date."; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800">
                        <thead class="bg-neutral-50 dark:bg-black">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-white uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-white uppercase tracking-wider">Locație</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-white uppercase tracking-wider">Număr Postări</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-white uppercase tracking-wider">Fotografie</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-white uppercase tracking-wider">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-black divide-y divide-neutral-200 dark:divide-neutral-700">
                            <?php $counter = ($currentPage - 1) * $itemsPerPage + 1; ?>
                            <?php foreach ($locations as $location): ?>
                            <?php if (!empty($location['location'])): ?>
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-white"><?php echo $counter++; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-neutral-900 dark:text-white"><?php echo htmlspecialchars($location['location']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-white">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-white">
                                        <?php echo number_format($location['post_count']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openPhotoUploadModal('<?php echo htmlspecialchars(addslashes($location['location'])); ?>')" class="text-blue-600 hover:text-blue-900 dark:text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="../location.php?location=<?php echo urlencode($location['location']); ?>" class="text-blue-600 hover:text-blue-900 dark:text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <button onclick="confirmDelete('<?php echo htmlspecialchars(addslashes($location['location'])); ?>')" class="text-red-600 hover:text-red-900 dark:text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 bg-white dark:bg-black border-t border-neutral-200 dark:border-neutral-800">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-neutral-700 dark:text-white">
                            Pagina <?php echo $currentPage; ?> din <?php echo $totalPages; ?>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($currentPage > 1): ?>
                            <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-4 py-2 border border-neutral-300 dark:border-neutral-700 rounded-md text-sm font-medium text-neutral-700 dark:text-white bg-white dark:bg-black hover:bg-neutral-50 dark:hover:bg-neutral-700">
                                Precedentă
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-4 py-2 border border-neutral-300 dark:border-neutral-700 rounded-md text-sm font-medium text-neutral-700 dark:text-white bg-white dark:bg-black hover:bg-neutral-50 dark:hover:bg-neutral-700">
                                Următoarea
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </main>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center" onclick="closeDeleteModal()">
            <div class="bg-white dark:bg-black rounded-lg shadow-lg max-w-md w-full mx-4" onclick="event.stopPropagation()">
                <div class="p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 bg-red-100 rounded-full p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Confirmare ștergere</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-white">
                                Ești sigur că vrei să ștergi această locație? Această acțiune va elimina locația din toate postările asociate.
                            </p>
                            <p class="mt-2 text-sm font-medium text-neutral-900 dark:text-white" id="locationToDelete"></p>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-neutral-700 dark:text-white bg-white dark:bg-black border border-neutral-300 dark:border-neutral-700 rounded-md hover:bg-neutral-50 dark:hover:bg-neutral-700 focus:outline-none">
                            Anulează
                        </button>
                        <form id="deleteForm" method="POST" action="admin/locations.php">
                            <input type="hidden" name="location_id" id="locationIdInput" value="">
                            <input type="hidden" name="location_name" id="locationNameInput" value="">
                            <input type="hidden" name="delete_location" value="1">
                            <button type="submit" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none">
                                Șterge locația
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Photo Upload Modal -->
    <div id="photoUploadModal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closePhotoUploadModal()"></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center" onclick="closePhotoUploadModal()">
            <div class="bg-white dark:bg-black rounded-lg shadow-lg max-w-md w-full mx-4" onclick="event.stopPropagation()">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white mb-4">Încarcă fotografie locație</h3>
                    <form id="photoUploadForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="location_name" id="photoLocationName">
                        <input type="hidden" name="upload_location_photo" value="1">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-neutral-700 dark:text-white mb-2">
                                Selectează o imagine
                            </label>
                            <input type="file" name="location_photo" accept="image/jpeg,image/png,image/webp" required
                                   class="w-full px-3 py-2 border border-neutral-300 dark:border-neutral-700 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closePhotoUploadModal()" 
                                    class="px-4 py-2 text-sm font-medium text-neutral-700 dark:text-white bg-white dark:bg-black border border-neutral-300 dark:border-neutral-700 rounded-md hover:bg-neutral-50 dark:hover:bg-neutral-700">
                                Anulează
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                                Încarcă
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Function to show delete confirmation modal
        function confirmDelete(locationName) {
            document.getElementById('locationToDelete').textContent = locationName;
            document.getElementById('locationIdInput').value = locationName;
            document.getElementById('locationNameInput').value = locationName;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        // Function to close delete confirmation modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Show toasts for success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($successMessage)): ?>
            showToast('<?php echo addslashes($successMessage); ?>', 'success');
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
            showToast('<?php echo addslashes($errorMessage); ?>', 'error');
            <?php endif; ?>
        });

        // Function to open photo upload modal
        function openPhotoUploadModal(locationName) {
            document.getElementById('photoLocationName').value = locationName;
            document.getElementById('photoUploadModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        // Function to close photo upload modal
        function closePhotoUploadModal() {
            document.getElementById('photoUploadModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    </script>
</body>
</html>