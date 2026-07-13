<?php

namespace App\Models\Tasks;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRead extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'view_count',
        'first_viewed_at',
        'last_viewed_at',
    ];

    protected $casts = [
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public static function registerView(int $taskId, int $userId): self
{
    $read = self::firstOrCreate(
        [
            'task_id' => $taskId,
            'user_id' => $userId,
        ],
        [
            'view_count' => 1,
            'first_viewed_at' => now(),
            'last_viewed_at' => now(),
        ]
    );

    if (! $read->wasRecentlyCreated) {
        $read->increment('view_count');

        $read->update([
            'last_viewed_at' => now(),
        ]);
    }

    return $read->fresh();
}
}