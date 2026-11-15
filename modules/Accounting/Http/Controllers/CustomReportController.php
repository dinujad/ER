<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\CustomReportBuilderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use RuntimeException;

class CustomReportController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $reports = Schema::hasTable('acct_saved_reports')
            ? DB::table('acct_saved_reports')->orderByDesc('updated_at')->paginate(25)
            : collect();

        return view('accounting::phase4.custom_reports.index', [
            'reports' => $reports,
            'types' => CustomReportBuilderService::ALLOWED_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'report_type' => 'required|string|max:60',
            'config' => 'nullable|array',
            'is_favorite' => 'nullable|boolean',
        ]);

        $bid = (int) (session('business.id') ?? 0);
        try {
            CustomReportBuilderService::create(
                $bid,
                $data['name'],
                $data['report_type'],
                $data['config'] ?? [],
                (int) Auth::id(),
                (bool) ($data['is_favorite'] ?? false)
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['report_type' => $e->getMessage()]);
        }

        return redirect(url('accounting/phase4/custom-reports'))->with('status', 'Report saved.');
    }

    public function destroy(int $id)
    {
        $this->requireManage();
        CustomReportBuilderService::delete($id);

        return back()->with('status', 'Report deleted.');
    }

    public function preview(int $id)
    {
        $row = DB::table('acct_saved_reports')->where('id', $id)->first();
        abort_unless($row, 404);
        $report = CustomReportBuilderService::generate((array) $row);

        return view('accounting::phase4.custom_reports.preview', compact('row', 'report'));
    }
}