<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 completion — additive schema for bank match safety + document recurring.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_statement_lines')) {
            Schema::table('bank_statement_lines', function (Blueprint $table) {
                if (! Schema::hasColumn('bank_statement_lines', 'excluded_reason')) {
                    $table->string('excluded_reason', 255)->nullable()->after('match_status');
                }
                if (! Schema::hasColumn('bank_statement_lines', 'matched_at')) {
                    $table->timestamp('matched_at')->nullable()->after('matched_source_id');
                }
                if (! Schema::hasColumn('bank_statement_lines', 'matched_by')) {
                    $table->unsignedBigInteger('matched_by')->nullable()->after('matched_at');
                }
            });

            // Prevent one ERP source from being matched to multiple statement lines.
            try {
                Schema::table('bank_statement_lines', function (Blueprint $table) {
                    $table->unique(['matched_source_type', 'matched_source_id'], 'bank_stmt_matched_src_uq');
                });
            } catch (\Throwable $e) {
                // Index may already exist on retry.
            }
        }

        if (Schema::hasTable('accounting_recurring_templates')) {
            Schema::table('accounting_recurring_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('accounting_recurring_templates', 'document_type')) {
                    $table->string('document_type', 30)->default('journal')->after('name'); // journal|invoice|bill
                }
                if (! Schema::hasColumn('accounting_recurring_templates', 'max_occurrences')) {
                    $table->unsignedInteger('max_occurrences')->nullable()->after('ends_on');
                }
                if (! Schema::hasColumn('accounting_recurring_templates', 'occurrence_count')) {
                    $table->unsignedInteger('occurrence_count')->default(0)->after('max_occurrences');
                }
            });
        }

        if (Schema::hasTable('accounting_recurring_runs')) {
            Schema::table('accounting_recurring_runs', function (Blueprint $table) {
                if (! Schema::hasColumn('accounting_recurring_runs', 'generated_type')) {
                    $table->string('generated_type', 40)->nullable()->after('manual_journal_id');
                }
                if (! Schema::hasColumn('accounting_recurring_runs', 'generated_id')) {
                    $table->unsignedBigInteger('generated_id')->nullable()->after('generated_type');
                }
                if (! Schema::hasColumn('accounting_recurring_runs', 'execution_key')) {
                    $table->string('execution_key', 80)->nullable()->after('generated_id');
                }
            });

            try {
                Schema::table('accounting_recurring_runs', function (Blueprint $table) {
                    $table->unique('execution_key', 'acct_recurring_exec_key_uq');
                });
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_recurring_runs')) {
            Schema::table('accounting_recurring_runs', function (Blueprint $table) {
                try {
                    $table->dropUnique('acct_recurring_exec_key_uq');
                } catch (\Throwable $e) {
                }
                foreach (['generated_type', 'generated_id', 'execution_key'] as $col) {
                    if (Schema::hasColumn('accounting_recurring_runs', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('accounting_recurring_templates')) {
            Schema::table('accounting_recurring_templates', function (Blueprint $table) {
                foreach (['document_type', 'max_occurrences', 'occurrence_count'] as $col) {
                    if (Schema::hasColumn('accounting_recurring_templates', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('bank_statement_lines')) {
            Schema::table('bank_statement_lines', function (Blueprint $table) {
                try {
                    $table->dropUnique('bank_stmt_matched_src_uq');
                } catch (\Throwable $e) {
                }
                foreach (['excluded_reason', 'matched_at', 'matched_by'] as $col) {
                    if (Schema::hasColumn('bank_statement_lines', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
