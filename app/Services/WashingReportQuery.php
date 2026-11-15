<?php

namespace App\Services;

use App\Models\ManufacturingJob;
use App\Models\WashingTransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WashingReportQuery
{
    /** @return array<string, mixed> */
    public function filtersFromRequest(Request $request): array
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => $request->get('status', 'all'),
            'record_type' => $request->get('record_type', 'all'),
            'job_id' => $request->get('job_id'),
            'washing_type' => $request->get('washing_type', 'all'),
            'washing_factory' => trim((string) $request->get('washing_factory', '')),
            'vehicle_number' => trim((string) $request->get('vehicle_number', '')),
            'sent_from' => trim((string) $request->get('sent_from', '')),
            'search' => trim((string) $request->get('search', '')),
        ];
    }

    /** @param array<string, mixed> $filters */
    public function baseQuery(array $filters): Builder
    {
        $query = WashingTransfer::query()
            ->with(['job.product', 'parent', 'children'])
            ->whereDate('created_at', '>=', $filters['date_from'])
            ->whereDate('created_at', '<=', $filters['date_to']);

        if (($filters['record_type'] ?? 'all') === 'gate_pass') {
            $query->parentGatePasses();
        } elseif (($filters['record_type'] ?? 'all') === 'receipt') {
            $query->whereNotNull('parent_id');
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['job_id'])) {
            $query->where('manufacturing_job_id', $filters['job_id']);
        }

        if (!empty($filters['washing_type']) && $filters['washing_type'] !== 'all') {
            $query->where('washing_type', $filters['washing_type']);
        }

        if (!empty($filters['washing_factory'])) {
            $query->where('washing_factory_name', 'like', '%' . $filters['washing_factory'] . '%');
        }

        if (!empty($filters['vehicle_number'])) {
            $query->where('vehicle_number', 'like', '%' . $filters['vehicle_number'] . '%');
        }

        if (!empty($filters['sent_from'])) {
            $query->where('sent_from', 'like', '%' . $filters['sent_from'] . '%');
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('batch_no', 'like', "%{$s}%")
                    ->orWhere('id', 'like', "%{$s}%")
                    ->orWhereHas('job', function ($jq) use ($s) {
                        $jq->where('job_number', 'like', "%{$s}%")
                            ->orWhere('style_ref', 'like', "%{$s}%");
                    });
            });
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /** @param array<string, mixed> $filters */
    public function summary(Collection $rows): array
    {
        $gatePasses = $rows->filter(fn ($r) => !$r->isReceiptBatch());
        $receipts = $rows->filter(fn ($r) => $r->isReceiptBatch());

        $totalSent = (int) $gatePasses->sum('quantity_sent');
        $totalReturnedOnParents = (int) $gatePasses->sum(fn ($g) => (int) ($g->quantity_returned ?? 0));
        $totalReceiptQty = (int) $receipts->sum('quantity_sent');

        return [
            'gate_pass_count' => $gatePasses->count(),
            'receipt_count' => $receipts->count(),
            'total_sent' => $totalSent,
            'total_returned' => max($totalReturnedOnParents, $totalReceiptQty),
            'pending_at_wash' => max(0, $totalSent - $totalReturnedOnParents),
        ];
    }

    public function filterOptions(): array
    {
        $jobs = ManufacturingJob::query()
            ->whereHas('washingTransfers')
            ->orderBy('job_number')
            ->get(['id', 'job_number', 'style_ref']);

        $factories = WashingTransfer::query()
            ->whereNotNull('washing_factory_name')
            ->where('washing_factory_name', '!=', '')
            ->distinct()
            ->orderBy('washing_factory_name')
            ->pluck('washing_factory_name');

        return [
            'jobs' => $jobs,
            'factories' => $factories,
            'statuses' => WashingTransfer::statusOptions(),
            'washing_types' => WashingTransfer::washingTypeOptions(),
        ];
    }
}
