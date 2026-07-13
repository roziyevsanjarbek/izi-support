<?php

namespace App\Models\Messages;

use App\Models\Messages\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'name',
        'avatar',
        'created_by',
        'last_message_id',
        'last_activity_at',
        'is_archived',
        'description',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'created_by' => 'integer',
        'last_message_id' => 'integer',
    ];
    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversation_id')->orderByDesc('id');
    }
    public function permissions(): HasMany
    {
        return $this->hasMany(ConversationPermission::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }



    public function adminPermissions(): HasMany
    {
        return $this->hasMany(ConversationPermission::class, 'conversation_id')
            ->where('role', 'admin');
    }


    public function task()
    {
        return $this->hasOne(\App\Models\Tasks\Task::class);
    }
    public function members(): HasMany
    {
        return $this->hasMany(ConversationPermission::class, 'conversation_id')
            ->where('role', 'member');
    }

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }
}
