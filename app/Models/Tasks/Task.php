<?php

namespace App\Models\Tasks;

use App\Models\Images\Attachment;
use App\Models\Messages\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Task extends Model
{
    protected $fillable = [
        'completed_by',
        'rejected_by',
        'created_by',

        'query_id',
        'custom_id',

        'conversation_id',
        'assigned_to',

        'name',
        'description',
        'type',
        'status',

        'priority',
        'start_date',
        'end_date',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'meta' => 'array',
    ];

    // public function team()
    // {
    //     return $this->belongsTo(Team::class);
    // }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function reads()
    {
        return $this->hasMany(\App\Models\Tasks\TaskRead::class);
    }

    public function readByUsers()
    {
        return $this->belongsToMany(\App\Models\User::class, 'task_reads')
            ->withPivot(['view_count', 'first_viewed_at', 'last_viewed_at'])
            ->withTimestamps();
    }
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
    public function gallery(): MorphMany
{
    return $this->morphMany(Attachment::class, 'attachable')
        ->where('collection', 'gallery')
        ->orderBy('order');
}
public function attachments(): MorphMany
{
    return $this->morphMany(Attachment::class, 'attachable');
}
}
