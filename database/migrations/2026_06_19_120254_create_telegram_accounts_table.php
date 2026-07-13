<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('phone')->unique();
            $table->string('session_path');

            $table->boolean('is_authorized')->default(false);
            $table->timestamp('authorized_at')->nullable();

            $table->string('status')->default('created'); 

            $table->string('message')->nullable();
            $table->string('message_key')->nullable();

            $table->timestamp('last_ping')->nullable();

            $table->timestamp('last_activity_at')->nullable();
            
            $table->unsignedInteger('error_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_accounts');
    }
};
