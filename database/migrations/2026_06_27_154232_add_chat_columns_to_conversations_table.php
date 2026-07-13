<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('name');
            $table->boolean('is_archived')->default(false)->after('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('last_activity_at');
        });
    }
};
