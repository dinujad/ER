<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('amount'); // cash, card
            }
            if (!Schema::hasColumn('expenses', 'register_id')) {
                $table->foreignId('register_id')->nullable()->after('created_by')->constrained()->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'payment_method')) $table->dropColumn('payment_method');
            if (Schema::hasColumn('expenses', 'register_id')) {
                $table->dropForeign(['register_id']);
                $table->dropColumn('register_id');
            }
        });
    }
};
