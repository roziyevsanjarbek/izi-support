<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_reminders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('calendar_event_id')->constrained('calendar_events')->cascadeOnDelete();
            $table->timestamp('occurrence_at')->nullable()->index();
            $table->string('color', 20)->nullable();
 
            $table->string('status')->default('pending')->index(); // pending, retrying, done, expired
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->unsignedInteger('interval_minutes')->default(20);

            $table->timestamp('next_send_at')->nullable()->index();
            $table->timestamp('last_sent_at')->nullable()->index();
            $table->timestamp('done_at')->nullable()->index();

            $table->text('last_error')->nullable();
            $table->boolean('need_call')->default(true);
            $table->string('call_status')->nullable();

            $table->json('channels')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['calendar_event_id', 'status']);
            $table->index(['status', 'next_send_at']);
            // $table->index(['calendar_event_id', 'status', 'next_send_at']);
            // $table->index(['calendar_event_id', 'occurrence_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_reminders');
    }
};