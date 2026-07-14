<div x-cloak x-show="modals.edit" x-transition.opacity class="fixed inset-0 z-[99990]">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-md" @click="closeModal('edit')"></div>

    <div class="relative z-[99991] flex min-h-[100dvh] items-center justify-center p-4">
        <div x-transition class="w-full max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900 max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Edit task</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update task details and attachments.</p>
                </div>

                <button type="button" @click="closeModal('edit')"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200">
                    ✕
                </button>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" x-model="editForm.name"
                        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea x-model="editForm.description" rows="6"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"></textarea>
                </div>

                <div>
                    <label for="editTaskEndDate" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        End Date
                    </label>

                    <input
                        type="text"
                        id="editTaskEndDate"
                        placeholder="YYYY-MM-DD"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        x-model="editForm.end_date"
                        readonly
                    >
                    <p id="editTaskEndDateError" class="mt-1 hidden text-xs text-red-500"></p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Current attachments</label>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <template x-for="attachment in (task.attachments || [])" :key="'existing-' + attachment.id">
                            <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-950/30"
                                 :class="isExistingAttachmentDeleted(attachment.id) ? 'opacity-50 ring-2 ring-red-500/30' : ''">
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

                                        <div class="mt-3">
                                            <button type="button"
                                                @click="removeExistingAttachment(attachment.id)"
                                                class="inline-flex h-9 items-center justify-center rounded-lg px-3 text-xs font-semibold"
                                                :class="isExistingAttachmentDeleted(attachment.id)
                                                    ? 'bg-emerald-500 text-white hover:bg-emerald-600'
                                                    : 'bg-red-500 text-white hover:bg-red-600'">
                                                <span x-show="!isExistingAttachmentDeleted(attachment.id)">Delete</span>
                                                <span x-show="isExistingAttachmentDeleted(attachment.id)" x-cloak>Undo delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="!task.attachments || task.attachments.length === 0" x-cloak
                        class="rounded-2xl border border-dashed border-gray-200 px-4 py-6 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        No current attachments.
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Add attachments</label>
                    <input type="file" multiple @change="onEditFilesChange($event)"
                        class="block w-full cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:file:bg-white/5 dark:file:text-gray-200">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Max 5 files total after delete. Each file up to 100MB.</p>

                    <div class="mt-3 space-y-2" x-show="editForm.attachments.length" x-cloak>
                        <template x-for="(file, index) in editForm.attachments" :key="file.name + '-' + index">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-3 py-2 dark:border-gray-700">
                                <div class="min-w-0 flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-xs font-bold text-gray-600 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300"
                                         x-text="(file.type || '').startsWith('image/') ? 'IMG' : (file.name.split('.').pop() || 'FILE').slice(0, 3).toUpperCase()"></div>

                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-gray-900 dark:text-gray-100" x-text="file.name"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="`${fileToMb(file)} MB`"></div>
                                    </div>
                                </div>

                                <button type="button" @click="removeEditFile(index)"
                                    class="text-sm font-semibold text-red-500 hover:text-red-600">
                                    Remove
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="closeModal('edit')"
                    class="h-11 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5">
                    Cancel
                </button>

                <button type="button" @click="saveEdit()" :disabled="savingEdit || !editForm.name.trim()"
                    class="h-11 rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!savingEdit">Update</span>
                    <span x-show="savingEdit" x-cloak>Updating...</span>
                </button>
            </div>
        </div>
    </div>
</div>
