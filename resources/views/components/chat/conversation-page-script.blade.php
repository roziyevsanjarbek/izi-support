<script>
(function () {
    const cfg = window.__conversationPage || {};
    const body = document.body;
    const q = (sel, root = document) => root.querySelector(sel);
    const qa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    const state = {
        conversationId: cfg.initialConversationId || null,
        conversationType: cfg.conversationType || 'private',
        selected: new Set(),
        currentContextMessage: null,
        pendingDeleteIds: [],
        resendIds: [],
        groupPicked: new Map(),
        usersMode: 'private',
        olderPage: 2,
        olderLoading: false,
        olderDone: false,
        pollTimer: null,
        sidebarTimer: null,
        activeSearchRequest: 0,
        activeContactRequest: 0,
        activeGroupRequest: 0,
        sendingMessage: false,
    };

    const timers = {
        search: null,
        contact: null,
        group: null,
    };

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const show = (el) => el && el.classList.add('show');
    const hide = (el) => el && el.classList.remove('show');
    const modal = (id) => document.getElementById(id);
    const messagesBox = () => q('.js-messages');
    const shell = () => q('.js-chat-v3');
    const csrf = () => q('meta[name="csrf-token"]')?.content || '';

    function notify(message, type = 'success') {
        if (!message) return;
        const existing = document.getElementById('chat-toast-stack');
        const stack = existing || (() => {
            const el = document.createElement('div');
            el.id = 'chat-toast-stack';
            el.style.position = 'fixed';
            el.style.right = '16px';
            el.style.bottom = '16px';
            el.style.zIndex = '9999';
            el.style.display = 'flex';
            el.style.flexDirection = 'column';
            el.style.gap = '10px';
            document.body.appendChild(el);
            return el;
        })();

        const toast = document.createElement('div');
        toast.textContent = String(message);
        toast.style.maxWidth = '360px';
        toast.style.padding = '12px 14px';
        toast.style.borderRadius = '14px';
        toast.style.boxShadow = '0 12px 30px rgba(0,0,0,.16)';
        toast.style.color = '#fff';
        toast.style.fontSize = '14px';
        toast.style.lineHeight = '1.35';
        toast.style.background = type === 'error' ? '#dc2626' : (type === 'warning' ? '#d97706' : '#2563eb');
        stack.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(4px)';
            toast.style.transition = 'all .2s ease';
            setTimeout(() => toast.remove(), 220);
        }, 2200);
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }),
                ...(options.headers || {}),
            },
            ...options,
        });
        const data = await response.json().catch(() => ({}));
        return { response, data };
    }

    function bodyLockSidebar(open) {
        body.classList.toggle('conversation-sidebar-open', !!open);
    }

    function isMobile() {
        return window.matchMedia('(max-width: 1024px)').matches;
    }

    function isNearBottom() {
        const box = messagesBox();
        if (!box) return true;
        return (box.scrollHeight - box.scrollTop - box.clientHeight) < 140;
    }

    function scrollToBottom(behavior = 'auto') {
        const box = messagesBox();
        if (!box) return;
        box.scrollTop = box.scrollHeight;
        toggleScrollButton();
    }

    function toggleScrollButton() {
        const btn = q('.js-scroll-bottom');
        if (!btn) return;
        btn.classList.toggle('show', !isNearBottom());
    }

    function selectedMessages() {
        const ids = Array.from(state.selected);
        return ids.map((id) => {
            const row = q(`.msg-row[data-message-id="${id}"]`);
            if (!row) return null;
            try { return JSON.parse(row.dataset.messageJson || '{}'); } catch (_) { return null; }
        }).filter(Boolean);
    }

    function syncSelectionUi() {
        const bar = q('.js-selection-bar');
        const count = q('.js-selection-count');
        const resendHeader = q('.js-resend-open');
        const deleteBtn = q('.js-selection-delete');
        const resendBtn = q('.js-selection-resend');
        const ids = Array.from(state.selected);

        if (!ids.length) {
            hide(bar);
            hide(resendHeader);
            deleteBtn?.classList.add('hidden');
            resendBtn?.classList.add('hidden');
            return;
        }

        show(bar);
        show(resendHeader);
        if (count) count.textContent = String(ids.length);

        const msgs = selectedMessages();
        const canDelete = msgs.length > 0 && msgs.every((m) => m.is_mine && !m.is_deleted);
        const canResend = msgs.length > 0 && msgs.some((m) => !m.is_deleted);

        deleteBtn?.classList.toggle('hidden', !canDelete);
        resendBtn?.classList.toggle('hidden', !canResend);
    }

    function clearSelection() {
        state.selected.clear();
        qa('.msg-row.selected').forEach((row) => row.classList.remove('selected'));
        qa('.js-select-msg').forEach((cb) => { cb.checked = false; });
        syncSelectionUi();
    }

    function filePreviewHtml(file) {
        const isImage = (file.type || '').startsWith('image/');
        if (isImage) {
            return `
                <div class="js-file-preview mb-2 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                    <div class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                        <span class="truncate">${escapeHtml(file.name)}</span>
                        <button type="button" class="js-file-remove rounded-lg px-2 py-1 text-slate-500">×</button>
                    </div>
                </div>
            `;
        }
        return `
            <div class="js-file-preview mb-2 flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-800 dark:bg-slate-950">
                <span class="truncate">${escapeHtml(file.name)}</span>
                <button type="button" class="js-file-remove rounded-lg px-2 py-1 text-slate-500">×</button>
            </div>
        `;
    }

    function renderMessage(message) {
        if (!message || message.is_deleted) return '';
        const mine = !!message.is_mine;
        const deleted = !!message.is_deleted;
        const read = !!message.is_read;
        const reply = message.reply_to || null;
        const ext = (message.file_name || '').split('.').pop().toLowerCase();
        const isImage = (message.mime_type || '').startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext);

        const replyHtml = reply ? `
            <div class="reply-preview">
                <div class="reply-preview-name">${escapeHtml(reply.user_name || 'User')}</div>
                <div class="reply-preview-text">
                    ${reply.is_deleted ? 'Message deleted' : escapeHtml(reply.message || reply.file_name || 'file')}
                </div>
            </div>
        ` : '';

        let bodyHtml = '';
        if (!deleted) {
            if (message.message) {
                bodyHtml += `<div class="msg-text">${escapeHtml(message.message)}</div>`;
            }
            if (message.file_url) {
                bodyHtml += `
                    <div class="msg-media">
                        ${isImage ? `
                            <a href="${escapeHtml(message.file_url)}" target="_blank" rel="noopener">
                                <img class="media-img" src="${escapeHtml(message.file_url)}" alt="${escapeHtml(message.file_name || 'image')}">
                            </a>
                        ` : `
                            <a class="media-file" href="${escapeHtml(message.file_url)}" target="_blank" rel="noopener">
                                <span class="text-lg">📎</span>
                                <div class="min-w-0">
                                    <div class="media-file-name truncate">${escapeHtml(message.file_name || 'File')}</div>
                                    <div class="media-file-meta">${escapeHtml(message.mime_type || 'File')}</div>
                                </div>
                            </a>
                        `}
                    </div>
                `;
            }
        } else {
            bodyHtml = '<div class="msg-text italic opacity-70">Message deleted</div>';
        }

        return `
            <div class="msg-row ${mine ? 'right' : 'left'}" data-message-id="${message.id}" data-message-json='${escapeHtml(JSON.stringify(message))}'>
                <div class="msg-wrap">
                    ${!mine && message.sender_name ? `<div class="msg-sender-name">${escapeHtml(message.sender_name)}</div>` : ''}
                    <div class="bubble ${mine ? 'mine' : 'other'} ${deleted ? 'deleted' : ''}">
                        <label class="msg-select">
                            <input type="checkbox" class="js-select-msg">
                            <span></span>
                        </label>
                        ${replyHtml}
                        ${bodyHtml}
                        <div class="msg-meta">
                            <span>${escapeHtml(message.created_at_time || '')}</span>
                            ${message.is_edited ? '<span>edited</span>' : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function prependMessages(messages) {
    const box = messagesBox();
    if (!box || !messages?.length) return;

    const before = box.scrollHeight;
    const firstExisting = q('.msg-row', box);
    const previousKey = firstExisting
        ? messageDateKey(getRowMessage(firstExisting))
        : null;

    const html = renderMessagesWithDateSeparators(messages, null);
    box.insertAdjacentHTML('afterbegin', html);
    box.scrollTop += (box.scrollHeight - before);
    attachRowStates();
    toggleScrollButton();
}

function appendMessages(messages, autoScroll = false) {
    const box = messagesBox();
    if (!box || !messages?.length) return;

    const lastExisting = qa('.msg-row', box).at(-1);
    const previousKey = lastExisting
        ? messageDateKey(getRowMessage(lastExisting))
        : null;

    const html = renderMessagesWithDateSeparators(messages, previousKey);
    box.insertAdjacentHTML('beforeend', html);
    attachRowStates();
    if (autoScroll) scrollToBottom();
    toggleScrollButton();
}

    function attachRowStates() {
        qa('.js-select-msg').forEach((cb) => {
            const row = cb.closest('.msg-row');
            if (!row) return;
            const msg = getRowMessage(row);
            if (!msg) return;
            cb.checked = state.selected.has(Number(msg.id));
            row.classList.toggle('selected', cb.checked);
        });
    }

    function getRowMessage(row) {
        if (!row) return null;
        try { return JSON.parse(row.dataset.messageJson || '{}'); } catch (_) { return null; }
    }

    function setReplyPreview(message) {
        const bar = q('.js-reply-bar');
        const id = q('.js-reply-id');
        const title = q('.js-reply-title');
        const text = q('.js-reply-text');
        if (!bar || !id || !title || !text) return;
        id.value = String(message.id || '');
        title.textContent = message.sender_name || 'User';
        text.textContent = message.is_deleted ? 'Message deleted' : (message.message || message.file_name || 'file');
        showReplyPreviewBar();
    }

    function resetReplyPreview() {
        const bar = q('.js-reply-bar');
        const id = q('.js-reply-id');
        const title = q('.js-reply-title');
        const text = q('.js-reply-text');
        if (id) id.value = '';
        if (title) title.textContent = '';
        if (text) text.textContent = '';
        if (bar) {
            bar.classList.add('hidden');
            bar.classList.remove('show');
        }
    }

    function showReplyPreviewBar() {
        const bar = q('.js-reply-bar');
        if (!bar) return;
        bar.classList.remove('hidden');
        bar.classList.add('show');
    }

    function replaceMessageRow(message) {
        if (!message) return;
        const current = q(`.msg-row[data-message-id="${message.id}"]`);
        const html = renderMessage(message);
        if (!current) return;
        if (!html) {
            current.remove();
        } else {
            current.outerHTML = html;
        }
        attachRowStates();
        toggleScrollButton();
    }
    function messageDateKey(message) {
    const raw = message?.created_at || message?.created_at_time || '';
    if (!raw) return '';
    // created_at bo‘lsa undan foydalanadi, bo‘lmasa created_at_time string’ni fallback qiladi
    const d = new Date(raw);
    if (!isNaN(d.getTime())) {
        return d.toISOString().slice(0, 10); // YYYY-MM-DD
    }
    return String(raw).slice(0, 10);
}

function formatDateSeparator(dateKey) {
    if (!dateKey) return '';
    const d = new Date(dateKey + 'T00:00:00');
    return new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(d);
}

function renderDateSeparator(dateKey) {
    return `
        <div class="my-4 flex justify-center">
            <div class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
                ${escapeHtml(formatDateSeparator(dateKey))}
            </div>
        </div>
    `;
}

function renderMessagesWithDateSeparators(messages, previousKey = null) {
    let html = '';
    let lastKey = previousKey;

    messages.forEach((message) => {
        const key = messageDateKey(message);

        if (key && key !== lastKey) {
            html += renderDateSeparator(key);
            lastKey = key;
        }

        html += renderMessage(message);
    });

    return html;
}

    function contextMenuHtml(message) {
        const actions = [
            ['reply', '↩ Reply', 'R'],
            ['copy', '⧉ Copy', 'C'],
        ];
        if (message.can_edit && !message.is_deleted) actions.push(['edit', '✎ Edit', 'E']);
        if (message.can_delete && !message.is_deleted) actions.push(['delete', '🗑 Delete', 'Del']);
        actions.push(['select', '☑ Select', '']);
        actions.push(['close', 'Close', '']);
        return actions.map(([action, label, key]) => `
            <button type="button" data-action="${action}">
                <span>${label}</span>
                ${key ? `<span>${key}</span>` : '<span></span>'}
            </button>
        `).join('');
    }

    function showContextMenu(message, x, y) {
        const menu = q('#messageContextMenu');
        if (!menu) return;
        menu.innerHTML = contextMenuHtml(message);
        menu.style.left = Math.min(x, window.innerWidth - 250) + 'px';
        menu.style.top = Math.min(y, window.innerHeight - 260) + 'px';
        menu.classList.add('show');
        state.currentContextMessage = message;
    }

    function hideContextMenu() {
        const menu = q('#messageContextMenu');
        if (!menu) return;
        menu.classList.remove('show');
        menu.innerHTML = '';
        state.currentContextMessage = null;
    }

    function openModal(id) {
        show(modal(id));
    }

    function closeModal(id) {
        hide(modal(id));
    }

    function closeAllModals() {
        qa('.modal-backdrop.show').forEach((el) => hide(el));
        hideContextMenu();
    }

    function buildUrl(base, id) {
        return String(base || '').replace('__MESSAGE__', encodeURIComponent(id));
    }

    async function pollMessages() {
        const s = shell();
        const box = messagesBox();
        if (!s || !box) return;
        if (document.hidden) return;

        const lastRow = qa('.msg-row', box).at(-1);
        const lastId = lastRow ? Number(lastRow.dataset.messageId || 0) : 0;
        if (!lastId) return;

        const url = new URL(s.dataset.fetchUrl, window.location.origin);
        url.searchParams.set('after_id', String(lastId));
        const { response, data } = await request(url.toString());
        if (!response.ok || !data?.success) return;

        const incoming = data.messages || [];
        if (incoming.length) {
            const stick = isNearBottom();
            appendMessages(incoming, stick);
            if (!stick) toggleScrollButton();
            refreshSidebarDebounced();
        }
    }

    async function loadOlderMessages() {
        const s = shell();
        const box = messagesBox();
        if (!s || !box || state.olderLoading || state.olderDone) return;

        state.olderLoading = true;
        const beforeHeight = box.scrollHeight;

        const url = new URL(s.dataset.fetchUrl, window.location.origin);
        url.searchParams.set('page', String(state.olderPage));
        url.searchParams.set('per_page', '25');

        const { response, data } = await request(url.toString());
        if (response.ok && data?.success) {
            const older = data.messages || [];
            if (!older.length) {
                state.olderDone = true;
            } else {
                prependMessages(older);
                state.olderPage += 1;
                box.scrollTop += (box.scrollHeight - beforeHeight);
            }
        }

        state.olderLoading = false;
    }

    async function loadConversation(url, focusId = null, pushState = true) {
        const targetUrl = focusId ? `${url}${url.includes('?') ? '&' : '?'}focus_id=${encodeURIComponent(focusId)}` : url;
        const { response, data } = await request(targetUrl);
        if (!response.ok || !data?.success) return;

        const sidebar = q('#conversationSidebar');
        const chat = q('#conversationChat');
        if (data.sidebar_html) sidebar.innerHTML = data.sidebar_html;
        if (data.chat_html) chat.innerHTML = data.chat_html;

        state.conversationId = data.conversation_id || state.conversationId;
        state.conversationType = data.conversation_type || state.conversationType;
        state.olderPage = 2;
        state.olderDone = false;
        clearSelection();
        hideContextMenu();
        closeAllModals();

        if (pushState && data.page_url) {
            history.pushState({}, '', data.page_url);
        }

        initSidebarState();
        rebindConversationState();
        if (focusId) {
            setTimeout(() => {
                const row = q(`.msg-row[data-message-id="${focusId}"]`);
                if (row) row.scrollIntoView({ behavior: 'auto', block: 'center' });
            }, 40);
        } else {
            setTimeout(() => scrollToBottom(), 40);
        }
    }

    async function refreshSidebar() {
        if (!cfg.pollUrl) return;
        const url = new URL(cfg.pollUrl, window.location.origin);
        url.searchParams.set('type', state.conversationType);
        if (state.conversationId) url.searchParams.set('conversation', state.conversationId);
        const { response, data } = await request(url.toString());
        if (!response.ok || !data?.success) return;
        const sidebar = q('#conversationSidebar');
        if (data.sidebar_html) sidebar.innerHTML = data.sidebar_html;
        initSidebarState();
    }

    function refreshSidebarDebounced() {
        clearTimeout(state.sidebarTimer);
        state.sidebarTimer = setTimeout(refreshSidebar, 8000);
    }

    async function sendMessage() {
        const s = shell();
        if (!s || state.sendingMessage) return;

        const textInput = q('.js-text-input', s);
        const fileInput = q('.js-files', s);
        const replyId = q('.js-reply-id', s);
        const sendBtn = q('.js-send');
        const text = (textInput?.value || '').trim();
        const files = fileInput?.files ? Array.from(fileInput.files) : [];
        if (!text && !files.length) return;

        state.sendingMessage = true;
        if (sendBtn) {
            sendBtn.disabled = true;
            sendBtn.setAttribute('aria-busy', 'true');
        }

        try {
            const form = new FormData();
            if (text) form.append('message', text);
            if (replyId?.value) form.append('reply_to_id', replyId.value);
            files.forEach((file) => form.append('files[]', file));

            const { response, data } = await request(s.dataset.sendUrl, {
                method: 'POST',
                body: form,
                headers: { 'X-CSRF-TOKEN': csrf() },
            });

            if (!response.ok || !data?.success) {
                return;
            }

            if (textInput) textInput.value = '';
            if (fileInput) fileInput.value = '';
            qa('.js-file-preview').forEach((el) => el.remove());
            resetReplyPreview();

            const created = Array.isArray(data.messages) && data.messages.length ? data.messages : (data.message ? [data.message] : []);
            if (created.length) {
                appendMessages(created, true);
                scrollToBottom();
            } else {
                scrollToBottom();
            }
            refreshSidebarDebounced();
        } finally {
            state.sendingMessage = false;
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.removeAttribute('aria-busy');
            }
        }
    }


    async function togglePinState() {
        const s = shell();
        if (!s) return;
        const btn = q('.js-pin-toggle');
        const isPinned = btn?.dataset.state === '1';
        const url = isPinned ? s.dataset.unpinUrl : s.dataset.pinUrl;
        if (!url) return;

        const { response, data } = await request(url, {
            method: 'POST',
            body: new URLSearchParams({ _method: 'PATCH' }),
            headers: { 'X-CSRF-TOKEN': csrf() },
        });

        if (!response.ok || !data?.success) {
            return;
        }

        if (btn) {
            btn.dataset.state = data.is_pinned ? '1' : '0';
            btn.textContent = data.is_pinned ? 'Unpin' : 'Pin';
        }

        refreshSidebarDebounced();
    }

    async function copyMessage(message) {
        const text = message.message || message.file_name || message.location_name || '';
        if (!text) return;
        try {
            await navigator.clipboard.writeText(String(text));
        } catch (_) {
            const ta = document.createElement('textarea');
            ta.value = String(text);
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
        }
    }

    function getEditPayload() {
    const editModal = modal('editMessageModal');
    if (!editModal) return null;

    const id = Number(editModal.dataset.messageId || 0);
    if (!id) return null;

    let base = {};
    try {
        base = editModal.dataset.messageJson ? JSON.parse(editModal.dataset.messageJson) : {};
    } catch (_) {}

    return { id, ...base };
}

async function editMessage() {
    const s = shell();
    const input = q('.js-edit-text');
    const msg = getEditPayload();

    if (!s || !input || !msg) return;

    const value = input.value.trim();
    if (!value) {
        return;
    }

    const baseUrl = s.dataset.updateUrlBase;
    if (!baseUrl) {
        return;
    }

    const { response, data } = await request(buildUrl(baseUrl, msg.id), {
        method: 'POST',
        body: new URLSearchParams({
            _method: 'PUT',
            message: value,
        }),
        headers: { 'X-CSRF-TOKEN': csrf() },
    });

    if (!response.ok || !data?.success) {
        return;
    }

    closeModal('editMessageModal');

    const editModal = modal('editMessageModal');
    if (editModal) {
        delete editModal.dataset.messageId;
        delete editModal.dataset.messageJson;
    }

    replaceMessageRow(data.message || null);
    clearSelection();
    refreshSidebarDebounced();
}

    async function deleteSelectedOrCurrent() {
    const s = shell();
    const box = messagesBox();
    if (!s) return;

    const keepScrollTop = box ? box.scrollTop : 0;
    const keepScrollLeft = box ? box.scrollLeft : 0;
    const beforeHeight = box ? box.scrollHeight : 0;

    const ids = state.pendingDeleteIds.length
        ? [...state.pendingDeleteIds]
        : (state.currentContextMessage ? [state.currentContextMessage.id] : []);

    if (!ids.length) return;

    const deletedIds = [];

    for (const id of ids) {
        const url = buildUrl(s.dataset.deleteUrlBase, id);

        const { response, data } = await request(url, {
            method: 'POST',
            body: new URLSearchParams({ _method: 'DELETE' }),
            headers: { 'X-CSRF-TOKEN': csrf() },
        });

        if (response.ok && data?.success) {
            deletedIds.push(Number(id));
            replaceMessageRow(data.message || null);
        }
    }

    state.pendingDeleteIds = [];
    closeModal('deleteMessageModal');
    clearSelection();

    deletedIds.forEach((id) => {
        q(`.msg-row[data-message-id="${id}"]`)?.remove();
    });

    if (box) {
        const afterHeight = box.scrollHeight;
        box.scrollTop = Math.max(0, keepScrollTop - (beforeHeight - afterHeight));
        box.scrollLeft = keepScrollLeft;
    }

    refreshSidebarDebounced();
}

    async function resendSelected() {
        const s = shell();
        if (!s) return;
        const ids = [...state.selected];
        const targetIds = qa('.js-resend-target:checked').map((el) => Number(el.value)).filter(Boolean);
        if (!ids.length || !targetIds.length) return;

        const payload = new URLSearchParams();
        ids.forEach((id) => payload.append('message_ids[]', String(id)));
        targetIds.forEach((id) => payload.append('conversation_ids[]', String(id)));

        const { response, data } = await request(s.dataset.resendUrl, {
            method: 'POST',
            body: payload,
            headers: { 'X-CSRF-TOKEN': csrf() },
        });
        if (!response.ok || !data?.success) {
            return;
        }

        closeModal('resendModal');
        clearSelection();
        refreshSidebarDebounced();
    }

    function collectConversationTargets() {
        const current = Number(state.conversationId || 0);
        return qa('.js-conversation-item', q('#conversationSidebar')).map((item) => ({
            id: Number(item.dataset.conversationId || 0),
            title: (item.querySelector('.truncate')?.textContent || 'Conversation').trim(),
            type: item.dataset.conversationType || 'private',
            search: (item.dataset.searchKey || '').toLowerCase(),
        })).filter((item) => item.id && item.id !== current);
    }

    function renderResendTargets() {
        const wrap = q('.js-resend-targets');
        const targets = collectConversationTargets();
        if (!wrap) return;
        wrap.innerHTML = targets.length ? targets.map((item) => `
            <label class="result-item flex items-center gap-3">
                <input type="checkbox" class="js-resend-target" value="${item.id}">
                <div class="result-avatar">${escapeHtml(item.title.slice(0, 2).toUpperCase())}</div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold">${escapeHtml(item.title)}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">${escapeHtml(item.type)}</div>
                </div>
            </label>
        `).join('') : '<div class="text-sm text-slate-500 dark:text-slate-400">No target conversations found.</div>';
    }

    function renderContactResults(items, mode) {
        if (!items.length) return '<div class="text-sm text-slate-500 dark:text-slate-400">Nothing found.</div>';

        if (mode === 'group') {
            return items.map((item) => `
                <label class="result-item flex items-center gap-3">
                    <input type="checkbox" class="js-group-user" value="${item.id}">
                    <div class="result-avatar">${escapeHtml((item.avatar || 'US').slice(0, 2))}</div>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold">${escapeHtml(item.title || 'User')}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">${escapeHtml(item.subtitle || '')}</div>
                    </div>
                </label>
            `).join('');
        }

        return items.map((item) => `
            <div class="result-item js-user-item" data-url="${escapeHtml(item.url || '')}">
                <div class="result-avatar">${escapeHtml((item.avatar || 'US').slice(0, 2))}</div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold">${escapeHtml(item.title || 'User')}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">${escapeHtml(item.subtitle || '')}</div>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Open</div>
            </div>
        `).join('');
    }

    function renderPickedUsers() {
        const wrap = q('.js-group-picked');
        if (!wrap) return;
        const picked = Array.from(state.groupPicked.values());
        wrap.innerHTML = picked.length ? picked.map((item) => `
            <span class="chip">
                ${escapeHtml(item.title)}
                <button type="button" class="js-group-remove" data-id="${item.id}">×</button>
            </span>
        `).join('') : '<span class="text-sm text-slate-500 dark:text-slate-400">No members picked yet.</span>';
    }

    async function searchUsers(mode, query = '') {
        if (!cfg.usersUrl) return [];
        const url = new URL(cfg.usersUrl, window.location.origin);
        url.searchParams.set('mode', mode);
        if (query) url.searchParams.set('q', query);

        const reqId = mode === 'group' ? ++state.activeGroupRequest : ++state.activeContactRequest;
        const { response, data } = await request(url.toString());
        const activeId = mode === 'group' ? state.activeGroupRequest : state.activeContactRequest;
        if (reqId !== activeId) return [];
        return response.ok && data?.success ? (data.items || []) : [];
    }

    async function runSearch() {
        const modalEl = modal('messageSearchModal');
        const input = q('.js-search-input', modalEl);
        const scope = q('.js-search-scope', modalEl)?.value || 'current';
        const results = q('.js-search-results', modalEl);
        const query = (input?.value || '').trim();
        if (!query) {
            if (results) results.innerHTML = '';
            return;
        }

        const searchId = ++state.activeSearchRequest;
        if (results) results.innerHTML = '<div class="text-sm text-slate-500 dark:text-slate-400">Searching...</div>';

        const url = new URL(cfg.searchUrl, window.location.origin);
        url.searchParams.set('q', query);
        if (scope === 'current' && state.conversationId) {
            url.searchParams.set('conversation_id', String(state.conversationId));
        }

        const { response, data } = await request(url.toString());
        if (searchId !== state.activeSearchRequest) return;

        if (!response.ok || !data?.success) return;

        const items = data.messages || [];
        if (!results) return;
        results.innerHTML = items.length ? items.map((item) => `
            <div class="result-item js-search-result" data-url="${escapeHtml(item.conversation_url)}" data-focus-id="${item.id}">
                <div class="result-avatar">${escapeHtml((item.conversation_title || 'MS').slice(0, 2).toUpperCase())}</div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold">${escapeHtml(item.conversation_title || 'Conversation')}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">${escapeHtml(item.sender_name || '')} · ${escapeHtml(item.created_at_time || '')}</div>
                    <div class="mt-1 line-clamp-2 text-sm text-slate-700 dark:text-slate-300">${escapeHtml(item.message || item.file_name || item.location_name || '')}</div>
                </div>
            </div>
        `).join('') : '<div class="text-sm text-slate-500 dark:text-slate-400">Nothing found.</div>';
    }

    async function runContactSearch() {
        const modalEl = modal('contactPickerModal');
        const query = (q('.js-user-search', modalEl)?.value || '').trim();
        const results = q('.js-user-results', modalEl);
        const mode = state.usersMode;
        if (results) results.innerHTML = '<div class="text-sm text-slate-500 dark:text-slate-400">Searching...</div>';
        const items = await searchUsers(mode, query);
        if (results) results.innerHTML = renderContactResults(items, mode);
    }

    async function runGroupSearch() {
        const modalEl = modal('groupCreateModal');
        const query = (q('.js-group-user-search', modalEl)?.value || '').trim();
        const results = q('.js-group-user-results', modalEl);
        if (results) results.innerHTML = '<div class="text-sm text-slate-500 dark:text-slate-400">Searching...</div>';
        const items = await searchUsers('group', query);
        if (results) results.innerHTML = renderContactResults(items, 'group');
        reapplyGroupSelections();
    }

    function reapplyGroupSelections() {
        qa('.js-group-user').forEach((cb) => {
            cb.checked = state.groupPicked.has(Number(cb.value));
        });
    }

    async function createGroup() {
        const name = (q('.js-group-name')?.value || '').trim();
        const userIds = Array.from(state.groupPicked.keys());
        if (!userIds.length) {
            return;
        }

        const payload = new URLSearchParams();
        payload.append('type', 'group');
        payload.append('name', name);
        userIds.forEach((id) => payload.append('user_ids[]', String(id)));

        const { response, data } = await request(cfg.createUrl, {
            method: 'POST',
            body: payload,
            headers: { 'X-CSRF-TOKEN': csrf() },
        });
        if (!response.ok || !data?.success) {
            return;
        }

        state.groupPicked.clear();
        closeAllModals();
        await loadConversation(data.page_url || shell()?.dataset.showUrl, null, true);
        refreshSidebarDebounced();
    }

    async function openNewChat() {
        state.usersMode = 'private';
        const title = q('.js-contact-modal-title');
        if (title) title.textContent = 'New chat';
        show(modal('contactPickerModal'));
        await runContactSearch();
    }

    async function openNewGroup() {
        state.groupPicked.clear();
        const name = q('.js-group-name');
        const search = q('.js-group-user-search');
        const results = q('.js-group-user-results');
        if (name) name.value = '';
        if (search) search.value = '';
        if (results) results.innerHTML = '';
        renderPickedUsers();
        show(modal('groupCreateModal'));
        await runGroupSearch();
    }

    function openResendModal() {
        if (!state.selected.size) return;
        const count = q('.js-resend-count');
        state.resendIds = Array.from(state.selected);
        if (count) count.textContent = String(state.resendIds.length);
        renderResendTargets();
        show(modal('resendModal'));
    }

    function openDeleteModal(ids = []) {
        state.pendingDeleteIds = ids;
        show(modal('deleteMessageModal'));
    }

    function openEditModal(message) {
        const input = q('.js-edit-text');
        const editModal = modal('editMessageModal');
        if (!input || !editModal) return;
        input.value = message.message || '';
        editModal.dataset.messageId = String(message.id || '');
        editModal.dataset.messageJson = JSON.stringify(message || {});
        show(editModal);
    }

    function setSidebarMobileDefault() {
        if (isMobile() && !state.conversationId) {
            bodyLockSidebar(true);
        }
    }

    function bindChatScroll() {
        const box = messagesBox();
        if (!box) return;
        if (box.__boundScrollHandler) {
            box.removeEventListener('scroll', box.__boundScrollHandler);
        }
        const handler = async () => {
            toggleScrollButton();
            if (box.scrollTop < 80 && !state.olderLoading && !state.olderDone) {
                await loadOlderMessages();
            }
        };
        box.__boundScrollHandler = handler;
        box.addEventListener('scroll', handler, { passive: true });
        toggleScrollButton();
    }

    function wireMessageSelection(row, checked) {
        const message = getRowMessage(row);
        if (!message) return;
        row.classList.toggle('selected', checked);
        if (checked) state.selected.add(Number(message.id));
        else state.selected.delete(Number(message.id));
        syncSelectionUi();
    }

    function wireReplyFromMessage(message) {
        if (!message || message.is_deleted) return;
        setReplyPreview(message);
    }

    function rebindConversationState() {
        bindChatScroll();
        attachRowStates();
        syncSelectionUi();
        if (isMobile() && !state.conversationId) {
            bodyLockSidebar(true);
        } else {
            bodyLockSidebar(false);
        }
    }

    function handleDocumentClick(e) {
        const target = e.target;

        const contextActionBtn = target.closest('#messageContextMenu button');
        if (contextActionBtn) {
            const action = contextActionBtn.dataset.action;
            const msg = state.currentContextMessage;
            if (!msg) return;

            if (action === 'reply') {
                wireReplyFromMessage(msg);
            } else if (action === 'copy') {
                copyMessage(msg);
            } else if (action === 'edit') {
                state.currentContextMessage = msg;
                openEditModal(msg);
            } else if (action === 'delete') {
                openDeleteModal([msg.id]);
            } else if (action === 'select') {
                const row = q(`.msg-row[data-message-id="${msg.id}"]`);
                if (row) {
                    state.selected.add(Number(msg.id));
                    const cb = q('.js-select-msg', row);
                    if (cb) cb.checked = true;
                    row.classList.add('selected');
                    syncSelectionUi();
                }
            } else if (action === 'close') {
                hideContextMenu();
            }
            hideContextMenu();
            return;
        }

        if (target.closest('#messageContextMenu')) return;

        if (target.classList?.contains('modal-backdrop')) {
            closeAllModals();
            return;
        }

        const openSidebarBtn = target.closest('.js-sidebar-open');
        if (openSidebarBtn) {
            bodyLockSidebar(true);
            return;
        }

        const closeSidebarBtn = target.closest('.js-sidebar-close, .js-sidebar-overlay');
        if (closeSidebarBtn) {
            bodyLockSidebar(false);
            return;
        }

        const closeModalBtn = target.closest('[data-modal-close], .js-modal-close');
        if (closeModalBtn) {
            closeAllModals();
            return;
        }

        const conversationItem = target.closest('.js-conversation-item');
        if (conversationItem) {
            e.preventDefault();
            const url = conversationItem.dataset.loadUrl || conversationItem.getAttribute('href');
            if (url) loadConversation(url, null, true);
            bodyLockSidebar(false);
            return;
        }

        const switchTypeBtn = target.closest('.js-switch-type');
        if (switchTypeBtn) {
            e.preventDefault();
            const url = switchTypeBtn.dataset.url;
            if (url) window.location.href = url;
            return;
        }

        const newChatBtn = target.closest('.js-open-new-chat');
        if (newChatBtn) {
            openNewChat();
            return;
        }

        const newGroupBtn = target.closest('.js-open-new-group');
        if (newGroupBtn) {
            openNewGroup();
            return;
        }

        const groupCreateBtn = target.closest('.js-group-create');
        if (groupCreateBtn) {
            createGroup();
            return;
        }

        const groupSearchBtn = target.closest('.js-group-user-search-run');
        if (groupSearchBtn) {
            runGroupSearch();
            return;
        }

        const pinToggleBtn = target.closest('.js-pin-toggle');
        if (pinToggleBtn) {
            togglePinState();
            return;
        }

        const globalSearchBtn = target.closest('.js-open-global-search, .js-message-search-btn');
        if (globalSearchBtn) {
            openModal('messageSearchModal');
            return;
        }

        const resendHeaderBtn = target.closest('.js-resend-open, .js-selection-resend');
        if (resendHeaderBtn) {
            if (state.selected.size) openResendModal();
            return;
        }

        const clearBtn = target.closest('.js-selection-clear');
        if (clearBtn) {
            clearSelection();
            return;
        }

        const deleteBtn = target.closest('.js-selection-delete, .js-delete-confirm');
        if (deleteBtn) {
            if (deleteBtn.classList.contains('js-selection-delete')) {
                const ids = selectedMessages().filter((m) => m.is_mine && !m.is_deleted).map((m) => m.id);
                if (ids.length) openDeleteModal(ids);
                return;
            }
            deleteSelectedOrCurrent();
            return;
        }

        const editSaveBtn = target.closest('.js-edit-save');
if (editSaveBtn) {
    e.preventDefault();
    e.stopPropagation();
    editMessage();
    return;
}

        const resendConfirmBtn = target.closest('.js-resend-confirm');
        if (resendConfirmBtn) {
            resendSelected();
            return;
        }

        const attachBtn = target.closest('.js-attach');
        if (attachBtn) {
            q('.js-files')?.click();
            return;
        }


        const sendBtn = target.closest('.js-send');
        if (sendBtn) {
            e.preventDefault();
            e.stopPropagation();
            sendMessage();
            return;
        }

        const replyCancel = target.closest('.js-reply-cancel');
        if (replyCancel) {
            resetReplyPreview();
            return;
        }

        const scrollBottomBtn = target.closest('.js-scroll-bottom');
        if (scrollBottomBtn) {
            scrollToBottom();
            return;
        }

        const bubble = target.closest('.bubble');
        if (bubble) {
            hideContextMenu();
            return;
        }

        const searchResult = target.closest('.js-search-result');
        if (searchResult) {
            closeAllModals();
            const url = searchResult.dataset.url;
            const focusId = Number(searchResult.dataset.focusId || 0);
            if (url) loadConversation(url, focusId, true);
            return;
        }

        const userResult = target.closest('.js-user-item');
        if (userResult) {
            const url = userResult.dataset.url;
            if (url) {
                closeAllModals();
                loadConversation(url, null, true);
            }
            return;
        }

        const groupRemove = target.closest('.js-group-remove');
        if (groupRemove) {
            state.groupPicked.delete(Number(groupRemove.dataset.id));
            renderPickedUsers();
            reapplyGroupSelections();
            return;
        }

        const fileRemove = target.closest('.js-file-remove');
        if (fileRemove) {
            const previews = qa('.js-file-preview');
            const filesInput = q('.js-files');
            const buttons = q('.js-files')?.files ? Array.from(q('.js-files').files) : [];
            const previewIndex = previews.indexOf(fileRemove.closest('.js-file-preview'));
            if (filesInput && previewIndex >= 0) {
                const dt = new DataTransfer();
                buttons.forEach((file, idx) => {
                    if (idx !== previewIndex) dt.items.add(file);
                });
                filesInput.files = dt.files;
                fileRemove.closest('.js-file-preview')?.remove();
            }
            return;
        }

        hideContextMenu();
    }

    function handleDocumentContextMenu(e) {
        const bubble = e.target.closest('.bubble');
        if (!bubble) {
            hideContextMenu();
            return;
        }
        const row = bubble.closest('.msg-row');
        const msg = getRowMessage(row);
        if (!msg) return;
        e.preventDefault();
        showContextMenu(msg, e.clientX, e.clientY);
    }

    function handleDocumentChange(e) {
        const target = e.target;

        if (target.matches('.js-select-msg')) {
            const row = target.closest('.msg-row');
            if (!row) return;
            wireMessageSelection(row, target.checked);
            return;
        }

        if (target.matches('.js-notif-toggle')) {
            const s = shell();
            if (!s) return;
            request(s.dataset.toggleNotificationsUrl, {
                method: 'POST',
                body: new URLSearchParams({ _method: 'PATCH' }),
                headers: { 'X-CSRF-TOKEN': csrf() },
            });
            return;
        }

        if (target.matches('.js-search-scope')) {
            runSearch();
            return;
        }

        if (target.matches('.js-group-user')) {
            const row = target.closest('.result-item');
            const title = row?.querySelector('.truncate')?.textContent?.trim() || 'User';
            const id = Number(target.value || 0);
            if (!id) return;
            if (target.checked) state.groupPicked.set(id, { id, title });
            else state.groupPicked.delete(id);
            renderPickedUsers();
            return;
        }

        if (target.matches('.js-files')) {
            const previews = q('.js-previews');
            if (!previews) return;
            previews.innerHTML = '';
            Array.from(target.files || []).forEach((file) => {
                previews.insertAdjacentHTML('beforeend', filePreviewHtml(file));
            });
        }
    }

    function handleDocumentInput(e) {
        const target = e.target;

        if (target.matches('.js-search-input')) {
            clearTimeout(timers.search);
            timers.search = setTimeout(runSearch, 250);
            return;
        }

        if (target.matches('.js-user-search')) {
            clearTimeout(timers.contact);
            timers.contact = setTimeout(runContactSearch, 250);
            return;
        }

        if (target.matches('.js-group-user-search')) {
            clearTimeout(timers.group);
            timers.group = setTimeout(runGroupSearch, 250);
            return;
        }
    }

    function handleDocumentKeydown(e) {
        if (e.key === 'Escape') {
            closeAllModals();
            hideContextMenu();
            return;
        }

        const target = e.target;

        if (target.matches('.js-text-input') && e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            e.stopPropagation();
            sendMessage();
            return;
        }

        if (target.matches('.js-search-input') && e.key === 'Enter') {
            e.preventDefault();
            runSearch();
            return;
        }

        if (target.matches('.js-user-search') && e.key === 'Enter') {
            e.preventDefault();
            runContactSearch();
            return;
        }

        if (target.matches('.js-group-user-search') && e.key === 'Enter') {
            e.preventDefault();
            runGroupSearch();
            return;
        }
    }

    function attachGlobalEvents() {
        document.addEventListener('click', handleDocumentClick);
        document.addEventListener('contextmenu', handleDocumentContextMenu);
        document.addEventListener('change', handleDocumentChange);
        document.addEventListener('input', handleDocumentInput);
        document.addEventListener('keydown', handleDocumentKeydown);
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.closest('.js-chat-v3')) {
                e.preventDefault();
                e.stopPropagation();
                sendMessage();
            }
        }, true);

        window.addEventListener('resize', () => {
            toggleScrollButton();
            if (window.innerWidth >= 1024 && !state.conversationId) {
                bodyLockSidebar(false);
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) pollMessages();
        });

        window.addEventListener('popstate', () => window.location.reload());
    }

    function bindSearchModalDefaults() {
        const scope = q('.js-search-scope');
        if (scope) scope.value = 'current';
    }

    function focusInitialMessage() {
        const focusId = Number(shell()?.dataset.focusMessageId || 0);
        if (!focusId) return;
        const row = q(`.msg-row[data-message-id="${focusId}"]`);
        if (row) {
            setTimeout(() => row.scrollIntoView({ behavior: 'auto', block: 'center' }), 80);
        }
    }

    function startPolling() {
        clearInterval(state.pollTimer);
        state.pollTimer = setInterval(pollMessages, 2000);
    }

    function bindSidebarSearch() {
        const sidebar = q('#conversationSidebar');
        const filter = q('.js-conversation-search', sidebar);
        if (!filter) return;
        const term = filter.value.trim().toLowerCase();
        qa('.js-conversation-item', sidebar).forEach((item) => {
            const key = (item.dataset.searchKey || '').toLowerCase();
            item.style.display = !term || key.includes(term) ? '' : 'none';
        });
    }

    async function initSearchResults() {
        bindSearchModalDefaults();
        bindSidebarSearch();
        bindChatScroll();
        attachRowStates();
        syncSelectionUi();
        startPolling();
        focusInitialMessage();
        if (!Number(shell()?.dataset.focusMessageId || 0)) {
            scrollToBottom();
        }

        if (isMobile() && !state.conversationId) {
            bodyLockSidebar(true);
        } else if (isMobile()) {
            bodyLockSidebar(false);
        } else {
            bodyLockSidebar(false);
        }

        if (!state.conversationId) {
            toggleScrollButton();
        }
    }

    function initSidebarState() {
        const sidebar = q('#conversationSidebar');
        if (!sidebar) return;
        const active = qa('.js-conversation-item', sidebar);
        active.forEach((item) => {
            item.classList.toggle('is-active', Number(item.dataset.conversationId || 0) === Number(state.conversationId || 0));
        });
        bindSidebarSearch();
    }

    async function init() {
        attachGlobalEvents();
        initSidebarState();
        await initSearchResults();
    }

    init();
})();
</script>