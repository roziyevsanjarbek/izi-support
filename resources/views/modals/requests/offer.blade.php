<div
    x-cloak
    x-show="modals.offer"
    x-transition.opacity
    class="fixed inset-0 z-[99990]"
>
    <div class="fixed inset-0 bg-black/60 backdrop-blur-md" @click="modals.offer = false"></div>

    <div class="relative z-[99991] flex min-h-[100dvh] items-center justify-center p-4">
        <div
            x-transition
            class="w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900 max-h-[90dvh]"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Make an Offer</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Submit your bid for this request.
                    </p>
                </div>

                <button
                    type="button"
                    @click="modals.offer = false"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200"
                >
                    ✕
                </button>
            </div>

            <template x-if="currentRequest">
                <div class="mt-6">
                    <div class="mb-4 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Request</div>
                        <div class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100" x-text="currentRequest.name"></div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Price
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                x-model="offerForm.price"
                                :class="offerErrors.price ? 'border-red-500 focus:border-red-500' : 'border-gray-200 dark:border-gray-700'"
                                class="h-11 w-full rounded-xl border bg-white px-4 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:bg-gray-800 dark:text-gray-100"
                                placeholder="Enter your offer"
                            >

                            <p
                                x-show="offerErrors.price"
                                x-text="offerErrors.price"
                                class="mt-2 text-sm text-red-600"
                            ></p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    @click="modals.offer = false"
                    class="h-11 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="saveOffer()"
                    :disabled="savingOffer || !offerForm.price"
                    class="h-11 rounded-xl bg-emerald-500 px-4 text-sm font-semibold text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span x-show="!savingOffer">Submit Offer</span>
                    <span x-show="savingOffer" x-cloak>Submitting...</span>
                </button>
            </div>
        </div>
    </div>
</div>