<?php

namespace App\Console\Commands\Artisan;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class MigrateFreshExceptCommand extends Command
{
    protected $signature = 'mf-except {--keep= : Comma separated tables to keep, e.g. telegram_accounts,new_users,users} {--seed} {--dry-run}';

    protected $description = 'Drop all tables except selected ones, keep their migration records, then run pending migrations and optional seed';

    public function handle(): int
    {
        $keepTables = $this->parseKeepTables((string) $this->option('keep'));

        if (empty($keepTables)) {
            $this->error('Usage: php artisan mf-except --keep=telegram_accounts,new_users,users --seed');
            return self::FAILURE;
        }

        $database = DB::getDatabaseName();
        $tableKey = 'Tables_in_' . $database;

        $this->info('Keeping tables: ' . implode(', ', $keepTables));

        $allTables = DB::select('SHOW TABLES');

        Schema::disableForeignKeyConstraints();

        foreach ($allTables as $table) {
            $tableName = $table->$tableKey;

            if ($tableName === 'migrations' || in_array($tableName, $keepTables, true)) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("[dry-run] Would drop: {$tableName}");
                continue;
            }

            Schema::dropIfExists($tableName);
            $this->info("Dropped: {$tableName}");
        }

        Schema::enableForeignKeyConstraints();

        $keepMigrationNames = $this->collectMigrationNamesForTables($keepTables);

        if (!$this->option('dry-run')) {
            if (!empty($keepMigrationNames)) {
                DB::table('migrations')
                    ->whereNotIn('migration', $keepMigrationNames)
                    ->delete();
            } else {
                $this->warn('No migration names matched kept tables. Migrations table was not changed.');
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run finished.');
            return self::SUCCESS;
        }

        $this->info('Running migrations...');
        $exitCode = Artisan::call('migrate', [
            '--force' => true,
        ]);

        $this->line(Artisan::output());

        if ($exitCode !== SymfonyCommand::SUCCESS) {
            $this->error('Migration failed.');
            return self::FAILURE;
        }

        if ($this->option('seed')) {
            $this->info('Seeding database...');
            $seedExitCode = Artisan::call('db:seed', [
                '--force' => true,
            ]);

            $this->line(Artisan::output());

            if ($seedExitCode !== SymfonyCommand::SUCCESS) {
                $this->error('Seeding failed.');
                return self::FAILURE;
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function parseKeepTables(string $input): array
    {
        $input = trim($input);

        if ($input === '') {
            return [];
        }

        $items = preg_split('/[\s,]+/', $input) ?: [];

        $items = array_map(static fn ($item) => trim($item), $items);
        $items = array_filter($items, static fn ($item) => $item !== '');
        $items = array_values(array_unique($items));

        return $items;
    }

    private function collectMigrationNamesForTables(array $tables): array
    {
        $files = File::files(database_path('migrations'));
        $names = [];

        foreach ($files as $file) {
            $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            foreach ($tables as $table) {
                $pattern = '/(^|_)' . preg_quote($table, '/') . '(_|$)/';

                if (preg_match($pattern, $filename)) {
                    $names[] = $filename;
                    break;
                }
            }
        }

        return array_values(array_unique($names));
    }
}