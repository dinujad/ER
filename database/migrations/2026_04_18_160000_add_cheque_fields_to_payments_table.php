<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->date('cheque_date')->nullable()->after('payment_date');
            $table->string('cheque_image_path')->nullable()->after('cheque_date');
            $table->boolean('cheque_exception_requested')->default(false)->after('cheque_image_path');
            $table->text('cheque_exception_reason')->nullable()->after('cheque_exception_requested');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'cheque_date',
                'cheque_image_path',
                'cheque_exception_requested',
                'cheque_exception_reason',
            ]);
        });
    }
};
