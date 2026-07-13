<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('conversation_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('last_read_message_id')->nullable()->after('can_send_messages');

        $table->foreign('last_read_message_id')->references('id')->on('messages')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('conversation_permissions', function (Blueprint $table) {
        $table->dropForeign(['last_read_message_id']);
        $table->dropColumn('last_read_message_id');
    });
}
};
