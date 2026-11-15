<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\TaxManagementService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class TaxManagementController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $codes = Schema::hasTable('acct_tax_codes')
            ? DB::table('acct_tax_codes')->orderBy('code')->paginate(25)
            : collect();
        $periods = Schema::hasTable('acct_tax_periods')
            ? DB::table('acct_tax_periods')->orderByDesc('starts_on')->paginate(25)
            : collect();

        return view('accounting::phase4.tax.index', compact('codes', 'periods'));
    }

    public function storeCode(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'code' => 'required|string|max:30',
            'name' => 'required|string|max:160',
            'tax_type' => 'required|in:vat,withholding,other',
            'rate' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date',
            'inclusive' => 'nullable|boolean',
        ]);

        $bid = (int) (session('business.id') ?? 0);
        TaxManagementService::createTaxCode($bid, $data);

        return back()->with('status', 'Tax code saved.');
    }

    public function storePeriod(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
        ]);

        DB::table('acct_tax_periods')->insert([
            'business_id' => (int) (session('business.id') ?? 0),
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Tax period created.');
    }

    public function summary(int $periodId)
    {
        $period = DB::table('acct_tax_periods')->where('id', $periodId)->first();
        abort_unless($period, 404);
        $this->assertBusinessScope((int) $period->business_id);

        $summary = TaxManagementService::taxSummary($periodId);

        return view('accounting::phase4.tax.summary', compact('summary'));
    }
}