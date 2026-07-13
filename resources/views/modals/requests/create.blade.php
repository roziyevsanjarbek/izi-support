<div
    x-cloak
    x-show="modals.create"
    x-transition.opacity
    class="fixed inset-0 z-[99990] "
>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-md" @click="modals.create = false"></div>

    <div class="relative z-[99991] flex min-h-[100dvh] items-center justify-center p-4">
        <div
            x-transition
            class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900 max-h-[90dvh] "
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create Request</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Create a new request. It will close automatically in 30 minutes.
                    </p>
                </div>

                <button
                    type="button"
                    @click="modals.create = false"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200"
                >
                    ✕
                </button>
            </div>

            <div class="mt-6 space-y-4">
                <div>
    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
    <input
        type="text"
        x-model="createForm.name"
        @input="delete createErrors.name"
        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
        :class="{ 'border-red-500 focus:border-red-500': createErrors.name }"
        placeholder="Request name"
    >

    <p x-show="createErrors.name" x-text="createErrors.name?.[0]" class="mt-1 text-sm text-red-500"></p>
</div>

                <div>
    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
    <textarea
        x-model="createForm.description"
        @input="delete createErrors.description"
        rows="6"
        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
        :class="{ 'border-red-500 focus:border-red-500': createErrors.description }"
        placeholder="Request description"
    ></textarea>

    <p x-show="createErrors.description" x-text="createErrors.description?.[0]" class="mt-1 text-sm text-red-500"></p>
</div>

                {{-- <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum price</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        x-model="createForm.min_price"
                        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        placeholder="Optional"
                    >
                </div> --}}
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    @click="modals.create = false"
                    class="h-11 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="saveCreate()"
                    :disabled="savingCreate || !createForm.name.trim()"
                    class="h-11 rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span x-show="!savingCreate">Save</span>
                    <span x-show="savingCreate" x-cloak>Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>