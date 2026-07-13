<div
    x-cloak
    x-show="modals.delete"
    x-transition.opacity
    class="fixed inset-0 z-[99990] "
>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-md" @click="modals.delete = false"></div>

    <div class="relative z-[99991] flex min-h-[100dvh] items-center justify-center p-4">
        <div
            x-transition
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Delete Request</h3>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                Are you sure you want to delete this request? This action cannot be undone.
            </p>

            <template x-if="deleteTarget">
                <div class="mt-4 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Request</div>
                    <div class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100" x-text="deleteTarget.name"></div>
                </div>
            </template>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    @click="modals.delete = false"
                    class="h-11 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="deleteRequest()"
                    :disabled="deleting"
                    class="h-11 rounded-xl bg-red-500 px-4 text-sm font-semibold text-white hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span x-show="!deleting">Delete</span>
                    <span x-show="deleting" x-cloak>Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>