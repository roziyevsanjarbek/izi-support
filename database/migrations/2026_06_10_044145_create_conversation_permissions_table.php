<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversation_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('role')->default('member');

            $table->boolean('notifications')->default(true);

            $table->boolean('can_add_user')->default(false);
            $table->boolean('can_remove_user')->default(false);
            $table->boolean('can_delete_message')->default(false);
            $table->boolean('can_change_name')->default(false);
            $table->boolean('can_pin_message')->default(false);
            $table->boolean('can_send_messages')->default(true);

            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_permissions');
    }
};
