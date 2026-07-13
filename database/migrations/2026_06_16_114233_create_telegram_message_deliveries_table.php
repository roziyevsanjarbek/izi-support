<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_message_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('message_id')
    ->nullable()
    ->constrained('messages')
    ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('telegram_chat_id');
            $table->unsignedBigInteger('telegram_message_id');

            $table->timestamps();

            $table->unique(
                ['telegram_chat_id', 'telegram_message_id'],
                'tg_msg_unique'
            );

            $table->index(
                ['message_id', 'user_id'],
                'tg_msg_user_idx'
            );
});
    }
    public function down(): void
    {
        Schema::dropIfExists('telegram_message_deliveries');
    }
};
