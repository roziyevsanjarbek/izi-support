<?php

namespace App\Console\Commands;

use App\Models\Telegram\Madeline\TelegramAccount;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo as MadelineAppInfo;
use danog\MadelineProto\Settings\Logger as LoggerSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramVerifyPasswordCommand extends Command
{
    protected $signature = 'telegram:verify-password {phone} {password}';
    protected $description = 'Complete Telegram 2FA password login';

    public function handle(): int
    {
        $phone = $this->normalizePhone((string) $this->argument('phone'));
        $password = (string) $this->argument('password');

        $account = TelegramAccount::where('phone', $phone)->first();

        if (! $account) {
            $this->error("Account not found for {$phone}");
            return self::FAILURE;
        }

        $sessionPath = $account->session_path;

        if (! file_exists($sessionPath) && ! is_dir($sessionPath)) {
            $account->update([
                'status' => 'failed',
                'message_key' => 'session_not_found',
                'message' => null,
                'last_error' => 'Session file not found',
                'last_error_at' => now(),
            ]);

            $this->error("Session not found: {$sessionPath}");
            return self::FAILURE;
        }

        try {
            $Madeline = new API($sessionPath, $this->buildSettings());

            $authorization = $Madeline->complete2falogin($password);

            if (isset($authorization['_']) && $authorization['_'] === 'account.needSignup') {
                throw new \Exception('ACCOUNT_NOT_REGISTERED');
            }

            $self = $Madeline->getSelf();

            $account->update([
                'is_authorized' => true,
                'authorized_at' => now(),
                'status' => 'success',
                'message_key' => 'telegram_verified',
                'message' => null,
                'last_ping' => now(),
                'meta' => array_merge((array) ($account->meta ?? []), [
                    'self' => $self,
                ]),
            ]);

            $this->info("✅ 2FA verified for {$phone}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $message = mb_substr($e->getMessage(), 0, 1000);

            $account->increment('error_count');
            $account->update([
                'status' => 'failed',
                'message' => $message,
                'message_key' => 'password_failed',
                'last_error' => $message,
                'last_error_at' => now(),
                'last_ping' => now(),
            ]);

            Log::error('telegram:verify-password failed', [
                'phone' => $phone,
                'error' => $message,
            ]);

            $this->error("❌ {$message}");
            return self::FAILURE;
        }
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if ($phone !== '' && $phone[0] !== '+') {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    protected function buildSettings(): Settings
    {
        $settings = new Settings();

        $appInfo = new MadelineAppInfo();
        $appInfo->setApiId((int) env('TELEGRAM_API_ID'));
        $appInfo->setApiHash((string) env('TELEGRAM_API_HASH'));

        $appInfo
            ->setDeviceModel('Server')
            ->setLangCode(config('app.locale', 'en'))
            ->setSystemLangCode('en')
            ->setShowPrompt(false);

        $settings->setAppInfo($appInfo);

        $loggerSettings = (new LoggerSettings())->setType(Logger::ERROR);
        $settings->setLogger($loggerSettings);

        return $settings;
    }
}