<div x-cloak x-show="modals.create" x-transition.opacity class="fixed inset-0 z-[99990]">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-md" @click="closeModal('create')"></div>

    <div class="relative z-[99991] flex min-h-[100dvh] items-center justify-center p-4">
        <div x-transition class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900 max-h-[90dvh] overflow-y-auto">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create task</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new task with up to 5 attachments.</p>
                </div>

                <button type="button" @click="closeModal('create')"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200">
                    ✕
                </button>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" x-model="createForm.name"
                        class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        placeholder="Task name">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea x-model="createForm.description" rows="6"
                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 focus:border-brand-500 focus:ring-0 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                        placeholder="Task description"></textarea>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Attachments</label>
                    <input type="file" multiple @change="onCreateFilesChange($event)"
                        class="block w-full cursor-pointer rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:file:bg-white/5 dark:file:text-gray-200">

                    <div class="mt-3 space-y-2" x-show="createForm.attachments.length" x-cloak>
                        <template x-for="(file, index) in createForm.attachments" :key="file.name + '-' + index">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 px-3 py-2 dark:border-gray-700">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-gray-900 dark:text-gray-100" x-text="file.name"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="`${fileToMb(file)} MB`"></div>
                                </div>

                                <button type="button" @click="removeCreateFile(index)" class="text-sm font-semibold text-red-500 hover:text-red-600">
                                    Remove
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="closeModal('create')"
                    class="h-11 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-white/5">
                    Cancel
                </button>

                <button type="button" @click="saveCreate()"
                    :disabled="savingCreate || !createForm.name.trim()"
                    class="h-11 rounded-xl bg-emerald-500 px-4 text-sm font-semibold text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="!savingCreate">Save</span>
                    <span x-show="savingCreate" x-cloak>Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>
