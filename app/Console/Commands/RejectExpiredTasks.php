<?php

namespace App\Console\Commands;

use App\Models\Tasks\Task;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RejectExpiredTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:reject-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reject expired tasks automatically';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        Task::whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now())
            ->update([
                'status' => 'rejected',
                'rejected_by' => null, // agar system reject qilayotgan bo'lsa
            ]);

        $this->info('Expired tasks rejected successfully.');

        return self::SUCCESS;
    }
}
