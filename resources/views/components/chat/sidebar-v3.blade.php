@props([
    'items' => [],
    'totalUnread' => 0,
    'activeConversationId' => null,
    'conversationType' => 'private',
    'tabs' => [],
])

@php
    $privateTab = $tabs['private'] ?? ['label' => 'Private', 'count' => 0, 'unread' => 0];
    $groupTab = $tabs['group'] ?? ['label' => 'Group', 'count' => 0, 'unread' => 0];
@endphp

<div class="conversation-sidebar-head">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-base font-semibold text-slate-900 dark:text-slate-100">
                Messages
                <span class="ml-1 text-xs font-medium text-slate-500 dark:text-slate-400">({{ $totalUnread }})</span>
            </div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Search chats or start a new one</div>
        </div>

        <button
            type="button"
            class="js-sidebar-close inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100 md:hidden dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900"
            aria-label="Close sidebar"
        >
            ×
        </button>
    </div>

    <div class="mt-4 flex items-center gap-2 rounded-2xl bg-slate-100 p-1 dark:bg-slate-900">
        <button
            type="button"
            class="js-switch-type flex-1 rounded-xl px-3 py-2 text-sm font-semibold transition {{ $conversationType === 'private' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400' }}"
            data-type="private"
            data-url="{{ route('messages.conversations.index', ['type' => 'private']) }}"
        >
            Private <span class="ml-1 text-xs">({{ $privateTab['count'] }})</span>
        </button>
        <button
            type="button"
            class="js-switch-type flex-1 rounded-xl px-3 py-2 text-sm font-semibold transition {{ $conversationType === 'group' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400' }}"
            data-type="group"
            data-url="{{ route('messages.conversations.index', ['type' => 'group']) }}"
        >
            Group <span class="ml-1 text-xs">({{ $groupTab['count'] }})</span>
        </button>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2">
        <button
            type="button"
            class="js-open-new-chat inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900"
        >
            New chat
        </button>
        <button
            type="button"
            class="js-open-new-group inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-900"
        >
            New group
        </button>
    </div>

    <div class="mt-4 flex items-center gap-2">
        <input
            type="text"
            class="conversation-search js-conversation-search flex-1"
            placeholder="Search chats..."
            autocomplete="off"
        >
        <button
            type="button"
            class="js-open-global-search inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-900"
            aria-label="Global message search"
        >
            ⌕
        </button>
    </div>
</div>

<div class="conversation-sidebar-body js-conversation-list">
    @forelse($items as $item)
        @php
            $isActive = (int) ($activeConversationId ?? 0) === (int) ($item['conversation_id'] ?? -1);
            $kindLabel = $item['conversation_type'] === 'group' ? 'Group' : 'Private';
            $badgeClass = $item['is_pinned']
                ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
        @endphp

        <a
            href="{{ $item['page_url'] ?? '#' }}"
            class="conversation-item js-conversation-item {{ $isActive ? 'is-active' : '' }}"
            data-load-url="{{ $item['url'] }}"
            data-page-url="{{ $item['page_url'] ?? '' }}"
            data-conversation-id="{{ $item['conversation_id'] ?? '' }}"
            data-search-key="{{ $item['search'] }}"
            data-conversation-type="{{ $item['conversation_type'] ?? $conversationType }}"
        >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-sm font-semibold text-white">
                {{ $item['avatar'] }}
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0 truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {{ $item['title'] }}
                    </div>

                    @if(($item['unread_count'] ?? 0) > 0)
                        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                            {{ $item['unread_count'] }}
                        </span>
                    @endif
                </div>

                <div class="mt-1 flex items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span class="min-w-0 truncate">
                        {{ $item['subtitle'] }}
                    </span>

                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $badgeClass }}">
                        {{ $kindLabel }}
                    </span>
                </div>
            </div>
        </a>
    @empty
        <div class="px-4 py-4 text-sm text-slate-500 dark:text-slate-400">
            No users or conversations
        </div>
    @endforelse
</div>
