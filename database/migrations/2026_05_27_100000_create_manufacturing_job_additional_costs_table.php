<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'mfg_job_add_costs_job_cat_idx';

    public function up(): void
    {
        if (!Schema::hasTable('manufacturing_job_additional_costs')) {
            Schema::create('manufacturing_job_additional_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('manufacturing_job_id')->constrained('manufacturing_jobs')->cascadeOnDelete();
                $table->string('category', 40)->default('other');
                $table->string('description');
                $table->decimal('amount', 12, 2);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['manufacturing_job_id', 'category'], self::INDEX);
            });

            return;
        }

        // Prior deploy may have created the table then failed on auto-named index (MySQL 64-char limit).
        $this->ensureIndex();
    }

    private function ensureIndex(): void
    {
        $rows = DB::select(
            'SHOW INDEX FROM manufacturing_job_additional_costs WHERE Key_name = ?',
            [self::INDEX]
        );

        if ($rows !== []) {
            return;
        }

        DB::statement(
            'ALTER TABLE manufacturing_job_additional_costs ADD INDEX ' . self::INDEX . ' (manufacturing_job_id, category)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_job_additional_costs');
    }
};
