<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\VatReturnService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

/**
 * Phase 6.3 — VAT return, SSCL return and tax invoice register.
 */
class VatReturnController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $report = VatReturnService::build($from, $to);

        if ($request->get('export') === 'csv') {
            return $this->csv($report);
        }

        return view('accounting::phase6.vat.index', compact('report', 'from', 'to'));
    }

    public function detail(Request $request, string $section)
    {
        abort_unless(in_array($section, ['sales', 'returns', 'credit_notes', 'bills', 'bill_returns'], true), 404);
        [$from, $to] = $this->period($request);
        $rows = VatReturnService::detail($section, $from, $to);
        $isPurchase = in_array($section, ['bills', 'bill_returns'], true);

        return view('accounting::phase6.vat.detail', compact('rows', 'section', 'from', 'to', 'isPurchase'));
    }

    public function register(Request $request)
    {
        [$from, $to] = $this->period($request);
        $register = VatReturnService::taxInvoiceRegister($from, $to);

        return view('accounting::phase6.vat.register', compact('register', 'from', 'to'));
    }

    /** @return array{0: string, 1: string} */
    private function period(Request $request): array
    {
        $this->requireAccountingAction('accounting.vat.view', false);
        $data = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->endOfMonth()->toDateString();

        return [$from, $to];
    }

    private function csv(array $r)
    {
        return response()->streamDownload(function () use ($r) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['VAT & SSCL return', $r['from'] . ' to ' . $r['to']]);
            fputcsv($out, []);
            fputcsv($out, ['Section', 'Documents', 'Value (excl. tax)', 'SSCL', 'VAT']);
            foreach ($r['by_treatment'] as $t) {
                fputcsv($out, ['Sales — ' . $t['label'], $t['count'], $t['goods'], $t['sscl'], $t['vat']]);
            }
            fputcsv($out, ['Less: sale returns', $r['returns']['count'], -$r['returns']['goods'], -$r['returns']['sscl'], -$r['returns']['vat']]);
            fputcsv($out, ['Less: credit notes', $r['credit_notes']['count'], -$r['credit_notes']['goods'], -$r['credit_notes']['sscl'], -$r['credit_notes']['vat']]);
            fputcsv($out, ['Output VAT', '', '', '', $r['output_vat']]);
            fputcsv($out, ['Input VAT on bills', $r['bills']['count'], $r['bills']['net'], '', $r['bills']['vat']]);
            fputcsv($out, ['Less: input VAT on purchase returns', $r['bill_returns']['count'], -$r['bill_returns']['net'], '', -$r['bill_returns']['vat']]);
            fputcsv($out, ['Input VAT', '', '', '', $r['input_vat']]);
            fputcsv($out, ['NET VAT PAYABLE / (REFUNDABLE)', '', '', '', $r['net_vat']]);
            fputcsv($out, ['SSCL PAYABLE', '', '', $r['sscl_payable'], '']);
            fclose($out);
        }, 'vat-return-' . $r['from'] . '-to-' . $r['to'] . '.csv', ['Content-Type' => 'text/csv']);
    }
}
