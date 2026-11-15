<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_routes', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_routes', 'from_location')) {
                $table->string('from_location')->nullable()->after('code');
            }
            if (! Schema::hasColumn('sales_routes', 'to_location')) {
                $table->string('to_location')->nullable()->after('from_location');
            }
        });

        if (! Schema::hasTable('sales_route_runs')) {
            Schema::create('sales_route_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sales_route_id')->constrained('sales_routes')->restrictOnDelete();
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['status', 'started_at']);
                $table->index('sales_route_id');
            });
        }

        Schema::table('sales_person_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_person_expenses', 'sales_route_run_id')) {
                $table->foreignId('sales_route_run_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('sales_route_runs')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_person_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('sales_person_expenses', 'sales_route_run_id')) {
                $table->dropConstrainedForeignId('sales_route_run_id');
            }
        });

        Schema::dropIfExists('sales_route_runs');

        Schema::table('sales_routes', function (Blueprint $table) {
            if (Schema::hasColumn('sales_routes', 'to_location')) {
                $table->dropColumn('to_location');
            }
            if (Schema::hasColumn('sales_routes', 'from_location')) {
                $table->dropColumn('from_location');
            }
        });
    }
};
