@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
@php
    $taskItems = $tasks->getCollection()->values()->map(function ($task, $index) use ($tasks) {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'custom_id' => $task->custom_id,
            'status' => $task->status,
            'created_by' => $task->created_by,
            'creator_name' => $task->creator?->name,
            'completed_by' => $task->completed_by,
            'completed_by_name' => $task->completedBy?->name,
            'start_date' => $task->start_date?->toIso8601String(),
            'end_date' => $task->end_date?->toIso8601String(),
            'duration_mode' => 'hours',
            'unread_count' => $task->unread_count ?? 0,
            'attachments_count' => $task->attachments_count ?? 0,
            'row_number' => ($tasks->currentPage() - 1) * $tasks->perPage() + $index + 1,

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
            'view_url' => route('tasks.show', $task),
        ];
    })->values();
@endphp

<div x-data="taskIndexPage(@js($taskItems), @js($stats))" x-init="init()" class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Tasks</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tasks list, filters, and actions.</p>
        </div>

        <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
            <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <input type="text" name="filter[name]" value="{{ request('filter.name') }}" placeholder="Name..."
                    class="h-11 rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">

                <input type="text" name="filter[description]" value="{{ request('filter.description') }}" placeholder="Description..."
                    class="h-11 rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">

                <select name="filter[status]"
                    class="h-11 rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">All status</option>
                    <option value="pending" @selected(request('filter.status') === 'pending')>Pending</option>
                    <option value="in_progress" @selected(request('filter.status') === 'in_progress')>In progress</option>
                    <option value="completed" @selected(request('filter.status') === 'completed')>Completed</option>
                    <option value="rejected" @selected(request('filter.status') === 'rejected')>Rejected</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600">
                        Filter
                    </button>

                    <a href="{{ $operation ? route('tasks.query-tasks.index') : route('tasks.index') }}"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5">
                        Reset
                    </a>

                    @if(!$operation)
                        <button type="button" @click="openCreate()"
                            class="inline-flex h-11 items-center justify-center rounded-xl bg-emerald-500 px-4 text-sm font-semibold text-white hover:bg-emerald-600">
                            + Create
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100" x-text="stats.total"></div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
            <div class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-400" x-text="stats.pending"></div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:col-span-2 xl:col-span-1">
            <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
            <div class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400" x-text="stats.completed"></div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Custom ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Creator</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Start date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        End Date
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Elapsed
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <template x-for="task in tasks" :key="task.id">
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-white/5">
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="task.row_number"></td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="task.custom_id || 'N/A'"></td>

                        <td class="px-4 py-4">
                        <div class="max-w-[280px]">
                            <div class="truncate text-sm font-medium text-gray-900 dark:text-gray-100" x-text="task.name"></div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="`${task.attachments_count ?? 0} attachment(s)`"></div>
                        </div>
                    </td>

                        <td class="px-4 py-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="task.creator_name || 'Unknown'"></span>
                        </td>

                        <td class="px-4 py-4">
                            <div class="text-sm whitespace-nowrap text-gray-700 dark:text-gray-300" x-text="formatDateTime(task.start_date)"></div>
                        </td>

                        <td class="px-4 py-4">
                            <div class="text-sm whitespace-nowrap text-gray-700 dark:text-gray-300"
                                 x-text="formatDateTime(task.end_date)">
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            <span
                                class="font-mono text-sm font-semibold"
                                :class="elapsedClass(task)"
                                x-text="countdown(task)">
                            </span>
                        </td>

                        <td class="px-4 py-4">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                :class="statusBadgeClass(task.status)" x-text="statusLabel(task.status)"></span>
                        </td>

                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                <a :href="task.view_url"
                                    class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5">
                                    View
                                </a>

                                {{-- <button type="button" x-show="canEdit(task)" x-cloak @click="openEdit(task)"
                                    class="inline-flex items-center rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5">
                                    Edit
                                </button> --}}

                                <button type="button" x-show="canComplete(task)" x-cloak @click="askComplete(task)"
                                    class="inline-flex items-center rounded-lg bg-emerald-500 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-600">
                                    Complete
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                <tr x-show="tasks.length === 0" x-cloak>
                    <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No tasks found.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{ $tasks->links() }}

    @include('pages.tasks.modals.create')
    @include('pages.tasks.modals.edit')
    @include('pages.tasks.modals.confirm-action')
</div>
@endsection

@push('scripts')
<script>
function fileToMb(file) {
    return (file.size / 1024 / 1024).toFixed(2);
}

function taskIndexPage(initialTasks = [], initialStats = {}) {
    return {
        tasks: initialTasks.map((task) => ({
            ...task,
            duration_mode: task.duration_mode || 'hours',
        })),
        stats: {
            total: initialStats.total ?? 0,
            pending: initialStats.pending ?? 0,
            completed: initialStats.completed ?? 0,
        },

        modals: {
            create: false,
            edit: false,
            confirm: false,
        },

        currentTask: null,
        confirmAction: null,
        confirmTitle: '',
        confirmMessage: '',
        savingCreate: false,
        savingEdit: false,
        loadingAction: false,

        now: Date.now(),
        timerInterval: null,

        createForm: {
            name: '',
            description: '',
            attachments: [],
        },

        editForm: {
            name: '',
            description: '',
            attachments: [],
            deleted_attachment_ids: [],
        },

        init() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.closeAllModals();
            });

            this.syncScrollLock();

            this.timerInterval = setInterval(() => {
                this.now = Date.now();
            }, 1000);
        },

        syncScrollLock() {
            const anyOpen = Object.values(this.modals).some(Boolean);
            document.documentElement.classList.toggle('overflow-hidden', anyOpen);
            document.body.classList.toggle('overflow-hidden', anyOpen);
        },

        openCreate() {
            this.createForm = { name: '', description: '', attachments: [] };
            this.modals.create = true;
            this.syncScrollLock();
        },

        openEdit(task) {
            this.currentTask = this.normalizeTask(task);
            this.editForm.name = task.name ?? '';
            this.editForm.description = task.description ?? '';
            this.editForm.attachments = [];
            this.editForm.deleted_attachment_ids = [];
            this.modals.edit = true;
            this.syncScrollLock();
        },

        askComplete(task) {
            this.currentTask = this.normalizeTask(task);
            this.confirmAction = 'complete';
            this.confirmTitle = 'Complete task?';
            this.confirmMessage = `This will mark "${task.name}" as completed.`;
            this.modals.confirm = true;
            this.syncScrollLock();
        },

        closeModal(name) {
            this.modals[name] = false;
            this.syncScrollLock();
        },

        closeAllModals() {
            this.modals.create = false;
            this.modals.edit = false;
            this.modals.confirm = false;
            this.syncScrollLock();
        },

        onCreateFilesChange(event) {
            const files = Array.from(event.target.files || []);
            const remaining = Math.max(0, 100 - this.createForm.attachments.length);
            this.createForm.attachments.push(...files.slice(0, remaining));
            event.target.value = '';
        },

        onEditFilesChange(event) {
            const files = Array.from(event.target.files || []);
            const remaining = Math.max(0, 100 - this.existingAttachmentCount(this.editForm) - this.editForm.attachments.length);
            this.editForm.attachments.push(...files.slice(0, remaining));
            event.target.value = '';
        },

        removeCreateFile(index) {
            this.createForm.attachments.splice(index, 1);
        },

        removeEditFile(index) {
            this.editForm.attachments.splice(index, 1);
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

        existingAttachmentCount(form) {
            return (this.currentTask?.attachments || []).filter((item) => !form.deleted_attachment_ids.includes(Number(item.id))).length;
        },

        statusLabel(status) {
            const labels = {
                pending: 'Pending',
                in_progress: 'In progress',
                completed: 'Completed',
                rejected: 'Rejected'
            };

            return labels[status] ?? status ?? '-';
        },

        statusBadgeClass(status) {
            const classes = {
                pending: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                in_progress: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
                completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                rejected: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
            };

            return classes[status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
        },

        formatDateTime(value) {
            if (!value) return '-';

            const date = new Date(value);
            if (isNaN(date.getTime())) return value;

            return new Intl.DateTimeFormat('en-GB', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            }).format(date).replace(',', '');
        },

        countdown(task) {

            if (!task.end_date) return '-';

            if (task.status === 'completed') {
                return 'COMPLETED';
            }

            if (task.status === 'rejected') {
                return 'REJECTED';
            }

            const end = new Date(task.end_date);

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

        elapsedClass(task) {

            if (task.status === 'completed') {
                return 'text-emerald-600';
            }

            if (task.status === 'rejected') {
                return 'text-red-600';
            }

            if (new Date(task.end_date) < new Date(this.now)) {
                return 'text-red-600';
            }

            return 'text-emerald-600';
        },

        normalizeTask(task) {
            return {
                ...task,
                duration_mode: task.duration_mode || 'hours',
                creator_name: task.creator_name ?? task.creator?.name ?? 'Unknown',
                completed_by_name: task.completed_by_name ?? task.completedBy?.name ?? null,
                reads: Array.isArray(task.reads)
                    ? task.reads.map((read) => ({
                        ...read,
                        user_name: read.user_name ?? read.user?.name ?? 'Unknown',
                    }))
                    : [],
                attachments: Array.isArray(task.attachments) ? task.attachments : [],
            };
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

        canEdit(task) {
            return Boolean(task.can_edit) || (Number(task.created_by) === Number(window.authUserId) && task.status === 'pending');
        },

        canComplete(task) {
            return task.status === 'in_progress'
                && Number(task.created_by) !== Number(window.authUserId);
        },

        async saveCreate() {
            if (!this.createForm.name?.trim()) return;

            this.savingCreate = true;

            try {
                const formData = new FormData();
                formData.append('name', this.createForm.name);
                formData.append('description', this.createForm.description || '');

                this.createForm.attachments.forEach((file) => {
                    formData.append('attachments[]', file);
                });

                const response = await axios.post("{{ route('tasks.store') }}", formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                if (response.data?.data) {
                    const newTask = this.normalizeTask(response.data.data);
                    this.tasks.unshift(newTask);
                    this.stats.total += 1;
                    this.stats.pending += 1;
                }

                this.closeModal('create');
            } catch (error) {
                //
            } finally {
                this.savingCreate = false;
            }
        },

        async saveEdit() {
            if (!this.currentTask) return;

            this.savingEdit = true;

            try {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('name', this.editForm.name);
                formData.append('description', this.editForm.description || '');

                this.editForm.attachments.forEach((file) => {
                    formData.append('attachments[]', file);
                });

                this.editForm.deleted_attachment_ids.forEach((id) => {
                    formData.append('deleted_attachment_ids[]', id);
                });

                const response = await axios.post(this.currentTask.update_details_url, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                if (response.data?.data) {
                    this.upsertTask(this.normalizeTask(response.data.data));
                }

                this.closeModal('edit');
            } catch (error) {
                //
            } finally {
                this.savingEdit = false;
            }
        },

        findTaskIndex(taskId) {
            return this.tasks.findIndex((task) => String(task.id) === String(taskId));
        },

        upsertTask(updatedTask) {
            const index = this.findTaskIndex(updatedTask.id);

            if (index === -1) return;

            const previous = this.tasks[index];
            const next = { ...previous, ...updatedTask };

            this.tasks.splice(index, 1, next);

            if (this.currentTask && String(this.currentTask.id) === String(updatedTask.id)) {
                this.currentTask = next;
            }
        },

        async submitAction() {
            if (!this.currentTask || !this.confirmAction) return;

            this.loadingAction = true;

            try {
                let response = null;

                if (this.confirmAction === 'complete') {
                    response = await axios.post(this.currentTask.complete_url);
                }

                if (this.confirmAction === 'reject') {
                    response = await axios.post(this.currentTask.reject_url);
                }

                if (response?.data?.data) {
                    this.upsertTask(this.normalizeTask(response.data.data));
                }

                this.closeAllModals();
                this.confirmAction = null;
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
