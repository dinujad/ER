# Database Migrations

Additive migration: `database/migrations/2026_11_15_230000_create_accounting_phase5_tables.php`

Tables: acct_ai_provider_settings, acct_ai_conversations, acct_ai_messages, acct_ai_usage_logs, acct_ocr_documents, acct_ocr_extractions, acct_categorization_suggestions, acct_anomaly_rules, acct_anomaly_alerts, acct_financial_insights, acct_automation_rules, acct_automation_executions, acct_ai_review_tasks, acct_ai_review_decisions.

No destructive drops of Phase 1–4 data in `up()`.
