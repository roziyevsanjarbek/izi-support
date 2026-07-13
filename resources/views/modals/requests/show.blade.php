<div x-cloak x-show="modals.show" x-transition.opacity class="fixed inset-0 z-[99990]">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="modals.show = false"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div x-transition
            class="w-full max-w-3xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="min-w-0">
                    <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Request Details
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        View request info and offers.
                    </p>
                </div>

                <button type="button" @click="modals.show = false"
                    class="shrink-0 rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200">
                    ✕
                </button>
            </div>

            <div class="max-h-[80vh] overflow-y-auto px-5 py-5">
                <template x-if="currentRequest">
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="currentRequest.name"></div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="currentRequest.status_label"></div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Description
                            </div>

                            <div class="mt-2 max-h-64 overflow-y-auto whitespace-pre-wrap break-words rounded-xl bg-gray-50 p-3 text-sm leading-6 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300"
                                x-text="currentRequest.description || '—'"></div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Creator
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="currentRequest.creator_name"></div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Created At
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="formatDateTime(currentRequest.created_at)"></div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Closes At
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="formatDateTime(currentRequest.closed_at)"></div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Lowest Price
                                </div>
                                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    x-text="currentRequest.lowest_price ?? '-'"></div>
                            </div>
                        </div>

                        <template x-if="currentRequest.can_view_own_offer && currentRequest.my_offer">
                            <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">My Offer</h4>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Created: <span x-text="formatDateTime(currentRequest.my_offer.created_at)"></span>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/5">
                                    <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-400"
                                        x-text="currentRequest.my_offer.price"></div>

                                    {{-- <div class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                                        x-text="currentRequest.my_offer.note || 'No note provided.'"></div> --}}
                                </div>
                            </div>
                        </template>

                        <template x-if="currentRequest.can_view_all_offers">
                            <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Offers</h4>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Total: <span x-text="(currentRequest.offers || []).length"></span>
                                    </div>
                                </div>

                                <div class="max-h-[34vh] space-y-3 overflow-y-auto pr-1">
                                    <template x-for="offer in (currentRequest.offers || [])" :key="offer.id">
                                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/5">
                                            <div class="flex flex-wrap items-start justify-between gap-2">
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                                        x-text="offer.user_name"></div>
                                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                                        x-text="formatDateTime(offer.created_at)"></div>
                                                </div>

                                                <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-400"
                                                    x-text="offer.price"></div>
                                            </div>
                                        </div>
                                    </template>

                                    <div x-show="(currentRequest.offers || []).length === 0" x-cloak
                                        class="rounded-xl border border-dashed border-gray-300 p-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                        No offers found.
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template
                            x-if="!currentRequest.can_view_all_offers && !(currentRequest.can_view_own_offer && currentRequest.my_offer)">
                            <div
                                class="rounded-2xl border border-dashed border-gray-300 p-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                -
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>