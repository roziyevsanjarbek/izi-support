<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('reply_to_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            $table->string('type')->default('text');

            $table->longText('message')->nullable();

            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->string('source')->default('web');
            $table->unsignedBigInteger('telegram_account_id')->nullable();
            $table->string('telegram_chat_id')->nullable()->index();
            $table->unsignedBigInteger('telegram_message_id')->nullable()->index();
            $table->unsignedBigInteger('telegram_reply_to_message_id')->nullable()->index();


            $table->boolean('is_edited')->default(false);
            $table->boolean('is_deleted')->default(false);

            $table->timestamps();

            $table->unique(['telegram_chat_id', 'telegram_message_id']);
            $table->index(['conversation_id', 'created_at']);
            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};