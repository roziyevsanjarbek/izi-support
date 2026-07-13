<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('telegram_scheduled_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('telegram_account_id')->nullable()->constrained('telegram_accounts')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('messages')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('peer')->index(); // @username, -100..., 123456
            $table->longText('message');
            $table->timestamp('send_at')->index();
            $table->timestamp('send_before_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);


            
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('telegram_message_id')->nullable()->index();
            $table->unsignedBigInteger('telegram_chat_id')->nullable()->index();
            $table->json('telegram_response')->nullable();



            $table->foreignId('calendar_event_id')->nullable()->constrained('calendar_events')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('calendar_event_reminder_id')->nullable()->constrained('calendar_event_reminders')->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('need_call')->default(false);
            $table->string('call_status')->nullable();





            $table->timestamps();

            $table->index(['status', 'send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_scheduled_messages');
    }
};