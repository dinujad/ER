<?php

namespace App\Http\Controllers;

use App\Services\WashingReportQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class WashingReportController extends Controller
{
    public function __construct(
        protected WashingReportQuery $reportQuery
    ) {}

    public function index(Request $request)
    {
        $this->ensureCanView();

        $filters = $this->reportQuery->filtersFromRequest($request);
        $options = $this->reportQuery->filterOptions();

        if ($request->get('export') === 'pdf') {
            return $this->exportPdf($filters);
        }

        $rows = $this->reportQuery->baseQuery($filters)->limit(2000)->get();
        $summary = $this->reportQuery->summary($rows);

        return view('erp.manufacturing.washing_report', compact('filters', 'options', 'rows', 'summary'));
    }

    public function export(Request $request)
    {
        $this->ensureCanView();

        $filters = $this->reportQuery->filtersFromRequest($request);

        return $this->exportPdf($filters);
    }

    /** @param array<string, mixed> $filters */
    protected function exportPdf(array $filters)
    {
        $rows = $this->reportQuery->baseQuery($filters)->limit(5000)->get();
        $rows = $this->flattenWithReceipts($rows);
        $summary = $this->reportQuery->summary(
            $this->reportQuery->baseQuery($filters)->limit(5000)->get()
        );
        $options = $this->reportQuery->filterOptions();

        $pdf = Pdf::loadView('erp.manufacturing.washing_report_pdf', compact('filters', 'rows', 'summary', 'options'))
            ->setPaper('a4', 'landscape');

        $filename = 'washing-report-' . $filters['date_from'] . '-to-' . $filters['date_to'] . '.pdf';

        return $pdf->download($filename);
    }

    protected function ensureCanView(): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }
        if ($user->isSystemAdmin() || $user->can_access('manufacturing.view') || $user->can_access('manufacturing.washing')) {
            return;
        }
        abort(403, 'You do not have permission to view washing reports.');
    }

    /** Include receipt batches under each gate pass for PDF detail. */
    protected function flattenWithReceipts($rows)
    {
        $flat = collect();
        $seen = $rows->pluck('id')->flip();

        foreach ($rows as $row) {
            $flat->push($row);
            if (!$row->isReceiptBatch() && $row->relationLoaded('children') && $row->children->isNotEmpty()) {
                foreach ($row->children->sortBy('created_at') as $child) {
                    if (!$seen->has($child->id)) {
                        $flat->push($child);
                        $seen->put($child->id, true);
                    }
                }
            }
        }

        return $flat;
    }
}
