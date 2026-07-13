<?php

namespace App\Console\Commands;

use App\Models\Messages\TelegramScheduledMessage;
use App\Models\Telegram\Madeline\TelegramAccount;
use App\Telegram\TelegramAccountHandler;
use Amp\CancelledException;
use Amp\TimeoutException;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramStartLoopCommand extends Command
{
    protected $signature = 'telegram:start-loop';
    protected $description = 'Start Telegram MadelineProto loop';

    public function handle(): int
    {
        $account = TelegramAccount::query()
            ->where('is_authorized', true)
            ->first();

        if (!$account) {
            $this->error('Authorized Telegram account not found.');
            return self::FAILURE;
        }

        if (!$account->session_path || !File::exists($account->session_path)) {
            $this->error("Session path not found: {$account->session_path}");
            return self::FAILURE;
        }

        $account->update([
            'status' => 'running',
            'last_ping' => now(),
            'last_activity_at' => now(),
            'last_error' => null,
        ]);

        $settings = $this->buildSettings();

        TelegramAccountHandler::startAndLoop($account->session_path, $settings);
    }

    private function buildSettings(): Settings
    {
        $settings = new Settings();

        $appInfo = new AppInfo();
        $appInfo->setApiId((int) env('TELEGRAM_API_ID'));
        $appInfo->setApiHash((string) env('TELEGRAM_API_HASH'));
        $appInfo->setLangCode(config('app.locale', 'en'));
        $appInfo->setSystemLangCode('en');
        $appInfo->setShowPrompt(false);

        $settings->setAppInfo($appInfo);

        $loggerSettings = (new LoggerSettings())
            ->setType(Logger::FILE_LOGGER)
            ->setExtra(storage_path('logs/madeline.log'));
        $loggerSettings->setMaxSize(50 * 1024 * 1024);
        $settings->setLogger($loggerSettings);

        return $settings;
    }


}