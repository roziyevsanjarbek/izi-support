<?php

namespace App\Services;

use App\Models\User;

class PermissionService
{
    public function __construct()
    {
        //
    }

    public static function options(): array
    {
        return [
            ['key' => 'users',  'text' => "Users"],
            ['key' => 'create_tasks',  'text' => "Create Tasks"],
            ['key' => 'reject_tasks',  'text' => "Reject Tasks"],
           
        ];
    }
    public static function all(): array
    {
        return array_column(self::options(), 'key');
    }
    public static function label(string $key): string
    {
        foreach (self::options() as $permission) {
            if ($permission['key'] === $key) {
                return $permission['text'];
            }
        }

        return $key;
    }
    public static function sync(User $user, array $permissions = [])
    {
        $allPermissions = self::all();
        $permissions = array_intersect($permissions, $allPermissions);

        $user->permissions()->delete();

        $insert = [];

        foreach ($permissions as $key) {
            $insert[] = [
                'user_id' => $user->id,
                'key' => $key,
                'allowed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($insert)) {
            $user->permissions()->insert($insert);
        }
    }
}
