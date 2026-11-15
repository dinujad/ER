<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Memos Table
        if (!Schema::hasTable('memos')) {
            Schema::create('memos', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('user_id'); // Created by
                $table->timestamps();
            });
        }

        // 2. Documents Table
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('path'); // File path
                $table->string('type')->nullable(); // pdf, doc, etc
                $table->unsignedBigInteger('uploaded_by');
                $table->timestamps();
            });
        }

        // 3. Reminders Table
        if (!Schema::hasTable('reminders')) {
            Schema::create('reminders', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->dateTime('date');
                $table->unsignedBigInteger('user_id');
                $table->boolean('is_completed')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memos');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('reminders');
    }
};
