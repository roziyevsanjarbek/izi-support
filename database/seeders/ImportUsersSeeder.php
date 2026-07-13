<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportUsersSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');

        $roleIds = DB::table('roles')
            ->pluck('id', 'name')
            ->toArray();

        DB::table('new_users')
            ->where('auth_type','App\\Models\\Users\\Employee')
            ->orderBy('id')
            ->chunk(500, function ($users) use ($defaultPassword, $roleIds) {
                foreach ($users as $u) {
                    $email = $u->email ?? "user_{$u->id}@import.local";
                    $emailLower = mb_strtolower($email);

                    $roleName = 'user';

                    if (str_contains($emailLower, 'operation')) {
                        $roleName = 'operation';
                    } elseif (str_contains($emailLower, 'sales')) {
                        $roleName = 'sales';
                    }

                    DB::table('users')->insert([
                        'id'=>$u->id,
                        'name' => $u->name,
                        'email' => $email,
                        'password' => $u->password ?? $defaultPassword,
                        'role_id' => $roleIds[$roleName] ?? $roleIds['user'] ?? 1,
                        'created_at'=>$u->created_at,
                        'updated_at'=>$u->updated_at,
                    ]);
                }
            });
    }
}