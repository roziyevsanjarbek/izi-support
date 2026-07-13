<div id="queryModal" class="fixed inset-0 z-[99990] hidden items-center justify-center">
    <div id="queryModalOverlay" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    <div class="relative w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-800">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Query Details</h2>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Full CRM-style record view</p>
            </div>

            <button
                type="button"
                data-close-modal="queryModal"
                class="rounded-md border border-gray-200 px-2 py-1 text-[11px] text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                x
            </button>
        </div>

        <div class="max-h-[72vh] overflow-y-auto p-3">
            <div id="queryModalBody" class="space-y-2"></div>
        </div>
    </div>
</div>

<div id="taskModal" class="fixed inset-0 z-[99990] hidden items-center justify-center">
    <div id="taskModalOverlay" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    <div class="relative w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-200 px-3 py-2 dark:border-gray-800">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Add Task</h2>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Create task from this query</p>
            </div>

            <button
                type="button"
                data-close-modal="taskModal"
                class="rounded-md border border-gray-200 px-2 py-1 text-[11px] text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                x
            </button>
        </div>

        <div class="max-h-[72vh] overflow-y-auto p-3">
            <input type="hidden" id="taskQueryId">
            <input type="hidden" id="taskOperationId">

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="taskName" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Task Name</label>
                    <input
                        type="text"
                        id="taskName"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        placeholder="Enter task name">
                    <p id="taskNameError" class="mt-1 hidden text-xs text-red-500"></p>
                </div>

                <div>
                    <label for="taskDescription" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea
                        id="taskDescription"
                        rows="4"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        placeholder="Enter task description"></textarea>
                    <p id="taskDescriptionError" class="mt-1 hidden text-xs text-red-500"></p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Attachments</label>
                    <input
                        type="file"
                        id="taskAttachments"
                        multiple
                        class="block w-full cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:file:bg-white/5 dark:file:text-gray-200">


                    <div id="taskAttachmentsList" class="mt-3 space-y-2"></div>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        data-close-modal="taskModal"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Cancel
                    </button>

                    <button
                        type="button"
                        id="saveTaskBtn"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Save Task
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>