<div id="messageSearchModal" class="modal-backdrop" data-modal="search">
    <div class="modal-card">
        <div class="modal-head flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-semibold">Search messages</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Current chat or all chats</div>
            </div>
            <button type="button" class="js-modal-close rounded-xl px-3 py-2" data-modal-close="true">×</button>
        </div>
        <div class="modal-body">
            <div class="grid gap-3 md:grid-cols-[1fr_180px]">
                <input type="text" class="js-search-input conversation-search" placeholder="Type a word or file name..." autocomplete="off">
                <select class="js-search-scope conversation-search">
                    <option value="current">Current chat</option>
                    <option value="all">Across all chats</option>
                </select>
            </div>
            <div class="mt-4 js-search-results"></div>
        </div>
    </div>
</div>

<div id="contactPickerModal" class="modal-backdrop" data-modal="contact">
    <div class="modal-card" style="width:min(760px,100%);">
        <div class="modal-head flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-semibold js-contact-modal-title">New chat</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Search a user and open immediately.</div>
            </div>
            <button type="button" class="js-modal-close rounded-xl px-3 py-2" data-modal-close="true">×</button>
        </div>
        <div class="modal-body">
            <input type="text" class="js-user-search conversation-search" placeholder="Search user..." autocomplete="off">
            <div class="mt-4 modal-results-scroll js-user-results"></div>
        </div>
    </div>
</div>

<div id="groupCreateModal" class="modal-backdrop" data-modal="group">
    <div class="modal-card" style="width:min(860px,100%);">
        <div class="modal-head flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-semibold">Create group</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Search users, pick members, then create the group.</div>
            </div>
            <button type="button" class="js-modal-close rounded-xl px-3 py-2" data-modal-close="true">×</button>
        </div>
        <div class="modal-body">
            <input type="text" class="js-group-name conversation-search" placeholder="Group name" autocomplete="off">

            <div class="mt-3 chips-wrap js-group-picked"></div>

            <div class="mt-4 grid gap-3 md:grid-cols-[1fr_160px]">
                <input type="text" class="js-group-user-search conversation-search" placeholder="Search users to add..." autocomplete="off">
                <button type="button" class="js-group-user-search-run inline-flex h-11 items-center justify-center rounded-2xl bg-blue-600 px-4 font-semibold text-white">
                    Search
                </button>
            </div>

            <div class="mt-4 modal-results-scroll js-group-user-results"></div>

            <div class="modal-footer-sticky">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        Pick members from the search list.
                    </div>
                    <button type="button" class="js-group-create inline-flex rounded-2xl bg-blue-600 px-4 py-2 font-semibold text-white">
                        Create
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="resendModal" class="modal-backdrop" data-modal="resend">
    <div class="modal-card" style="width:min(840px,100%);">
        <div class="modal-head flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-semibold">Resend messages</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Choose target conversations.</div>
            </div>
            <button type="button" class="js-modal-close rounded-xl px-3 py-2" data-modal-close="true">×</button>
        </div>
        <div class="modal-body">
            <div class="text-sm text-slate-600 dark:text-slate-300">
                Selected messages: <span class="js-resend-count font-semibold">0</span>
            </div>
            <div class="mt-4 modal-results-scroll js-resend-targets"></div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="js-modal-close rounded-2xl border border-slate-200 bg-white px-4 py-2 font-semibold dark:border-slate-800 dark:bg-slate-950">Cancel</button>
                <button type="button" class="js-resend-confirm inline-flex rounded-2xl bg-blue-600 px-4 py-2 font-semibold text-white">Resend</button>
            </div>
        </div>
    </div>
</div>

<div id="editMessageModal" class="modal-backdrop" data-modal="edit">
    <div class="modal-card" style="width:min(680px,100%);">
        <div class="modal-head flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-semibold">Edit message</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Update only your own message.</div>
            </div>
            <button type="button" class="js-modal-close rounded-xl px-3 py-2" data-modal-close="true">×</button>
        </div>
        <div class="modal-body">
            <textarea class="js-edit-text conversation-search min-h-40 w-full" placeholder="Edit message..."></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="js-modal-close rounded-2xl border border-slate-200 bg-white px-4 py-2 font-semibold dark:border-slate-800 dark:bg-slate-950">Cancel</button>
                <button type="button" class="js-edit-save rounded-2xl bg-blue-600 px-4 py-2 font-semibold text-white">Save</button>
            </div>
        </div>
    </div>
</div>

<div id="deleteMessageModal" class="modal-backdrop" data-modal="delete">
    <div class="modal-card" style="width:min(560px,100%);">
        <div class="modal-head flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-semibold">Delete message</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">This will soft delete the selected message.</div>
            </div>
            <button type="button" class="js-modal-close rounded-xl px-3 py-2" data-modal-close="true">×</button>
        </div>
        <div class="modal-body">
            <div class="flex justify-end gap-2">
                <button type="button" class="js-modal-close rounded-2xl border border-slate-200 bg-white px-4 py-2 font-semibold dark:border-slate-800 dark:bg-slate-950">Cancel</button>
                <button type="button" class="js-delete-confirm rounded-2xl bg-rose-600 px-4 py-2 font-semibold text-white">Delete</button>
            </div>
        </div>
    </div>
</div>

<div id="messageContextMenu" class="context-menu"></div>