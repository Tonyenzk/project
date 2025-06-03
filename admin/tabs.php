<?php
// Include header for theme support
require_once '../includes/header.php';
?>
<div class="w-full">
    <div role="tablist" aria-orientation="horizontal" class="items-center justify-center text-muted-foreground w-full h-auto p-1.5 bg-gray-100 dark:bg-black border border-neutral-200 dark:border-neutral-800 grid grid-flow-col auto-cols-fr gap-1.5 rounded-xl">
        <!-- Dashboard Tab -->
        <a class="focus:outline-none" href="../admin/dashboard.php">
            <button type="button" role="tab" aria-selected="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? 'true' : 'false'; ?>" data-state="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'dashboard.php' ? 'active' : 'inactive'; ?>" class="whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:shadow-sm w-full flex items-center justify-center gap-2 px-4 py-2.5 data-[state=active]:bg-gradient-to-br data-[state=active]:from-indigo-500 data-[state=active]:to-blue-600 data-[state=active]:text-white data-[state=active]:border-none rounded-lg transition-all duration-200 hover:bg-gray-200 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 font-medium text-sm"
            data-orientation="horizontal">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard h-4 w-4">
                    <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                    <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                    <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                    <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                </svg>
                <span>Panou de administrare</span>
            </button>
        </a>

        <!-- Users Management Tab -->
        <a class="focus:outline-none" href="../admin/users.php">
            <button type="button" role="tab" aria-selected="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'users.php' ? 'true' : 'false'; ?>" data-state="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'users.php' ? 'active' : 'inactive'; ?>" class="whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:shadow-sm w-full flex items-center justify-center gap-2 px-4 py-2.5 data-[state=active]:bg-gradient-to-br data-[state=active]:from-fuchsia-500 data-[state=active]:to-purple-600 data-[state=active]:text-white data-[state=active]:border-none rounded-lg transition-all duration-200 hover:bg-gray-200 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 font-medium text-sm"
            data-orientation="horizontal">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users h-4 w-4">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Utilizatori</span>
            </button>
        </a>

        <!-- Verification Tab - Only visible to Master_Admin -->
        <?php if ($user['role'] === 'Master_Admin'): ?>
        <a class="focus:outline-none" href="../admin/verify.php">
            <button type="button" role="tab" aria-selected="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'verify.php' ? 'true' : 'false'; ?>" data-state="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'verify.php' ? 'active' : 'inactive'; ?>" class="whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:shadow-sm w-full flex items-center justify-center gap-2 px-4 py-2.5 data-[state=active]:bg-gradient-to-br data-[state=active]:from-emerald-400 data-[state=active]:to-green-600 data-[state=active]:text-white data-[state=active]:border-none rounded-lg transition-all duration-200 hover:bg-gray-200 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 font-medium text-sm"
            data-orientation="horizontal">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check h-4 w-4">
                    <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span>Cereri de Verificare</span>
            </button>
        </a>
        <?php endif; ?>

        <!-- Locations Tab -->
        <a class="focus:outline-none" href="../admin/locations.php">
            <button type="button" role="tab" aria-selected="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'locations.php' ? 'true' : 'false'; ?>" data-state="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'locations.php' ? 'active' : 'inactive'; ?>" class="whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:shadow-sm w-full flex items-center justify-center gap-2 px-4 py-2.5 data-[state=active]:bg-gradient-to-br data-[state=active]:from-green-400 data-[state=active]:to-teal-600 data-[state=active]:text-white data-[state=active]:border-none rounded-lg transition-all duration-200 hover:bg-gray-200 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 font-medium text-sm"
            data-orientation="horizontal">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin h-4 w-4">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span>Locații</span>
            </button>
        </a>

        <!-- Reports Tab -->
        <a class="focus:outline-none" href="../admin/reports.php">
            <button type="button" role="tab" aria-selected="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'reports.php' ? 'true' : 'false'; ?>" data-state="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'reports.php' ? 'active' : 'inactive'; ?>" class="whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:shadow-sm w-full flex items-center justify-center gap-2 px-4 py-2.5 data-[state=active]:bg-gradient-to-br data-[state=active]:from-orange-400 data-[state=active]:to-red-600 data-[state=active]:text-white data-[state=active]:border-none rounded-lg transition-all duration-200 hover:bg-gray-200 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 font-medium text-sm"
            data-orientation="horizontal">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flag h-4 w-4">
                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                    <line x1="4" x2="4" y1="22" y2="15"></line>
                </svg>
                <span>Rapoarte</span>
            </button>
        </a>

        <!-- Maintenance Tab - Only visible to Master_Admin -->
        <?php if ($user['role'] === 'Master_Admin'): ?>
        <a class="focus:outline-none" href="../admin/maintenance.php">
            <button type="button" role="tab" aria-selected="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'maintenance.php' ? 'true' : 'false'; ?>" data-state="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'maintenance.php' ? 'active' : 'inactive'; ?>" class="whitespace-nowrap ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:shadow-sm w-full flex items-center justify-center gap-2 px-4 py-2.5 data-[state=active]:bg-gradient-to-br data-[state=active]:from-yellow-400 data-[state=active]:to-orange-600 data-[state=active]:text-white data-[state=active]:border-none rounded-lg transition-all duration-200 hover:bg-gray-200 dark:hover:bg-neutral-800/50 text-neutral-700 dark:text-neutral-300 font-medium text-sm"
            data-orientation="horizontal">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench h-4 w-4">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
                <span>Mentenanță</span>
            </button>
        </a>
        <?php endif; ?>
    </div>
</div>