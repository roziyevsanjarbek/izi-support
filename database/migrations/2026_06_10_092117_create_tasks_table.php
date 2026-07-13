<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('query_id')->nullable();

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('type')->default('general'); // general, personal, team
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->string('priority')->default('medium'); // low, medium, high, urgent

            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};