<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Accounting\SourceDocumentResolver;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 6.1 — ERP-wide audit trail: who did what, when, on which document (Xero History / QBO Audit log).
 */
class AuditTrailController extends Controller
{
    use RequiresAccountingPermission;

    /** Filter value → morph class. */
    public const DOCUMENT_TYPES = [
        'invoice' => \App\Models\Order::class,
        'invoice_line' => \App\Models\OrderItem::class,
        'payment' => \App\Models\Payment::class,
        'sale_return' => \App\Models\SaleReturn::class,
        'credit_note' => \App\Models\CreditNote::class,
        'bill' => \App\Models\Purchase::class,
        'bill_line' => \App\Models\PurchaseItem::class,
        'supplier_payment' => \App\Models\PurchasePayment::class,
        'purchase_return' => \App\Models\PurchaseReturn::class,
        'expense' => \App\Models\Expense::class,
        'customer' => \App\Models\Customer::class,
        'supplier' => \App\Models\Supplier::class,
        'product' => \App\Models\Product::class,
    ];

    public function index(Request $request)
    {
        $this->requireAccountingAction('accounting.audit.view', false);
        $filters = $this->filters($request);

        if ($request->get('export') === 'csv') {
            return $this->exportCsv($filters);
        }

        $logs = Schema::hasTable('audit_logs')
            ? $this->query($filters)->paginate(50)->withQueryString()
            : collect();
        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $events = AuditLog::EVENT_LABELS;
        $types = self::DOCUMENT_TYPES;

        return view('accounting::phase6.audit.index', compact('logs', 'users', 'events', 'types', 'filters'));
    }

    /** History of one document, e.g. /accounting/phase6/audit-trail/document/invoice/12 */
    public function document(string $type, int $id)
    {
        $this->requireAccountingAction('accounting.audit.view', false);
        abort_unless(isset(self::DOCUMENT_TYPES[$type]), 404);
        $class = self::DOCUMENT_TYPES[$type];
        $logs = Schema::hasTable('audit_logs')
            ? AuditLog::query()->where('auditable_type', $class)->where('auditable_id', $id)->orderByDesc('id')->get()
            : collect();
        $source = SourceDocumentResolver::resolve($class, $id);

        return view('accounting::phase6.audit.document', compact('logs', 'source', 'type', 'id'));
    }

    /**
     * @return array{user_id: ?int, type: ?string, event: ?string, from: ?string, to: ?string, q: ?string}
     */
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer',
            'type' => 'nullable|string|max:40',
            'event' => 'nullable|string|max:40',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'q' => 'nullable|string|max:120',
        ]);

        return [
            'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
            'type' => isset($data['type'], self::DOCUMENT_TYPES[$data['type']]) ? $data['type'] : null,
            'event' => $data['event'] ?? null,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'q' => isset($data['q']) ? trim($data['q']) : null,
        ];
    }

    private function query(array $f)
    {
        return AuditLog::query()
            ->when($f['user_id'], fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['type'], fn ($q, $v) => $q->where('auditable_type', self::DOCUMENT_TYPES[$v]))
            ->when($f['event'], fn ($q, $v) => $q->where('event', $v))
            ->when($f['from'], fn ($q, $v) => $q->where('created_at', '>=', $v . ' 00:00:00'))
            ->when($f['to'], fn ($q, $v) => $q->where('created_at', '<=', $v . ' 23:59:59'))
            ->when($f['q'], fn ($q, $v) => $q->where(function ($w) use ($v) {
                $w->where('document_label', 'like', "%{$v}%")
                    ->orWhere('user_name', 'like', "%{$v}%")
                    ->orWhere('note', 'like', "%{$v}%");
            }))
            ->orderByDesc('id');
    }

    private function exportCsv(array $filters): StreamedResponse
    {
        $query = $this->query($filters);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date & time', 'User', 'Action', 'Document type', 'Document', 'Field', 'Old value', 'New value', 'Note', 'IP address']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $log) {
                    $changes = $log->changes() ?: [['field' => '', 'old' => '', 'new' => '']];
                    foreach ($changes as $c) {
                        fputcsv($out, [
                            $log->created_at?->format('Y-m-d H:i:s'),
                            $log->user_name,
                            $log->eventLabel(),
                            $log->documentTypeLabel(),
                            $log->document_label,
                            $c['field'],
                            $c['old'],
                            $c['new'],
                            $log->note,
                            $log->ip_address,
                        ]);
                    }
                }
            });
            fclose($out);
        }, 'audit-trail-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
