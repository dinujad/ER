<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheque_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->date('cheque_date');
            $table->string('cheque_number', 100);
            $table->string('cheque_bank', 100)->nullable();
            $table->string('cheque_image_path')->nullable();
            $table->boolean('cheque_exception_requested')->default(false);
            $table->text('cheque_exception_reason')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'cheque_batch_id')) {
                $table->foreignId('cheque_batch_id')->nullable()->after('order_id')->constrained('cheque_batches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'cheque_batch_id')) {
                $table->dropConstrainedForeignId('cheque_batch_id');
            }
        });

        Schema::dropIfExists('cheque_batches');
    }
};
