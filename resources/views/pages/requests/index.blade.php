@extends('layouts.app')

@section('title', 'Requests')

@section('content')
    @php
        $user = auth()->user();

        $isSales = $user->hasRole('sales');
        $isOperations = $user->hasRole('operation');
        $isSuperAdmin = $user->isSuperAdmin();

        $canCreateRequest = $isSales;
        $canMakeOffer = $isOperations || $isSuperAdmin;

        $requestItems = $requests->getCollection()->map(function ($request) use ($user, $isSales, $isOperations, $isSuperAdmin) {
            // $canViewAllOffers = ($isSales || $isSuperAdmin) && $request->canSeePrices();
            $canViewAllOffers = $request->status->value === 'open';
$canViewOwnOffer = $isOperations || $isSuperAdmin;

            $myOffer = null;

            if ($request->relationLoaded('bids')) {
                $myOffer = $request->bids->firstWhere('user_id', $user->id);
            }

            return [
                'id' => $request->id,
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status->value,
                'status_label' => $request->status_label,
                'status_color' => $request->status_color,
                'created_by' => $request->created_by,
                'creator_name' => $request->creator?->name ?? 'Unknown',
                'closed_at' => $request->closed_at?->toIso8601String(),
                'can_place_bid' => $request->canPlaceBid(),
                'can_see_prices' => $request->canSeePrices(),
                'can_view_all_offers' => $canViewAllOffers,
                'can_view_own_offer' => $canViewOwnOffer,
                'has_my_offer' => (bool) $myOffer,
                'lowest_price' => $canViewAllOffers ? $request->min_price : null,
                'offers_count' => $canViewAllOffers && $request->relationLoaded('bids') ? $request->bids->count() : null,
                'my_offer' => $myOffer ? [
                    'id' => $myOffer->id,
                    'price' => $myOffer->price,
                    'note' => $myOffer->note,
                    'created_at' => $myOffer->created_at?->toIso8601String(),
                ] : null,
                'show_url' => route('requests.show', $request),
                'offer_url' => route('requests.offers.store', $request),
                'delete_url' => route('requests.destroy', $request),
            ];
        })->values();

        $stats = $stats ?? [
            'total' => $requests->total(),
            'open' => $requests->getCollection()->where('status.value', 'open')->count(),
            'closed' => $requests->getCollection()->where('status.value', 'closed')->count(),
        ];
    @endphp

    <div x-data="requestIndexPage(@js($requestItems), @js($stats))" x-init="init()" class="space-y-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Requests</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Tender requests are created by sales and processed by operations.
                </p>
            </div>

            @if($canCreateRequest)
                <button type="button" @click="openCreate()"
                    class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600 sm:w-auto">
                    + Create
                </button>
            @endif
        </div>

        <form method="GET"
            class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-5">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Name
                    </label>
                    <input type="text" name="filter[name]" value="{{ request('filter.name') }}" placeholder="Name..."
                        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Description
                    </label>
                    <input type="text" name="filter[description]" value="{{ request('filter.description') }}"
                        placeholder="Description..."
                        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Status
                    </label>
                    <select name="filter[status]"
                        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">All status</option>
                        <option value="open" @selected(request('filter.status') === 'open')>Open</option>
                        <option value="closed" @selected(request('filter.status') === 'closed')>Closed</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Sales user
                    </label>
                    <select name="filter[sales_user_id]"
                        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">All sales users</option>
                        @foreach($salesUsers as $salesUser)
                            <option value="{{ $salesUser->id }}" @selected((string) request('filter.sales_user_id') === (string) $salesUser->id)>
                                {{ $salesUser->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <button type="submit"
                    class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600 sm:w-auto">
                    Filter
                </button>

                <a href="{{ route('requests.index') }}"
                    class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5 sm:w-auto">
                    Reset
                </a>

                @if($isSales)
                    <a href="{{ route('requests.index', array_merge(request()->query(), ['mine' => 1])) }}"
                        class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5 sm:w-auto">
                        My Requests
                    </a>
                @endif
            </div>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
                <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100" x-text="stats.total"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Open</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400" x-text="stats.open"></div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Closed</div>
                <div class="mt-2 text-3xl font-semibold text-slate-600 dark:text-slate-300" x-text="stats.closed"></div>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                #</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Name</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Creator</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Status</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Info</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        <template x-for="request in requests" :key="request.id">
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-white/5">
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="request.id"></td>

                                <td class="px-4 py-4">
                                    <div class="max-w-[280px] truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                                        x-text="request.name"></div>
                                    <div class="mt-1 max-w-[280px] truncate text-xs text-gray-500 dark:text-gray-400"
                                        x-text="request.description || '-'"></div>
                                </td>

                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300"
                                    x-text="request.creator_name"></td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="statusBadgeClass(request.status)" x-text="request.status_label"></span>
                                </td>

                                <td class="px-4 py-4">
                                    <template x-if="request.can_view_all_offers">
                                        <div class="space-y-1">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                Lowest: <span x-text="request.lowest_price ?? '-'"></span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Offers: <span x-text="request.offers_count ?? 0"></span>
                                            </div>
                                        </div>
                                    </template>

                                    <template
                                        x-if="!request.can_view_all_offers && request.can_view_own_offer && request.my_offer">
                                        <div class="space-y-1">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                My offer: <span x-text="request.my_offer.price"></span>
                                            </div>
                                        </div>
                                    </template>

                                    <template
                                        x-if="!request.can_view_all_offers && (!request.can_view_own_offer || !request.my_offer)">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Hidden</div>
                                    </template>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="openShow(request)"
                                            class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5">
                                            View
                                        </button>

                                        <button type="button" x-show="canOffer(request)" x-cloak @click="openOffer(request)"
                                            class="inline-flex items-center rounded-lg bg-emerald-500 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-600">
                                            Make an Offer
                                        </button>

                                        <button type="button" x-show="canDelete(request)" x-cloak
                                            @click="askDelete(request)"
                                            class="inline-flex items-center rounded-lg bg-red-500 px-3 py-2 text-xs font-semibold text-white hover:bg-red-600">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="requests.length === 0" x-cloak>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                No requests found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $requests->withQueryString()->links() }}
        </div>

        @include('modals.requests.create')
        @include('modals.requests.show')
        @include('modals.requests.offer')
        @include('modals.requests.confirm-delete')
    </div>
@endsection

@push('scripts')
    <script>
        function requestIndexPage(initialRequests = [], initialStats = {}) {
            return {
                requests: initialRequests,
                stats: {
                    total: initialStats.total ?? 0,
                    open: initialStats.open ?? 0,
                    closed: initialStats.closed ?? 0,
                },

                modals: {
                    create: false,
                    show: false,
                    offer: false,
                    delete: false,
                },
                createErrors: {},
                currentRequest: null,
                deleteTarget: null,

                createForm: {
                    name: '',
                    description: '',
                    min_price: '',
                },

                offerForm: {
                    price: '',
                    note: '',
                },

                offerErrors: {},

                loadingShow: false,
                savingCreate: false,
                savingOffer: false,
                deleting: false,

                init() {
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') this.closeAll();
                    });

                    this.$watch('modals', () => {
                        this.toggleBodyScroll();
                    }, { deep: true });

                    this.toggleBodyScroll();
                },
                toggleBodyScroll() {
                    const hasOpenModal =
                        this.modals.create ||
                        this.modals.show ||
                        this.modals.offer ||
                        this.modals.delete;

                    document.body.classList.toggle('overflow-hidden', hasOpenModal);
                },

                closeAll() {
                    this.modals.create = false;
                    this.modals.show = false;
                    this.modals.offer = false;
                    this.modals.delete = false;
                },

                statusBadgeClass(status) {
                    const map = {
                        open: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                        closed: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                    };

                    return map[status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                },

                normalizeRequest(request) {
                    return {
                        ...request,
                        creator_name: request.creator_name ?? 'Unknown',
                        has_my_offer: Boolean(request.has_my_offer ?? request.my_offer),
                        my_offer: request.my_offer ?? null,
                    };
                },

                findRequestIndex(id) {
                    return this.requests.findIndex(item => String(item.id) === String(id));
                },

                upsertRequest(updatedRequest) {
                    const index = this.findRequestIndex(updatedRequest.id);

                    if (index === -1) {
                        this.requests.unshift(this.normalizeRequest(updatedRequest));
                        return;
                    }

                    this.requests.splice(index, 1, {
                        ...this.requests[index],
                        ...this.normalizeRequest(updatedRequest),
                    });
                },

                formatDateTime(value) {
                    if (!value) return '—';

                    const date = new Date(value);
                    if (isNaN(date.getTime())) return '—';

                    return new Intl.DateTimeFormat('en-GB', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                    }).format(date).replace(',', '');
                },

                removeRequest(id) {
                    this.requests = this.requests.filter(item => String(item.id) !== String(id));
                },

                canOffer(request) {
                    return {{ $canMakeOffer ? 'true' : 'false' }}
                        && request.can_place_bid === true
                        && !request.has_my_offer;
                },

                canDelete(request) {
                    return {{ $isSales ? 'true' : 'false' }}
                        && Number(request.created_by) === Number(window.authUserId)
                        && request.status === 'open';
                },

                openCreate() {
                    this.createForm = {
                        name: '',
                        description: '',
                        min_price: '',
                    };

                    this.createErrors = {};
                    this.modals.create = true;
                },

                async saveCreate() {
                    if (!this.createForm.name?.trim()) return;

                    this.savingCreate = true;
                    this.createErrors = {};

                    try {
                        const response = await axios.post("{{ route('requests.store') }}", this.createForm);

                        if (response.data?.data) {
                            const item = this.normalizeRequest(response.data.data);
                            this.requests.unshift(item);
                            this.stats.total += 1;
                            this.stats.open += 1;
                        }

                        this.toastSuccess(response.data?.message || 'Request created successfully.');
                        this.modals.create = false;
                    } catch (error) {
                        if (error.response?.status === 422) {
                            this.createErrors = error.response.data.errors || {};
                            return;
                        }

                        this.toastError(error);
                    } finally {
                        this.savingCreate = false;
                    }
                },

                async openShow(request) {
                    this.currentRequest = this.normalizeRequest(request);
                    this.modals.show = true;
                    this.loadingShow = true;

                    try {
                        const response = await axios.get(request.show_url);

                        if (response.data?.data) {
                            this.currentRequest = this.normalizeRequest(response.data.data);
                            this.upsertRequest(this.currentRequest);
                        }
                    } catch (error) {
                        this.toastError(error);
                    } finally {
                        this.loadingShow = false;
                    }
                },

                openOffer(request) {
                    if (!this.canOffer(request)) return;

                    this.currentRequest = this.normalizeRequest(request);
                    this.offerForm = {
                        price: '',
                        note: '',
                    };
                    this.offerErrors = {};
                    this.modals.offer = true;
                },

                async saveOffer() {
                    if (!this.currentRequest || !this.canOffer(this.currentRequest)) return;

                    this.savingOffer = true;
                    this.offerErrors = {};

                    try {
                        const response = await axios.post(
                            this.currentRequest.offer_url || `/requests/${this.currentRequest.id}/offers`,
                            {
                                price: this.offerForm.price,
                                note: this.offerForm.note,
                            }
                        );

                        if (response.data?.data?.request) {
                            const updated = this.normalizeRequest(response.data.data.request);
                            this.currentRequest = {
                                ...this.currentRequest,
                                ...updated,
                                my_offer: response.data.data.my_bid ?? updated.my_offer ?? this.currentRequest.my_offer,
                                has_my_offer: true,
                            };

                            this.upsertRequest(this.currentRequest);
                        }

                        this.modals.offer = false;
                        this.toastSuccess(response.data?.message || 'Offer saved successfully.');
                    } catch (error) {
                        if (error.response?.status === 422) {
                            const errors = error.response.data.errors || {};

                            Object.keys(errors).forEach(key => {
                                this.offerErrors[key] = errors[key][0];
                            });

                            if (error.response.data?.message) {
                                this.toastError(error);
                            }

                            return;
                        }

                        this.toastError(error);
                    } finally {
                        this.savingOffer = false;
                    }
                },

                askDelete(request) {
                    this.deleteTarget = this.normalizeRequest(request);
                    this.modals.delete = true;
                },

                async deleteRequest() {
                    if (!this.deleteTarget) return;

                    this.deleting = true;

                    try {
                        const response = await axios.delete(this.deleteTarget.delete_url);

                        this.removeRequest(this.deleteTarget.id);
                        this.stats.total = Math.max(0, this.stats.total - 1);

                        if (this.deleteTarget.status === 'open') {
                            this.stats.open = Math.max(0, this.stats.open - 1);
                        } else {
                            this.stats.closed = Math.max(0, this.stats.closed - 1);
                        }

                        this.toastSuccess(response.data?.message || 'Request deleted successfully.');
                        this.modals.delete = false;
                        this.deleteTarget = null;
                    } catch (error) {
                        this.toastError(error);
                    } finally {
                        this.deleting = false;
                    }
                },

                toastSuccess(message) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { type: 'success', message }
                    }));
                },

                toastError(error) {
                    const message =
                        error?.response?.data?.message ||
                        error?.response?.data?.error ||
                        'Something went wrong.';

                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { type: 'error', message }
                    }));
                },
            };
        }

        window.authUserId = @json(auth()->id());
    </script>
@endpush