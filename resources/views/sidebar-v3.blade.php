@props([
    'items' => collect(),
    'activeConversationId' => null,
])

@php
    $groupLabels = [
        'pinned_conversation' => 'Pinned conversations',
        'conversation' => 'Conversations',
        'user' => 'Users',
    ];

    $groupOrder = ['pinned_conversation', 'conversation', 'user'];
    $grouped = collect($items)->groupBy('kind');
@endphp

<div class="space-y-0" data-chat-sidebar-list>
    @foreach($groupOrder as $kind)
        @if($grouped->has($kind))
            <section class="chat-group" data-chat-group data-chat-kind="{{ $kind }}">
                <div class="sticky top-0 z-10 border-y border-slate-200 bg-slate-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
                    {{ $groupLabels[$kind] ?? 'Items' }}
                </div>

                @foreach($grouped->get($kind) as $item)
                    @php
                        $isUser = data_get($item, 'kind') === 'user';
                        $isActive = !$isUser && (int) data_get($item, 'conversation_id') === (int) $activeConversationId;
                    @endphp

                    <div
                        class="border-b border-slate-100 bg-white transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800/70 {{ $isActive ? 'bg-slate-100 dark:bg-slate-800/80' : '' }}"
                        data-chat-row
                        data-chat-kind="{{ data_get($item, 'kind') }}"
                        data-chat-search="{{ data_get($item, 'search') }}"
                        data-open-url="{{ data_get($item, 'url') }}"
                    >
                        @if($isUser)
                            <button type="button" class="js-open-chat flex w-full items-center gap-3 px-4 py-3 text-left">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-semibold text-white">
                                    {{ data_get($item, 'avatar') }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                                            {{ data_get($item, 'title') }}
                                        </h3>
                                        <span class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                            New
                                        </span>
                                    </div>
                                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ data_get($item, 'subtitle') }}
                                    </p>
                                </div>
                            </button>
                        @else
                            <button type="button" class="js-open-chat flex w-full items-center gap-3 px-4 py-3 text-left">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">
                                    {{ data_get($item, 'avatar') }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3 class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">
                                            {{ data_get($item, 'title') }}
                                        </h3>

                                        <div class="flex items-center gap-2">
                                            @if(data_get($item, 'is_pinned'))
                                                <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                                    Pinned
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ data_get($item, 'subtitle') }}
                                    </p>
                                </div>
                            </button>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif
    @endforeach
</div>
