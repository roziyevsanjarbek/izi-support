<?php

namespace App\Models\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        // 'value',
        'allowed',
    ];

    protected $casts = [
        'allowed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}