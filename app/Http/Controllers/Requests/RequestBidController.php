<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Requests\Request as TenderRequest;
use App\Models\Requests\RequestBid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestBidController extends Controller
{
    public function store(HttpRequest $httpRequest, TenderRequest $request): JsonResponse
    {
        abort_unless(
            $httpRequest->user()->hasRole('operation') || $httpRequest->user()->isSuperAdmin(),
            403,
            'Only operations users can make offers.'
        );

        abort_unless($request->canPlaceBid(), 422, 'This request is already closed.');

        $data = $httpRequest->validate([
            'price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $bid = DB::transaction(function () use ($httpRequest, $request, $data) {
            $lockedRequest = TenderRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyBid = RequestBid::query()
                ->where('request_id', $lockedRequest->id)
                ->where('user_id', $httpRequest->user()->id)
                ->exists();

            if ($alreadyBid) {
                throw ValidationException::withMessages([
                    'price' => ['You have already submitted an offer for this request.'],
                ]);
            }

            $newBid = RequestBid::create([
                'request_id' => $lockedRequest->id,
                'user_id' => $httpRequest->user()->id,
                'price' => $data['price'],
                'note' => $data['note'] ?? null,
            ]);

            $lowestPrice = RequestBid::query()
                ->where('request_id', $lockedRequest->id)
                ->min('price');

            $lockedRequest->update([
                'min_price' => $lowestPrice,
            ]);

            return $newBid;
        });

        $freshRequest = $request->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Your offer has been saved successfully.',
            'data' => [
                'request' => [
                    'id' => $freshRequest->id,
                    'min_price' => $freshRequest->canViewAllOffersForUser($httpRequest->user()) ? $freshRequest->min_price : null,
                    'can_see_prices' => $freshRequest->canSeePrices(),
                    'can_view_all_offers' => $freshRequest->canViewAllOffersForUser($httpRequest->user()),
                    'can_view_own_offer' => $freshRequest->canViewOwnOfferForUser($httpRequest->user()),
                    'has_my_offer' => true,
                    'my_offer' => [
                        'id' => $bid->id,
                        'price' => $bid->price,
                        'note' => $bid->note,
                        'created_at' => optional($bid->created_at)->toIso8601String(),
                    ],
                ],
                'my_bid' => [
                    'id' => $bid->id,
                    'price' => $bid->price,
                    'note' => $bid->note,
                    'created_at' => optional($bid->created_at)->toIso8601String(),
                ],
            ],
        ]);
    }
}