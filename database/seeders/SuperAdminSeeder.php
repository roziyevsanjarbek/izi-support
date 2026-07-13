<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Users\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'iziSuper@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role_id' => Role::where('name', 'superadmin')->value('id'),
            ]
        );
    }
}