<?php

namespace App\Services\Accounting;

use App\Models\Accounting\RecurringAccountingRun;
use App\Models\Accounting\RecurringAccountingTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecurringAccountingService
{
    public static function createTemplate(array $data, ?int $userId = null): RecurringAccountingTemplate
    {
        $payload = $data['payload'] ?? [];
        if (empty($payload['lines']) || ! is_array($payload['lines'])) {
            throw new RuntimeException('Recurring template requires journal lines in payload.');
        }
        ManualJournalService::assertLinesValid($payload['lines']);

        $starts = Carbon::parse($data['starts_on'])->toDateString();

        return RecurringAccountingTemplate::create([
            'business_id' => (int) ($data['business_id'] ?? 0),
            'name' => $data['name'],
            'frequency' => $data['frequency'],
            'starts_on' => $starts,
            'ends_on' => $data['ends_on'] ?? null,
            'next_run_on' => $data['next_run_on'] ?? $starts,
            'status' => $data['status'] ?? 'active',
            'execution_mode' => $data['execution_mode'] ?? 'draft',
            'payload' => $payload,
            'created_by' => $userId,
        ]);
    }

    /**
     * Idempotent run for a single occurrence date.
     */
    public static function executeOccurrence(RecurringAccountingTemplate $template, string $occurrenceDate, ?int $userId = null): RecurringAccountingRun
    {
        if ($template->status !== 'active') {
            throw new RuntimeException('Template is not active.');
        }

        if ($template->ends_on && $occurrenceDate > $template->ends_on->format('Y-m-d')) {
            throw new RuntimeException('Template has ended.');
        }

        return DB::transaction(function () use ($template, $occurrenceDate, $userId) {
            $existing = RecurringAccountingRun::query()
                ->where('template_id', $template->id)
                ->whereDate('occurrence_date', $occurrenceDate)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            try {
                $lines = $template->payload['lines'] ?? [];
                $journal = ManualJournalService::createDraft([
                    'business_id' => $template->business_id,
                    'journal_date' => $occurrenceDate,
                    'description' => ($template->payload['description'] ?? $template->name).' (recurring)',
                    'reference' => $template->payload['reference'] ?? ('REC-'.$template->id),
                    'location_id' => $template->payload['location_id'] ?? null,
                ], $lines, $userId);

                if ($template->execution_mode === 'auto_post') {
                    ManualJournalService::post($journal, false, $userId);
                }

                $run = RecurringAccountingRun::create([
                    'template_id' => $template->id,
                    'occurrence_date' => $occurrenceDate,
                    'status' => 'created',
                    'manual_journal_id' => $journal->id,
                    'message' => 'OK',
                ]);

                $template->next_run_on = self::nextDate($occurrenceDate, $template->frequency);
                if ($template->ends_on && $template->next_run_on && $template->next_run_on->format('Y-m-d') > $template->ends_on->format('Y-m-d')) {
                    $template->status = 'ended';
                    $template->next_run_on = null;
                }
                $template->save();

                return $run;
            } catch (\Throwable $e) {
                return RecurringAccountingRun::create([
                    'template_id' => $template->id,
                    'occurrence_date' => $occurrenceDate,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    public static function processDue(?string $asOf = null): int
    {
        $asOf = $asOf ?: date('Y-m-d');
        $count = 0;

        $templates = RecurringAccountingTemplate::query()
            ->where('status', 'active')
            ->whereNotNull('next_run_on')
            ->whereDate('next_run_on', '<=', $asOf)
            ->get();

        foreach ($templates as $template) {
            $occ = $template->next_run_on->format('Y-m-d');
            self::executeOccurrence($template, $occ);
            $count++;
        }

        return $count;
    }

    public static function nextDate(string $from, string $frequency): Carbon
    {
        $d = Carbon::parse($from);

        return match ($frequency) {
            'daily' => $d->addDay(),
            'weekly' => $d->addWeek(),
            'monthly' => $d->addMonth(),
            'quarterly' => $d->addMonths(3),
            'yearly' => $d->addYear(),
            default => $d->addMonth(),
        };
    }
}
