<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->morphs('attachable');

            $table->string('collection')->default('default');
            $table->string('disk')->default('public');

            $table->string('path');

            $table->string('original_name');

            $table->string('file_name');

            $table->string('extension', 20);

            $table->string('mime_type')->nullable();

            $table->unsignedBigInteger('size')->default(0);

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->unsignedInteger('order')->default(0);

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['collection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};