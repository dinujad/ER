<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — AI assistant, OCR, categorization, anomalies, insights, automation (additive only).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createAiTables();
        $this->createOcrTables();
        $this->createCategorizationTables();
        $this->createAnomalyTables();
        $this->createInsightTables();
        $this->createAutomationTables();
        $this->createReviewTables();
    }

    public function down(): void
    {
        Schema::dropIfExists('acct_ai_review_decisions');
        Schema::dropIfExists('acct_ai_review_tasks');
        Schema::dropIfExists('acct_automation_executions');
        Schema::dropIfExists('acct_automation_rules');
        Schema::dropIfExists('acct_financial_insights');
        Schema::dropIfExists('acct_anomaly_alerts');
        Schema::dropIfExists('acct_anomaly_rules');
        Schema::dropIfExists('acct_categorization_suggestions');
        Schema::dropIfExists('acct_ocr_extractions');
        Schema::dropIfExists('acct_ocr_documents');
        Schema::dropIfExists('acct_ai_usage_logs');
        Schema::dropIfExists('acct_ai_messages');
        Schema::dropIfExists('acct_ai_conversations');
        Schema::dropIfExists('acct_ai_provider_settings');
    }

    private function createAiTables(): void
    {
        if (! Schema::hasTable('acct_ai_provider_settings')) {
            Schema::create('acct_ai_provider_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('provider', 40)->default('fake');
                $table->string('model', 120)->nullable();
                $table->string('endpoint', 255)->nullable();
                $table->unsignedInteger('timeout_seconds')->default(30);
                $table->unsignedInteger('max_tokens')->default(2048);
                $table->unsignedInteger('monthly_request_limit')->default(500);
                $table->boolean('enabled')->default(false);
                $table->boolean('external_disclosure_accepted')->default(false);
                $table->unsignedInteger('retention_days')->default(90);
                $table->json('allowed_features')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id']);
            });
        }

        if (! Schema::hasTable('acct_ai_conversations')) {
            Schema::create('acct_ai_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('title', 200)->nullable();
                $table->string('locale', 10)->default('en');
                $table->date('context_from')->nullable();
                $table->date('context_to')->nullable();
                $table->json('context_filters')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_ai_messages')) {
            Schema::create('acct_ai_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->string('role', 20);
                $table->text('content');
                $table->json('tool_calls')->nullable();
                $table->json('tool_results')->nullable();
                $table->json('source_refs')->nullable();
                $table->boolean('is_estimate')->default(false);
                $table->unsignedInteger('prompt_tokens')->nullable();
                $table->unsignedInteger('completion_tokens')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_ai_usage_logs')) {
            Schema::create('acct_ai_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('feature', 60);
                $table->string('provider', 40);
                $table->string('model', 120)->nullable();
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('completion_tokens')->default(0);
                $table->decimal('estimated_cost', 12, 6)->nullable();
                $table->string('status', 30)->default('ok');
                $table->string('error_code', 60)->nullable();
                $table->timestamps();
                $table->index(['business_id', 'created_at']);
            });
        }
    }

    private function createOcrTables(): void
    {
        if (! Schema::hasTable('acct_ocr_documents')) {
            Schema::create('acct_ocr_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('original_filename', 255);
                $table->string('mime_type', 120);
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('storage_disk', 40)->default('local');
                $table->string('storage_path', 500);
                $table->string('document_type', 40)->default('unknown');
                $table->string('status', 40)->default('uploaded');
                $table->string('fingerprint', 64)->nullable()->index();
                $table->unsignedTinyInteger('retry_count')->default(0);
                $table->text('last_error')->nullable();
                $table->unsignedBigInteger('draft_source_id')->nullable();
                $table->string('draft_source_type', 60)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_ocr_extractions')) {
            Schema::create('acct_ocr_extractions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ocr_document_id')->index();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->json('raw_text')->nullable();
                $table->json('extracted_fields')->nullable();
                $table->json('line_items')->nullable();
                $table->json('validation_errors')->nullable();
                $table->decimal('confidence', 5, 2)->nullable();
                $table->string('currency_flag', 10)->default('LKR');
                $table->boolean('currency_supported')->default(true);
                $table->boolean('amounts_valid')->default(false);
                $table->boolean('duplicate_suspected')->default(false);
                $table->string('duplicate_reason', 255)->nullable();
                $table->string('extractor', 40)->default('fake');
                $table->timestamps();
            });
        }
    }

    private function createCategorizationTables(): void
    {
        if (! Schema::hasTable('acct_categorization_suggestions')) {
            Schema::create('acct_categorization_suggestions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('source_type', 60);
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('suggestion_type', 60);
                $table->unsignedBigInteger('suggested_account_id')->nullable();
                $table->string('suggested_tax_code', 40)->nullable();
                $table->unsignedBigInteger('suggested_cost_center_id')->nullable();
                $table->unsignedBigInteger('suggested_project_id')->nullable();
                $table->json('payload')->nullable();
                $table->decimal('confidence', 5, 2)->default(0);
                $table->string('reason', 500)->nullable();
                $table->string('status', 30)->default('pending');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_note')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'status']);
            });
        }
    }

    private function createAnomalyTables(): void
    {
        if (! Schema::hasTable('acct_anomaly_rules')) {
            Schema::create('acct_anomaly_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('name', 160);
                $table->string('rule_type', 60);
                $table->decimal('threshold', 20, 4)->nullable();
                $table->string('severity', 20)->default('medium');
                $table->boolean('enabled')->default(true);
                $table->date('effective_from')->nullable();
                $table->json('config')->nullable();
                $table->string('notify')->default('dashboard');
                $table->unsignedInteger('rule_version')->default(1);
                $table->timestamps();
                $table->unique(['business_id', 'rule_type', 'name']);
            });
        }

        if (! Schema::hasTable('acct_anomaly_alerts')) {
            Schema::create('acct_anomaly_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->unsignedBigInteger('rule_id')->nullable()->index();
                $table->string('alert_type', 60);
                $table->string('severity', 20)->default('medium');
                $table->string('reference_type', 60)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->decimal('amount', 20, 4)->nullable();
                $table->string('currency', 3)->default('LKR');
                $table->text('explanation');
                $table->json('evidence')->nullable();
                $table->unsignedInteger('rule_version')->default(1);
                $table->string('status', 30)->default('new');
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->string('idempotency_key', 120)->unique();
                $table->timestamp('detected_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'status']);
            });
        }
    }

    private function createInsightTables(): void
    {
        if (! Schema::hasTable('acct_financial_insights')) {
            Schema::create('acct_financial_insights', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('category', 60);
                $table->string('title', 200);
                $table->date('period_from')->nullable();
                $table->date('period_to')->nullable();
                $table->string('source_metric', 80);
                $table->string('comparison_basis', 80)->nullable();
                $table->decimal('actual_amount', 20, 4)->nullable();
                $table->decimal('change_amount', 20, 4)->nullable();
                $table->decimal('change_pct', 10, 4)->nullable();
                $table->text('observed_fact');
                $table->text('possible_explanation')->nullable();
                $table->text('suggested_action')->nullable();
                $table->string('drilldown_url', 500)->nullable();
                $table->string('certainty', 20)->default('observed');
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'category']);
            });
        }
    }

    private function createAutomationTables(): void
    {
        if (! Schema::hasTable('acct_automation_rules')) {
            Schema::create('acct_automation_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('name', 160);
                $table->string('trigger', 60);
                $table->json('conditions')->nullable();
                $table->string('action', 60);
                $table->json('action_config')->nullable();
                $table->string('schedule_cron', 60)->nullable();
                $table->boolean('requires_approval')->default(true);
                $table->string('notify_recipient', 120)->nullable();
                $table->boolean('active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acct_automation_executions')) {
            Schema::create('acct_automation_executions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rule_id')->index();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('occurrence_key', 160)->unique();
                $table->string('status', 30)->default('pending');
                $table->json('payload')->nullable();
                $table->json('result')->nullable();
                $table->text('error')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createReviewTables(): void
    {
        if (! Schema::hasTable('acct_ai_review_tasks')) {
            Schema::create('acct_ai_review_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->default(0)->index();
                $table->string('task_type', 60);
                $table->string('subject_type', 80);
                $table->unsignedBigInteger('subject_id');
                $table->string('title', 200);
                $table->json('suggestion_payload')->nullable();
                $table->decimal('confidence', 5, 2)->nullable();
                $table->string('status', 30)->default('pending');
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'status']);
                $table->unique(['business_id', 'subject_type', 'subject_id', 'task_type'], 'acct_ai_review_unique');
            });
        }

        if (! Schema::hasTable('acct_ai_review_decisions')) {
            Schema::create('acct_ai_review_decisions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('review_task_id')->index();
                $table->unsignedBigInteger('business_id')->default(0);
                $table->unsignedBigInteger('user_id');
                $table->string('decision', 30);
                $table->json('edited_payload')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }
};
