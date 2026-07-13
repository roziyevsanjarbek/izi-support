<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Models\Messages\Conversation;
use App\Models\Messages\ConversationPermission;
use App\Models\Messages\Message;
use App\Services\ConversationMessageNotificationService;
use App\Support\FileHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConversationMessageController extends Controller
{
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeAccess($conversation);

        $perPage = max(1, min(50, (int) $request->integer('per_page', 25)));
        $baseQuery = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where(function ($query) {
                $query->whereNull('is_deleted')->orWhere('is_deleted', false);
            })
            ->with(['sender', 'replyTo.sender', 'conversation.permissions.user']);

        if ($request->filled('focus_id')) {
            $focusId = (int) $request->integer('focus_id');
            $focus = (clone $baseQuery)->whereKey($focusId)->first();

            if ($focus) {
                $before = (clone $baseQuery)
                    ->where('id', '<', $focusId)
                    ->orderBy('id', 'desc')
                    ->limit(25)
                    ->get()
                    ->reverse();

                $after = (clone $baseQuery)
                    ->where('id', '>', $focusId)
                    ->orderBy('id', 'asc')
                    ->limit(24)
                    ->get();

                $messages = $before->merge(collect([$focus]))->merge($after)->values();

                $this->markFetchedMessagesAsRead($conversation, $messages);

                return response()->json([
                    'success' => true,
                    'messages' => $messages->map(fn (Message $message) => $this->formatMessage($message))->values(),
                    'has_more' => (clone $baseQuery)->where('id', '<', $messages->first()?->id)->exists(),
                    'next_page' => null,
                    'focus_id' => $focusId,
                ]);
            }
        }

        if ($request->filled('before_id')) {
            $beforeId = (int) $request->integer('before_id');
            $messages = (clone $baseQuery)
                ->where('id', '<', $beforeId)
                ->orderBy('id', 'desc')
                ->limit($perPage)
                ->get()
                ->reverse()
                ->values();

            if ($messages->isNotEmpty()) {
                $this->markFetchedMessagesAsRead($conversation, $messages);
            }

            $oldestId = $messages->first()?->id;

            return response()->json([
                'success' => true,
                'messages' => $messages->map(fn (Message $message) => $this->formatMessage($message))->values(),
                'has_more' => $oldestId ? (clone $baseQuery)->where('id', '<', $oldestId)->exists() : false,
                'before_id' => $beforeId,
                'next_before_id' => $oldestId ? (clone $baseQuery)->where('id', '<', $oldestId)->max('id') : null,
            ]);
        }

        if ($request->filled('after_id')) {
            $afterId = (int) $request->integer('after_id');
            $messages = (clone $baseQuery)
                ->where('id', '>', $afterId)
                ->orderBy('id', 'asc')
                ->limit(100)
                ->get()
                ->values();

            if ($messages->isNotEmpty()) {
                $this->markFetchedMessagesAsRead($conversation, $messages);
            }

            return response()->json([
                'success' => true,
                'messages' => $messages->map(fn (Message $message) => $this->formatMessage($message))->values(),
                'has_more' => false,
                'after_id' => $afterId,
                'next_after_id' => $messages->last()?->id,
            ]);
        }

        $page = max(1, (int) $request->integer('page', 1));
        $total = (clone $baseQuery)->count();

        $messages = (clone $baseQuery)
            ->orderBy('id', 'desc')
            ->forPage($page, 50)
            ->get()
            ->reverse()
            ->values();

        $this->markFetchedMessagesAsRead($conversation, $messages);

        return response()->json([
            'success' => true,
            'messages' => $messages->map(fn (Message $message) => $this->formatMessage($message))->values(),
            'has_more' => ($page * 50) < $total,
            'next_page' => $page + 1,
        ]);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeAccess($conversation);

        $validated = $request->validate([
            'message' => ['nullable', 'string'],
            'reply_to_id' => [
                'nullable',
                'integer',
                Rule::exists('messages', 'id')->where(fn ($query) => $query->where('conversation_id', $conversation->id)),
            ],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:20480'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->storePayload($conversation, $validated, $request->file('files', []));
    }

    public function storeLocation(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeAccess($conversation);

        $validated = $request->validate([
            'message' => ['nullable', 'string'],
            'reply_to_id' => [
                'nullable',
                'integer',
                Rule::exists('messages', 'id')->where(fn ($query) => $query->where('conversation_id', $conversation->id)),
            ],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_name' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->storePayload($conversation, $validated, [], true);
    }

    public function update(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorizeAccess($conversation);
        $this->ensureBelongsToConversation($conversation, $message);
        $this->ensureEditable($message);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:3500'],
        ]);

        $message->forceFill([
            'message' => trim($data['message']),
            'is_edited' => true,
        ])->save();

        $message->load(['sender', 'replyTo.sender']);

        return response()->json([
            'success' => true,
            'message' => $this->formatMessage($message),
        ]);
    }

    public function destroy(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorizeAccess($conversation);
        $this->ensureBelongsToConversation($conversation, $message);
        $this->ensureEditable($message);

        $message->update([
            'is_deleted' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => $this->formatMessage($message->fresh(['sender', 'replyTo.sender', 'conversation'])),
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_ids' => ['required', 'array', 'min:1'],
            'message_ids.*' => ['integer', Rule::exists('messages', 'id')],
            'conversation_ids' => ['required', 'array', 'min:1'],
            'conversation_ids.*' => ['integer', Rule::exists('conversations', 'id')],
        ]);

        $sourceMessages = Message::query()
            ->whereIn('id', $data['message_ids'])
            ->where(function ($query) {
                $query->whereNull('is_deleted')->orWhere('is_deleted', false);
            })
            ->with(['sender', 'replyTo.sender', 'conversation.permissions.user'])
            ->orderBy('id')
            ->get();

        abort_if($sourceMessages->isEmpty(), 404, 'Source message not found.');

        $targetConversations = Conversation::query()
            ->whereIn('id', $data['conversation_ids'])
            ->with(['permissions.user', 'lastMessage.sender'])
            ->get();

        $results = [];

        DB::transaction(function () use ($sourceMessages, $targetConversations, &$results) {
            foreach ($sourceMessages as $sourceMessage) {
                $this->authorizeAccess($sourceMessage->conversation);

                foreach ($targetConversations as $targetConversation) {
                    $this->authorizeAccess($targetConversation);
                    $copied = $this->duplicateMessageToConversation($sourceMessage, $targetConversation);

                    $targetConversation->forceFill([
                        'last_activity_at' => now(),
                    ])->save();

                    $results[] = $copied->fresh(['sender', 'replyTo.sender']);
                }
            }
        });

        $results = collect($results)->map(fn (Message $message) => $this->formatMessage($message))->values();

        return response()->json([
            'success' => true,
            'message' => 'Message resent successfully.',
            'messages' => $results,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:500'],
            'conversation_id' => ['nullable', 'integer', Rule::exists('conversations', 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $q = trim($data['q']);
        $page = max(1, (int) ($data['page'] ?? 1));
        $perPage = 30;

        $baseQuery = Message::query()
            ->where(function ($query) {
                $query->whereNull('is_deleted')->orWhere('is_deleted', false);
            })
            ->with(['sender', 'replyTo.sender', 'conversation.permissions.user'])
            ->where(function ($query) use ($q) {
                $query->where('message', 'like', '%' . $q . '%')
                    ->orWhere('file_name', 'like', '%' . $q . '%')
                    ->orWhere('location_name', 'like', '%' . $q . '%')
                    ->orWhereHas('sender', function ($senderQuery) use ($q) {
                        $senderQuery->where('name', 'like', '%' . $q . '%');
                    });
            })
            ->whereHas('conversation.permissions', function ($query) {
                $query->where('user_id', auth()->id());
            });

        if (!empty($data['conversation_id'])) {
            $conversation = Conversation::query()->findOrFail((int) $data['conversation_id']);
            $this->authorizeAccess($conversation);

            $baseQuery->where('conversation_id', $conversation->id);
        }

        $total = (clone $baseQuery)->count();

        $messages = (clone $baseQuery)
            ->orderBy('id', 'desc')
            ->forPage($page, $perPage)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Message $message) => $this->formatSearchMessage($message))
            ->values();

        return response()->json([
            'success' => true,
            'query' => $q,
            'conversation_id' => $data['conversation_id'] ?? null,
            'messages' => $messages,
            'has_more' => ($page * $perPage) < $total,
            'next_page' => $page + 1,
            'total' => $total,
        ]);
    }

    private function storePayload(Conversation $conversation, array $validated, array $files, bool $forceLocation = false): JsonResponse
    {
        $rawText = (string) ($validated['message'] ?? '');
        $replyToId = $validated['reply_to_id'] ?? null;
        $hasLocation = $forceLocation || (isset($validated['latitude']) && isset($validated['longitude']));

        if (trim($rawText) === '' && empty($files) && ! $hasLocation) {
            return response()->json([
                'success' => false,
                'message' => 'Xabar, fayl yoki location yuboring.',
            ], 422);
        }

        $createdMessages = DB::transaction(function () use ($conversation, $rawText, $replyToId, $files, $validated, $hasLocation) {
            $messages = [];

            if ($hasLocation) {
                $messages[] = $this->createMessage($conversation, [
                    'message' => trim($rawText) !== '' ? $rawText : null,
                    'reply_to_id' => $replyToId,
                    'type' => 'location',
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'location_name' => $validated['location_name'] ?? null,
                    'is_read' => false,
                ]);
            } elseif (trim($rawText) !== '') {
                foreach ($this->splitMessageIntoChunks($rawText, 3000) as $chunk) {
                    $messages[] = $this->createMessage($conversation, [
                        'message' => $chunk,
                        'reply_to_id' => $replyToId,
                        'type' => 'text',
                        'is_read' => false,
                    ]);
                }
            }

            foreach ($files as $file) {
                $messages[] = $this->createMessage($conversation, [
                    'message' => null,
                    'reply_to_id' => $replyToId,
                    'type' => 'file',
                    'file' => $file,
                    'is_read' => false,
                ]);
            }

            $conversation->forceFill([
                'last_activity_at' => now(),
            ])->save();

            return collect($messages);
        });

        $createdMessages->each(function (Message $message) {
            app(ConversationMessageNotificationService::class)->schedule($message);
        });

        return response()->json([
            'success' => true,
            'messages' => $createdMessages->map(fn (Message $message) => $this->formatMessage($message))->values(),
            'message' => $this->formatMessage($createdMessages->last()),
        ]);
    }

    private function splitMessageIntoChunks(string $text, int $limit = 3500): array
    {
        $chunks = [];
        $length = mb_strlen($text, 'UTF-8');

        for ($offset = 0; $offset < $length; $offset += $limit) {
            $chunks[] = mb_substr($text, $offset, $limit, 'UTF-8');
        }

        return $chunks;
    }

    private function createMessage(Conversation $conversation, array $data): Message
    {
        $filePath = null;
        $meta = [];

        if (!empty($data['file']) && $data['file'] instanceof UploadedFile) {
            [$filePath, $meta] = $this->handleFile($data['file'], $conversation->id);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'message' => $data['message'] ?? null,
            'type' => $data['type'] ?? ($filePath ? 'file' : 'text'),
            'reply_to_id' => $data['reply_to_id'] ?? null,
            'file_path' => $filePath,
            'file_name' => $meta['name'] ?? null,
            'mime_type' => $meta['mime'] ?? null,
            'file_size' => $meta['size'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'location_name' => $data['location_name'] ?? null,
            'source' => 'web',
            'telegram_account_id' => null,
            'telegram_chat_id' => null,
            'telegram_message_id' => null,
            'telegram_reply_to_message_id' => null,
            'is_read' => (bool) ($data['is_read'] ?? false),
            'is_edited' => false,
            'is_deleted' => false,
        ]);

        $message->load(['sender', 'replyTo.sender']);

        return $message;
    }

    private function handleFile(?UploadedFile $file, int $conversationId): array
    {
        if (! $file) {
            return [null, []];
        }

        $path = FileHelper::store($file, "conversations/{$conversationId}");

        return [
            $path,
            [
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
        ];
    }

    private function duplicateMessageToConversation(Message $sourceMessage, Conversation $targetConversation): Message
    {
        $filePath = null;
        $fileName = $sourceMessage->file_name;
        $mimeType = $sourceMessage->mime_type;
        $fileSize = $sourceMessage->file_size;

        if ($sourceMessage->file_path) {
            $filePath = $this->duplicateFile($sourceMessage->file_path, $targetConversation->id);
        }

        $created = Message::create([
            'conversation_id' => $targetConversation->id,
            'user_id' => auth()->id(),
            'message' => $sourceMessage->message,
            'type' => $sourceMessage->type,
            'reply_to_id' => null,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'latitude' => $sourceMessage->latitude,
            'longitude' => $sourceMessage->longitude,
            'location_name' => $sourceMessage->location_name,
            'source' => 'web',
            'telegram_account_id' => null,
            'telegram_chat_id' => null,
            'telegram_message_id' => null,
            'telegram_reply_to_message_id' => null,
            'is_read' => false,
            'is_edited' => false,
            'is_deleted' => false,
        ]);

        $created->load(['sender', 'replyTo.sender']);

        return $created;
    }

    private function duplicateFile(string $sourcePath, int $conversationId): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            return $sourcePath;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $newPath = "conversations/{$conversationId}/resend-" . Str::uuid() . ($extension ? '.' . $extension : '');

        $disk->copy($sourcePath, $newPath);

        return $newPath;
    }

    private function markFetchedMessagesAsRead(Conversation $conversation, $messages): void
    {
        $ids = collect($messages)->pluck('id')->filter()->values()->all();

        if (!empty($ids)) {
            Message::query()
                ->where('conversation_id', $conversation->id)
                ->whereIn('id', $ids)
                ->where('user_id', '!=', auth()->id())
                ->where(function ($query) {
                    $query->whereNull('is_read')->orWhere('is_read', false);
                })
                ->update([
                    'is_read' => true,
                ]);
        }

        ConversationPermission::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', auth()->id())
            ->update([
                'unread_count' => 0,
                'last_read_at' => now(),
            ]);
    }

    private function authorizeAccess(Conversation $conversation): ConversationPermission
    {
        $permission = $conversation->permissions()
            ->where('user_id', auth()->id())
            ->first();

        abort_unless($permission, 403, 'No access to conversation');

        return $permission;
    }

    private function ensureBelongsToConversation(Conversation $conversation, Message $message): void
    {
        abort_unless((int) $message->conversation_id === (int) $conversation->id, 404);
    }

    private function ensureEditable(Message $message): void
    {
        abort_unless((int) $message->user_id === (int) auth()->id(), 403, 'Message is not editable.');
        abort_unless(! $message->is_deleted, 422, 'Message was already deleted.');
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
            'location_url' => $message->latitude && $message->longitude
                ? 'https://www.google.com/maps?q=' . $message->latitude . ',' . $message->longitude
                : null,
            'is_read' => (bool) $message->is_read,
            'is_edited' => (bool) $message->is_edited,
            'is_deleted' => (bool) $message->is_deleted,
            'can_edit' => (bool) ($message->user_id === auth()->id() && ! $message->is_deleted),
            'can_delete' => (bool) ($message->user_id === auth()->id() && ! $message->is_deleted),
            'created_at' => optional($message->created_at)?->toIso8601String(),
            'created_at_time' => optional($message->created_at)?->format('H:i'),
            'updated_at' => optional($message->updated_at)?->toIso8601String(),
        ];
    }

    private function formatSearchMessage(Message $message): array
    {
        $payload = $this->formatMessage($message);
        $payload['conversation_title'] = $message->conversation?->name ?: ($message->conversation?->type === 'group' ? 'Group conversation' : 'Private conversation');
        $payload['conversation_url'] = route('messages.conversations.show', $message->conversation_id);
        $payload['conversation_type'] = $message->conversation?->type;

        return $payload;
    }
}
