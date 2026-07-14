@extends('layouts.app')

@section('title', $task->name)

@section('content')
    @php
    $taskPayload = [
        'id' => $task->id,
        'name' => $task->name,
        'description' => $task->description,
        'status' => $task->status,
        'custom_id' => $task->custom_id,
        'created_by' => $task->created_by,
        'creator_name' => $task->creator?->name,
        'completed_by' => $task->completed_by,
        'completed_by_name' => $task->completedBy?->name,
        'rejected_by' => $task->rejected_by,
        'rejected_by_name' => $task->rejectedBy?->name,
        'start_date' => $task->start_date?->toIso8601String(),
        'end_date' => $task->end_date?->toIso8601String(),
        'attachments' => $task->attachments ?? [],
        'attachments_count' => $task->attachments_count ?? 0,
        'reads' => $task->reads->map(function ($read) {
            return [
                'user_id' => $read->user_id,
                'user_name' => $read->user?->name ?? 'Unknown',
                'view_count' => $read->view_count,
                'first_viewed_at' => $read->first_viewed_at?->toIso8601String(),
                'last_viewed_at' => $read->last_viewed_at?->toIso8601String(),
            ];
        })->values(),
        'update_details_url' => route('tasks.update-details', $task),
        'complete_url' => route('tasks.complete', $task),
        'reject_url' => route('tasks.reject', $task),
        'can_edit' => (bool) $canEdit,
        'can_reject' => (bool) $canReject,
    ];
    @endphp

    <div x-data="taskShowPage(@js($taskPayload))" x-init="init()" class="space-y-4 sm:space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:px-6 sm:py-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            Task #<span x-text="task.id"></span>
                        </span>

                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :class="statusBadgeClass(task.status)" x-text="statusLabel(task.status)"></span>
                    </div>

                    <div>
                        <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100 sm:text-2xl" x-text="task.name"></h1>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button"
                        onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href='{{ route('tasks.index') }}'; }"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/5">
                        Back
                    </button>

                    <button type="button" x-show="canEdit()" x-cloak @click="openEdit()"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-gray-900 dark:text-slate-200 dark:hover:bg-white/5">
                        Edit
                    </button>

                    <button type="button" x-show="canReject()" x-cloak @click="askReject()"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-rose-500 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-600">
                        Reject
                    </button>

                    <button type="button" x-show="canComplete()" x-cloak @click="askComplete()"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-500 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">
                        Complete
                    </button>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2 xl:items-start">
            <section class="xl:sticky xl:top-4">
                <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 xl:h-[calc(100dvh-170px)] xl:overflow-auto">
                    <div class="flex items-start justify-between gap-4">
                        <div class="mt-2 flex items-center gap-4">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Task Information
                            </h2>

                            <button
                                type="button"
                                @click="copyCustomId(task.custom_id)"
                                :title="copied ? 'Copied!' : 'Click to copy'"
                                class="group inline-flex cursor-pointer items-center rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 text-base font-bold tracking-wide text-brand-700 shadow-sm transition hover:bg-brand-100 hover:shadow-md active:scale-95 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-300 dark:hover:bg-brand-500/20">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="mr-2 h-4 w-4 opacity-70 group-hover:opacity-100"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2M10 10h8a2 2 0 012 2v6a2 2 0 01-2 2h-8a2 2 0 01-2-2v-6a2 2 0 012-2z"/>
                                </svg>

                                <span class="mr-2">ID:</span>

                                <span x-text="task.custom_id || 'N/A'"></span>
                            </button>
                        </div>

                        <div class="rounded-2xl bg-gray-50 px-4 py-3 text-right dark:bg-white/5">
                            <div class="text-[11px] uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">ID</div>
                            <div class="mt-1 text-sm font-bold text-gray-900 dark:text-gray-100">
                                #<span x-text="task.id"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Creator</div>
                            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                x-text="task.creator_name || '-'"></div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Status</div>
                            <div class="mt-2">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="statusBadgeClass(task.status)" x-text="statusLabel(task.status)"></span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Timeline</div>

                            <div class="mt-3 space-y-3">
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Start date</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="task.start_date ? formatDateTime(task.start_date) : '-'"></span>
                                </div>

                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">End date</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="task.end_date ? formatDateTime(task.end_date) : '-'"></span>
                                </div>

                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Duration</span>
                                    <button
                                        type="button"
                                        @click="toggleDurationMode()"
                                        class="text-right text-sm font-semibold hover:underline focus:outline-none"
                                        :class="durationClass(task)"
                                        x-text="taskDuration(task)">
                                    </button>
                                </div>

                                <div class="text-right text-[11px] text-gray-500 dark:text-gray-400">
                                    <span x-text="durationMode === 'compact' ? 'hours format' : 'day format'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Result</div>

                            <div class="mt-3 space-y-3">
                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Completed by</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="task.status === 'completed' ? (task.completed_by_name || 'Not completed yet') : 'Not completed yet'"></span>
                                </div>

                                <div class="flex items-start justify-between gap-4">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Completed at</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        x-text="task.status === 'completed' ? formatDateTime(task.end_date) : '-'"></span>
                                </div>

                                <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
                                    <div class="flex items-start justify-between gap-4">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Rejected by</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                            x-text="task.status === 'rejected' ? (task.rejected_by_name || 'Not rejected yet') : 'Not rejected yet'"></span>
                                    </div>

                                    <div class="mt-3 flex items-start justify-between gap-4">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Rejected at</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                            x-text="task.status === 'rejected' ? formatDateTime(task.end_date) : '-'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800 sm:col-span-2">
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Attachments</div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <template x-for="attachment in (task.attachments || [])" :key="attachment.id">
                                    <a :href="attachment.url" target="_blank"
                                        class="group rounded-2xl border border-gray-100 bg-white p-3 shadow-sm transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-950/40 dark:hover:bg-white/5">
                                        <div class="flex items-start gap-3">
                                            <template x-if="isImageAttachment(attachment)">
                                                <img :src="attachment.url"
                                                     class="h-16 w-16 shrink-0 rounded-xl border border-gray-200 object-cover dark:border-gray-700"
                                                     :alt="attachment.original_name">
                                            </template>

                                            <template x-if="!isImageAttachment(attachment)">
                                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-sm font-bold text-gray-600 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300"
                                                     x-text="attachmentBadge(attachment)"></div>
                                            </template>

                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100"
                                                    x-text="attachment.original_name"></div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                                    x-text="attachment.mime_type || '-'"></div>
                                                <div class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                                                    x-text="humanFileSize(attachment.size)"></div>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>

                            <div x-show="!task.attachments || task.attachments.length === 0" x-cloak
                                class="mt-3 rounded-2xl border border-dashed border-gray-200 px-4 py-6 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                No attachments yet.
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-4 dark:border-gray-800 sm:col-span-2">
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Views</div>
                            <div class="mt-2 flex items-end justify-between gap-4">
                                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100"
                                    x-text="task.reads?.length ?? 0"></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    users have opened this task
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">
                            Description</div>
                        <div class="mt-2 whitespace-pre-line break-words text-sm leading-7 text-gray-800 dark:text-gray-200"
                            x-text="task.description || 'No description provided.'"></div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-gray-200 p-4 dark:border-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Read list</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400"
                                x-text="`${task.reads?.length ?? 0} entries`"></div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <template x-for="read in (task.reads || [])" :key="read.user_id + '-' + read.first_viewed_at">
                                <div
                                    class="rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-gray-800 dark:bg-gray-950/40">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                                x-text="read.user_name || 'Unknown'"></div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                First viewed: <span x-text="formatDateTime(read.first_viewed_at)"></span>
                                            </div>
                                        </div>

                                        <div class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            x-text="`${read.view_count ?? 0} views`"></div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="!task.reads || task.reads.length === 0" x-cloak
                                class="rounded-2xl border border-dashed border-gray-200 px-4 py-6 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                No views yet.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 xl:h-[calc(100dvh-170px)] xl:sticky xl:top-4 xl:flex xl:flex-col">
                <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-800 sm:px-6">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">
                            Discussion</div>
                        <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Chat</h2>
                    </div>
                </div>

                <div class="flex-1 min-h-0 p-3 sm:p-4">
                    <div class="h-[70dvh] xl:h-full">
                        <x-chat.chat-v2 :conversation="$conversation" :users="$users" :messages="$messages"
                            :fetch-url="route('messages.conversations.messages.index', $conversation)"
                            :send-url="route('messages.conversations.messages.store', $conversation)"
                            :toggle-notifications-url="route('messages.conversations.toggle-notifications', $conversation)"
                            :polling="true" width="100%" height="100%" />
                    </div>
                </div>
            </section>
        </div>

        @include('pages.tasks.modals.edit')
        @include('pages.tasks.modals.confirm-action')
    </div>
@endsection

@push('scripts')
    <script>

        function taskShowPage(initialTask) {
        return {
            task: initialTask,

            endDatePicker: null,

            modals: {
                edit: false,
                confirm: false,
            },

            actionType: null,
            confirmTitle: '',
            confirmMessage: '',
            loadingAction: false,
            savingEdit: false,

            editForm: {
                name: '',
                description: '',
                end_date: '',
                start_date: '',
                attachments: [],
                deleted_attachment_ids: [],
            },

            durationMode: 'compact',
            timerInterval: null,
            now: Date.now(),

            init() {
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') this.closeAll();
                });

                this.$watch('modals.edit', () => this.updateScrollLock());
                this.$watch('modals.confirm', () => this.updateScrollLock());

                this.timerInterval = setInterval(() => {
                    this.now = Date.now();
                }, 1000);

                history.scrollRestoration = 'manual';

                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        window.scrollTo({
                            top: document.documentElement.scrollHeight,
                            behavior: 'instant' // yoki 'auto'
                        });
                    });
                });

            },

            copied: false,

            async copyCustomId(id) {
                if (!id) return;

                try {
                    await navigator.clipboard.writeText(id);

                    toastSuccess(`ID copied: ${id}`, 'Copied', 1500);

                } catch (error) {
                    toastError('Failed to copy ID');
                }
            },


            updateScrollLock() {
                const locked = this.modals.edit || this.modals.confirm;

                document.documentElement.classList.toggle('overflow-hidden', locked);
                document.body.classList.toggle('overflow-hidden', locked);
            },

            toggleDurationMode() {
                this.durationMode = this.durationMode === 'compact' ? 'detailed' : 'compact';
            },

            normalize(task) {
                return {
                    ...task,
                    creator_name: task.creator_name ?? task.creator?.name ?? '-',
                    completed_by_name: task.completed_by_name ?? task.completedBy?.name ?? null,
                    rejected_by_name: task.rejected_by_name ?? task.rejectedBy?.name ?? null,
                    reads: Array.isArray(task.reads)
                        ? task.reads.map((read) => ({
                            ...read,
                            user_name: read.user_name ?? read.user?.name ?? 'Unknown',
                        }))
                        : [],
                    attachments: Array.isArray(task.attachments) ? task.attachments : [],
                };
            },

            applyTask(payload) {
                const normalized = this.normalize(payload);

                this.task = {
                    ...this.task,
                    ...normalized,
                };
            },

            statusLabel(status) {
                const labels = {
                    pending: 'Pending',
                    in_progress: 'In progress',
                    completed: 'Completed',
                    rejected: 'Rejected',
                };

                return labels[status] ?? status ?? '-';
            },

            statusBadgeClass(status) {
                const classes = {
                    pending: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                    in_progress: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
                    completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                    rejected: 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                };

                return classes[status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
            },

            formatDateTime(value) {
                if (!value) return '-';

                const date = new Date(value);
                if (isNaN(date.getTime())) return '-';

                return new Intl.DateTimeFormat('en-GB', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                }).format(date).replace(',', '');
            },

            humanFileSize(bytes) {
                if (!bytes) return '0 B';

                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let size = Number(bytes);
                let unitIndex = 0;

                while (size >= 1024 && unitIndex < units.length - 1) {
                    size /= 1024;
                    unitIndex++;
                }

                return `${size.toFixed(unitIndex === 0 ? 0 : 2)} ${units[unitIndex]}`;
            },

            formatDuration(ms) {
                if (ms < 0) ms = 0;

                const totalSeconds = Math.floor(ms / 1000);
                const days = Math.floor(totalSeconds / 86400);
                const hours = Math.floor((totalSeconds % 86400) / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                const pad = (n) => String(n).padStart(2, '0');

                if (this.durationMode === 'detailed') {
                    const parts = [];
                    if (days > 0) parts.push(`${days}d`);
                    if (hours > 0 || days > 0) parts.push(`${hours}h`);
                    if (minutes > 0 || hours > 0 || days > 0) parts.push(`${minutes}m`);
                    parts.push(`${pad(seconds)}s`);
                    return parts.join(' ');
                }

                const totalHours = Math.floor(totalSeconds / 3600);
                if (totalHours > 0) return `${totalHours}h ${pad(minutes)}m`;
                if (minutes > 0) return `${minutes}m ${pad(seconds)}s`;
                return `${seconds}s`;
            },

            taskDuration(task) {
                if (!task?.end_date) return '-';

                if (task.status === 'completed') {
                    return 'COMPLETED';
                }

                if (task.status === 'rejected') {
                    return 'REJECTED';
                }

                const end = new Date(task.end_date);

                if (isNaN(end.getTime())) return '-';

                let diff = end.getTime() - this.now;

                if (diff <= 0) {
                    return 'OVERDUE';
                }

                const days = Math.floor(diff / 86400000);
                diff %= 86400000;

                const hours = Math.floor(diff / 3600000);
                diff %= 3600000;

                const minutes = Math.floor(diff / 60000);
                diff %= 60000;

                const seconds = Math.floor(diff / 1000);

                return `${days}d ${String(hours).padStart(2,'0')}h ${String(minutes).padStart(2,'0')}m ${String(seconds).padStart(2,'0')}s`;
            },

            durationClass(task) {

                if (task.status === 'completed') {
                    return 'text-emerald-600';
                }

                if (task.status === 'rejected') {
                    return 'text-rose-600';
                }

                if (new Date(task.end_date) < new Date(this.now)) {
                    return 'text-red-600';
                }

                return 'text-emerald-600';
            },

            isImageAttachment(attachment) {
                const mime = (attachment?.mime_type || '').toLowerCase();
                return mime.startsWith('image/');
            },

            attachmentBadge(attachment) {
                const mime = (attachment?.mime_type || '').toLowerCase();
                const ext = (attachment?.extension || '').toLowerCase();

                if (mime.includes('pdf') || ext === 'pdf') return 'PDF';
                if (mime.includes('word') || ext === 'doc' || ext === 'docx') return 'DOC';
                if (mime.includes('excel') || ext === 'xls' || ext === 'xlsx' || ext === 'csv') return 'XLS';
                if (mime.includes('zip') || ext === 'zip' || ext === 'rar' || ext === '7z') return 'ZIP';
                if (mime.startsWith('video/')) return 'VID';
                if (mime.startsWith('audio/')) return 'AUD';
                if (ext) return ext.slice(0, 3).toUpperCase();

                return 'FILE';
            },

            canEdit() {
                return Boolean(this.task.can_edit) && this.task.status === 'pending';
            },

            canReject() {
                return Boolean(this.task.can_reject) && ['pending', 'in_progress'].includes(this.task.status);
            },

            canComplete() {
                return this.task.status === 'in_progress'
                    && Number(this.task.created_by) !== Number(window.authUserId);
            },

            openEdit() {
                this.editForm.name = this.task.name ?? '';
                this.editForm.description = this.task.description ?? '';
                this.editForm.end_date = this.task.end_date ?? '';

                this.editForm.attachments = [];
                this.editForm.deleted_attachment_ids = [];

                this.modals.edit = true;

                this.$nextTick(() => {

                    if (this.endDatePicker) {
                        this.endDatePicker.destroy();
                    }

                    this.endDatePicker = flatpickr("#editTaskEndDate", {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "F j, Y",
                        defaultDate: this.editForm.end_date,
                        disableMobile: true,

                        onChange: (selectedDates, dateStr) => {
                            this.editForm.end_date = dateStr;
                        }
                    });

                });
            },

            askComplete() {
                this.actionType = 'complete';
                this.confirmTitle = 'Complete task?';
                this.confirmMessage = `This will mark "${this.task.name}" as completed.`;
                this.modals.confirm = true;
            },

            askReject() {
                this.actionType = 'reject';
                this.confirmTitle = 'Reject task?';
                this.confirmMessage = `This will mark "${this.task.name}" as rejected.`;
                this.modals.confirm = true;
            },

            closeModal(name) {
                if ((name === 'confirm' && this.loadingAction) || (name === 'edit' && this.savingEdit)) return;
                this.modals[name] = false;

                if (name === 'edit' && this.endDatePicker) {
                    this.endDatePicker.destroy();
                    this.endDatePicker = null;
                }
            },

            closeAll(force = false) {
                if (!force && (this.loadingAction || this.savingEdit)) return;

                this.modals.edit = false;
                this.modals.confirm = false;
                this.actionType = null;

                document.documentElement.classList.remove('overflow-hidden');
                document.body.classList.remove('overflow-hidden');
            },

            removeExistingAttachment(id) {
                const idx = this.editForm.deleted_attachment_ids.indexOf(Number(id));

                if (idx === -1) {
                    this.editForm.deleted_attachment_ids.push(Number(id));
                    return;
                }

                this.editForm.deleted_attachment_ids.splice(idx, 1);
            },

            isExistingAttachmentDeleted(id) {
                return this.editForm.deleted_attachment_ids.includes(Number(id));
            },

            onEditFilesChange(event) {
                const files = Array.from(event.target.files || []);
                const remaining = Math.max(0, 5 - this.existingAttachmentCount() - this.editForm.attachments.length);
                this.editForm.attachments.push(...files.slice(0, remaining));
                event.target.value = '';
            },

            removeEditFile(index) {
                this.editForm.attachments.splice(index, 1);
            },

            existingAttachmentCount() {
                return (this.task.attachments || []).filter((item) => !this.isExistingAttachmentDeleted(item.id)).length;
            },

            async saveEdit() {
                if (!this.editForm.name?.trim()) return;

                this.savingEdit = true;

                try {
                    const formData = new FormData();
                    formData.append('_method', 'PUT');
                    formData.append('name', this.editForm.name);
                    formData.append('end_date', this.editForm.end_date || '');
                    formData.append('description', this.editForm.description || '');

                    this.editForm.attachments.forEach((file) => {
                        formData.append('attachments[]', file);
                    });

                    this.editForm.deleted_attachment_ids.forEach((id) => {
                        formData.append('deleted_attachment_ids[]', id);
                    });

                    const response = await axios.post(this.task.update_details_url, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                    });

                    if (response.data?.data) {
                        this.applyTask(response.data.data);
                    } else {
                        this.task.name = this.editForm.name;
                        this.task.description = this.editForm.description;
                    }

                    this.modals.edit = false;
                } catch (error) {
                    //
                } finally {
                    this.savingEdit = false;
                }
            },

            async submitAction() {
                if (!this.task || !this.actionType) return;

                this.loadingAction = true;

                try {
                    let response = null;

                    if (this.actionType === 'complete') {
                        response = await axios.post(this.task.complete_url);
                    }

                    if (this.actionType === 'reject') {
                        response = await axios.post(this.task.reject_url);
                    }

                    if (response?.data?.data) {
                        this.applyTask(response.data.data);
                    }

                    this.closeAll(true);
                } catch (error) {
                    //
                } finally {
                    this.loadingAction = false;
                }
            },
        };
    }

    window.authUserId = @json(auth()->id());
    </script>
@endpush
