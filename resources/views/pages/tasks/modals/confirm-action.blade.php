<div
    x-cloak
    x-show="modals.confirm"
    x-transition.opacity
    class="fixed inset-0 z-[99990] "
>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-md" @click="closeModal('confirm')"></div>

    <div class="relative z-[99991] flex min-h-[100dvh] items-center justify-center p-4">
        <div
            x-transition
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="confirmTitle"></h3>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400" x-text="confirmMessage"></p>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    @click="closeModal('confirm')"
                    class="h-11 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="submitAction()"
                    class="h-11 rounded-xl bg-emerald-500 px-4 text-sm font-semibold text-white hover:bg-emerald-600"
                >
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>