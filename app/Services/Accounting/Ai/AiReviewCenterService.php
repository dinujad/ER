<?php

namespace App\Services\Accounting\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Unified review of AI/OCR/automation suggestions.
 * Accept never bypasses source-document validation / journal posting rules.
 */
class AiReviewCenterService
{
    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function pending(int $businessId, ?string $type = null)
    {
        if (! Schema::hasTable('acct_ai_review_tasks')) {
            return collect();
        }
        $q = DB::table('acct_ai_review_tasks')
            ->where('business_id', $businessId)
            ->where('status', 'pending')
            ->orderByDesc('id');
        if ($type) {
            $q->where('task_type', $type);
        }

        return $q->limit(100)->get();
    }

    /**
     * @param  array<string, mixed>|null  $edited
     * @return array<string, mixed>
     */
    public function decide(
        int $businessId,
        int $taskId,
        string $decision,
        int $userId,
        ?array $edited = null,
        ?string $note = null
    ): array {
        if (! in_array($decision, ['accepted', 'rejected', 'clarification'], true)) {
            throw new RuntimeException('Invalid review decision.');
        }

        $task = DB::table('acct_ai_review_tasks')
            ->where('id', $taskId)
            ->where('business_id', $businessId)
            ->first();
        if (! $task) {
            throw new RuntimeException('Review task not found.');
        }

        $status = match ($decision) {
            'accepted' => 'accepted',
            'rejected' => 'rejected',
            'clarification' => 'clarification',
        };

        DB::table('acct_ai_review_tasks')->where('id', $taskId)->update([
            'status' => $status,
            'suggestion_payload' => $edited ? json_encode($edited) : $task->suggestion_payload,
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('acct_ai_review_decisions')) {
            DB::table('acct_ai_review_decisions')->insert([
                'review_task_id' => $taskId,
                'business_id' => $businessId,
                'user_id' => $userId,
                'decision' => $decision,
                'edited_payload' => $edited ? json_encode($edited) : null,
                'note' => $note,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Route side-effects through domain services — never post journals here
        if ($decision === 'accepted') {
            if ($task->task_type === 'categorization' && $task->subject_type === 'categorization_suggestion') {
                app(SmartCategorizationService::class)->decide(
                    $businessId,
                    (int) $task->subject_id,
                    'accepted',
                    $userId,
                    $note,
                    $edited['account_id'] ?? null
                );
            }
            if ($task->task_type === 'ocr_extraction' && $task->subject_type === 'ocr_document') {
                // Accepting OCR review only marks readiness — draft creation is separate explicit step
                DB::table('acct_ocr_documents')
                    ->where('id', $task->subject_id)
                    ->where('business_id', $businessId)
                    ->update(['status' => 'review_accepted', 'updated_at' => now()]);
            }
        }

        if ($decision === 'rejected' && $task->task_type === 'categorization') {
            app(SmartCategorizationService::class)->decide($businessId, (int) $task->subject_id, 'rejected', $userId, $note);
        }

        return [
            'task_id' => $taskId,
            'decision' => $decision,
            'status' => $status,
            'gl_posted' => false,
            'note' => 'Acceptance does not bypass journal approval or period locks.',
        ];
    }
}
