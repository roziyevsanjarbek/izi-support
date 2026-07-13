<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class NewUsersSqlSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/sql/new_users.sql');

        if (!File::exists($path)) {
            $this->command->error("SQL file not found: {$path}");
            return;
        }

        $sql = File::get($path);

        DB::unprepared($sql);

        $this->command->info("new_users.sql imported successfully");
    }
}