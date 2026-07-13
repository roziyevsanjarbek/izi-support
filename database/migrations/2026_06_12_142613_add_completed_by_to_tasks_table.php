<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('completed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->after('query_id')->constrained('conversations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
        });
    }
};