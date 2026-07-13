<?php

namespace App\Console\Commands;

use App\Models\Telegram\Madeline\TelegramAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Telegram loop watchdog — supervisor/systemd olmay turib
 * telegram:start-loop ni avtomatik qayta ishga tushiradi.
 *
 * Ishlatish:
 *   php artisan telegram:watchdog
 *
 * Background'da ishlatish (WSL/Linux):
 *   nohup php artisan telegram:watchdog >> storage/logs/watchdog.log 2>&1 &
 */
class TelegramWatchdogCommand extends Command
{
    protected $signature = 'telegram:watchdog
                            {--delay=10      : Qayta ishga tushirishdan oldin kutish (soniya)}
                            {--max-restarts=0 : Maksimal qayta ishga tushirish soni (0 = cheksiz)}';

    protected $description = 'Watchdog: telegram:start-loop ni avtomatik qayta ishga tushiradi';

    public function handle(): int
    {
        $delay       = max(1, (int) $this->option('delay'));
        $maxRestarts = (int) $this->option('max-restarts');
        $restartCount = 0;

        $this->info('🐕 Telegram watchdog started');
        $this->info("   delay={$delay}s | max_restarts=" . ($maxRestarts ?: '∞'));

        Log::info('Telegram watchdog started', [
            'delay'        => $delay,
            'max_restarts' => $maxRestarts,
        ]);

        while ($maxRestarts === 0 || $restartCount < $maxRestarts) {
            $attempt = $restartCount + 1;
            $this->info('');
            $this->info("[" . now() . "] 🚀 Starting loop (attempt #{$attempt})...");

            $exitCode = $this->runLoop();

            $restartCount++;

            // Auth yo'qolgan bo'lsa qayta urinmaymiz
            if ($this->isAuthLost()) {
                $this->error('[' . now() . '] ❌ Account is logged out. Watchdog stopping.');

                Log::error('Telegram watchdog stopped: account is logged out', [
                    'restarts' => $restartCount,
                ]);

                return self::FAILURE;
            }

            $this->warn("[" . now() . "] ⚠️  Loop exited (code={$exitCode}). Restart #{$restartCount} in {$delay}s...");

            Log::warning('Telegram loop exited, watchdog restarting', [
                'exit_code'     => $exitCode,
                'restart_count' => $restartCount,
            ]);

            sleep($delay);
        }

        $this->error("Max restarts ({$maxRestarts}) reached. Watchdog stopping.");

        Log::error('Telegram watchdog stopped: max restarts reached', [
            'max_restarts' => $maxRestarts,
        ]);

        return self::FAILURE;
    }

    /**
     * telegram:start-loop ni subprocess sifatida ishga tushiradi va
     * tugagunga qadar kutadi. Stdout/stderr ni real-time ko'rsatadi.
     */
    private function runLoop(): int
    {
        $process = new Process(
            command:    [PHP_BINARY, base_path('artisan'), 'telegram:start-loop'],
            // command:    [config('app.php_binary', PHP_BINARY), base_path('artisan'), 'telegram:start-loop'],
            cwd:        base_path(),
            env:        null,    // joriy muhitni meros oladi (.env, etc.)
            input:      null,
            timeout:    null,    // cheksiz — loop abadiy ishlashi kerak
        );

        // Accountni "restarting" holatga qo'yamiz
        $this->markAccountRestarting();

        try {
            $process->run(function (string $type, string $buffer): void {
                // Subprocess output ni bu jarayonning stdout/stderr ga yo'naltiramiz
                echo $buffer;
            });
        } catch (Throwable $e) {
            Log::error('Watchdog process error', ['error' => $e->getMessage()]);
        }

        return $process->getExitCode() ?? 1;
    }

    /**
     * DB'da authorized account logged_out bo'lganini tekshiradi.
     * Agar bo'lsa, qayta urinish mantiqsiz.
     */
    private function isAuthLost(): bool
    {
        try {
            return !TelegramAccount::query()
                ->where('is_authorized', true)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function markAccountRestarting(): void
    {
        try {
            TelegramAccount::query()
                ->where('is_authorized', true)
                ->whereNotIn('status', ['logged_out'])
                ->update(['status' => 'restarting']);
        } catch (Throwable $e) {
            Log::warning('Watchdog: could not mark account as restarting', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}