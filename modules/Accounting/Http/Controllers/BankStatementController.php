<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\BankStatementImport;
use App\Services\Accounting\BankStatementImportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Services\FlashService;

class BankStatementController extends Controller
{
    public function index()
    {
        $imports = BankStatementImport::query()->orderByDesc('id')->paginate(25);
        $accounts = ChartOfAccount::query()
            ->where('active', 1)
            ->where(function ($q) {
                $q->where('account_type', 'like', '%asset%')
                    ->orWhereIn('gl_code', ['1000', '1010']);
            })
            ->orderBy('gl_code')
            ->get();

        return view('accounting::phase2.bank_statements.index', compact('imports', 'accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'chart_of_account_id' => 'required|integer',
            'file' => 'required|file|mimes:csv,txt',
        ]);

        try {
            $contents = file_get_contents($request->file('file')->getRealPath());
            $rows = BankStatementImportService::parseCsv($contents ?: '');
            $import = BankStatementImportService::import(
                (int) $request->chart_of_account_id,
                $rows,
                $request->file('file')->getClientOriginalName(),
                0,
                Auth::id(),
                true
            );
            (new FlashService())->onSave();

            return redirect('accounting/bank-statements/'.$import->id);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function show($id)
    {
        $import = BankStatementImport::with('lines')->findOrFail($id);

        return view('accounting::phase2.bank_statements.show', compact('import'));
    }
}
