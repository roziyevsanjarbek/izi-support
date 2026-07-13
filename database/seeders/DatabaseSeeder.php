<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Throwable;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            RoleSeeder::class,
            SuperAdminSeeder::class,
            NewUsersSqlSeeder::class,
            ImportUsersSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            try {
                $this->call($seeder);
            } catch (Throwable $e) {
                report($e);
                $this->command?->error(class_basename($seeder) . ' failed: ' . $e->getMessage());
                continue;
            }
        }
    }
}