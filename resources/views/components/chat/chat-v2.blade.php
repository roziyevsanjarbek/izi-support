@props([
    'conversation',
    'users' => collect(),
    'messages' => collect(),
    'fetchUrl',
    'sendUrl',
    'toggleNotificationsUrl' => null,
    'polling' => true,
    'width' => '100%',
    'height' => 'calc(100dvh - 160px)',
])

@php
    $chatId = 'chat-' . $conversation->id;
    $messagesId = $chatId . '-messages';
    $filesId = $chatId . '-files';
    $textId = $chatId . '-text';
    $previewId = $chatId . '-previews';
    $sendBtnId = $chatId . '-send';
    $topLoaderId = $chatId . '-top-loader';
    $isCurrentUserPermission = collect($users)->firstWhere('user_id', auth()->id());
    $currentUserId = auth()->id();
@endphp

<style>
    .chat-shell {
        --chat-bg: #ffffff;
        --chat-surface: #ffffff;
        --chat-surface-2: #f8fafc;
        --chat-soft: #f8fafc;
        --chat-soft-2: #e2e8f0;
        --chat-border: #e5e7eb;
        --chat-border-2: #dbe4ee;
        --chat-text: #0f172a;
        --chat-text-2: #334155;
        --chat-muted: #64748b;
        --chat-muted-2: #94a3b8;
        --chat-primary: #2563eb;
        --chat-primary-hover: #1d4ed8;
        --chat-success: #10b981;
        --chat-success-hover: #059669;
        --chat-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);

        position: relative;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--chat-border);
        border-radius: 16px;
        background: var(--chat-bg);
        color: var(--chat-text);
        box-shadow: var(--chat-shadow);
    }

    html.dark .chat-shell {
        --chat-bg: #0f172a;
        --chat-surface: #111827;
        --chat-surface-2: #0b1220;
        --chat-soft: rgba(255, 255, 255, 0.04);
        --chat-soft-2: rgba(255, 255, 255, 0.08);
        --chat-border: #1f2937;
        --chat-border-2: #334155;
        --chat-text: #e5e7eb;
        --chat-text-2: #cbd5e1;
        --chat-muted: #94a3b8;
        --chat-muted-2: #64748b;
        --chat-primary: #3b82f6;
        --chat-primary-hover: #2563eb;
        --chat-success: #10b981;
        --chat-success-hover: #059669;
        --chat-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
    }

    .chat-header {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--chat-border);
        background: var(--chat-surface);
    }

    .chat-header-info {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .chat-title {
        font-size: 16px;
        font-weight: 700;
        line-height: 1.2;
        color: var(--chat-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-subtitle {
        font-size: 12px;
        color: var(--chat-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .chat-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid var(--chat-border);
        background: var(--chat-surface);
        cursor: pointer;
        font-size: 13px;
        text-decoration: none;
        color: var(--chat-text);
        white-space: nowrap;
    }

    .chat-btn:hover {
        background: var(--chat-soft);
    }

    .chat-switch {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        user-select: none;
        color: var(--chat-text);
    }

    .chat-switch input {
        display: none;
    }

    .chat-switch-track {
        width: 44px;
        height: 24px;
        border-radius: 999px;
        background: #d1d5db;
        position: relative;
        transition: 0.2s ease;
        flex-shrink: 0;
    }

    .chat-switch-thumb {
        width: 18px;
        height: 18px;
        border-radius: 999px;
        background: #fff;
        position: absolute;
        top: 3px;
        left: 3px;
        box-shadow: 0 1px 4px rgba(0,0,0,.18);
        transition: 0.2s ease;
    }

    .chat-switch input:checked + .chat-switch-track {
        background: var(--chat-success);
    }

    .chat-switch input:checked + .chat-switch-track .chat-switch-thumb {
        transform: translateX(20px);
    }

    .chat-switch-label {
        font-size: 13px;
    }

    .chat-body {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
        overflow: hidden;
        position: relative;
    }

    .chat-messages {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 10px 8px 12px;
        scroll-behavior: smooth;
    }

    .chat-loader {
        text-align: center;
        font-size: 12px;
        color: var(--chat-muted);
        padding: 8px 0;
    }

    .chat-day {
        display: flex;
        justify-content: center;
        margin: 12px 0;
    }

    .chat-day span {
        font-size: 12px;
        padding: 6px 10px;
        border-radius: 999px;
        background: var(--chat-soft-2);
        color: var(--chat-text-2);
    }

    .msg-row {
        display: flex;
        margin-bottom: 10px;
    }

    .msg-row.left {
        justify-content: flex-start;
    }

    .msg-row.right {
        justify-content: flex-end;
    }

    .msg-wrap {
        max-width: min(78%, 680px);
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .msg-name {
        font-size: 12px;
        color: var(--chat-muted);
        padding-left: 2px;
        font-weight: 600;
    }

    .bubble {
        border-radius: 14px;
        padding: 10px 12px;
        font-size: 14px;
        line-height: 1.45;
        word-break: break-word;
        background: var(--chat-surface);
        border: 1px solid var(--chat-border);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        color: var(--chat-text);
    }

    .bubble.mine {
        background: linear-gradient(135deg, var(--chat-primary), var(--chat-primary-hover));
        color: #fff;
        border-color: transparent;
        border-bottom-right-radius: 4px;
    }

    .bubble.other {
        background: var(--chat-surface);
        color: var(--chat-text);
        border-bottom-left-radius: 4px;
    }

    .msg-topline {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 6px;
    }

    .msg-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .msg-reply-btn {
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        opacity: 0.9;
        padding: 0;
        line-height: 1;
        white-space: nowrap;
    }

    .msg-reply-btn:hover {
        text-decoration: underline;
        opacity: 1;
    }

    .bubble.mine .msg-reply-btn {
        color: rgba(255,255,255,0.9);
    }

    .bubble.other .msg-reply-btn {
        color: var(--chat-primary);
    }

    .reply-preview {
        width: 100%;
        display: flex;
        align-items: stretch;
        gap: 8px;
        text-align: left;
        border: 0;
        background: rgba(255,255,255,0.08);
        color: inherit;
        border-radius: 10px;
        padding: 8px 10px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: transform 0.15s ease, opacity 0.15s ease, background 0.15s ease;
    }

    .reply-preview:hover {
        transform: translateY(-1px);
        opacity: 0.98;
    }

    .bubble.other .reply-preview {
        background: var(--chat-soft);
    }

    .reply-line {
        width: 3px;
        border-radius: 999px;
        background: rgba(255,255,255,0.35);
        flex-shrink: 0;
    }

    .bubble.other .reply-line {
        background: var(--chat-primary);
    }

    .reply-content {
        min-width: 0;
        flex: 1;
    }

    .reply-user {
        font-size: 12px;
        font-weight: 700;
        opacity: 0.95;
        margin-bottom: 2px;
    }

    .reply-text {
        font-size: 12px;
        opacity: 0.88;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .msg-text {
        white-space: normal;
    }

    .msg-meta {
        display: flex;
        justify-content: flex-end;
        margin-top: 4px;
    }

    .msg-time {
        font-size: 11px;
        color: var(--chat-muted-2);
    }

    .msg-media {
        margin-top: 8px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .media-thumb {
        width: 160px;
        height: 160px;
        object-fit: cover;
        border-radius: 10px;
        cursor: zoom-in;
        display: block;
    }

    .media-file {
        display: flex;
        gap: 8px;
        align-items: center;
        padding: 10px;
        border: 1px solid var(--chat-border);
        border-radius: 10px;
        background: var(--chat-surface);
        color: inherit;
        text-decoration: none;
        max-width: 280px;
    }

    .media-icon {
        font-size: 18px;
    }

    .media-filename {
        font-weight: 600;
    }

    .media-file small {
        display: block;
        color: var(--chat-muted);
        margin-top: 2px;
    }

    .chat-reply-bar {
        display: none;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin: 0 12px 8px;
        padding: 10px 12px;
        border: 1px solid var(--chat-border);
        border-radius: 12px;
        background: var(--chat-surface);
    }

    .chat-reply-bar.show {
        display: flex;
    }

    .chat-reply-info {
        min-width: 0;
        flex: 1;
    }

    .chat-reply-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--chat-primary);
        margin-bottom: 3px;
    }

    .chat-reply-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--chat-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-reply-text {
        font-size: 12px;
        color: var(--chat-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 2px;
    }

    .chat-reply-cancel {
        border: 0;
        width: 28px;
        height: 28px;
        border-radius: 999px;
        background: var(--chat-soft-2);
        color: var(--chat-text);
        cursor: pointer;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .chat-reply-cancel:hover {
        background: var(--chat-border-2);
    }

    .chat-form {
        flex-shrink: 0;
        margin-top: auto;
        display: flex;
        align-items: flex-end;
        gap: 10px;
        padding: 12px;
        border-top: 1px solid var(--chat-border);
        background: var(--chat-surface);
        position: sticky;
        bottom: 0;
        z-index: 10;
    }

    .chat-input {
        flex: 1;
        min-height: 44px;
        max-height: 120px;
        border: 1px solid var(--chat-border);
        border-radius: 14px;
        padding: 10px 12px;
        resize: none;
        outline: none;
        font: inherit;
        background: var(--chat-surface);
        color: var(--chat-text);
    }

    .chat-input::placeholder {
        color: var(--chat-muted);
    }

    .chat-input:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.10);
    }

    .icon-btn {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        border: 1px solid var(--chat-border);
        background: var(--chat-surface);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--chat-text);
    }

    .icon-btn:hover {
        background: var(--chat-soft);
    }

    .send-btn {
        background: var(--chat-primary);
        color: #fff;
        border-color: transparent;
    }

    .send-btn:hover {
        background: var(--chat-primary-hover);
    }

    .preview-row {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        max-width: 100%;
        padding-bottom: 2px;
    }

    .preview-item {
        position: relative;
        width: 64px;
        height: 64px;
        border: 1px solid var(--chat-border);
        border-radius: 10px;
        overflow: hidden;
        background: var(--chat-surface);
        flex-shrink: 0;
        color: var(--chat-text);
    }

    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-item .remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 20px;
        height: 20px;
        border-radius: 999px;
        border: none;
        background: rgba(0,0,0,.65);
        color: #fff;
        font-size: 12px;
        cursor: pointer;
    }

    .users-dropdown {
        position: relative;
    }

    .users-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        z-index: 20;
        width: 280px;
        max-height: 260px;
        overflow-y: auto;
        border: 1px solid var(--chat-border);
        border-radius: 14px;
        background: var(--chat-surface);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
        padding: 8px;
    }

    .users-menu-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 13px;
        color: var(--chat-text);
    }

    .users-menu-item:hover {
        background: var(--chat-soft);
    }

    .users-name {
        font-weight: 600;
    }

    .users-role,
    .users-notif,
    .users-empty {
        font-size: 11px;
        color: var(--chat-muted);
    }

    .highlight-reply {
        animation: replyFlash 1.2s ease;
        outline: 2px solid var(--chat-primary);
        outline-offset: 2px;
        border-radius: 14px;
    }

    @keyframes replyFlash {
        0% { transform: scale(1); }
        20% { transform: scale(1.01); }
        100% { transform: scale(1); }
    }

    .chat-scroll-bottom {
        position: absolute;
        right: 16px;
        bottom: 88px;
        z-index: 15;
        width: 42px;
        height: 42px;
        border: 1px solid var(--chat-border);
        border-radius: 999px;
        background: var(--chat-surface);
        color: var(--chat-text);
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
    }

    .chat-scroll-bottom:hover {
        background: var(--chat-soft);
    }

    .chat-scroll-bottom.show {
        display: inline-flex;
    }

    .chat-scroll-bottom svg {
        width: 18px;
        height: 18px;
    }
</style>

<div
    id="{{ $chatId }}"
    class="chat-shell"
    style="width: {{ $width }}; height: {{ $height }};"
    data-fetch-url="{{ $fetchUrl }}"
    data-send-url="{{ $sendUrl }}"
    data-toggle-notifications-url="{{ $toggleNotificationsUrl }}"
    data-polling="{{ $polling ? '1' : '0' }}"
>
    <div class="chat-header">
        <div class="chat-header-info">
            <div class="chat-title">
                {{ $conversation->name ?: ('Conversation #' . $conversation->id) }}
            </div>
            <div class="chat-subtitle">
                {{ $conversation->description ?: 'Chat' }}
            </div>
        </div>

        <div class="chat-actions">
            <div class="users-dropdown" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="chat-btn" @click="open = !open">
                    <span>Users</span>
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none">
                        <path d="M5 7L10 12L15 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <div x-show="open" x-transition class="users-menu" style="display:none;">
                    @forelse($users as $permission)
                        <div class="users-menu-item">
                            <div>
                                <div class="users-name">
                                    {{ $permission->user->name ?? ('User #' . $permission->user_id) }}
                                </div>
                                <div class="users-role">
                                    {{ $permission->role ?? 'member' }}
                                </div>
                            </div>
                            <div class="users-notif">
                                {{ $permission->notifications ? 'On' : 'Off' }}
                            </div>
                        </div>
                    @empty
                        <div class="users-menu-item users-empty">
                            No users
                        </div>
                    @endforelse
                </div>
            </div>

            @if($isCurrentUserPermission && $toggleNotificationsUrl)
                <label class="chat-switch">
                    <input
                        type="checkbox"
                        id="{{ $chatId }}-notif-toggle"
                        {{ $isCurrentUserPermission->notifications ? 'checked' : '' }}
                    >

                    <span class="chat-switch-track">
                        <span class="chat-switch-thumb"></span>
                    </span>

                    <span class="chat-switch-label">Notification</span>
                </label>
            @endif
        </div>
    </div>

    <div class="chat-body">
        <button type="button" class="chat-scroll-bottom" id="{{ $chatId }}-scroll-bottom" title="Eng oxiriga tushish">
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M5 8L10 13L15 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>

        <div id="{{ $messagesId }}" class="chat-messages" aria-live="polite" aria-atomic="false">
            <div id="{{ $topLoaderId }}" class="chat-loader" style="display:none;">Yuklanmoqda...</div>

            @php
                $lastDay = null;
            @endphp

            @foreach($messages as $message)
                @php
                    $day = optional($message->created_at)->format('Y-m-d');
                    $isMine = (int) $message->user_id === (int) $currentUserId;
                    $fileUrl = $message->file_url ?? ($message->file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($message->file_path) : null);
                    $mime = $message->mime_type ?? '';
                @endphp

                @if($day !== $lastDay)
                    <div class="chat-day">
                        <span>{{ optional($message->created_at)->translatedFormat('d F Y') }}</span>
                    </div>
                    @php $lastDay = $day; @endphp
                @endif

                <div
                    class="msg-row {{ $isMine ? 'right' : 'left' }}"
                    data-message-id="{{ $message->id }}"
                    data-day="{{ $day }}"
                    data-reply-to-id="{{ $message->reply_to_id ?? '' }}"
                    data-message-text="{{ e($message->message ?? '') }}"
                    data-message-name="{{ e($message->sender->name ?? 'User') }}"
                >
                    <div class="msg-wrap">
                        @if(!$isMine)
                            <div class="msg-name">
                                {{ $message->sender->name ?? 'User' }}
                            </div>
                        @endif

                        <div class="bubble {{ $isMine ? 'mine' : 'other' }}">
                            <div class="msg-topline">
                                <div></div>
                                <div class="msg-actions">
                                    <button
                                        type="button"
                                        class="msg-reply-btn js-set-reply"
                                        data-reply-id="{{ $message->id }}"
                                        data-reply-name="{{ e($message->sender->name ?? 'User') }}"
                                        data-reply-text="{{ e($message->message ?: ($message->file_name ?? 'file')) }}"
                                    >
                                        Reply
                                    </button>
                                </div>
                            </div>

                            @if($message->replyTo)
                                <button
                                    type="button"
                                    class="reply-preview js-reply-jump"
                                    data-reply-id="{{ $message->replyTo->id }}"
                                >
                                    <div class="reply-line"></div>
                                    <div class="reply-content">
                                        <div class="reply-user">
                                            {{ $message->replyTo->sender->name ?? 'User' }}
                                        </div>
                                        <div class="reply-text">
                                            {{ $message->replyTo->message ?: ($message->replyTo->file_name ?? 'file') }}
                                        </div>
                                    </div>
                                </button>
                            @endif

                            @if($message->message)
                                <div class="msg-text">
                                    {!! nl2br(e($message->message)) !!}
                                </div>
                            @endif

                            @if($fileUrl)
                                <div class="msg-media">
                                    @if(str_starts_with($mime, 'image/') || in_array(strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']))
                                        <a href="{{ $fileUrl }}" class="js-media-open" data-mime="{{ $mime }}" data-name="{{ $message->file_name ?? 'file' }}">
                                            <img src="{{ $fileUrl }}" alt="{{ $message->file_name ?? 'file' }}" class="media-thumb">
                                        </a>
                                    @else
                                        <a href="{{ $fileUrl }}" class="js-media-open media-file" data-mime="{{ $mime }}" data-name="{{ $message->file_name ?? 'file' }}">
                                            <div class="media-icon">📎</div>
                                            <div>
                                                <div class="media-filename">
                                                    {{ $message->file_name ?? basename($fileUrl) }}
                                                </div>
                                                <small>{{ $mime ?: 'File' }}</small>
                                            </div>
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <div class="msg-meta">
                                <span class="msg-time">
                                    {{ optional($message->created_at)->format('H:i') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="{{ $chatId }}-reply-bar" class="chat-reply-bar">
            <div class="chat-reply-info">
                <div class="chat-reply-label">Reply to</div>
                <div id="{{ $chatId }}-reply-title" class="chat-reply-title"></div>
                <div id="{{ $chatId }}-reply-text" class="chat-reply-text"></div>
            </div>
            <button type="button" class="chat-reply-cancel" id="{{ $chatId }}-reply-cancel" title="Cancel reply">×</button>
        </div>

        <div class="chat-form">
            <input id="{{ $filesId }}" type="file" hidden multiple>
            <input type="hidden" id="{{ $chatId }}-reply-to-message-id" name="reply_to_id" value="">

            <button type="button" class="icon-btn" id="{{ $chatId }}-attach" title="Attach">
                +
            </button>

            <textarea
                id="{{ $textId }}"
                class="chat-input"
                placeholder="Xabar yozing..."
                rows="1"
            ></textarea>

            <div id="{{ $previewId }}" class="preview-row"></div>

            <button type="button" class="icon-btn send-btn" id="{{ $sendBtnId }}" title="Send">
                ➤
            </button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.chat-shell[data-fetch-url]').forEach(function (root) {
                    if (root.dataset.initialized === '1') return;
                    root.dataset.initialized = '1';

                    const fetchUrl = root.dataset.fetchUrl;
                    const sendUrl = root.dataset.sendUrl;
                    const pollingEnabled = root.dataset.polling === '1';
                    const toggleUrl = root.dataset.toggleNotificationsUrl || '';

                    const messagesContainer = root.querySelector('.chat-messages');
                    const topLoader = root.querySelector('[id$="-top-loader"]');
                    const filesInput = root.querySelector('input[type="file"]');
                    const textInput = root.querySelector('textarea');
                    const previewRow = root.querySelector('.preview-row');
                    const attachBtn = root.querySelector('[id$="-attach"]');
                    const sendBtn = root.querySelector('[id$="-send"]');
                    const notifCheckbox = root.querySelector('[id$="-notif-toggle"]');
                    const scrollBottomBtn = root.querySelector('[id$="-scroll-bottom"]');
                    const replyBar = root.querySelector('[id$="-reply-bar"]');
                    const replyTitle = root.querySelector('[id$="-reply-title"]');
                    const replyText = root.querySelector('[id$="-reply-text"]');
                    const replyCancelBtn = root.querySelector('[id$="-reply-cancel"]');
                    const replyInput = root.querySelector('[id$="-reply-to-message-id"]');

                    const renderedMessageIds = new Set(
                        [...messagesContainer.querySelectorAll('.msg-row[data-message-id]')]
                            .map(row => String(row.dataset.messageId || ''))
                            .filter(Boolean)
                    );

                    let currentPage = 1;
                    let hasMore = true;
                    let isLoading = false;
                    let selectedFiles = [];
                    let lastSeenMessageId = 0;
                    let firstLoaded = false;

                    function csrfToken() {
                        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    }

                    function escapeHtml(text = '') {
                        return String(text)
                            .replaceAll('&', '&amp;')
                            .replaceAll('<', '&lt;')
                            .replaceAll('>', '&gt;')
                            .replaceAll('"', '&quot;')
                            .replaceAll("'", '&#039;');
                    }

                    function dayKey(dateValue) {
                        if (!dateValue) return '';
                        const d = new Date(dateValue);
                        if (isNaN(d.getTime())) return '';
                        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                    }

                    function formatDay(dateValue) {
                        const d = new Date(dateValue);
                        if (isNaN(d.getTime())) return '';
                        return d.toLocaleDateString(undefined, { day: '2-digit', month: 'long', year: 'numeric' });
                    }

                    function messageId(msg) {
                        return Number(msg?.id || 0);
                    }

                    function messageExists(id) {
                        return renderedMessageIds.has(String(id));
                    }

                    function registerMessageId(id) {
                        if (id) renderedMessageIds.add(String(id));
                    }

                    function createDaySeparator(text) {
                        const div = document.createElement('div');
                        div.className = 'chat-day';
                        div.innerHTML = `<span>${escapeHtml(text)}</span>`;
                        return div;
                    }

                    function createReplyPreview(reply) {
                        if (!reply) return null;

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'reply-preview js-reply-jump';
                        btn.dataset.replyId = reply.id;

                        const line = document.createElement('div');
                        line.className = 'reply-line';

                        const content = document.createElement('div');
                        content.className = 'reply-content';

                        const user = document.createElement('div');
                        user.className = 'reply-user';
                        user.textContent = reply.user_name || 'User';

                        const text = document.createElement('div');
                        text.className = 'reply-text';
                        text.textContent = reply.message || reply.file_name || 'file';

                        content.appendChild(user);
                        content.appendChild(text);
                        btn.appendChild(line);
                        btn.appendChild(content);

                        return btn;
                    }

                    function createMessageNode(msg) {
                        const row = document.createElement('div');
                        row.className = 'msg-row ' + (msg.is_mine ? 'right' : 'left');
                        row.dataset.messageId = messageId(msg);
                        row.dataset.day = dayKey(msg.created_at);
                        row.dataset.replyToId = msg.reply_to?.id || msg.reply_to_id || '';
                        row.dataset.messageText = msg.message || '';
                        row.dataset.messageName = msg.sender_name || 'User';

                        const wrap = document.createElement('div');
                        wrap.className = 'msg-wrap';

                        if (!msg.is_mine && msg.sender_name) {
                            const name = document.createElement('div');
                            name.className = 'msg-name';
                            name.textContent = msg.sender_name;
                            wrap.appendChild(name);
                        }

                        const bubble = document.createElement('div');
                        bubble.className = 'bubble ' + (msg.is_mine ? 'mine' : 'other');

                        const topline = document.createElement('div');
                        topline.className = 'msg-topline';

                        const actions = document.createElement('div');
                        actions.className = 'msg-actions';

                        const replyBtn = document.createElement('button');
                        replyBtn.type = 'button';
                        replyBtn.className = 'msg-reply-btn js-set-reply';
                        replyBtn.dataset.replyId = messageId(msg);
                        replyBtn.dataset.replyName = msg.sender_name || 'User';
                        replyBtn.dataset.replyText = msg.message || msg.file_name || 'file';
                        replyBtn.textContent = 'Reply';

                        actions.appendChild(replyBtn);
                        topline.appendChild(document.createElement('div'));
                        topline.appendChild(actions);
                        bubble.appendChild(topline);

                        if (msg.reply_to) {
                            const reply = createReplyPreview(msg.reply_to);
                            if (reply) bubble.appendChild(reply);
                        }

                        if (msg.message) {
                            const text = document.createElement('div');
                            text.className = 'msg-text';
                            text.innerHTML = escapeHtml(msg.message).replace(/\n/g, '<br>');
                            bubble.appendChild(text);
                        }

                        if (msg.file_url) {
                            const media = document.createElement('div');
                            media.className = 'msg-media';

                            const ext = (msg.file_name || msg.file_url.split('/').pop() || '').split('.').pop().toLowerCase();
                            const isImage = (msg.mime_type || '').startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext);

                            const a = document.createElement('a');
                            a.href = msg.file_url;
                            a.className = isImage ? 'js-media-open' : 'js-media-open media-file';
                            a.dataset.mime = msg.mime_type || '';
                            a.dataset.name = msg.file_name || 'file';

                            if (isImage) {
                                const img = document.createElement('img');
                                img.src = msg.file_url;
                                img.alt = msg.file_name || 'file';
                                img.className = 'media-thumb';
                                a.appendChild(img);
                            } else {
                                a.innerHTML = `
                                    <div class="media-icon">📎</div>
                                    <div>
                                        <div class="media-filename">${escapeHtml(msg.file_name || 'File')}</div>
                                        <small>${escapeHtml(msg.mime_type || 'File')}</small>
                                    </div>
                                `;
                            }

                            media.appendChild(a);
                            bubble.appendChild(media);
                        }

                        const meta = document.createElement('div');
                        meta.className = 'msg-meta';
                        meta.innerHTML = `<span class="msg-time">${escapeHtml(msg.created_at_time || '')}</span>`;
                        bubble.appendChild(meta);

                        wrap.appendChild(bubble);
                        row.appendChild(wrap);

                        return row;
                    }

                    function findMessageRow(id) {
                        return root.querySelector(`.msg-row[data-message-id="${CSS.escape(String(id))}"]`);
                    }

                    function jumpToMessage(id) {
                        const target = findMessageRow(id);
                        if (!target) return;

                        const containerRect = messagesContainer.getBoundingClientRect();
                        const targetRect = target.getBoundingClientRect();
                        const delta = targetRect.top - containerRect.top - 80;

                        messagesContainer.scrollTo({
                            top: messagesContainer.scrollTop + delta,
                            behavior: 'smooth'
                        });

                        target.classList.add('highlight-reply');
                        setTimeout(() => target.classList.remove('highlight-reply'), 1400);
                    }

                    function setReplyTarget(id, name, text) {
                        replyInput.value = id || '';
                        replyBar.classList.add('show');
                        replyTitle.textContent = name || 'User';
                        replyText.textContent = text || 'message';
                    }

                    function clearReplyTarget() {
                        replyInput.value = '';
                        replyBar.classList.remove('show');
                        replyTitle.textContent = '';
                        replyText.textContent = '';
                    }

                    function appendMessage(msg) {
                        if (!msg || !messageId(msg) || messageExists(messageId(msg))) return false;

                        const day = dayKey(msg.created_at);
                        const lastRow = [...messagesContainer.querySelectorAll('.msg-row')].pop();
                        const lastDay = lastRow ? lastRow.dataset.day : null;

                        if (lastDay !== day) {
                            messagesContainer.appendChild(createDaySeparator(formatDay(msg.created_at)));
                        }

                        messagesContainer.appendChild(createMessageNode(msg));
                        registerMessageId(messageId(msg));
                        return true;
                    }

                    function prependMessages(msgs) {
                        const uniqueMsgs = msgs.filter(msg => msg && messageId(msg) && !messageExists(messageId(msg)));
                        if (!uniqueMsgs.length) return;

                        const fragment = document.createDocumentFragment();
                        let previousDay = null;

                        uniqueMsgs.forEach(msg => {
                            const day = dayKey(msg.created_at);

                            if (day && day !== previousDay) {
                                fragment.appendChild(createDaySeparator(formatDay(msg.created_at)));
                                previousDay = day;
                            }

                            fragment.appendChild(createMessageNode(msg));
                            registerMessageId(messageId(msg));
                        });

                        const loader = messagesContainer.querySelector('[id$="-top-loader"]');
                        messagesContainer.insertBefore(fragment, loader ? loader.nextSibling : messagesContainer.firstChild);
                    }

                    function getLastMessageIdFromDom() {
                        const rows = [...messagesContainer.querySelectorAll('.msg-row[data-message-id]')];
                        if (!rows.length) return 0;
                        return Number(rows[rows.length - 1].dataset.messageId || 0);
                    }

                    function isNearBottom(threshold = 180) {
                        return (messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight) < threshold;
                    }

                    function scrollToBottom() {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }

                    function toggleScrollBottomBtn() {
                        if (!scrollBottomBtn) return;
                        if (isNearBottom(120)) {
                            scrollBottomBtn.classList.remove('show');
                        } else {
                            scrollBottomBtn.classList.add('show');
                        }
                    }

                    async function toggleNotif() {
                        if (!toggleUrl || !notifCheckbox) return;

                        try {
                            const res = await fetch(toggleUrl, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken(),
                                    'Content-Type': 'application/json'
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({})
                            });

                            const data = await res.json().catch(() => ({}));

                            if (typeof data.notifications !== 'undefined') {
                                notifCheckbox.checked = !!data.notifications;
                            } else if (!res.ok) {
                                notifCheckbox.checked = !notifCheckbox.checked;
                            }
                        } catch (e) {
                            console.error(e);
                            notifCheckbox.checked = !notifCheckbox.checked;
                        }
                    }

                    async function loadMessages(page = 1, prepend = false) {
                        if (isLoading) return;
                        if (prepend && !hasMore) return;

                        isLoading = true;
                        if (prepend) topLoader.style.display = 'block';

                        const prevScrollHeight = messagesContainer.scrollHeight;
                        const prevScrollTop = messagesContainer.scrollTop;

                        try {
                            const res = await fetch(`${fetchUrl}?page=${page}`, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin'
                            });

                            if (!res.ok) throw new Error('Network error');

                            const data = await res.json();
                            const msgs = Array.isArray(data.messages) ? data.messages : [];

                            msgs.sort((a, b) => (a.id || 0) - (b.id || 0));

                            if (prepend) {
                                prependMessages(msgs);
                                messagesContainer.scrollTop = messagesContainer.scrollHeight - prevScrollHeight + prevScrollTop;
                            } else {
                                msgs.forEach(appendMessage);
                            }

                            hasMore = !!data.has_more;
                        } catch (e) {
                            console.error(e);
                        } finally {
                            isLoading = false;
                            topLoader.style.display = 'none';
                            toggleScrollBottomBtn();
                        }
                    }

                    async function loadInitial() {
                        if (firstLoaded) return;
                        firstLoaded = true;

                        const hasServerMessages = messagesContainer.querySelectorAll('.msg-row[data-message-id]').length > 0;

                        if (!hasServerMessages) {
                            await loadMessages(1, false);
                        }

                        lastSeenMessageId = getLastMessageIdFromDom();
                        // scrollToBottom();
                        requestAnimationFrame(() => {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 50);
});
                        
                        toggleScrollBottomBtn();
                    }

                    async function sendMessage() {
                        const text = (textInput.value || '').trim();

                        if (!text && selectedFiles.length === 0) return;

                        const fd = new FormData();
                        fd.append('_token', csrfToken());
                        fd.append('message', text);

                        if (replyInput.value) {
                            fd.append('reply_to_id', replyInput.value);
                        }

                        selectedFiles.forEach(file => fd.append('files[]', file));

                        sendBtn.disabled = true;

                        try {
                            const res = await fetch(sendUrl, {
                                method: 'POST',
                                body: fd,
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin'
                            });

                            const data = await res.json().catch(() => ({}));

                            if (!res.ok) {
                                alert(data?.message || 'Xabar yuborilmadi.');
                                return;
                            }

                            if (data?.success && data?.message) {
                                appendMessage(data.message);
                                scrollToBottom();
                                textInput.value = '';
                                selectedFiles = [];
                                renderPreviews();
                                clearReplyTarget();
                                lastSeenMessageId = Math.max(lastSeenMessageId, Number(messageId(data.message)));
                                toggleScrollBottomBtn();
                            }
                        } catch (e) {
                            console.error(e);
                            alert('Xabar yuborishda xatolik yuz berdi.');
                        } finally {
                            sendBtn.disabled = false;
                        }
                    }

                    function renderPreviews() {
                        previewRow.innerHTML = '';

                        selectedFiles.forEach((file, index) => {
                            const wrap = document.createElement('div');
                            wrap.className = 'preview-item';

                            const remove = document.createElement('button');
                            remove.type = 'button';
                            remove.className = 'remove';
                            remove.textContent = '×';
                            remove.addEventListener('click', () => {
                                selectedFiles.splice(index, 1);
                                renderPreviews();
                            });

                            if (file.type.startsWith('image/')) {
                                const img = document.createElement('img');
                                img.src = URL.createObjectURL(file);
                                img.alt = file.name;
                                wrap.appendChild(img);
                            } else {
                                const txt = document.createElement('div');
                                txt.style.fontSize = '11px';
                                txt.style.padding = '8px 6px';
                                txt.style.wordBreak = 'break-word';
                                txt.textContent = file.name;
                                wrap.appendChild(txt);
                            }

                            wrap.appendChild(remove);
                            previewRow.appendChild(wrap);
                        });
                    }

                    attachBtn?.addEventListener('click', () => filesInput.click());

                    filesInput?.addEventListener('change', function () {
                        Array.from(this.files || []).forEach(file => {
                            if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                                selectedFiles.push(file);
                            }
                        });

                        renderPreviews();
                        this.value = '';
                    });

                    sendBtn?.addEventListener('click', function (e) {
                        e.preventDefault();
                        sendMessage();
                    });

                    textInput?.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            sendMessage();
                        }
                    });

                    notifCheckbox?.addEventListener('change', toggleNotif);

                    replyCancelBtn?.addEventListener('click', function () {
                        clearReplyTarget();
                    });

                    messagesContainer.addEventListener('scroll', function () {
                        if (messagesContainer.scrollTop <= 60 && hasMore && !isLoading) {
                            currentPage += 1;
                            loadMessages(currentPage, true);
                        }
                        toggleScrollBottomBtn();
                    });

                    scrollBottomBtn?.addEventListener('click', function () {
                        scrollToBottom();
                        toggleScrollBottomBtn();
                    });

                    messagesContainer.addEventListener('click', function (e) {
                        const replyBtn = e.target.closest('.js-reply-jump');
                        if (replyBtn) {
                            e.preventDefault();
                            jumpToMessage(replyBtn.dataset.replyId);
                            return;
                        }

                        const setReplyBtn = e.target.closest('.js-set-reply');
                        if (setReplyBtn) {
                            e.preventDefault();
                            setReplyTarget(
                                setReplyBtn.dataset.replyId,
                                setReplyBtn.dataset.replyName,
                                setReplyBtn.dataset.replyText
                            );
                        }
                    });

                    if (pollingEnabled) {
                        setInterval(async () => {
                            try {
                                const res = await fetch(`${fetchUrl}?after_id=${lastSeenMessageId}`, {
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                    credentials: 'same-origin'
                                });

                                if (!res.ok) return;

                                const data = await res.json();
                                const msgs = Array.isArray(data.messages) ? data.messages : [];
                                const wasNearBottom = isNearBottom();

                                msgs
                                    .slice()
                                    .sort((a, b) => (a.id || 0) - (b.id || 0))
                                    .forEach(msg => {
                                        if (appendMessage(msg)) {
                                            lastSeenMessageId = Math.max(lastSeenMessageId, Number(messageId(msg)));
                                        }
                                    });

                                if (msgs.length && wasNearBottom) {
                                    scrollToBottom();
                                }

                                toggleScrollBottomBtn();
                            } catch (e) {
                                // silent
                            }
                        }, 3000);
                    }

                    loadInitial();
                });
            });
        </script>
    @endpush
@endonce