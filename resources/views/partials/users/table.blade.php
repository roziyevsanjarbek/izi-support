<div class="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 md:px-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">User list</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">View, edit, and remove users from here.</p>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Total on page: <span class="font-medium text-gray-900 dark:text-white" x-text="users.length"></span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50/80 dark:bg-gray-800/40">
                <tr class="text-left">
                    <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">#</th>
                    <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Name</th>
                    <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                    <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Role</th>
                    <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <template x-for="(user, index) in users" :key="user.id">
                    <tr class="group transition hover:bg-gray-50/70 dark:hover:bg-gray-800/30">
                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300" x-text="startIndex + index"></td>

                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-500 text-sm font-semibold text-white shadow-sm">
                                    <span x-text="(user.name || '?').charAt(0).toUpperCase()"></span>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white" x-text="user.name"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="'ID: ' + user.id"></div>
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300" x-text="user.email"></td>

                        <td class="px-5 py-4 text-sm">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
                                :class="user.role_name === 'superadmin'
                                    ? 'bg-purple-50 text-purple-700 ring-purple-200 dark:bg-purple-500/15 dark:text-purple-200 dark:ring-purple-500/30'
                                    : 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700'"
                                x-text="user.role_name || '-'"
                            ></span>
                        </td>

                        <td class="px-5 py-4 text-sm">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="openModal('show', user.id)"
                                    class="inline-flex h-10 items-center gap-2 rounded-2xl border border-blue-200 bg-blue-50 px-4 text-sm font-semibold text-blue-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-blue-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-blue-500/15 dark:border-blue-900/50 dark:bg-blue-900/20 dark:text-blue-200 dark:hover:bg-blue-900/30"
                                >
                                    View
                                </button>

                                @if($isSuperadmin)
                                    <template x-if="user.role_name !== 'superadmin'">
                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                @click="openModal('edit', user.id)"
                                                class="inline-flex h-10 items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-amber-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-amber-500/15 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200 dark:hover:bg-amber-900/30"
                                            >
                                                Edit
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(user)"
                                                class="inline-flex h-10 items-center gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-red-100 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-red-500/15 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-200 dark:hover:bg-red-900/30"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </template>
                                @endif
                            </div>
                        </td>
                    </tr>
                </template>

                <template x-if="!users.length">
                    <tr>
                        <td colspan="5" class="px-5 py-14 text-center">
                            <div class="mx-auto max-w-sm rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-8 dark:border-gray-700 dark:bg-gray-950/40">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300">—</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">No users found.</div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try changing the page or add a new user.</div>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>