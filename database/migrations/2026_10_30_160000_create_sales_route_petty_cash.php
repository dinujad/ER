<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_route_petty_cash', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_route_run_id')->constrained('sales_route_runs')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_route_petty_cash');
    }
};
