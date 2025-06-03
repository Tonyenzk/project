<!-- Report Modal -->
<div id="reportModal" class="fixed inset-0 z-50 hidden" style="z-index: 9999;">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeReportModal()"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center" onclick="closeReportModal()">
        <div class="bg-white dark:bg-black w-[400px] max-h-[70vh] flex flex-col rounded-xl overflow-hidden" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center p-4 border-b border-neutral-200 dark:border-neutral-800">
                <div class="w-10"></div>
                <h2 class="font-semibold text-center">Report <span id="reportTypeTitle">Post</span></h2>
                <button onclick="closeReportModal()" class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-neutral-100 dark:hover:bg-neutral-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x h-5 w-5">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <form id="reportForm" class="space-y-4">
                    <input type="hidden" id="reportType" name="type" value="post">
                    <input type="hidden" id="reportId" name="id">
                    <div>
                        <label for="reportDescription" class="block text-sm font-medium mb-2 text-black dark:text-white">Why are you reporting this <span id="reportTypeLabel">post</span>? (max 150 characters)</label>
                        <textarea id="reportDescription" name="description" maxlength="150" rows="3" class="w-full px-3 py-2 border border-neutral-200 dark:border-neutral-800 rounded-lg bg-white dark:bg-black text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none placeholder-neutral-400 dark:placeholder-neutral-600" placeholder="Please provide details about your report..."></textarea>
                        <div class="text-right text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                            <span id="charCount">0</span>/150
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                        Submit Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</div> 