<div
    x-cloak
    x-show="modals.upgrade"
    x-transition.opacity
    class="fixed inset-0 z-[60]"
>
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal('upgrade')"></div>

    <div class="relative flex min-h-full items-center justify-center p-4">
        <div
            x-transition
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-800 dark:bg-gray-900"
        >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Upgrade / info modal</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Bu yerga keyin pro, priority, assign, yoki boshqa task meta ma'lumotlarini qo'yasan.
            </p>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    @click="closeModal('upgrade')"
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>