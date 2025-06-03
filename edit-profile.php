<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $isPrivate = isset($_POST['isPrivate']) ? 1 : 0;
    
    // Validate name length
    if (strlen($name) > 30) {
        $error = "Name must be 30 characters or less";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, bio = ?, isPrivate = ? WHERE user_id = ?");
            $stmt->execute([$name, $bio, $isPrivate, $_SESSION['user_id']]);
            
            // Redirect to profile page after successful update
            header("Location: profile.php");
            exit();
        } catch (PDOException $e) {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT username, name, profile_picture, bio, verifybadge, isPrivate FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    $username = $user['username'];
    $name = $user['name'];
    $profile_picture = $user['profile_picture'] ?: './images/profile_placeholder.webp';
    $bio = $user['bio'];
    $isVerified = $user['verifybadge'] === 'true';
    $isPrivate = $user['isPrivate'] == 1;
} catch (PDOException $e) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
      <?php include 'includes/header.php'; ?>
   </head>
   <body>
   <?php include 'navbar.php'; ?>
<main class="flex-1 bg-white dark:bg-black">
    <div class="container max-w-4xl mx-auto">
        <div class="flex flex-col space-y-4 p-4 pt-12">
            <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-800 pb-4">
                <a href="profile.php" class="flex items-center gap-2 text-sm font-medium text-neutral-600 dark:text-neutral-400 hover:text-black dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left w-4 h-4">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    Înapoi
                </a>
                <div class="flex items-center gap-3">
                    <button onclick="switchTab('profile')" class="px-6 py-2 rounded-full text-sm font-medium transition-colors bg-blue-500 text-white hover:bg-blue-600">Informații profil</button>
                    <button onclick="switchTab('password')" class="px-6 py-2 rounded-full text-sm font-medium transition-colors bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700">Parolă</button>
                    <button onclick="switchTab('blocked')" class="px-6 py-2 rounded-full text-sm font-medium transition-colors bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-700">Utilizatori blocați</button>
                </div>
            </div>
            <div>
                <div class="bg-white dark:bg-black">
                    <div class="flex flex-col">
                        <!-- Profile Tab -->
                        <div id="profile-tab" class="tab-content">
                            <div class="bg-white dark:bg-black p-8 sm:p-10 border-b border-neutral-200/80 dark:border-neutral-800/80">
                            <div class="flex flex-col items-center gap-7 max-w-3xl mx-auto">
                                <div class="relative group">
                                    <div class="relative cursor-pointer overflow-hidden rounded-full">
                                            <span class="relative flex shrink-0 overflow-hidden rounded-full w-28 h-28 sm:w-32 sm:h-32 border-4 border-white dark:border-neutral-800 shadow-xl cursor-pointer transition-transform duration-300 group-hover:scale-105 !rounded-full">
                                            <div class="relative aspect-square h-full w-full rounded-full">
                                                <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                        <img alt="Enzoku's profile picture" referrerpolicy="no-referrer" loading="lazy" decoding="async" data-nimg="fill" class="object-cover rounded-full w-28 h-28 sm:w-32 sm:h-32 border-4 border-white dark:border-neutral-800 shadow-xl cursor-pointer transition-transform duration-300 group-hover:scale-105 !rounded-full" src="<?php echo htmlspecialchars($profile_picture); ?>">
                                                </div>
                                            </div>
                                        </span>
                                        <div class="absolute inset-0 bg-black/40 rounded-full opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200">
                                            <span class="text-white text-sm font-medium">Schimbă poza</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex items-center gap-2">
                                            <h3 class="text-xl font-semibold text-neutral-900 dark:text-white"><?php echo htmlspecialchars($username); ?></h3>
                                        <?php if ($isVerified): ?>
                                        <div class="relative inline-block">
                                            <svg aria-label="Verified" class="text-blue-500" fill="currentColor" height="100%" role="img" viewBox="0 0 40 40" width="100%" style="width: 20px; height: 20px;">
                                                <title>Verified</title>
                                                <path d="M19.998 3.094 14.638 0l-2.972 5.15H5.432v6.354L0 14.64 3.094 20 0 25.359l5.432 3.137v5.905h5.975L14.638 40l5.36-3.094L25.358 40l3.232-5.6h6.162v-6.01L40 25.359 36.905 20 40 14.641l-5.248-3.03v-6.46h-6.419L25.358 0l-5.36 3.094Zm7.415 11.225 2.254 2.287-11.43 11.5-6.835-6.93 2.244-2.258 4.587 4.581 9.18-9.18Z" fill-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isVerified): ?>
                                        <span class="flex items-center gap-1 px-3 py-1 text-sm font-medium text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle2 w-4 h-4">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="m9 12 2 2 4-4"></path>
                                        </svg>
                                        Cont Verificat
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <form class="p-6 sm:p-8" method="POST" action="">
                            <?php if (isset($error)): ?>
                                <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="space-y-8 max-w-3xl mx-auto">
                                <div class="space-y-6">
                                    <div class="flex items-center gap-2">
                                        <span class="relative flex shrink-0 overflow-hidden rounded-full w-5 h-5">
                                            <div class="relative aspect-square h-full w-full rounded-full">
                                                <div class="relative rounded-full overflow-hidden h-full w-full bg-background">
                                                    <img alt="<?php echo htmlspecialchars($username); ?>'s profile picture" referrerpolicy="no-referrer" loading="lazy" decoding="async" data-nimg="fill" class="object-cover rounded-full w-5 h-5" src="<?php echo htmlspecialchars($profile_picture); ?>">
                                                </div>
                                            </div>
                                        </span>
                                            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Informații de bază</h2>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-3 md:gap-8 items-start">
                                            <div class="flex flex-col">
                                                    <label class="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-neutral-900 dark:text-white font-semibold mb-1" for="name">Nume afișat</label>
                                                    <p id="name-description" class="text-neutral-500 dark:text-neutral-400 text-sm">Numele tău public afișat</p>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="relative">
                                                        <input class="flex w-full px-3 py-2 ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 outline-none ring-0 bg-white dark:bg-neutral-900 border-2 border-neutral-200 dark:border-neutral-800 rounded-xl focus-visible:ring-blue-500 text-base h-11 pl-4 pr-10 dark:text-white" placeholder="Numele tău afișat" id="name" aria-describedby="name-description" aria-invalid="false" value="<?php echo htmlspecialchars($name); ?>" name="name" maxlength="30">
                                                </div>
                                                    <p id="name-description" class="text-neutral-500 dark:text-neutral-400 text-sm flex justify-between">
                                                    <span class="hidden sm:inline">Ajută oamenii să-ți descopere contul folosind numele cu care ești cunoscut.</span>
                                                        <span class="text-neutral-400 dark:text-neutral-500 sm:ml-1"><span id="name-char-count"><?php echo strlen($name); ?></span>/30</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-6">
                                    <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-5 h-5 text-neutral-500 dark:text-neutral-400">
                                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                            <line x1="16" x2="16" y1="2" y2="6"></line>
                                            <line x1="8" x2="8" y1="2" y2="6"></line>
                                            <line x1="3" x2="21" y1="10" y2="10"></line>
                                        </svg>
                                            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Biografie și Descriere</h2>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-3 md:gap-8 items-start">
                                            <div class="flex flex-col">
                                                    <label class="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-neutral-900 dark:text-white font-semibold mb-1" for="bio">Biografie</label>
                                                    <p id="bio-description" class="text-neutral-500 dark:text-neutral-400 text-sm">Spune povestea ta</p>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="relative">
                                                        <textarea class="flex w-full ring-offset-background placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 resize-none bg-white dark:bg-neutral-900 border-2 border-neutral-200 dark:border-neutral-800 rounded-xl focus-visible:ring-blue-500 min-h-[140px] text-base p-4 pr-12 dark:text-white" placeholder="Scrie ceva despre tine..." name="bio" id="bio" aria-describedby="bio-description" aria-invalid="false" maxlength="150"><?php echo htmlspecialchars($bio); ?></textarea>
                                                    <div class="absolute right-3 bottom-3">
                                                            <button type="button" aria-label="Add emoji" class="rounded-full p-1.5 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-smile w-5 h-5 text-neutral-600 dark:text-neutral-400 hover:text-neutral-800 dark:hover:text-neutral-200 cursor-pointer transition-colors">
                                                                <circle cx="12" cy="12" r="10"></circle>
                                                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                                                <line x1="9" x2="9.01" y1="9" y2="9"></line>
                                                                <line x1="15" x2="15.01" y1="9" y2="9"></line>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                    <p id="bio-description" class="text-neutral-500 dark:text-neutral-400 text-sm flex justify-between">
                                                    <span>Adaugă o biografie pentru a spune mai multe despre tine (salturile de linie vor fi eliminate)</span>
                                                        <span class="text-neutral-400 dark:text-neutral-500 sm:ml-1"><span id="bio-char-count"><?php echo strlen($bio); ?></span>/150</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-6">
                                    <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe2 w-5 h-5 text-neutral-500 dark:text-neutral-400">
                                            <path d="M21.54 15H17a2 2 0 0 0-2 2v4.54"></path>
                                            <path d="M7 3.34V5a3 3 0 0 0 3 3v0a2 2 0 0 1 2 2v0c0 1.1.9 2 2 2v0a2 2 0 0 0 2-2v0c0-1.1.9-2 2-2h3.17"></path>
                                            <path d="M11 21.95V18a2 2 0 0 0-2-2v0a2 2 0 0 1-2-2v-1a2 2 0 0 0-2-2H2.05"></path>
                                            <circle cx="12" cy="12" r="10"></circle>
                                        </svg>
                                            <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Setări de confidențialitate</h2>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="grid grid-cols-1 md:grid-cols-[240px_1fr] gap-3 md:gap-8 items-start">
                                            <div class="flex flex-col">
                                                    <label class="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-neutral-900 dark:text-white font-semibold mb-1" for="privacy">Confidențialitatea contului</label>
                                                    <p id="privacy-description" class="text-neutral-500 dark:text-neutral-400 text-sm">Controlează-ți vizibilitatea</p>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <div class="space-y-0.5">
                                                        <div class="text-sm font-medium text-neutral-900 dark:text-white">Cont privat</div>
                                                        <div class="text-sm text-neutral-500 dark:text-neutral-400">Oricine îți poate vedea fotografiile și videoclipurile</div>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" name="isPrivate" class="sr-only peer" <?php echo $isPrivate ? 'checked' : ''; ?>>
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <div class="flex justify-end pt-6 border-t border-neutral-200 dark:border-neutral-800">
                                    <button class="inline-flex items-center justify-center whitespace-nowrap text-sm ring-offset-background disabled:pointer-events-none disabled:opacity-50 bg-primary hover:bg-primary/90 h-10 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-8 py-2.5 rounded-xl font-medium shadow-sm disabled:from-blue-500/50 disabled:to-blue-600/50 disabled:cursor-not-allowed transition-all duration-300" type="submit">Salvează</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Blocked Users Tab -->
                        <div id="blocked-tab" class="tab-content hidden">
                            <div class="bg-white dark:bg-black p-8 sm:p-10">
                                <div class="max-w-3xl mx-auto">
                                    <div class="flex flex-col items-center justify-center py-12 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ban w-12 h-12 text-neutral-500 dark:text-neutral-400 mb-4">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="4.93" y1="19.07" x2="19.07" y2="4.93"></line>
                                        </svg>
                                        <h2 class="text-2xl font-semibold mb-2 text-neutral-900 dark:text-white">Nu ai utilizatori blocați momentan</h2>
                                        <p class="text-neutral-500 dark:text-neutral-400 max-w-sm">Când blochezi pe cineva, aceștia nu vor mai putea să-ți vadă profilul sau să-ți trimită mesaje.</p>
                                    </div>
                                    
                                    <!-- Blocked Users List (will be populated dynamically) -->
                                    <div id="blocked-users-list" class="space-y-4 mt-8 hidden">
                                        <!-- Example blocked user (will be populated dynamically) -->
                                        <div class="flex items-center justify-between p-4 bg-neutral-50 dark:bg-neutral-900 rounded-xl">
                                            <div class="flex items-center gap-3">
                                                <div class="relative w-10 h-10 rounded-full overflow-hidden">
                                                    <img src="images/profile_placeholder.webp" alt="User avatar" class="w-full h-full object-cover">
                                                </div>
                                                <div>
                                                    <h3 class="font-medium text-neutral-900 dark:text-white">Username</h3>
                                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">@username</p>
                                                </div>
                                            </div>
                                            <button onclick="unblockUser(userId)" class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                Deblochează
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const bioTextarea = document.getElementById('bio');
    const nameCharCount = document.getElementById('name-char-count');
    const bioCharCount = document.getElementById('bio-char-count');

    // Update character counts
    function updateCharCount(input, counter) {
        counter.textContent = input.value.length;
    }

    // Add input event listeners
    nameInput.addEventListener('input', () => updateCharCount(nameInput, nameCharCount));
    bioTextarea.addEventListener('input', () => updateCharCount(bioTextarea, bioCharCount));
});

function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Show selected tab
    document.getElementById(tab + '-tab').classList.remove('hidden');
    
    // Update button styles
    const buttons = document.querySelectorAll('.flex.items-center.gap-3 button');
    buttons.forEach(button => {
        if (button.textContent.toLowerCase().includes(tab)) {
            button.classList.remove('bg-neutral-100', 'dark:bg-neutral-800', 'text-neutral-600', 'dark:text-neutral-400');
            button.classList.add('bg-blue-500', 'text-white');
        } else {
            button.classList.remove('bg-blue-500', 'text-white');
            button.classList.add('bg-neutral-100', 'dark:bg-neutral-800', 'text-neutral-600', 'dark:text-neutral-400');
        }
    });

    // If blocked tab is selected, fetch blocked users
    if (tab === 'blocked') {
        fetchBlockedUsers();
    }
}

function fetchBlockedUsers() {
    fetch('get_blocked_users.php')
        .then(response => response.json())
        .then(data => {
            const blockedList = document.getElementById('blocked-users-list');
            const emptyState = document.querySelector('#blocked-tab .flex.flex-col.items-center');
            
            if (data.success && data.blocked_users.length > 0) {
                // Show the list and hide empty state
                blockedList.classList.remove('hidden');
                emptyState.classList.add('hidden');
                
                // Clear existing list
                blockedList.innerHTML = '';
                
                // Add each blocked user to the list
                data.blocked_users.forEach(user => {
                    const userElement = document.createElement('div');
                    userElement.className = 'flex items-center justify-between p-4 bg-neutral-50 dark:bg-neutral-900 rounded-xl';
                    userElement.setAttribute('data-user-id', user.user_id);
                    
                    userElement.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="relative w-10 h-10 rounded-full overflow-hidden">
                                <img src="${user.profile_picture || 'images/profile_placeholder.webp'}" alt="${user.username}'s avatar" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h3 class="font-medium text-neutral-900 dark:text-white">${user.name || user.username}</h3>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">@${user.username}</p>
                            </div>
                        </div>
                        <button onclick="unblockUser(${user.user_id})" class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                            Deblochează
                        </button>
                    `;
                    
                    blockedList.appendChild(userElement);
                });
            } else {
                // Show empty state and hide list
                blockedList.classList.add('hidden');
                emptyState.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error fetching blocked users:', error);
        });
}

function unblockUser(userId) {
    // Add confirmation dialog
    if (confirm('Ești sigur că vrei să deblochezi acest utilizator?')) {
        fetch('unblock_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the user from the list
                const userElement = document.querySelector(`[data-user-id="${userId}"]`);
                if (userElement) {
                    userElement.remove();
                }
                
                // If no more blocked users, show the empty state
                const blockedList = document.getElementById('blocked-users-list');
                if (blockedList.children.length === 0) {
                    blockedList.classList.add('hidden');
                    document.querySelector('#blocked-tab .flex.flex-col.items-center').classList.remove('hidden');
                }
            } else {
                alert('A apărut o eroare la deblocarea utilizatorului. Te rugăm să încerci din nou.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('A apărut o eroare la deblocarea utilizatorului. Te rugăm să încerci din nou.');
        });
    }
}

// Initialize with profile tab
document.addEventListener('DOMContentLoaded', function() {
    switchTab('profile');
});
</script>
   </body>
</html>

