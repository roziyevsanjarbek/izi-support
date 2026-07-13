<?php

namespace App\Http\Controllers\Page;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Users\Role;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
{
    $permissionMap = collect(PermissionService::options())->pluck('text', 'key');

    $direction = strtolower($request->get('direction', 'desc'));
    if (!in_array($direction, ['asc', 'desc'])) {
        $direction = 'desc';
    }

    $usersQuery = User::with(['role', 'permissions'])
        ->when($request->filled('name'), function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->name . '%');
        })
        ->when($request->filled('email'), function ($query) use ($request) {
            $query->where('email', 'like', '%' . $request->email . '%');
        })
        ->when($request->filled('role_id'), function ($query) use ($request) {
            $query->where('role_id', $request->role_id);
        })
        ->orderBy('id', $direction);

    $users = $usersQuery->paginate(20)->withQueryString();

    $usersForJs = $users->getCollection()->map(function ($user) use ($permissionMap) {
        $permissionKeys = $user->permissions?->pluck('key')->values() ?? collect();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telegram_id' => $user->telegram_id,
            'role_id' => $user->role_id,
            'role_name' => $user->role?->name,
            'permissions' => $permissionKeys->values(),
            'permissions_labels' => $permissionKeys->map(
                fn($key) => $permissionMap[$key] ?? $key
            )->values(),
            'created_at' => optional($user->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($user->updated_at)?->format('Y-m-d H:i:s'),
        ];
    })->values();

    $roles = Role::query()
        ->whereRaw('LOWER(name) != ?', ['superadmin'])
        ->orderBy('name')
        ->get();
        $roleFilters = Role::query()
    ->orderBy('name')
    ->get();

    $permissionOptions = PermissionService::options();

    return view('pages.users.index', compact(
        'users',
        'usersForJs',
        'roles',
        'roleFilters',
        'permissionOptions'
    ));
}

    public function show(User $user): JsonResponse
    {
        $user->load(['role', 'permissions']);

        return response()->json([
            'success' => true,
            'data' => $this->payload($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureSuperadmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        if ($this->isSuperadminRoleId($validated['role_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Superadmin role cannot be assigned here.',
            ], 422);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        PermissionService::sync($user, $validated['permissions'] ?? []);
        $user->load(['role', 'permissions']);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $this->payload($user),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->ensureSuperadmin();

        if ($this->isSuperadminUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Superadmin user cannot be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        if ($this->isSuperadminRoleId($validated['role_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Superadmin role cannot be assigned here.',
            ], 422);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role_id = $validated['role_id'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        PermissionService::sync($user, $validated['permissions'] ?? []);
        $user->load(['role', 'permissions']);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $this->payload($user),
        ]);
    }

    public function destroy(User $user): JsonResponse
{
    $this->ensureSuperadmin();

    if ($this->isSuperadminUser($user)) {
        return response()->json([
            'success' => false,
            'message' => 'Superadmin user cannot be deleted.',
        ], 422);
    }

    if (auth()->id() === $user->id) {
        return response()->json([
            'success' => false,
            'message' => 'You cannot delete yourself.',
        ], 422);
    }

    // fix: HasMany relation
    $user->permissions()->delete();

    $user->delete();

    return response()->json([
        'success' => true,
        'message' => 'User deleted successfully.',
    ]);
}

    private function ensureSuperadmin(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }

    private function isSuperadminUser(User $user): bool
    {
        return strtolower((string) $user->role?->name) === 'superadmin';
    }

    private function isSuperadminRoleId($roleId): bool
    {
        return (string) $roleId === (string) Role::query()
            ->whereRaw('LOWER(name) = ?', ['superadmin'])
            ->value('id');
    }

    private function payload(User $user): array
    {
        $user->loadMissing(['role', 'permissions']);

        $permissionMap = collect(PermissionService::options())->pluck('text', 'key');
        $permissionKeys = $user->permissions?->pluck('key')->values() ?? collect();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role_name' => $user->role?->name,
            'permissions' => $permissionKeys->values(),
            'permissions_labels' => $permissionKeys->map(
                fn ($key) => $permissionMap[$key] ?? $key
            )->values(),
            'created_at' => optional($user->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($user->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}