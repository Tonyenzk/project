<?php
// Start session and include necessary files
session_start();
require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle verification request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_verification'])) {
    try {
        // Check if user already has a pending request
        $stmt = $pdo->prepare("SELECT request_id FROM verification_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$_SESSION['user_id']]);
        $existing_request = $stmt->fetch();

        if ($existing_request) {
            $_SESSION['error'] = "Ai deja o cerere de verificare în așteptare.";
        } else {
            // Insert new verification request
            $stmt = $pdo->prepare("INSERT INTO verification_requests (user_id) VALUES (?)");
            $stmt->execute([$_SESSION['user_id']]);

            // Update user's verifybadge status to pending
            $stmt = $pdo->prepare("UPDATE users SET verifybadge = 'pending' WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            $_SESSION['success'] = "Cererea ta de verificare a fost trimisă cu succes!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "A apărut o eroare. Vă rugăm să încercați din nou.";
    }
    
    // Redirect to prevent form resubmission
    header("Location: verify.php");
    exit();
}

// Get user's verification status
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT verifybadge FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$verification_status = $user['verifybadge'] ?? 'false';
?>

<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <?php include 'includes/header.php'; ?>
   </head>
   <body class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <?php include 'includes/header.php'; ?>
    <?php include 'navbar.php'; ?>
    
    <?php if ($verification_status === 'true'): ?>
    <!-- Verified User Content -->
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl w-full space-y-8">
                <div class="text-center">
                    <div class="relative inline-block transform transition-transform hover:scale-105 duration-300 mb-8">
                        <div class="absolute -inset-4 rounded-full bg-green-500/30 dark:bg-green-500/20 animate-pulse"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-24 w-24 md:h-32 md:w-32 text-green-500 relative drop-shadow-lg">
                            <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-bold text-green-500 tracking-tight mb-6">Felicitări! Ești verificat</h1>
                    <p class="text-lg md:text-xl text-gray-600 dark:text-white max-w-2xl mx-auto leading-relaxed mb-8">
                        Contul tău a fost verificat de Social Land. Insigna de verificare apare lângă numele tău, indicând faptul că Social Land a confirmat că acest cont îndeplinește cerințele noastre de verificare.
                    </p>
                    <div class="flex items-center justify-center gap-3 text-green-500 animate-pulse mb-12">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6 md:h-7 md:w-7">
                            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                            <path d="M5 3v4"></path>
                            <path d="M19 17v4"></path>
                            <path d="M3 5h4"></path>
                            <path d="M17 19h4"></path>
                        </svg>
                        <p class="text-lg font-medium">Bucură-te de funcțiile exclusive verificate</p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6 md:h-7 md:w-7">
                            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                            <path d="M5 3v4"></path>
                            <path d="M19 17v4"></path>
                            <path d="M3 5h4"></path>
                            <path d="M17 19h4"></path>
                        </svg>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="rounded-xl dark:bg-gray-900 border border-green-100 dark:border-green-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-6 w-6 text-green-500">
                                    <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                Insignă de Verificare
                            </h3>
                            <p class="text-gray-600 dark:text-white">Bifa ta albastră este acum vizibilă pentru toată lumea</p>
                        </div>
                    </div>

                    <div class="rounded-xl dark:bg-gray-900 border border-green-100 dark:border-green-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6 text-green-500">
                                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                                    <path d="M5 3v4"></path>
                                    <path d="M19 17v4"></path>
                                    <path d="M3 5h4"></path>
                                    <path d="M17 19h4"></path>
                                </svg>
                                Asistență Prioritară
                            </h3>
                            <p class="text-gray-600 dark:text-white">Primește răspunsuri mai rapide de la echipa noastră de asistență</p>
                        </div>
                    </div>

                    <div class="rounded-xl dark:bg-gray-900 border border-green-100 dark:border-green-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6 text-green-500">
                                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                                    <path d="M5 3v4"></path>
                                    <path d="M19 17v4"></path>
                                    <path d="M3 5h4"></path>
                                    <path d="M17 19h4"></path>
                                </svg>
                                Funcții Exclusiv
                            </h3>
                            <p class="text-gray-600 dark:text-white">Acces la funcții speciale și actualizări timpurii</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php elseif ($verification_status === 'pending'): ?>
    <!-- Pending Verification Content -->
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl w-full space-y-8">
                <div class="text-center">
                    <div class="relative inline-block transform transition-transform hover:scale-105 duration-300 mb-8">
                        <div class="absolute -inset-4 rounded-full bg-orange-500/30 dark:bg-orange-500/20 animate-pulse"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-24 w-24 md:h-32 md:w-32 relative drop-shadow-lg">
                            <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-bold text-orange-500 dark:text-white tracking-tight mb-6">Cerere de Verificare în Așteptare</h1>
                    <p class="text-lg md:text-xl text-gray-600 dark:text-white max-w-2xl mx-auto leading-relaxed mb-8">
                        Cererea ta de verificare este în prezent analizată de echipa noastră. Acest proces durează de obicei 1-3 zile lucrătoare. Te vom notifica odată ce se va lua o decizie.
                    </p>
                    <div class="flex items-center justify-center gap-3 text-orange-500 animate-pulse mb-12">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6 md:h-7 md:w-7">
                            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                            <path d="M5 3v4"></path>
                            <path d="M19 17v4"></path>
                            <path d="M3 5h4"></path>
                            <path d="M17 19h4"></path>
                        </svg>
                        <p class="text-lg font-medium">Bucură-te de funcțiile exclusive verificate</p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6 md:h-7 md:w-7">
                            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                            <path d="M5 3v4"></path>
                            <path d="M19 17v4"></path>
                            <path d="M3 5h4"></path>
                            <path d="M17 19h4"></path>
                        </svg>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="rounded-xl dark:bg-gray-900 border border-orange-100 dark:border-orange-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-6 w-6">
                                    <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                Insignă de Verificare
                            </h3>
                            <p class="text-gray-600 dark:text-white">Bifa ta lbastră este în curs de verificare</p>
                        </div>
                    </div>

                    <div class="rounded-xl dark:bg-gray-900 border border-orange-100 dark:border-orange-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6">
                                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                                    <path d="M5 3v4"></path>
                                    <path d="M19 17v4"></path>
                                    <path d="M3 5h4"></path>
                                    <path d="M17 19h4"></path>
                                </svg>
                                Asistență Prioritară
                            </h3>
                            <p class="text-gray-600 dark:text-white">Primești asistență prioritară în timpul procesului de verificare</p>
                        </div>
                    </div>

                    <div class="rounded-xl dark:bg-gray-900 border border-orange-100 dark:border-orange-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6">
                                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                                    <path d="M5 3v4"></path>
                                    <path d="M19 17v4"></path>
                                    <path d="M3 5h4"></path>
                                    <path d="M17 19h4"></path>
                                </svg>
                                Funcții Exclusiv
                            </h3>
                            <p class="text-gray-600 dark:text-white">Acces la funcții speciale după verificare</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php else: ?>
    <!-- Unverified User Content -->
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl w-full space-y-8">
                <div class="text-center">
                    <div class="relative inline-block transform transition-transform hover:scale-105 duration-300 mb-8">
                        <div class="absolute -inset-4 rounded-full bg-blue-500/30 dark:bg-blue-500/20 animate-pulse"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-24 w-24 md:h-32 md:w-32 text-blue-500 relative drop-shadow-lg">
                            <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-bold text-blue-500 tracking-tight mb-6">Obține Verificare</h1>
                    <p class="text-lg md:text-xl text-gray-600 dark:text-white max-w-2xl mx-auto leading-relaxed mb-8">
                        Solicită o insignă de verificare pentru a le arăta urmăritorilor tăi că ești autentic. Această insignă ajută la distingerea conturilor autentice de conturile fanilor sau de impostori.
                    </p>
                    <div class="flex items-center justify-center gap-3 text-blue-500 animate-pulse mb-12">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6 md:h-7 md:w-7">
                            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                            <path d="M5 3v4"></path>
                            <path d="M19 17v4"></path>
                            <path d="M3 5h4"></path>
                            <path d="M17 19h4"></path>
                        </svg>
                        <p class="text-lg font-medium">Bucură-te de funcțiile exclusive verificate</p>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles h-6 w-6 md:h-7 md:w-7">
                            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"></path>
                            <path d="M5 3v4"></path>
                            <path d="M19 17v4"></path>
                            <path d="M3 5h4"></path>
                            <path d="M17 19h4"></path>
                        </svg>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="rounded-xl dark:bg-gray-900 border border-blue-100 dark:border-blue-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-6 w-6 text-blue-500">
                                    <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                Autentic
                            </h3>
                            <p class="text-gray-600 dark:text-white">Contul tău trebuie să reprezinte o persoană reală, o afacere înregistrată sau o entitate.</p>
                        </div>
                    </div>

                    <div class="rounded-xl dark:bg-gray-900 border border-blue-100 dark:border-blue-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-6 w-6 text-blue-500">
                                    <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                Unic
                            </h3>
                            <p class="text-gray-600 dark:text-white">Contul tău trebuie să fie prezența unică a persoanei sau afacerii pe care o reprezintă.</p>
                        </div>
                    </div>

                    <div class="rounded-xl dark:bg-gray-900 border border-blue-100 dark:border-blue-900/30 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="p-6">
                            <h3 class="font-semibold text-xl flex items-center gap-3 mb-4 text-gray-900 dark:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-6 w-6 text-blue-500">
                                    <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                                Notabil
                            </h3>
                            <p class="text-gray-600 dark:text-white">Contul tău trebuie să fie de interes public, știri, divertisment sau alt domeniu desemnat.</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center mt-8">
                    <form method="POST" action="verify.php">
                        <input type="hidden" name="request_verification" value="1">
                        <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap ring-offset-background disabled:pointer-events-none disabled:opacity-50 h-10 w-full md:w-auto bg-blue-500 hover:bg-blue-600 text-white px-8 md:px-10 py-5 md:py-6 text-base md:text-lg font-semibold rounded-full relative overflow-hidden transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1">Solicită Verificare</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php endif; ?>
   </body>
</html>
