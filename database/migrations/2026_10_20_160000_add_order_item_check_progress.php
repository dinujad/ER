<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'ordered_quantity')) {
                $table->decimal('ordered_quantity', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('order_items', 'checked_at')) {
                $table->timestamp('checked_at')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'checked_by')) {
                $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'checked_by')) {
                $table->dropConstrainedForeignId('checked_by');
            }
            if (Schema::hasColumn('order_items', 'checked_at')) {
                $table->dropColumn('checked_at');
            }
            if (Schema::hasColumn('order_items', 'ordered_quantity')) {
                $table->dropColumn('ordered_quantity');
            }
        });
    }
};
