@extends('layouts.app')

@section('title', 'Messages')
@section('content_wrapper_class', 'p-0 mx-0 max-w-none')
@section('sidebar_default', 'collapsed')
@section('sidebar_hover_enabled', 'false')

@section('content')
    @include('components.chat.conversation-page-styles')

    <button type="button" class="sidebar-mobile-toggle js-sidebar-open fixed left-4 top-20 z-[60] rounded-2xl border border-slate-200 bg-white/95 px-3 py-2 text-sm font-semibold text-slate-900 shadow-lg backdrop-blur md:hidden dark:border-slate-800 dark:bg-slate-950/95 dark:text-slate-100">
        Chats
    </button>
    <button type="button" class="sidebar-mobile-overlay js-sidebar-overlay" aria-label="Close sidebar"></button>

    <div class="conversation-page">
        <aside class="conversation-sidebar" id="conversationSidebar">{!! $sidebarHtml !!}</aside>
        <main class="conversation-chat" id="conversationChat">{!! $chatHtml !!}</main>
    </div>

    <script>
        window.__conversationPage = {
            initialConversationId: @json($activeConversationId ?? null),
            conversationType: @json($conversationType ?? 'private'),
            pollUrl: @json(route('messages.conversations.poll')),
            usersUrl: @json(route('messages.conversations.users')),
            createUrl: @json(route('messages.conversations.store')),
            searchUrl: @json(route('messages.search.messages')),
            resendUrl: @json(route('messages.messages.resend')),
        };
    </script>

    @include('components.chat.conversation-page-modals')
    @include('components.chat.conversation-page-script')
@endsection
