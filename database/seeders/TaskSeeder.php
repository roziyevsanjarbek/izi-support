<?php

namespace Database\Seeders;

use App\Models\Tasks\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Create users first.');
            return;
        }

        $statuses = ['pending', 'in_progress', 'completed'];

        for ($i = 1; $i <= 40; $i++) {

            $creator = $users->random();

            Task::create([
                'created_by' => $creator->id,
                'name' => 'Task ' . $i . ' - ' . Str::title(fake()->words(3, true)),
                'description' => fake()->sentence(12),
                'status' => $statuses[array_rand($statuses)],
                'start_date' => now()->subDays(rand(0, 20)),
                'end_date' => rand(0, 1) ? now()->addDays(rand(1, 10)) : null,
                'type' => 'default',
            ]);
        }
    }
}