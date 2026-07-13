<?php

namespace Database\Seeders;

use App\Models\Users\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles=[
            'user',
            'admin',
            'superadmin',

            'operation',
            'sales',
            ];
        foreach($roles as $role)
        {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
