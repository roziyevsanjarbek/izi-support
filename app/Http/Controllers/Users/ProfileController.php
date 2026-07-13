<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Telegram\TelegramLinkToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user()->load('role');

        return view('pages.users.profile', [
            'user' => $user,
            'roleName' => $user->role?->name ?? 'Unknown',
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function connectTelegram(): JsonResponse
    {
        $user = auth()->user();

        if ($user->telegram_id) {
            return response()->json([
                'message' => 'Telegram is already linked.',
            ], 422);
        }

        $botUsername = config('services.telegram.bot_username');

        if (!$botUsername) {
            return response()->json([
                'message' => 'Telegram bot username is not configured.',
            ], 500);
        }

        $token = Str::random(48);

        TelegramLinkToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addMinutes(20),
        ]);

        return response()->json([
            'message' => 'Opening Telegram...',
            'deep_link' => "https://t.me/{$botUsername}?start=bind_{$token}",
        ]);
    }

    public function disconnectTelegram(): JsonResponse
    {
        $user = auth()->user();

        $user->update([
            'telegram_id' => null,
        ]);

        TelegramLinkToken::where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        return response()->json([
            'message' => 'Telegram ID has been unset.',
            'telegram_id' => null,
        ]);
    }
}