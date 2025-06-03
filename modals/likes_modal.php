<!-- Likes Modal -->
<div id="likesModal" class="fixed inset-0 z-50 hidden" style="z-index: 9999;">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeLikesModal()"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center" onclick="closeLikesModal()">
        <div class="bg-white dark:bg-black w-[400px] max-h-[70vh] flex flex-col rounded-xl overflow-hidden" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center p-4 border-b border-neutral-200 dark:border-neutral-800">
                <div class="w-10"></div>
                <h2 class="font-semibold text-center">Likes</h2>
                <button onclick="closeLikesModal()" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-neutral-100 dark:hover:bg-neutral-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-5 w-5">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="likesContainer" class="overflow-y-auto flex-1 p-2">
                <div class="flex justify-center p-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-neutral-900 dark:border-neutral-100"></div>
                </div>
            </div>
            <div id="likesLoader" class="py-4 text-center border-t border-neutral-200 dark:border-neutral-800 hidden">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-neutral-900 dark:border-neutral-100 mx-auto"></div>
            </div>
        </div>
    </div>
</div>
