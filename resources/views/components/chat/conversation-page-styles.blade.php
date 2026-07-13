<style>
    html, body {
        height: 100%;
    }

    main {
        height: 100%;
        overflow: hidden;
    }
    .members-scroll {
    scrollbar-width: thin;
    overscroll-behavior: contain;
}

    :root {
        --chat-bg: #f8fafc;
        --panel-bg: #ffffff;
        --panel-border: #e5e7eb;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --blue: #2563eb;
        --blue-soft: rgba(37, 99, 235, 0.10);
        --shadow-sm: 0 10px 30px rgba(15, 23, 42, .04);
        --shadow-md: 0 20px 60px rgba(15, 23, 42, .12);
        --radius-xl: 22px;
        --radius-lg: 18px;
        --radius-md: 14px;
    }

    html.dark {
        --chat-bg: #0f172a;
        --panel-bg: #0b1220;
        --panel-border: #1f2937;
        --text-main: #e2e8f0;
        --text-muted: #94a3b8;
        --blue-soft: rgba(59, 130, 246, 0.12);
    }

    .conversation-page {
        position: relative;
        display: grid;
        grid-template-columns: 340px minmax(0, 1fr);
        gap: 14px;
        height: calc(100dvh - 72px);
        overflow: hidden;
        min-height: 0;
    }

    .conversation-sidebar {
        display: flex;
        flex-direction: column;
        min-width: 0;
        min-height: 0;
        overflow: hidden;
        border: 1px solid var(--panel-border);
        border-radius: var(--radius-xl);
        background: var(--panel-bg);
        color: var(--text-main);
        box-shadow: var(--shadow-sm);
    }

    .conversation-sidebar-head {
        flex-shrink: 0;
        padding: 16px;
        border-bottom: 1px solid var(--panel-border);
        background: inherit;
    }

    .conversation-sidebar-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 8px 0 16px;
    }

    .conversation-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 14px;
        text-decoration: none;
        color: inherit;
        border-bottom: 1px solid var(--panel-border);
        transition: background-color .15s ease, border-color .15s ease, transform .15s ease;
    }

    .conversation-item:hover {
        background: rgba(148, 163, 184, .06);
    }

    .conversation-item.is-active {
        background: var(--blue-soft);
        border-left: 3px solid var(--blue);
        padding-left: 11px;
    }

    html.dark .conversation-item.is-active {
        border-left-color: #60a5fa;
    }

    .conversation-search {
        width: 100%;
        border-radius: 16px;
        border: 1px solid var(--panel-border);
        background: rgba(248, 250, 252, .95);
        padding: 10px 14px;
        font-size: 14px;
        outline: none;
        color: var(--text-main);
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }

    .conversation-search::placeholder {
        color: var(--text-muted);
    }

    .conversation-search:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .12);
        background: #fff;
    }

    html.dark .conversation-search {
        background: rgba(15, 23, 42, .92);
    }

    .conversation-chat {
        min-width: 0;
        min-height: 0;
        height: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        border-radius: var(--radius-xl);
        background: var(--chat-bg);
        border: 1px solid var(--panel-border);
        box-shadow: var(--shadow-sm);
    }

    .sidebar-mobile-toggle,
    .sidebar-mobile-overlay {
        display: none;
    }

    .chat-shell {
        height: 100%;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .chat-header {
        flex-shrink: 0;
        border-bottom: 1px solid var(--panel-border);
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        z-index: 10;
    }

    html.dark .chat-header {
        background: rgba(11, 18, 32, .92);
    }

    .chat-body {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        background: var(--chat-bg);
    }

    .chat-messages {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 18px 18px 24px;
        scroll-behavior: smooth;
    }

    .msg-row {
        display: flex;
        margin-bottom: 8px;
        position: relative;
    }

    .msg-row.left {
        justify-content: flex-start;
    }

    .msg-row.right {
        justify-content: flex-end;
    }

    .msg-wrap {
        max-width: min(720px, 86%);
        min-width: 180px;
        position: relative;
    }

    .msg-sender-name {
        font-size: 12px;
        margin-bottom: 4px;
        color: var(--text-muted);
    }

    .bubble {
        position: relative;
        border-radius: 18px;
        padding: 12px 12px 8px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        overflow: hidden;
        border: 1px solid transparent;
    }

    .bubble.mine {
        background: linear-gradient(180deg, rgba(219, 234, 254, 1), rgba(191, 219, 254, .96));
        color: #0f172a;
        border-bottom-right-radius: 6px;
    }

    .bubble.other {
        background: #fff;
        color: #0f172a;
        border-bottom-left-radius: 6px;
        border-color: rgba(226, 232, 240, .72);
    }

    html.dark .bubble.other {
        background: #111827;
        color: #e5e7eb;
        border-color: rgba(148, 163, 184, .12);
    }

    html.dark .bubble.mine {
        background: linear-gradient(180deg, rgba(30, 64, 175, .34), rgba(37, 99, 235, .24));
        color: #e5e7eb;
    }

    .bubble.deleted {
        opacity: .76;
        background: rgba(148, 163, 184, .12);
    }

    .bubble.focused {
        outline: 2px solid rgba(59, 130, 246, .55);
        transform: translateY(-1px);
    }

    .reply-preview {
        border: 1px solid rgba(59, 130, 246, .18);
        border-left-width: 4px;
        padding: 10px 12px;
        margin-bottom: 10px;
        background: rgba(59, 130, 246, .06);
        border-radius: 18px;
    }

    .js-reply-bar.show {
        display: block;
    }

    .reply-preview-name {
        font-size: 11px;
        line-height: 1.2;
        opacity: .72;
    }

    .reply-preview-text {
        font-size: 12px;
        margin-top: 3px;
        opacity: .92;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .msg-text {
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 14px;
        line-height: 1.45;
    }

    .msg-media {
        margin-top: 10px;
    }

    .media-file,
    .location-card {
        display: flex;
        gap: 10px;
        align-items: center;
        border-radius: 14px;
        padding: 10px 12px;
        background: rgba(148, 163, 184, .12);
        text-decoration: none;
        color: inherit;
    }

    .media-img {
        display: block;
        max-width: 280px;
        max-height: 240px;
        border-radius: 16px;
        object-fit: cover;
    }

    .msg-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        margin-top: 6px;
        font-size: 11px;
        opacity: .82;
    }

    .chat-composer {
        flex-shrink: 0;
        border-top: 1px solid var(--panel-border);
        background: rgba(255, 255, 255, .94);
        padding: 12px;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    html.dark .chat-composer {
        background: rgba(11, 18, 32, .94);
    }

    .composer-box {
        border: 1px solid var(--panel-border);
        border-radius: 22px;
        background: rgba(248, 250, 252, .98);
        padding: 10px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    html.dark .composer-box {
        background: rgba(15, 23, 42, .92);
    }

    .composer-input {
        width: 100%;
        min-height: 48px;
        max-height: 120px;
        resize: none;
        border: 0;
        outline: none;
        background: transparent;
        color: inherit;
    }

    .context-menu {
        position: fixed;
        z-index: 100001;
        min-width: 230px;
        background: rgba(15, 23, 42, .98);
        color: #e2e8f0;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 18px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .28);
        padding: 8px;
        display: none;
        overflow: hidden;
    }

    .context-menu.show {
        display: block;
    }

    .context-menu button,
    .context-menu a {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        border: 0;
        background: transparent;
        color: inherit;
        padding: 10px 12px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 14px;
        cursor: pointer;
        transition: background .15s ease, transform .15s ease;
    }

    .context-menu button:hover,
    .context-menu a:hover {
        background: rgba(148, 163, 184, .14);
        transform: translateX(2px);
    }

    .modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 100000;
        background: rgba(15, 23, 42, .56);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }

    .modal-backdrop.show {
        display: flex;
    }

    .modal-card {
        width: min(920px, 100%);
        max-height: min(88vh, 840px);
        overflow: hidden;
        border-radius: 26px;
        background: #fff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 28px 80px rgba(15, 23, 42, .35);
        display: flex;
        flex-direction: column;
    }

    html.dark .modal-card {
        background: #0b1220;
        border-color: #1f2937;
        color: #e2e8f0;
    }

    .modal-head {
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(to bottom, rgba(255,255,255,.04), rgba(255,255,255,0));
    }

    html.dark .modal-head {
        border-color: #1f2937;
    }

    .modal-body {
        padding: 16px 18px;
        overflow: auto;
    }

    .result-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 12px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease, transform .15s ease;
    }

    .result-item:hover {
        background: rgba(148, 163, 184, .08);
        transform: translateY(-1px);
    }

    html.dark .result-item {
        border-color: #1f2937;
    }

    .result-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 16px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        font-weight: 700;
        flex-shrink: 0;
        box-shadow: 0 10px 22px rgba(37, 99, 235, .18);
    }

    .msg-select {
        position: absolute;
        top: 10px;
        left: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: rgba(15, 23, 42, .18);
        opacity: 0;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
        transform: scale(.9);
        z-index: 2;
    }

    .bubble:hover .msg-select,
    .msg-row.selected .msg-select,
    body.touch-device .msg-select {
        opacity: 1;
        pointer-events: auto;
        transform: scale(1);
    }

    .msg-select input {
        display: none;
    }

    .msg-select span {
        width: 16px;
        height: 16px;
        border-radius: 999px;
        border: 2px solid rgba(255, 255, 255, .9);
        background: transparent;
        box-sizing: border-box;
    }

    .msg-row.selected .msg-select span {
        background: #2563eb;
        border-color: #fff;
    }

    .selection-bar {
        position: sticky;
        top: 0;
        z-index: 11;
    }

    .scroll-bottom {
        position: absolute;
        right: 18px;
        bottom: 92px;
        z-index: 12;
        width: 48px;
        height: 48px;
        border-radius: 999px;
        border: 1px solid rgba(37, 99, 235, .16);
        background: linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(239, 246, 255, .92));
        box-shadow: 0 16px 36px rgba(15, 23, 42, .16);
        display: none;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        transition: transform .15s ease, background .15s ease, box-shadow .15s ease;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .scroll-bottom:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 20px 42px rgba(37, 99, 235, .22);
    }

    .scroll-bottom-icon {
        width: 22px;
        height: 22px;
        fill: currentColor;
    }

    html.dark .scroll-bottom {
        background: rgba(15, 23, 42, .95);
        border-color: rgba(96, 165, 250, .25);
        color: #93c5fd;
    }

    .scroll-bottom.show {
        display: inline-flex;
    }

    .modal-results-scroll {
        max-height: 52vh;
        overflow-y: auto;
        padding-right: 2px;
    }

    .modal-footer-sticky {
        position: sticky;
        bottom: 0;
        background: inherit;
        border-top: 1px solid #e5e7eb;
        margin: 14px -18px -16px;
        padding: 14px 18px 16px;
    }

    html.dark .modal-footer-sticky {
        border-color: #1f2937;
    }

    .chips-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        padding: 6px 10px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    html.dark .chip {
        border-color: #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .chip button {
        border: 0;
        background: transparent;
        color: inherit;
        cursor: pointer;
        font-size: 16px;
        line-height: 1;
    }

    .day-separator {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 14px 0;
        color: var(--text-muted);
        font-size: 12px;
    }

    .day-separator span {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border-radius: 999px;
        padding: 6px 12px;
        background: rgba(148, 163, 184, .10);
        border: 1px solid rgba(148, 163, 184, .16);
    }

    .tick-double {
        display: inline-flex;
        gap: 1px;
        font-size: 12px;
        opacity: .75;
    }

    .tick-double .seen {
        color: #60a5fa;
    }

    .message-actions {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .message-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, .18);
        background: rgba(255, 255, 255, .04);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background .15s ease, transform .15s ease, border-color .15s ease;
        color: inherit;
    }

    .message-action-btn:hover {
        background: rgba(148, 163, 184, .10);
        transform: translateY(-1px);
    }

    .message-action-btn.is-danger:hover {
        background: rgba(239, 68, 68, .12);
        border-color: rgba(239, 68, 68, .18);
    }

    .message-action-btn.is-primary:hover {
        background: rgba(37, 99, 235, .12);
        border-color: rgba(37, 99, 235, .18);
    }

    @media (max-width: 1024px) {
        .conversation-page {
            grid-template-columns: 1fr;
            height: calc(100dvh - 72px);
            gap: 0;
        }

        .conversation-sidebar {
            position: fixed;
            top: 72px;
            left: 0;
            bottom: 0;
            width: min(88vw, 360px);
            transform: translateX(-105%);
            z-index: 50;
            border-radius: 0 22px 22px 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
            transition: transform .22s ease;
        }

        body.conversation-sidebar-open .conversation-sidebar {
            transform: translateX(0);
        }

        .sidebar-mobile-toggle {
            display: inline-flex;
        }

        .sidebar-mobile-overlay {
            display: block;
            position: fixed;
            inset: 72px 0 0 0;
            background: rgba(15, 23, 42, .42);
            opacity: 0;
            pointer-events: none;
            transition: opacity .22s ease;
            z-index: 45;
        }

        body.conversation-sidebar-open .sidebar-mobile-overlay {
            opacity: 1;
            pointer-events: auto;
        }
    }

    @media (max-width: 768px) {
        .chat-messages {
            padding: 14px 12px 18px;
        }

        .msg-wrap {
            max-width: 92%;
            min-width: 160px;
        }

        .media-img {
            max-width: 220px;
        }

        .chat-header .flex.items-center.justify-between {
            gap: 10px;
        }

        .chat-header button {
            min-width: 38px;
        }

        .scroll-bottom {
            right: 12px;
            bottom: 88px;
        }

        .modal-backdrop {
            padding: 12px;
            align-items: flex-end;
        }

        .modal-card {
            width: 100%;
            max-height: 92dvh;
            border-radius: 24px 24px 18px 18px;
        }

        .modal-head,
        .modal-body {
            padding-left: 14px;
            padding-right: 14px;
        }

        .modal-results-scroll {
            max-height: 42dvh;
        }
    }
</style>