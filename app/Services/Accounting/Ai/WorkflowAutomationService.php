<?php

namespace App\Services\Accounting\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Allowlisted workflow automation — no arbitrary code, no silent GL posting.
 */
class WorkflowAutomationService
{
    /** @var list<string> */
    public const TRIGGERS = [
        'invoice_overdue',
        'supplier_bill_due',
        'payment_received',
        'bank_import_completed',
        'recon_difference',
        'budget_threshold',
        'recurring_draft_created',
        'ocr_extraction_completed',
        'anomaly_detected',
        'period_ending',
    ];

    /** @var list<string> */
    public const ACTIONS = [
        'create_notification',
        'create_review_task',
        'request_approval',
        'send_email',
        'generate_report',
        'prepare_draft',
        'flag_for_review',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRule(int $businessId, array $data, ?int $userId = null): int
    {
        $trigger = $data['trigger'] ?? '';
        $action = $data['action'] ?? '';
        if (! in_array($trigger, self::TRIGGERS, true)) {
            throw new RuntimeException('Trigger not allowlisted.');
        }
        if (! in_array($action, self::ACTIONS, true)) {
            throw new RuntimeException('Action not allowlisted.');
        }

        return (int) DB::table('acct_automation_rules')->insertGetId([
            'business_id' => $businessId,
            'name' => $data['name'] ?? ($trigger.' → '.$action),
            'trigger' => $trigger,
            'conditions' => json_encode($data['conditions'] ?? []),
            'action' => $action,
            'action_config' => json_encode($data['action_config'] ?? []),
            'schedule_cron' => $data['schedule_cron'] ?? null,
            'requires_approval' => (bool) ($data['requires_approval'] ?? true),
            'notify_recipient' => $data['notify_recipient'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function execute(int $ruleId, string $occurrenceKey, array $payload = []): array
    {
        if (! Schema::hasTable('acct_automation_executions')) {
            throw new RuntimeException('Automation tables missing.');
        }

        $existing = DB::table('acct_automation_executions')->where('occurrence_key', $occurrenceKey)->first();
        if ($existing) {
            return [
                'id' => $existing->id,
                'status' => $existing->status,
                'deduplicated' => true,
                'result' => json_decode($existing->result ?? 'null', true),
            ];
        }

        $rule = DB::table('acct_automation_rules')->where('id', $ruleId)->first();
        if (! $rule || ! $rule->active) {
            throw new RuntimeException('Automation rule inactive or missing.');
        }

        $execId = (int) DB::table('acct_automation_executions')->insertGetId([
            'rule_id' => $ruleId,
            'business_id' => $rule->business_id,
            'occurrence_key' => $occurrenceKey,
            'status' => 'running',
            'payload' => json_encode($payload),
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $result = $this->performAction($rule, $payload);
            DB::table('acct_automation_executions')->where('id', $execId)->update([
                'status' => 'completed',
                'result' => json_encode($result),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

            return ['id' => $execId, 'status' => 'completed', 'deduplicated' => false, 'result' => $result];
        } catch (\Throwable $e) {
            DB::table('acct_automation_executions')->where('id', $execId)->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
            throw $e;
        }
    }

    /**
     * Fire matching active rules for a trigger (idempotent per occurrence).
     *
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function dispatch(int $businessId, string $trigger, string $occurrenceSuffix, array $payload = []): array
    {
        if (! in_array($trigger, self::TRIGGERS, true)) {
            throw new RuntimeException('Invalid trigger.');
        }
        $rules = DB::table('acct_automation_rules')
            ->where('business_id', $businessId)
            ->where('trigger', $trigger)
            ->where('active', true)
            ->get();

        $out = [];
        foreach ($rules as $rule) {
            $key = 'auto:'.$businessId.':'.$rule->id.':'.$trigger.':'.$occurrenceSuffix;
            $out[] = $this->execute((int) $rule->id, $key, $payload);
        }

        return $out;
    }

    public function setActive(int $businessId, int $ruleId, bool $active): void
    {
        DB::table('acct_automation_rules')
            ->where('id', $ruleId)
            ->where('business_id', $businessId)
            ->update(['active' => $active, 'updated_at' => now()]);
    }

    /**
     * @param  object  $rule
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function performAction(object $rule, array $payload): array
    {
        $config = json_decode($rule->action_config ?? '{}', true) ?: [];

        return match ($rule->action) {
            'create_notification' => [
                'type' => 'notification',
                'message' => $config['message'] ?? ('Automation: '.$rule->name),
                'recipient' => $rule->notify_recipient,
                'gl_posted' => false,
            ],
            'create_review_task' => $this->createReviewTask($rule, $payload, $config),
            'request_approval' => [
                'type' => 'approval_request',
                'requires_human' => true,
                'gl_posted' => false,
                'note' => 'Financial posting still requires existing approval workflows.',
            ],
            'send_email' => [
                'type' => 'email_queued',
                'to' => $rule->notify_recipient,
                'subject' => $config['subject'] ?? $rule->name,
                'external_send' => false,
                'note' => 'External email requires configured delivery channel and authorization.',
                'gl_posted' => false,
            ],
            'generate_report' => [
                'type' => 'report',
                'report' => $config['report_type'] ?? 'profit_and_loss',
                'url' => url('accounting/phase4/statements/profit-and-loss'),
                'gl_posted' => false,
            ],
            'prepare_draft' => [
                'type' => 'draft',
                'draft' => 'expense_or_bill_candidate',
                'gl_posted' => false,
                'note' => 'Draft only — uses existing purchase/expense modules after human review.',
            ],
            'flag_for_review' => $this->createReviewTask($rule, $payload, array_merge($config, ['flag' => true])),
            default => throw new RuntimeException('Action not allowlisted.'),
        };
    }

    /**
     * @param  object  $rule
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function createReviewTask(object $rule, array $payload, array $config): array
    {
        if (! Schema::hasTable('acct_ai_review_tasks')) {
            return ['type' => 'review_task', 'created' => false, 'gl_posted' => false];
        }
        $subjectType = $payload['subject_type'] ?? 'automation';
        $subjectId = (int) ($payload['subject_id'] ?? $rule->id);
        $id = (int) DB::table('acct_ai_review_tasks')->insertGetId([
            'business_id' => $rule->business_id,
            'task_type' => 'automation_exception',
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'title' => $config['title'] ?? ('Automation: '.$rule->name),
            'suggestion_payload' => json_encode($payload),
            'confidence' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['type' => 'review_task', 'task_id' => $id, 'gl_posted' => false];
    }
}
