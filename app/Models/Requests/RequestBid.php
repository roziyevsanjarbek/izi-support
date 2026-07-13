<?php

namespace App\Models\Requests;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestBid extends Model
{
    protected $fillable = [
        'request_id',
        'user_id',
        'price',
        'note',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}