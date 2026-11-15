<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('item'); // device/item name
            $table->text('issue')->nullable();
            $table->decimal('estimate', 12, 2)->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, done
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
