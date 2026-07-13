<div class="mb-6 flex flex-col gap-4 rounded-[28px] border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 md:flex-row md:items-center md:justify-between md:p-6">
    <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            User management
        </div>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white md:text-3xl">Users</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage users, roles and permissions in one place.</p>
    </div>

    @if($isSuperadmin)
        <button
            type="button"
            @click="openCreateModal()"
            class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-brand-500 to-indigo-500 px-5 text-sm font-semibold text-white shadow-lg shadow-brand-500/20 transition-all duration-200 hover:-translate-y-0.5 hover:from-brand-600 hover:to-indigo-600 hover:shadow-xl hover:shadow-brand-500/25 focus:outline-none focus:ring-4 focus:ring-brand-500/20"
        >
            <span class="text-base leading-none">＋</span>
            Add User
        </button>
    @endif
</div>