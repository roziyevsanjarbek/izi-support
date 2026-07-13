<div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input
                type="text"
                name="name"
                value="{{ request('name') }}"
                placeholder="Search by name"
                class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >
        </div>

        <div class="min-w-[180px] flex-1">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input
                type="text"
                name="email"
                value="{{ request('email') }}"
                placeholder="Search by email"
                class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >
        </div>

        <div class="min-w-[160px]">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
            <select
                name="role_id"
                class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >
                <option value="">All roles</option>
                @foreach ($roleFilters as $role)
                    <option value="{{ $role->id }}" @selected(request('role_id') == $role->id)>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="min-w-[160px]">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Order</label>
            <select
                name="direction"
                class="h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-800 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
            >
                <option value="desc" @selected(request('direction', 'desc') === 'desc')>DESC</option>
                <option value="asc" @selected(request('direction') === 'asc')>ASC</option>
            </select>
        </div>

        <button
            type="submit"
            class="h-11 rounded-xl bg-gradient-to-r from-brand-500 to-indigo-500 px-5 text-sm font-semibold text-white shadow-lg shadow-brand-500/20 transition hover:-translate-y-0.5"
        >
            Search
        </button>

        <a
            href="{{ route('users.index') }}"
            class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:bg-gray-800"
        >
            Reset
        </a>
    </form>
</div>