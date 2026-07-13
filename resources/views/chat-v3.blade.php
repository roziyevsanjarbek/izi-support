@props([
    'conversation',
    'users' => collect(),
    'messages' => collect(),
    'fetchUrl' => null,
    'sendUrl' => null,
    'updateUrlTemplate' => null,
    'deleteUrlTemplate' => null,
    'pinUrl' => null,
    'unpinUrl' => null,
])

@php
    $chatId = 'chat-v3-' . $conversation->id;
    $messagesId = $chatId . '-messages';
    $formId = $chatId . '-form';
    $inputId = $chatId . '-input';
    $sendBtnId = $chatId . '-send';
    $pinBtnId = $chatId . '-pin';
    $searchBoxId = $chatId . '-search';
    $isPinned = (bool) collect($users)->firstWhere('user_id', auth()->id())?->is_pinned;
    $myId = auth()->id();
    $otherUser = collect($users)->firstWhere('user_id', '!=', $myId)?->user;
    $title = $conversation->name ?: ($otherUser?->name ?? 'Private conversation');
    $currentUserPermission = collect($users)->firstWhere('user_id', $myId);
@endphp

<div
    id="{{ $chatId }}"
    class="flex h-full min-h-0 w-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
    data-chat-root
    data-conversation-id="{{ $conversation->id }}"
    data-fetch-url="{{ $fetchUrl }}"
    data-send-url="{{ $sendUrl }}"
    data-update-url-template="{{ $updateUrlTemplate }}"
    data-delete-url-template="{{ $deleteUrlTemplate }}"
    data-pin-url="{{ $pinUrl }}"
    data-unpin-url="{{ $unpinUrl }}"
    data-pinned="{{ $isPinned ? '1' : '0' }}"
>
    <div class="shrink-0 border-b border-slate-200 px-4 py-4 dark:border-slate-800">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="truncate text-lg font-semibold text-slate-900 dark:text-slate-100">
                        {{ $title }}
                    </h2>

                    @if($isPinned)
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                            Pinned
                        </span>
                    @endif
                </div>

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{ $conversation->messages_count ?? $messages->count() }} messages
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="{{ $pinBtnId }}"
                    class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-medium transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                    data-chat-pin-btn
                    data-pinned="{{ $isPinned ? 1 : 0 }}"
                >
                    {{ $isPinned ? 'Unpin' : 'Pin' }}
                </button>
            </div>
        </div>

        @if($currentUserPermission)
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Ready
            </div>
        @endif
    </div>

    <div
        id="{{ $messagesId }}"
        class="min-h-0 flex-1 overflow-y-auto px-4 py-4"
        data-chat-messages
    >
        <div class="space-y-4">
            @forelse($messages as $message)
                @php
                    $mine = (int) $message->user_id === (int) $myId;
                    $deleted = (bool) $message->is_deleted;
                    $canEditDelete = $mine && ! (bool) $message->is_read && ! $deleted;
                    $replyableText = $deleted ? '[deleted]' : ($message->message ?: ($message->file_name ?? 'file'));
                @endphp

                <div
                    class="flex {{ $mine ? 'justify-end' : 'justify-start' }}"
                    data-message-row
                    data-message-id="{{ $message->id }}"
                    data-message-user-id="{{ $message->user_id }}"
                    data-message-read="{{ (int) $message->is_read }}"
                >
                    <div class="max-w-[82%] rounded-3xl border px-4 py-3 shadow-sm {{ $mine ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100' }}">
                        <div class="mb-1 flex items-center justify-between gap-3 text-xs opacity-70">
                            <span class="truncate">
                                {{ $message->user?->name ?? 'User' }}
                            </span>

                            @if($canEditDelete)
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="text-xs font-semibold hover:underline js-edit-message"
                                        data-edit-id="{{ $message->id }}"
                                        data-edit-text="{{ e($message->message ?? '') }}"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold hover:underline js-delete-message"
                                        data-delete-id="{{ $message->id }}"
                                    >
                                        Delete
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if($deleted)
                            <div class="italic opacity-80">
                                This message was deleted.
                            </div>
                        @else
                            @if($message->message)
                                <div class="whitespace-pre-wrap text-sm leading-6">
                                    {!! nl2br(e($message->message)) !!}
                                </div>
                            @endif
                        @endif

                        <div class="mt-2 text-right text-[11px] opacity-60">
                            {{ optional($message->created_at)->format('d M, H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                    No messages yet.
                </div>
            @endforelse
        </div>
    </div>

    <div class="shrink-0 border-t border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-900">
        <form
            id="{{ $formId }}"
            data-chat-send-form
            action="{{ $sendUrl }}"
            class="space-y-3"
        >
            @csrf

            <input type="hidden" name="editing_message_id" value="" data-chat-edit-id>

            <label class="block">
                <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Write a message
                </span>

                <textarea
                    id="{{ $inputId }}"
                    name="message"
                    rows="3"
                    placeholder="Type your message..."
                    class="w-full resize-none rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white dark:border-slate-800 dark:bg-slate-950 dark:focus:bg-slate-900"
                    data-chat-input
                >{{ old('message') }}</textarea>
            </label>

            <div class="hidden rounded-2xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300" data-chat-edit-banner>
                Editing message
            </div>

            <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-slate-500 dark:text-slate-400" data-chat-helper>
                    Enter is not sending here; use the button.
                </p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="hidden rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                        data-chat-cancel-edit
                    >
                        Cancel
                    </button>

                    <button
                        id="{{ $sendBtnId }}"
                        type="submit"
                        class="inline-flex items-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        data-chat-send-btn
                    >
                        Send message
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
