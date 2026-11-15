<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\YearEndClosingService;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class YearEndClosingController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $years = Schema::hasTable('acct_financial_years')
            ? DB::table('acct_financial_years')->orderByDesc('starts_on')->paginate(20)
            : collect();

        $retainedId = AccountingService::accountId(AccountingService::GL_OWNER_EQUITY);

        return view('accounting::phase4.year_end.index', compact('years', 'retainedId'));
    }

    public function storeYear(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
        ]);

        DB::table('acct_financial_years')->insert([
            'business_id' => (int) (session('business.id') ?? 0),
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Financial year created.');
    }

    public function show(int $id)
    {
        $fy = DB::table('acct_financial_years')->where('id', $id)->first();
        abort_unless($fy, 404);
        $this->assertBusinessScope((int) $fy->business_id);

        $checklist = YearEndClosingService::buildChecklist($id);
        $retainedId = AccountingService::accountId(AccountingService::GL_OWNER_EQUITY);
        $preview = YearEndClosingService::previewClosing($id, (int) $retainedId);

        return view('accounting::phase4.year_end.show', compact('checklist', 'preview', 'id', 'retainedId'));
    }

    public function execute(Request $request, int $id)
    {
        $this->requireManage();
        $fy = DB::table('acct_financial_years')->where('id', $id)->first();
        abort_unless($fy, 404);
        $this->assertBusinessScope((int) $fy->business_id);

        $retained = (int) $request->input('retained_earnings_account_id', AccountingService::accountId(AccountingService::GL_OWNER_EQUITY));
        $txn = YearEndClosingService::executeClosing($id, $retained, Auth::id());

        return back()->with('status', "Year-end posted: {$txn}");
    }
}