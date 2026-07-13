{{-- MAIN MODAL --}}
<div
    x-show="modal.open"
    x-cloak
    x-transition.opacity.duration.150ms
    class="fixed inset-0 z-[99990] flex items-center justify-center  bg-slate-950/70 p-4 backdrop-blur-[2px] sm:p-6"
    @keydown.escape.window="closeModal()"
    @click.self="closeModal()"
>
    <div
        x-transition.scale.duration.150ms
        class="mx-auto w-full max-w-4xl"
    >
        <div class="overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 bg-gray-50 px-6 py-5 dark:border-gray-800 dark:bg-gray-950/40">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="modalTitle()"></h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="modalSubtitle()"></p>
                </div>

                <button
                    type="button"
                    @click="closeModal()"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-500/10 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    ✕
                </button>
            </div>

            <div class="max-h-[85vh]  p-6">
                <template x-if="modal.mode === 'show'">
                    <div>
                        <template x-if="loadingDetails">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-400">
                                Loading...
                            </div>
                        </template>

                        <template x-if="selectedUser && !loadingDetails">
                            <div class="space-y-6">
                                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 shadow-sm dark:border-gray-800 dark:from-gray-950 dark:to-gray-900">
                                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</div>
                                        <div class="mt-2 break-words text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedUser.name"></div>
                                    </div>

                                    <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 shadow-sm dark:border-gray-800 dark:from-gray-950 dark:to-gray-900">
                                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</div>
                                        <div class="mt-2 break-words text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedUser.email"></div>
                                    </div>
                                    <div
                                        class="rounded-3xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 shadow-sm dark:border-gray-800 dark:from-gray-950 dark:to-gray-900">
                                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Telegram ID</div>
                                        <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedUser.telegram_id || '-'">
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 shadow-sm dark:border-gray-800 dark:from-gray-950 dark:to-gray-900">
                                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Role</div>
                                        <div class="mt-2">
                                            <span
                                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
                                                :class="selectedUser.role_name === 'superadmin'
                                                    ? 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/15 dark:text-purple-200 dark:ring-purple-500/30'
                                                    : 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700'"
                                                x-text="selectedUser.role_name || '-'"
                                            ></span>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 shadow-sm dark:border-gray-800 dark:from-gray-950 dark:to-gray-900">
                                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">User ID</div>
                                        <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedUser.id || '-'"></div>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-gray-200 p-5 dark:border-gray-800">
                                    <div class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Permissions</div>

                                    <div class="flex flex-wrap gap-2" x-show="selectedUser.permissions_labels && selectedUser.permissions_labels.length">
                                        <template x-for="permission in selectedUser.permissions_labels" :key="permission">
                                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-200" x-text="permission"></span>
                                        </template>
                                    </div>

                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-show="!selectedUser.permissions_labels || !selectedUser.permissions_labels.length">
                                        No permissions assigned.
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 shadow-sm dark:border-gray-800 dark:from-gray-950 dark:to-gray-900">
                                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</div>
                                        <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedUser.created_at || '-'"></div>
                                    </div>

                                    <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5 shadow-sm dark:border-gray-800 dark:from-gray-950 dark:to-gray-900">
                                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Updated</div>
                                        <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white" x-text="selectedUser.updated_at || '-'"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="modal.mode === 'create' || modal.mode === 'edit'">
                    <form @submit.prevent="saveUser()" class="space-y-6">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input
                                    type="text"
                                    x-model="form.name"
                                    class="h-12 w-full rounded-2xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                    placeholder="Enter full name"
                                >
                                <template x-if="errors.name"><p class="mt-2 text-sm text-red-500" x-text="errors.name[0]"></p></template>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input
                                    type="email"
                                    x-model="form.email"
                                    class="h-12 w-full rounded-2xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                    placeholder="name@example.com"
                                >
                                <template x-if="errors.email"><p class="mt-2 text-sm text-red-500" x-text="errors.email[0]"></p></template>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                                <div class="relative">
                                    <input
                                        :type="showPassword ? 'text' : 'password'"
                                        x-model="form.password"
                                        autocomplete="new-password"
                                        class="h-12 w-full rounded-2xl border border-gray-300 bg-white px-4 pr-12 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                        placeholder="Enter password"
                                    >

                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-500 transition hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"
                                        aria-label="Toggle password visibility"
                                    >
                                        <svg x-show="!showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0s-3-6-9-6-9 6-9 6 3 6 9 6 9-6 9-6z" />
                                        </svg>
                                        <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-6 0-9-7-9-7a17.45 17.45 0 014.523-5.774M9.88 9.88a3 3 0 104.24 4.24M6.1 6.1L3 3m18 18l-3.1-3.1M14.12 14.12L20.485 20.485M9.88 9.88L3.515 3.515" />
                                        </svg>
                                    </button>
                                </div>
                                <template x-if="errors.password"><p class="mt-2 text-sm text-red-500" x-text="errors.password[0]"></p></template>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                                <div class="relative">
                                    <input
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        x-model="form.password_confirmation"
                                        autocomplete="new-password"
                                        class="h-12 w-full rounded-2xl border border-gray-300 bg-white px-4 pr-12 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                        placeholder="Repeat password"
                                    >

                                    <button
                                        type="button"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                                        class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-500 transition hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"
                                        aria-label="Toggle password confirmation visibility"
                                    >
                                        <svg x-show="!showPasswordConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0s-3-6-9-6-9 6-9 6 3 6 9 6 9-6 9-6z" />
                                        </svg>
                                        <svg x-show="showPasswordConfirmation" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-6 0-9-7-9-7a17.45 17.45 0 014.523-5.774M9.88 9.88a3 3 0 104.24 4.24M6.1 6.1L3 3m18 18l-3.1-3.1M14.12 14.12L20.485 20.485M9.88 9.88L3.515 3.515" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                                <select
                                    x-model="form.role_id"
                                    class="h-12 w-full rounded-2xl border border-gray-300 bg-white px-4 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                >
                                    <option value="">Select role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <template x-if="errors.role_id"><p class="mt-2 text-sm text-red-500" x-text="errors.role_id[0]"></p></template>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-gray-200 p-5 shadow-sm dark:border-gray-800">
                            <div class="mb-3 flex items-center justify-between gap-4">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Permissions</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Choose what this user can access.</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach($permissionOptions as $permission)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 transition hover:-translate-y-0.5 hover:border-brand-300 hover:bg-brand-50/60 hover:shadow-sm dark:border-gray-800 dark:bg-gray-950 dark:hover:border-brand-700 dark:hover:bg-brand-500/10">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                            value="{{ $permission['key'] }}"
                                            x-model="form.permissions"
                                        >
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $permission['text'] }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <template x-if="errors.permissions">
                                <p class="mt-2 text-sm text-red-500" x-text="errors.permissions[0]"></p>
                            </template>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
                            <button
                                type="button"
                                @click="closeModal()"
                                class="inline-flex h-11 items-center justify-center rounded-2xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-gray-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                :disabled="saving"
                                class="inline-flex h-11 items-center justify-center rounded-2xl bg-gradient-to-r from-brand-500 to-indigo-500 px-5 text-sm font-semibold text-white shadow-lg shadow-brand-500/20 transition-all duration-200 hover:-translate-y-0.5 hover:from-brand-600 hover:to-indigo-600 hover:shadow-xl hover:shadow-brand-500/25 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span x-show="!saving" x-text="modal.mode === 'create' ? 'Save' : 'Update'"></span>
                                <span x-show="saving" x-text="modal.mode === 'create' ? 'Saving...' : 'Updating...'"></span>
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
<div
    x-show="deleteModal.open"
    x-cloak
    x-transition.opacity.duration.150ms
    class="fixed inset-0 z-[99991] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-[2px]"
    @keydown.escape.window="closeDeleteModal()"
    @click.self="closeDeleteModal()"
>
    <div x-transition.scale.duration.150ms class="w-full max-w-md overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-gray-900">
        <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-8.1 14A2 2 0 003.92 21h16.16a2 2 0 001.73-3.14l-8.1-14a2 2 0 00-3.46 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Delete User</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                </div>
            </div>
        </div>

        <div class="px-6 py-5">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                Are you sure you want to delete
                <span class="font-semibold text-gray-900 dark:text-white" x-text="deleteModal.user?.name || '-'"></span>?
            </p>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
            <button
                type="button"
                @click="closeDeleteModal()"
                class="inline-flex h-11 items-center justify-center rounded-2xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-gray-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:bg-gray-800"
            >
                Cancel
            </button>

            <button
                type="button"
                @click="deleteUser()"
                :disabled="deleting"
                class="inline-flex h-11 items-center justify-center rounded-2xl bg-gradient-to-r from-red-500 to-rose-500 px-5 text-sm font-semibold text-white shadow-lg shadow-red-500/20 transition-all duration-200 hover:-translate-y-0.5 hover:from-red-600 hover:to-rose-600 hover:shadow-xl hover:shadow-red-500/25 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span x-show="!deleting">Delete</span>
                <span x-show="deleting">Deleting...</span>
            </button>
        </div>
    </div>
</div>