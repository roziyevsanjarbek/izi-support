<?php

namespace App\Models\Requests;

use App\Enums\RequestStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'description',
        'prices_visible_at',
        'closed_at',
        'min_price',
        'status',
    ];

    protected $casts = [
        'prices_visible_at' => 'datetime',
        'closed_at' => 'datetime',
        'status' => RequestStatus::class,
        'min_price' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(RequestBid::class);
    }

    public function isOpen(): bool
    {
        return $this->status === RequestStatus::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === RequestStatus::CLOSED;
    }

    public function isExpired(): bool
    {
        return $this->closed_at !== null && now()->gte($this->closed_at);
    }

    public function canPlaceBid(): bool
    {
        return $this->isOpen() && !$this->isExpired();
    }

    

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function getLowestPriceAttribute(): ?float
{
    if (! $this->canSeePrices()) {
        return null;
    }

    return $this->min_price;
}

    public function scopeOpen($query)
    {
        return $query->where('status', RequestStatus::OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', RequestStatus::CLOSED);
    }
    public function hideOffersForOperations(): bool
    {
        return (bool) config('requests.hide_offers_for_operations', true);
    }




    public function canSeePrices(): bool
    {
        return $this->isClosed() || ($this->closed_at && now()->gte($this->closed_at));
    }

    public function canViewAllOffersForUser(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasRole('sales') && $this->canSeePrices();
    }

    public function canViewOwnOfferForUser(User $user): bool
    {
        return $user->hasRole('operation') || $user->isSuperAdmin();
    }

}