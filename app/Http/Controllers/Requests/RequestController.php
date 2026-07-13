<?php

namespace App\Http\Controllers\Requests;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Requests\Request as TenderRequest;
use App\Models\User;
use App\Services\Telegram\TenderRequestNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RequestController extends Controller
{
    public function index(HttpRequest $httpRequest): View
    {
        $this->closeExpiredRequests();

        $user = $httpRequest->user();
        $isSales = $user->hasRole('sales');
        $isOperation = $user->hasRole('operation');
        $isSuperAdmin = $user->isSuperAdmin();

        $filter = $httpRequest->input('filter', []);

        $query = TenderRequest::query()
            ->with(['creator:id,name'])
            ->latest();

        if ($isSales || $isSuperAdmin) {
            $query->with(['bids.user:id,name']);
        } elseif ($isOperation) {
            $query->with([
                'bids' => function ($q) use ($user) {
                    $q->where('user_id', $user->id)->latest('id');
                }
            ]);
        }

        if (!empty($filter['name'])) {
            $query->where('name', 'like', '%' . trim($filter['name']) . '%');
        }

        if (!empty($filter['description'])) {
            $query->where('description', 'like', '%' . trim($filter['description']) . '%');
        }

        if (!empty($filter['status'])) {
            if ($filter['status'] === 'open') {
                $query->where('status', RequestStatus::OPEN);
            }

            if ($filter['status'] === 'closed') {
                $query->where('status', RequestStatus::CLOSED);
            }
        }

        if (!empty($filter['sales_user_id'])) {
            $query->where('created_by', (int) $filter['sales_user_id']);
        }

        if ($httpRequest->boolean('mine') && $isSales) {
            $query->where('created_by', $user->id);
        }

        $requests = $query->paginate(10)->withQueryString();

        $salesUsers = User::query()
            ->whereHas('role', function ($q) {
                $q->where('name', 'sales');
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('pages.requests.index', [
            'requests' => $requests,
            'salesUsers' => $salesUsers,
            'canCreateRequest' => $isSales, // superadmin removed
            'makeAnOffer' => $isOperation,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function store(HttpRequest $httpRequest,TenderRequestNotificationService $notificationService): JsonResponse {
        abort_unless($httpRequest->user()->hasRole('sales'), 403, 'Only sales users can create requests.');

        $data = $httpRequest->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $request = DB::transaction(function () use ($httpRequest, $data, $notificationService) {
            $request = TenderRequest::create([
                'created_by' => $httpRequest->user()->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'min_price' => $data['min_price'] ?? null,
                'status' => RequestStatus::OPEN,
                'closed_at' => now()->addMinutes(30),
            ]);

            $notificationService->scheduleForTenderRequest($request, $httpRequest->user()->id);

            return $request;
        });

        return response()->json([
            'success' => true,
            'message' => 'Request created successfully. It will close automatically in 30 minutes.',
            'data' => $request->load('creator:id,name'),
        ]);
    }

    public function show(HttpRequest $httpRequest, TenderRequest $request): JsonResponse
    {
        $this->closeExpiredRequests();

        $user = $httpRequest->user();

        $request->load(['creator:id,name']);

        $canViewAllOffers = true;
        $canViewOwnOffer = $request->canViewOwnOfferForUser($user);

        $myOffer = null;

        if ($canViewOwnOffer) {
            $bid = $request->bids()
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            if ($bid) {
                $myOffer = [
                    'id' => $bid->id,
                    'price' => $bid->price,
                    'note' => $bid->note,
                    'created_at' => optional($bid->created_at)->toIso8601String(),
                ];
            }
        }

        $offers = [];
        $offersCount = null;

        if ($canViewAllOffers) {
            $request->load(['bids.user:id,name']);

            $offers = $request->bids->map(function ($bid) {
                return [
                    'id' => $bid->id,
                    'user_name' => $bid->user?->name,
                    'price' => $bid->price,
                    'note' => $bid->note,
                    'created_at' => optional($bid->created_at)->toIso8601String(),
                ];
            })->values();

            $offersCount = $request->bids->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $request->id,
                'name' => $request->name,
                'description' => $request->description,
                'created_at' => optional($request->created_at)->toIso8601String(),
                'status' => $request->status->value,
                'status_label' => $request->status_label,
                'status_color' => $request->status_color,
                'created_by' => $request->created_by,
                'creator_name' => $request->creator?->name,
                'closed_at' => optional($request->closed_at)->toIso8601String(),
                'can_place_bid' => $request->canPlaceBid(),
                'can_see_prices' => $request->canSeePrices(),
                'can_view_all_offers' => $canViewAllOffers,
                'can_view_own_offer' => $canViewOwnOffer,
                'has_my_offer' => (bool) $myOffer,
                'my_offer' => $myOffer,
                'lowest_price' => $canViewAllOffers ? $request->min_price : null,
                'offers_count' => $offersCount,
                'offers' => $offers,
                'show_url' => route('requests.show', $request),
                'offer_url' => route('requests.offers.store', $request),
                'delete_url' => route('requests.destroy', $request),
            ],
        ]);
    }

    public function destroy(HttpRequest $httpRequest, TenderRequest $request): JsonResponse
    {
        abort_unless($httpRequest->user()->hasRole('sales'), 403, 'Only sales users can delete requests.');
        abort_unless($request->created_by === $httpRequest->user()->id, 403, 'You can only delete your own requests.');
        abort_unless($request->isOpen() && !$request->isExpired(), 422, 'Closed requests cannot be deleted.');

        $request->delete();

        return response()->json([
            'success' => true,
            'message' => 'Request deleted successfully.',
        ]);
    }

    private function closeExpiredRequests(): void
    {
        TenderRequest::query()
            ->where('status', RequestStatus::OPEN)
            ->whereNotNull('closed_at')
            ->where('closed_at', '<=', now())
            ->update([
                'status' => RequestStatus::CLOSED,
            ]);
    }
}