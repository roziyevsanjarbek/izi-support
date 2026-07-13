<?php

namespace App\Models\Messages;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationPermission extends Model
{
    protected $table = 'conversation_permissions';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'notifications',
        'is_pinned',
        'unread_count',
        'last_read_at',
        'can_add_user',
        'can_remove_user',
        'can_delete_message',
        'can_change_name',
        'can_pin_message',
        'can_send_messages',
    ];

    protected $casts = [
        'notifications' => 'boolean',
        'unread_count' => 'integer',
        'last_read_at' => 'datetime',
        'can_add_user' => 'boolean',
        'can_remove_user' => 'boolean',
        'can_delete_message' => 'boolean',
        'can_change_name' => 'boolean',
        'can_pin_message' => 'boolean',
        'can_send_messages' => 'boolean',
    ];

    public $timestamps = false;

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}