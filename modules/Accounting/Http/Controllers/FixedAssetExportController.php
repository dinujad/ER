<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\FaAsset;
use App\Services\Accounting\FixedAssetReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\AccountingExport;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class FixedAssetExportController extends Controller
{
    use RequiresAccountingPermission;

    public function register(Request $request, string $format = 'pdf')
    {
        $data = FixedAssetReportService::register(0, $request->get('status'));

        return $this->respond($format, 'accounting::phase3.reports.register', $data, 'fixed-asset-register');
    }

    public function depreciationSchedule(Request $request, int $assetId, string $format = 'pdf')
    {
        $data = FixedAssetReportService::depreciationSchedule($assetId);

        return $this->respond($format, 'accounting::phase3.reports.depreciation_schedule', $data, 'fa-depreciation-'.$assetId);
    }

    public function history(int $assetId, string $format = 'pdf')
    {
        $data = FixedAssetReportService::history($assetId);

        return $this->respond($format, 'accounting::phase3.reports.asset_history', $data, 'fa-history-'.$assetId);
    }

    public function disposals(Request $request, string $format = 'pdf')
    {
        $data = FixedAssetReportService::disposalReport($request->get('from'), $request->get('to'));

        return $this->respond($format, 'accounting::phase3.reports.disposals', $data, 'fa-disposals');
    }

    private function respond(string $format, string $view, array $data, string $basename)
    {
        if ($format === 'pdf') {
            return Pdf::loadView($view, $data)->setPaper('a4')->download($basename.'.pdf');
        }

        $viewObj = view($view, $data);

        if ($format === 'xlsx') {
            return Excel::download(new AccountingExport($viewObj), $basename.'.xlsx');
        }
        if ($format === 'xls') {
            return Excel::download(new AccountingExport($viewObj), $basename.'.xls');
        }

        return Excel::download(new AccountingExport($viewObj), $basename.'.csv');
    }
}