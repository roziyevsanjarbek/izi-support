@props([
    'conversation',
    'users' => collect(),
    'messages' => collect(),
    'fetchUrl',
    'sendUrl',
    'locationUrl' => null,
    'updateUrlBase',
    'deleteUrlBase',
    'resendUrl' => null,
    'searchUrl' => null,
    'usersUrl' => null,
    'pollUrl' => null,
    'pinUrl' => null,
    'unpinUrl' => null,
    'toggleNotificationsUrl' => null,
    'polling' => true,
    'isPinned' => false,
    'conversationType' => 'private',
])

@php
    $currentUserId = auth()->id();
    $usersCollection = collect($users);
    $permission = $usersCollection->firstWhere('user_id', $currentUserId);
    $focusMessageId = $conversation->focus_message_id ?? null;

    $partner = $usersCollection->first(function ($item) use ($currentUserId) {
        return (int) ($item['user_id'] ?? 0) !== (int) $currentUserId;
    });

    $title = $conversation->type === 'group'
        ? ($conversation->name ?: 'Group conversation')
        : ($partner['user']['name'] ?? $partner['name'] ?? 'Private chat');

    $subtitle = $conversation->type === 'group'
        ? ($usersCollection->count() . ' members')
        : 'Direct conversation';

    $previousDateKey = null;
@endphp

<div
    class="js-chat-v3 chat-shell"
    data-conversation-id="{{ $conversation->id }}"
    data-conversation-type="{{ $conversation->type }}"
    data-show-url="{{ route('messages.conversations.show', $conversation->id) }}"
    data-fetch-url="{{ $fetchUrl }}"
    data-send-url="{{ $sendUrl }}"
    data-location-url="{{ $locationUrl ?? $sendUrl }}"
    data-update-url-base="{{ $updateUrlBase }}"
    data-delete-url-base="{{ $deleteUrlBase }}"
    data-resend-url="{{ $resendUrl ?? '' }}"
    data-search-url="{{ $searchUrl ?? '' }}"
    data-users-url="{{ $usersUrl ?? '' }}"
    data-poll-url="{{ $pollUrl ?? '' }}"
    data-pin-url="{{ $pinUrl ?? '' }}"
    data-unpin-url="{{ $unpinUrl ?? '' }}"
    data-toggle-notifications-url="{{ $toggleNotificationsUrl ?? '' }}"
    data-polling="{{ $polling ? 1 : 0 }}"
    data-focus-message-id="{{ $focusMessageId }}"
>
    <div class="chat-header sticky top-0 z-10">
        <div class="flex items-center justify-between gap-3 px-4 py-3">
            <div class="min-w-0">
                <div class="truncate text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {{ $title }}
                </div>

                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $subtitle }} · {{ $messages->count() }} messages
                    </div>

                    @if($conversation->type === 'group')
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                            {{ $usersCollection->count() }} members
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="js-message-search-btn inline-flex h-10 items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" title="Search">
                    ⌕
                </button>

                <button type="button" class="js-resend-open hidden inline-flex h-10 items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" title="Resend">
                    ↗
                </button>

                @if($conversation->type === 'group')
    <details class="relative">
        <summary class="list-none inline-flex h-10 items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 cursor-pointer dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">
            Members
        </summary>

        <div class="absolute right-0 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg dark:border-slate-800 dark:bg-slate-950 z-20">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                    Group members
                </div>
                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ $usersCollection->count() }} total
                </div>
            </div>

            <div class="max-h-[320px] overflow-y-auto overscroll-contain p-2">
                @forelse($usersCollection as $member)
                    <div class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-900">
                        <div class="min-w-0">
                            <div class="truncate font-semibold">
                                {{ $member['user']['name'] ?? $member['name'] ?? 'User' }}
                            </div>
                            <div class="truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ $member['role'] ?? 'member' }}
                            </div>
                        </div>

                        <span class="shrink-0 text-xs text-slate-400">
                            {{ !empty($member['notifications']) ? 'On' : 'Off' }}
                        </span>
                    </div>
                @empty
                    <div class="px-3 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                        No members
                    </div>
                @endforelse
            </div>
        </div>
    </details>
@endif

                @if($pinUrl && $unpinUrl)
                    <button type="button" class="js-pin-toggle inline-flex h-10 items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" data-state="{{ $isPinned ? 1 : 0 }}">
                        {{ $isPinned ? 'Unpin' : 'Pin' }}
                    </button>
                @endif
            </div>
        </div>

        <div class="js-selection-bar selection-bar hidden border-t border-slate-200 bg-white/90 px-4 py-2 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <span class="js-selection-count">0</span> selected
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="js-selection-clear inline-flex h-9 items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        Clear
                    </button>
                    <button type="button" class="js-selection-delete hidden inline-flex h-9 items-center justify-center rounded-2xl bg-rose-600 px-3 text-sm font-semibold text-white">
                        Delete
                    </button>
                    <button type="button" class="js-selection-resend hidden inline-flex h-9 items-center justify-center rounded-2xl bg-blue-600 px-3 text-sm font-semibold text-white">
                        Resend
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="chat-body">
        <div class="chat-messages js-messages" data-fetch-url="{{ $fetchUrl }}">
            @forelse($messages as $message)
                @php
                    $isMine = !empty($message['is_mine']);
                    $isDeleted = !empty($message['is_deleted']);
                    $ext = strtolower(pathinfo($message['file_name'] ?? '', PATHINFO_EXTENSION));
                    $isImage = str_starts_with((string) ($message['mime_type'] ?? ''), 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);

                    $messageDateKey = !empty($message['created_at'])
                        ? \Carbon\Carbon::parse($message['created_at'])->toDateString()
                        : null;
                @endphp

                @if($messageDateKey && $messageDateKey !== $previousDateKey)
                    <div class="my-4 flex justify-center">
                        <div class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
                            {{ \Carbon\Carbon::parse($message['created_at'])->format('j F Y') }}
                        </div>
                    </div>
                    @php $previousDateKey = $messageDateKey; @endphp
                @endif

                @if(!$isDeleted)
                    <div class="msg-row {{ $isMine ? 'right' : 'left' }}" data-message-id="{{ $message['id'] }}" data-message-json='@json($message)'>
                        <div class="msg-wrap">
                            @if(!$isMine && !empty($message['sender_name']))
                                <div class="msg-sender-name">{{ $message['sender_name'] }}</div>
                            @endif

                            <div class="bubble {{ $isMine ? 'mine' : 'other' }}">
                                <label class="msg-select">
                                    <input type="checkbox" class="js-select-msg">
                                    <span></span>
                                </label>

                                @if(!empty($message['reply_to']))
                                    <div class="reply-preview">
                                        <div class="reply-preview-name">
                                            {{ $message['reply_to']['user_name'] ?? 'User' }}
                                        </div>
                                        <div class="reply-preview-text">
                                            {{ $message['reply_to']['is_deleted'] ? 'Message deleted' : ($message['reply_to']['message'] ?? $message['reply_to']['file_name'] ?? 'file') }}
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($message['message']))
                                    <div class="msg-text">{{ $message['message'] }}</div>
                                @endif

                                @if(!empty($message['file_url']))
                                    <div class="msg-media">
                                        @if($isImage)
                                            <a href="{{ $message['file_url'] }}" target="_blank" rel="noopener">
                                                <img class="media-img" src="{{ $message['file_url'] }}" alt="{{ $message['file_name'] ?? 'image' }}">
                                            </a>
                                        @else
                                            <a class="media-file" href="{{ $message['file_url'] }}" target="_blank" rel="noopener">
                                                <span class="text-lg">📎</span>
                                                <div class="min-w-0">
                                                    <div class="media-file-name truncate">{{ $message['file_name'] ?? 'File' }}</div>
                                                    <div class="media-file-meta">{{ $message['mime_type'] ?? 'File' }}</div>
                                                </div>
                                            </a>
                                        @endif
                                    </div>
                                @endif

                                <div class="msg-meta">
                                    <span>{{ $message['created_at_time'] ?? '' }}</span>
                                    @if(!empty($message['is_edited']))
                                        <span>edited</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white/60 p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-400">
                    Start the conversation
                </div>
            @endforelse
        </div>

        <button type="button" class="js-scroll-bottom scroll-bottom" aria-label="Scroll to bottom">
            <svg viewBox="0 0 24 24" aria-hidden="true" class="scroll-bottom-icon">
                <path d="M12 17.5c-.32 0-.64-.12-.88-.37l-6.25-6.25a1.25 1.25 0 1 1 1.77-1.77L12 14.48l5.36-5.37a1.25 1.25 0 1 1 1.77 1.77l-6.25 6.25c-.24.25-.56.37-.88.37Z"/>
            </svg>
        </button>

        <div class="chat-composer">
            <div class="reply-preview js-reply-bar mb-3 hidden">
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-600"></div>
                    <div class="min-w-0 flex-1">
                        <div class="reply-preview-name uppercase tracking-wide">Reply to:</div>
                        <div class="reply-preview-name js-reply-title mt-1"></div>
                        <div class="reply-preview-text js-reply-text"></div>
                    </div>
                    <button type="button" class="js-reply-cancel inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-semibold text-slate-600 shadow-sm transition hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900" aria-label="Cancel reply">×</button>
                </div>
                <input type="hidden" class="js-reply-id">
            </div>

            <div class="composer-box">
                <div class="js-previews mb-2"></div>
                <div class="flex items-end gap-2">
                    <button
                        type="button"
                        class="js-attach inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        ＋
                    </button>
                    <input type="file" class="js-files hidden" multiple>
                    <textarea class="composer-input js-text-input" placeholder="Write a message..."></textarea>
                    <button type="button" class="js-send inline-flex h-11 items-center justify-center rounded-2xl bg-blue-600 px-4 font-semibold text-white">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>
