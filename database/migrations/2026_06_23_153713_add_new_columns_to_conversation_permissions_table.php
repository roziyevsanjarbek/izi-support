<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('conversation_permissions', function (Blueprint $table) {
            $table->unsignedInteger('unread_count')->default(0)->after('notifications');
            $table->timestamp('last_read_at')->nullable()->after('unread_count');
        });
    }


    public function down(): void
    {
        Schema::table('conversation_permissions', function (Blueprint $table) {
            $table->dropColumn('unread_count');
            $table->dropColumn('last_read_at');
        });
    }
};
