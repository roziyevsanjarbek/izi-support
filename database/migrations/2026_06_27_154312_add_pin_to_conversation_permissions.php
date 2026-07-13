<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('conversation_permissions', function (Blueprint $table) {

            $table->boolean('is_pinned')->default(false)->after('notifications');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_permissions', function (Blueprint $table) {
            $table->dropColumn('is_pinned');
        });
    }
};
