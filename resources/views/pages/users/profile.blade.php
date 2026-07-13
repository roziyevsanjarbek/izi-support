@extends('layouts.app')

@section('title', 'Profile')

@section('content')

<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-8 lg:px-8">

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="lg:col-span-1">
        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111827]">
            <div class="bg-gradient-to-br from-blue-600 to-indigo-600 px-6 py-6 text-white">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 text-2xl font-semibold backdrop-blur">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <h1 id="profileNameText" class="truncate text-2xl font-semibold">{{ $user->name }}</h1>
                        <p class="mt-1 text-sm text-white/80">{{ $roleName }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-2xl bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</div>
                        <div id="profileEmailText" class="mt-1 break-all text-sm font-medium text-gray-900 dark:text-white">
                            {{ $user->email }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Telegram ID</div>
                        <div id="telegramIdText" class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $user->telegram_id ?? 'Not linked' }}
                        </div>
                    </div>

                    <div class="rounded-2xl bg-gray-50 p-4 dark:bg-white/5 sm:col-span-2 lg:col-span-1">
                        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Role</div>
                        <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $roleName }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111827]">
            <div class="border-b border-gray-200 px-5 py-5 dark:border-white/10 sm:px-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update your account details.</p>
            </div>

            <form id="profileForm" class="space-y-5 p-5 sm:p-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                        <p class="mt-2 hidden text-sm text-red-600" data-error-for="name"></p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                        <p class="mt-2 hidden text-sm text-red-600" data-error-for="email"></p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                        id="profileSubmitBtn"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111827]">
            <div class="border-b border-gray-200 px-5 py-5 dark:border-white/10 sm:px-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use a strong password to keep your account secure.</p>
            </div>

            <form id="passwordForm" class="space-y-5 p-5 sm:p-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            name="current_password"
                            autocomplete="current-password"
                            class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-14 text-base text-gray-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/5 dark:text-white"
                        >
                        <button
                            type="button"
                            data-toggle-password
                            class="absolute right-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-gray-100 text-gray-500 transition hover:bg-gray-200 hover:text-gray-700 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/15 dark:hover:text-white"
                            aria-label="Show password"
                        >
                            <svg data-eye-icon class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm0 0c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3zm6 0c0 5.523-4.477 10-10 10S1 17.523 1 12 5.477 2 11 2s10 4.477 10 10z"/>
                            </svg>
                            <svg data-eye-off-icon class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0111 19c-5.523 0-10-4.477-10-10 0-1.422.298-2.775.835-4.003M3 3l18 18M10.477 10.5a3 3 0 004.243 4.243"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 hidden text-sm text-red-600" data-error-for="current_password"></p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-14 text-base text-gray-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            >
                            <button
                                type="button"
                                data-toggle-password
                                class="absolute right-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-gray-100 text-gray-500 transition hover:bg-gray-200 hover:text-gray-700 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/15 dark:hover:text-white"
                                aria-label="Show password"
                            >
                                <svg data-eye-icon class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm0 0c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3zm6 0c0 5.523-4.477 10-10 10S1 17.523 1 12 5.477 2 11 2s10 4.477 10 10z"/>
                                </svg>
                                <svg data-eye-off-icon class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.875 18.825A10.05 10.05 0 0111 19c-5.523 0-10-4.477-10-10 0-1.422.298-2.775.835-4.003M3 3l18 18M10.477 10.5a3 3 0 004.243 4.243"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-2 hidden text-sm text-red-600" data-error-for="password"></p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 pr-14 text-base text-gray-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-white/10 dark:bg-white/5 dark:text-white"
                            >
                            <button
                                type="button"
                                data-toggle-password
                                class="absolute right-2 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl bg-gray-100 text-gray-500 transition hover:bg-gray-200 hover:text-gray-700 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/15 dark:hover:text-white"
                                aria-label="Show password"
                            >
                                <svg data-eye-icon class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm0 0c0 1.657-1.343 3-3 3s-3-1.343-3-3 1.343-3 3-3 3 1.343 3 3zm6 0c0 5.523-4.477 10-10 10S1 17.523 1 12 5.477 2 11 2s10 4.477 10 10z"/>
                                </svg>
                                <svg data-eye-off-icon class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.875 18.825A10.05 10.05 0 0111 19c-5.523 0-10-4.477-10-10 0-1.422.298-2.775.835-4.003M3 3l18 18M10.477 10.5a3 3 0 004.243 4.243"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-2 hidden text-sm text-red-600" data-error-for="password_confirmation"></p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-gray-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 sm:w-auto"
                        id="passwordSubmitBtn"
                    >
                        Change Password
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111827]">
            <div class="border-b border-gray-200 px-5 py-5 dark:border-white/10 sm:px-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Telegram Connection</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Link your Telegram account to enable bot actions and notifications.
                </p>
            </div>

            <div class="p-5 sm:p-6" id="telegramSection">
                @if($user->telegram_id)
                    <div id="telegramStateBox" class="rounded-2xl border border-green-200 bg-green-50 p-5 dark:border-green-900/40 dark:bg-green-900/20">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-green-700 dark:text-green-300">
                                    Telegram is linked
                                </div>
                                <p class="mt-1 text-sm text-green-700/80 dark:text-green-200/80">
                                    Your Telegram ID is saved and active.
                                </p>
                                <div class="mt-3 text-sm text-gray-700 dark:text-gray-200">
                                    <span class="font-medium">Telegram ID:</span>
                                    <span id="telegramIdInline" class="break-all">{{ $user->telegram_id }}</span>
                                </div>
                            </div>

                            <button
                                type="button"
                                id="telegramActionBtn"
                                data-action="disconnect"
                                class="inline-flex w-full items-center justify-center rounded-2xl border border-red-200 bg-white px-4 py-3 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/40 dark:bg-white/5 dark:hover:bg-red-900/10 sm:w-auto"
                            >
                                Unset Telegram ID
                            </button>
                        </div>
                    </div>
                @else
                    <div id="telegramStateBox" class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                            Telegram is not linked
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Click the button below to open Telegram and start the bot.
                        </p>

                        <div class="mt-4">
                            <button
                                type="button"
                                id="telegramActionBtn"
                                data-action="connect"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                            >
                                Set Telegram ID
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (window.axios && csrf) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    }

    const alertBox = document.getElementById('profileAlert');

    function showMessage(message, type = 'success') {
        if (!alertBox) return;

        const styles = {
            success: 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300',
            error: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300',
            info: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300',
        };

        alertBox.className = `mb-6 rounded-2xl border px-4 py-3 text-sm ${styles[type] || styles.info}`;
        alertBox.textContent = message;
        alertBox.classList.remove('hidden');
    }

    function clearErrors(form) {
        form.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function showErrors(form, errors = {}) {
        Object.keys(errors).forEach((key) => {
            const el = form.querySelector(`[data-error-for="${key}"]`);
            if (el) {
                el.textContent = errors[key][0];
                el.classList.remove('hidden');
            }
        });
    }

    function setButtonLoading(button, loadingText, isLoading) {
        if (!button) return;

        if (!button.dataset.originalText) {
            button.dataset.originalText = button.textContent.trim();
        }

        button.disabled = isLoading;
        button.textContent = isLoading ? loadingText : button.dataset.originalText;
    }

    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    const profileSubmitBtn = document.getElementById('profileSubmitBtn');
    const passwordSubmitBtn = document.getElementById('passwordSubmitBtn');

    const profileNameText = document.getElementById('profileNameText');
    const profileEmailText = document.getElementById('profileEmailText');
    const telegramIdText = document.getElementById('telegramIdText');

    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors(profileForm);
            setButtonLoading(profileSubmitBtn, 'Saving...', true);

            try {
                const payload = Object.fromEntries(new FormData(profileForm).entries());
                const { data } = await axios.put("{{ route('profile.update') }}", payload);

                showMessage(data.message || 'Profile updated successfully.', 'success');

                if (data.user?.name && profileNameText) profileNameText.textContent = data.user.name;
                if (data.user?.email && profileEmailText) profileEmailText.textContent = data.user.email;
            } catch (error) {
                if (error.response?.status === 422) {
                    showErrors(profileForm, error.response.data.errors || {});
                } else {
                    showMessage(error.response?.data?.message || 'Something went wrong.', 'error');
                }
            } finally {
                setButtonLoading(profileSubmitBtn, 'Saving...', false);
            }
        });
    }

    if (passwordForm) {
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors(passwordForm);
            setButtonLoading(passwordSubmitBtn, 'Changing...', true);

            try {
                const payload = Object.fromEntries(new FormData(passwordForm).entries());
                const { data } = await axios.put("{{ route('profile.password.update') }}", payload);

                showMessage(data.message || 'Password changed successfully.', 'success');
                passwordForm.reset();
            } catch (error) {
                if (error.response?.status === 422) {
                    showErrors(passwordForm, error.response.data.errors || {});
                } else {
                    showMessage(error.response?.data?.message || 'Something went wrong.', 'error');
                }
            } finally {
                setButtonLoading(passwordSubmitBtn, 'Changing...', false);
            }
        });
    }

    const telegramSection = document.getElementById('telegramSection');

    function bindTelegramButton() {
        const btn = document.getElementById('telegramActionBtn');
        if (!btn) return;

        btn.addEventListener('click', async () => {
            const action = btn.dataset.action;
            if (action === 'connect') {
                await handleTelegramConnect(btn);
            } else if (action === 'disconnect') {
                await handleTelegramDisconnect(btn);
            }
        });
    }

    function renderTelegramLinked(telegramId) {
        telegramSection.innerHTML = `
            <div id="telegramStateBox" class="rounded-2xl border border-green-200 bg-green-50 p-5 dark:border-green-900/40 dark:bg-green-900/20">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-green-700 dark:text-green-300">Telegram is linked</div>
                        <p class="mt-1 text-sm text-green-700/80 dark:text-green-200/80">Your Telegram ID is saved and active.</p>
                        <div class="mt-3 text-sm text-gray-700 dark:text-gray-200">
                            <span class="font-medium">Telegram ID:</span>
                            <span id="telegramIdInline" class="break-all">${telegramId ?? ''}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        id="telegramActionBtn"
                        data-action="disconnect"
                        class="inline-flex w-full items-center justify-center rounded-2xl border border-red-200 bg-white px-4 py-3 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/40 dark:bg-white/5 dark:hover:bg-red-900/10 sm:w-auto"
                    >
                        Unset Telegram ID
                    </button>
                </div>
            </div>
        `;

        bindTelegramButton();
    }

    function renderTelegramUnlinked() {
        telegramSection.innerHTML = `
            <div id="telegramStateBox" class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-white/10 dark:bg-white/5">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">Telegram is not linked</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Click the button below to open Telegram and start the bot.</p>

                <div class="mt-4">
                    <button
                        type="button"
                        id="telegramActionBtn"
                        data-action="connect"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                    >
                        Set Telegram ID
                    </button>
                </div>
            </div>
        `;

        bindTelegramButton();
    }

    async function handleTelegramConnect(btn) {
        setButtonLoading(btn, 'Opening...', true);

        try {
            const { data } = await axios.get("{{ route('profile.telegram.connect') }}");

            if (data.message) {
                showMessage(data.message, 'info');
            }

            if (data.deep_link) {
                window.location.href = data.deep_link;
                return;
            }

            showMessage('Telegram link was not generated.', 'error');
        } catch (error) {
            showMessage(error.response?.data?.message || 'Something went wrong.', 'error');
        } finally {
            setButtonLoading(btn, 'Set Telegram ID', false);
        }
    }

    async function handleTelegramDisconnect(btn) {
        setButtonLoading(btn, 'Unsetting...', true);

        try {
            const { data } = await axios.delete("{{ route('profile.telegram.disconnect') }}");

            showMessage(data.message || 'Telegram ID has been unset.', 'success');

            if (telegramIdText) telegramIdText.textContent = 'Not linked';
            renderTelegramUnlinked();
        } catch (error) {
            showMessage(error.response?.data?.message || 'Something went wrong.', 'error');
        } finally {
            setButtonLoading(btn, 'Unset Telegram ID', false);
        }
    }

    bindTelegramButton();

    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const wrapper = button.closest('.relative');
            const input = wrapper?.querySelector('input');
            const eyeIcon = button.querySelector('[data-eye-icon]');
            const eyeOffIcon = button.querySelector('[data-eye-off-icon]');

            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            if (eyeIcon && eyeOffIcon) {
                eyeIcon.classList.toggle('hidden', !isPassword);
                eyeOffIcon.classList.toggle('hidden', isPassword);
            }

            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });
});
</script>

@endpush
