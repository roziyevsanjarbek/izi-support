<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\Messages\Conversation;
use App\Models\Messages\ConversationPermission;
use App\Models\Messages\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'private');
        abort_unless(in_array($type, ['private', 'group'], true), 404);

        $activeConversationId = $request->integer('conversation') ?: null;
        $sidebarData = $this->sidebarData(auth()->id(), $type);
        $tabs = $this->sidebarTabs(auth()->id());

        $chatHtml = $this->renderEmptyChatHtml();
        if ($activeConversationId) {
            $conversation = Conversation::query()->with(['permissions.user', 'lastMessage.sender'])->find($activeConversationId);
            if ($conversation && $this->userHasAccess($conversation)) {
                $chatHtml = $this->renderChatHtml($this->loadConversationForChat($conversation));
            }
        }

        return view('pages.conversations.index', [
            'sidebarHtml' => $this->renderSidebarHtml(
                $sidebarData['items'],
                $sidebarData['totalUnread'],
                $activeConversationId,
                $type,
                $tabs
            ),
            'chatHtml' => $chatHtml,
            'conversation' => null,
            'search' => '',
            'activeConversationId' => $activeConversationId,
            'conversationType' => $type,
            'tabs' => $tabs,
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $type = $request->input('type', 'private');
        abort_unless(in_array($type, ['private', 'group'], true), 404);

        $activeConversationId = $request->integer('conversation') ?: null;
        $sidebarData = $this->sidebarData(auth()->id(), $type);
        $tabs = $this->sidebarTabs(auth()->id());

        return response()->json([
            'success' => true,
            'conversation_type' => $type,
            'total_unread' => $sidebarData['totalUnread'],
            'conversation_count' => count($sidebarData['items']),
            'active_conversation_id' => $activeConversationId,
            'tabs' => $tabs,
            'sidebar_html' => $this->renderSidebarHtml(
                $sidebarData['items'],
                $sidebarData['totalUnread'],
                $activeConversationId,
                $type,
                $tabs
            ),
            'items' => $sidebarData['items'],
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['private', 'group'])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $mode = $data['mode'];
        $q = trim((string) ($data['q'] ?? ''));
        $userId = auth()->id();

        $query = User::query()->whereKeyNot($userId);

        if ($mode === 'private') {
            $existingPartnerIds = $this->getPrivateConversationPartnerIds($userId);
            if ($existingPartnerIds->isNotEmpty()) {
                $query->whereNotIn('id', $existingPartnerIds->all());
            }
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            });
        }

        $users = $query
            ->orderBy('name')
            // ->limit(50)
            ->get()
            ->map(function (User $user) use ($mode) {
                $title = $user->name ?: 'User';

                return [
                    'id' => $user->id,
                    'title' => $title,
                    'subtitle' => $mode === 'private' ? 'Start new chat' : 'Add to group',
                    'avatar' => Str::upper(Str::substr($title, 0, 2)),
                    'search' => Str::lower($title),
                    'url' => $mode === 'private' ? route('messages.conversations.start', $user->id) : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'mode' => $mode,
            'items' => $users,
        ]);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->ensurePrivateAccessOrGroupAccess($conversation);

        return $this->conversationPayload(
            $this->loadConversationForChat($conversation, $request->integer('focus_id') ?: null),
            'Conversation loaded.'
        );
    }

    public function start(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === auth()->id(), 404);

        $conversation = $this->findOrCreatePrivateConversation($user);

        return $this->conversationPayload(
            $this->loadConversationForChat($conversation),
            'Conversation loaded.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['private', 'group'])],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['type'] === 'private') {
            abort_unless(!empty($validated['user_id']), 422, 'user_id is required for private chat.');
            $user = User::findOrFail((int) $validated['user_id']);
            abort_if($user->id === auth()->id(), 404);

            $conversation = $this->findOrCreatePrivateConversation($user);

            return $this->conversationPayload(
                $this->loadConversationForChat($conversation),
                'Conversation loaded.'
            );
        }

        $conversation = $this->createGroupConversation(
            name: trim((string) ($validated['name'] ?? '')) ?: null,
            userIds: collect($validated['user_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        return $this->conversationPayload(
            $this->loadConversationForChat($conversation),
            'Group created successfully.'
        );
    }

    public function pin(Request $request, Conversation $conversation): JsonResponse
    {
        $permission = $this->ensurePrivateAccessOrGroupAccess($conversation);
        $permission->update(['is_pinned' => true]);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'is_pinned' => true,
            'message' => 'Conversation pinned successfully.',
            'sidebar_html' => $this->renderSidebarHtmlForConversationType($conversation, $conversation->id),
        ]);
    }

    public function unpin(Request $request, Conversation $conversation): JsonResponse
    {
        $permission = $this->ensurePrivateAccessOrGroupAccess($conversation);
        $permission->update(['is_pinned' => false]);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'is_pinned' => false,
            'message' => 'Conversation unpinned successfully.',
            'sidebar_html' => $this->renderSidebarHtmlForConversationType($conversation, $conversation->id),
        ]);
    }

    public function toggleNotifications(Request $request, Conversation $conversation): JsonResponse
    {
        $permission = $this->ensurePrivateAccessOrGroupAccess($conversation);
        $permission->update(['notifications' => ! (bool) $permission->notifications]);

        return $this->conversationPayload(
            $this->loadConversationForChat($conversation),
            'Notification setting updated.'
        );
    }

    private function conversationPayload(Conversation $conversation, string $message = 'Conversation loaded.'): JsonResponse
    {
        $sidebarData = $this->sidebarData(auth()->id(), $conversation->type);
        $tabs = $this->sidebarTabs(auth()->id());

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'conversation_type' => $conversation->type,
            'page_url' => route('messages.conversations.index', ['conversation' => $conversation->id, 'type' => $conversation->type]),
            'chat_html' => $this->renderChatHtml($conversation),
            'sidebar_html' => $this->renderSidebarHtml($sidebarData['items'], $sidebarData['totalUnread'], $conversation->id, $conversation->type, $tabs),
            'tabs' => $tabs,
            'message' => $message,
        ]);
    }

    private function renderSidebarHtmlForConversationType(Conversation $conversation, ?int $activeConversationId = null): string
    {
        $sidebarData = $this->sidebarData(auth()->id(), $conversation->type);
        $tabs = $this->sidebarTabs(auth()->id());

        return $this->renderSidebarHtml($sidebarData['items'], $sidebarData['totalUnread'], $activeConversationId, $conversation->type, $tabs);
    }

    private function renderChatHtml(Conversation $conversation): string
    {
        $permission = $conversation->permissions->firstWhere('user_id', auth()->id());
        $messages = collect($conversation->formatted_messages ?? collect())->values();
        $oldestMessageId = $messages->first()['id'] ?? null;
        $newestMessageId = $messages->last()['id'] ?? null;
        $hasMoreOlder = false;

        if ($oldestMessageId) {
            $hasMoreOlder = Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('id', '<', $oldestMessageId)
                ->exists();
        }

        return view('components.chat.chat-v3', [
            'conversation' => $conversation,
            'users' => $conversation->permissions->map(fn ($permission) => $permission->toArray())->values(),
            'messages' => $messages,
            'fetchUrl' => route('messages.conversations.messages.index', $conversation->id),
            'sendUrl' => route('messages.conversations.messages.store', $conversation->id),
            'locationUrl' => route('messages.conversations.messages.location', $conversation->id),
            'updateUrlBase' => route('messages.conversations.messages.update', [$conversation->id, '__MESSAGE__']),
            'deleteUrlBase' => route('messages.conversations.messages.destroy', [$conversation->id, '__MESSAGE__']),
            'resendUrl' => route('messages.messages.resend'),
            'searchUrl' => route('messages.search.messages'),
            'usersUrl' => route('messages.conversations.users'),
            'pollUrl' => route('messages.conversations.poll'),
            'pinUrl' => route('messages.conversations.pin', $conversation->id),
            'unpinUrl' => route('messages.conversations.unpin', $conversation->id),
            'toggleNotificationsUrl' => route('messages.conversations.toggle-notifications', $conversation->id),
            'polling' => true,
            'isPinned' => (bool) $permission?->is_pinned,
            'conversationType' => $conversation->type,
            'messagesOldestId' => $oldestMessageId,
            'messagesNewestId' => $newestMessageId,
            'messagesHasMoreOlder' => $hasMoreOlder,
        ])->render();
    }

    private function renderEmptyChatHtml(): string
    {
        return <<<'HTML'
<div class="chat-shell">
    <div class="chat-header">
        <div class="flex items-center justify-between gap-3 px-4 py-3">
            <div>
                <div class="text-lg font-semibold text-slate-900 dark:text-slate-100">Messages</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Choose a chat or create a new one</div>
            </div>
        </div>
    </div>
    <div class="chat-body">
        <div class="flex h-full items-center justify-center p-6 text-center">
            <div class="max-w-md rounded-[28px] border border-dashed border-slate-300 bg-white/80 p-8 shadow-sm dark:border-slate-700 dark:bg-slate-950/60">
                <div class="text-3xl">✉️</div>
                <div class="mt-4 text-xl font-semibold text-slate-900 dark:text-slate-100">No conversation selected</div>
                <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">Pick a chat from the left sidebar or start a new private chat/group.</div>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    private function renderSidebarHtml(Collection|array $items, int $totalUnread, ?int $activeConversationId = null, string $conversationType = 'private', array $tabs = []): string
    {
        return view('components.chat.sidebar-v3', [
            'items' => $items instanceof Collection ? $items->all() : $items,
            'totalUnread' => $totalUnread,
            'activeConversationId' => $activeConversationId,
            'conversationType' => $conversationType,
            'tabs' => $tabs,
        ])->render();
    }

    private function sidebarTabs(int $userId): array
    {
        $private = $this->sidebarData($userId, 'private');
        $group = $this->sidebarData($userId, 'group');

        return [
            'private' => ['label' => 'Private', 'count' => count($private['items']), 'unread' => $private['totalUnread']],
            'group' => ['label' => 'Group', 'count' => count($group['items']), 'unread' => $group['totalUnread']],
        ];
    }

    private function sidebarData(int $userId, string $type): array
    {
        $conversations = Conversation::query()
            ->where('type', $type)
            ->whereHas('permissions', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['permissions.user', 'lastMessage.sender'])
            ->get()
            ->map(function (Conversation $conversation) use ($userId) {
                return $this->buildConversationSidebarItem($conversation, $userId);
            });

        $items = collect($conversations->all())
            ->sort(function (array $a, array $b) {
                if (!empty($a['is_pinned']) !== !empty($b['is_pinned'])) {
                    return !empty($b['is_pinned']) <=> !empty($a['is_pinned']);
                }

                if (($a['unread_count'] ?? 0) !== ($b['unread_count'] ?? 0)) {
                    return ($b['unread_count'] ?? 0) <=> ($a['unread_count'] ?? 0);
                }

                return ($b['sort_time'] ?? 0) <=> ($a['sort_time'] ?? 0);
            })
            ->values();

        return [
            'items' => $items->all(),
            'totalUnread' => $conversations->sum(fn (array $item) => $item['unread_count'] ?? 0),
        ];
    }

    private function buildConversationSidebarItem(Conversation $conversation, int $userId): array
    {
        $myPermission = $conversation->permissions->firstWhere('user_id', $userId);
        $otherPermission = $conversation->permissions->firstWhere('user_id', '!=', $userId);
        $otherUsers = $conversation->permissions->where('user_id', '!=', $userId)->pluck('user.name')->filter()->values();

        if ($conversation->type === 'group') {
            $title = $conversation->name ?: 'Group conversation';
            $subtitle = $otherUsers->isNotEmpty()
                ? $otherUsers->take(3)->implode(', ')
                : ($conversation->lastMessage?->message ?: 'No messages yet');
        } else {
            $title = $conversation->name ?: ($otherPermission?->user?->name ?: 'Private conversation');
            $subtitle = $conversation->lastMessage?->message ?: ($conversation->lastMessage?->file_name ?: 'No messages yet');
        }

        return [
            'kind' => 'conversation',
            'conversation_type' => $conversation->type,
            'conversation_id' => $conversation->id,
            'title' => $title,
            'subtitle' => Str::limit($subtitle, 72),
            'avatar' => Str::upper(Str::substr($title, 0, 2)),
            'unread_count' => (int) ($myPermission?->unread_count ?? 0),
            'is_pinned' => (bool) $myPermission?->is_pinned,
            'url' => route('messages.conversations.show', $conversation->id),
            'page_url' => route('messages.conversations.index', ['conversation' => $conversation->id, 'type' => $conversation->type]),
            'search' => Str::lower($title . ' ' . $subtitle),
            'sort_time' => optional($conversation->last_activity_at)->timestamp ?? 0,
        ];
    }

    private function getPrivateConversationPartnerIds(int $userId): Collection
    {
        $conversationIds = ConversationPermission::query()
            ->join('conversations', 'conversations.id', '=', 'conversation_permissions.conversation_id')
            ->where('conversations.type', 'private')
            ->where('conversation_permissions.user_id', $userId)
            ->pluck('conversation_permissions.conversation_id');

        if ($conversationIds->isEmpty()) {
            return collect();
        }

        return ConversationPermission::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('user_id', '!=', $userId)
            ->distinct()
            ->pluck('user_id');
    }

    private function loadConversationForChat(Conversation $conversation, ?int $focusId = null): Conversation
    {
        $this->markConversationAsRead($conversation);
        $conversation->load(['permissions.user', 'lastMessage.sender'])->loadCount('messages');

        $messages = $focusId
            ? $this->messagesWindow($conversation->id, $focusId)
            : Message::query()
                ->where('conversation_id', $conversation->id)
                ->where(function ($query) {
                    $query->whereNull('is_deleted')->orWhere('is_deleted', false);
                })
                ->with(['sender', 'replyTo.sender'])
                ->orderBy('id', 'asc')
                ->get()
                ->values();

        $conversation->setRelation('formatted_messages', $messages->map(fn (Message $message) => $this->formatMessage($message))->values());
        $conversation->setAttribute('focus_message_id', $focusId);

        return $conversation;
    }

    private function messagesWindow(int $conversationId, int $focusId)
    {
        $base = Message::query()
            ->where('conversation_id', $conversationId)
            ->where(function ($query) {
                $query->whereNull('is_deleted')->orWhere('is_deleted', false);
            })
            ->with(['sender', 'replyTo.sender']);
        $focus = (clone $base)->whereKey($focusId)->first();

        if (!$focus) {
            return (clone $base)->orderBy('id', 'desc')->limit(50)->get()->reverse()->values();
        }

        $before = (clone $base)->where('id', '<', $focusId)->orderBy('id', 'desc')->limit(25)->get()->reverse();
        $after = (clone $base)->where('id', '>', $focusId)->orderBy('id', 'asc')->limit(24)->get();

        return $before->merge(collect([$focus]))->merge($after)->values();
    }

    private function markConversationAsRead(Conversation $conversation): void
    {
        DB::transaction(function () use ($conversation) {
            ConversationPermission::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', auth()->id())
                ->update([
                    'unread_count' => 0,
                    'last_read_at' => now(),
                ]);

            Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', '!=', auth()->id())
                ->where(function ($query) {
                    $query->whereNull('is_read')->orWhere('is_read', false);
                })
                ->update(['is_read' => true]);
        });
    }

    private function userHasAccess(Conversation $conversation): bool
    {
        return $conversation->permissions()->where('user_id', auth()->id())->exists();
    }

    private function ensurePrivateAccessOrGroupAccess(Conversation $conversation): ConversationPermission
    {
        abort_unless(in_array($conversation->type, ['private', 'group'], true), 404);

        $permission = $conversation->permissions()->where('user_id', auth()->id())->first();
        abort_unless($permission, 403, 'You do not have access to this conversation.');

        return $permission;
    }

    private function findOrCreatePrivateConversation(User $user): Conversation
    {
        $existing = Conversation::query()
            ->where('type', 'private')
            ->whereHas('permissions', fn ($query) => $query->where('user_id', auth()->id()))
            ->whereHas('permissions', fn ($query) => $query->where('user_id', $user->id))
            ->with(['permissions.user', 'lastMessage.sender'])
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = DB::transaction(function () use ($user) {
            $conversation = Conversation::create([
                'type' => 'private',
                'name' => null,
                'last_activity_at' => now(),
                'is_archived' => false,
            ]);

            $conversation->permissions()->create([
                'user_id' => auth()->id(),
                'role' => 'admin',
                'notifications' => true,
                'is_pinned' => false,
                'unread_count' => 0,
                'last_read_at' => now(),
            ]);

            $conversation->permissions()->create([
                'user_id' => $user->id,
                'role' => 'member',
                'notifications' => true,
                'is_pinned' => false,
                'unread_count' => 0,
                'last_read_at' => null,
            ]);

            return $conversation;
        });

        return $conversation->load(['permissions.user', 'lastMessage.sender']);
    }

    private function createGroupConversation(?string $name, array $userIds): Conversation
    {
        $userIds = collect($userIds)->merge([auth()->id()])->map(fn ($id) => (int) $id)->unique()->values();
        abort_if($userIds->count() < 2, 422, 'Group must have at least 2 users.');

        $users = User::query()->whereIn('id', $userIds->all())->get();
        abort_if($users->count() !== $userIds->count(), 422, 'Some users were not found.');

        $conversation = DB::transaction(function () use ($name, $userIds) {
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $name,
                'last_activity_at' => now(),
                'is_archived' => false,
            ]);

            foreach ($userIds as $id) {
                $conversation->permissions()->create([
                    'user_id' => $id,
                    'role' => $id === auth()->id() ? 'admin' : 'member',
                    'notifications' => true,
                    'is_pinned' => false,
                    'unread_count' => 0,
                    'last_read_at' => $id === auth()->id() ? now() : null,
                ]);
            }

            return $conversation;
        });

        return $conversation->load(['permissions.user', 'lastMessage.sender']);
    }

    private function formatMessage(Message $message): array
    {
        $message->loadMissing(['sender', 'replyTo.sender', 'conversation']);

        $fileUrl = $message->file_url ?: ($message->file_path ? Storage::disk('public')->url($message->file_path) : null);

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $message->user_id,
            'is_mine' => (int) $message->user_id === (int) auth()->id(),
            'sender_name' => $message->sender?->name,
            'message' => $message->message,
            'type' => $message->type,
            'reply_to_id' => $message->reply_to_id,
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'message' => $message->replyTo->message,
                'file_name' => $message->replyTo->file_name,
                'user_name' => $message->replyTo->sender?->name,
                'is_deleted' => (bool) $message->replyTo->is_deleted,
            ] : null,
            'file_url' => $fileUrl,
            'file_name' => $message->file_name,
            'mime_type' => $message->mime_type,
            'file_size' => $message->file_size,
            'latitude' => $message->latitude,
            'longitude' => $message->longitude,
            'location_name' => $message->location_name,
            'location_url' => $message->latitude && $message->longitude ? 'https://www.google.com/maps?q=' . $message->latitude . ',' . $message->longitude : null,
            'is_read' => (bool) $message->is_read,
            'is_edited' => (bool) $message->is_edited,
            'is_deleted' => (bool) $message->is_deleted,
            'can_edit' => (bool) ($message->user_id === auth()->id() && !$message->is_deleted),
            'can_delete' => (bool) ($message->user_id === auth()->id() && !$message->is_deleted),
            'created_at' => optional($message->created_at)?->toIso8601String(),
            'created_at_time' => optional($message->created_at)?->format('H:i'),
            'updated_at' => optional($message->updated_at)?->toIso8601String(),
        ];
    }

    private function formatSearchMessage(Message $message): array
    {
        $payload = $this->formatMessage($message);
        $partner = $message->conversation?->permissions?->firstWhere('user_id', '!=', auth()->id());
        $payload['conversation_title'] = $message->conversation?->name ?: ($message->conversation?->type === 'group' ? 'Group conversation' : ($partner?->user?->name ?: 'Private conversation'));
        $payload['conversation_url'] = route('messages.conversations.show', $message->conversation_id);
        $payload['conversation_type'] = $message->conversation?->type;

        return $payload;
    }
}
