<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamp('start_at')->nullable()->index();
            $table->timestamp('end_at')->nullable()->index();

            $table->boolean('all_day')->default(false);
            $table->string('timezone')->nullable();

            $table->string('location')->nullable();
            $table->string('color', 32)->nullable();

            $table->string('status')->default('planned');

            $table->timestamp('reminder_at')->nullable()->index();
            $table->timestamp('next_reminder_at')->nullable()->index();

            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable()->index();

            $table->boolean('reminder_call_enabled')->default(false);

            $table->unsignedSmallInteger('reminder_attempts')->default(0);
            $table->timestamp('reminder_last_attempt_at')->nullable();
            $table->text('reminder_last_error')->nullable();

            $table->boolean('repeat')->default(false);
            $table->string('repeat_type')->nullable(); // daily, weekly, monthly, custom
            $table->unsignedInteger('repeat_interval_minutes')->nullable();
            $table->timestamp('repeat_until')->nullable();

            $table->json('reminder_channels')->nullable();
            $table->json('meta')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['reminder_sent', 'reminder_at']);
            $table->index(['repeat', 'next_reminder_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};