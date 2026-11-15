<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\RecurringAccountingTemplate;
use App\Services\Accounting\RecurringAccountingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Services\FlashService;

class RecurringJournalController extends Controller
{
    public function index()
    {
        $templates = RecurringAccountingTemplate::query()->orderByDesc('id')->paginate(25);

        return view('accounting::phase2.recurring.index', compact('templates'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::query()->where('active', 1)->orderBy('gl_code')->get();

        return view('accounting::phase2.recurring.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:160',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'starts_on' => 'required|date',
            'execution_mode' => 'required|in:draft,draft_notify,auto_post',
            'lines' => 'required|array|min:2',
        ]);

        try {
            RecurringAccountingService::createTemplate([
                'name' => $request->name,
                'frequency' => $request->frequency,
                'starts_on' => $request->starts_on,
                'ends_on' => $request->ends_on,
                'execution_mode' => $request->execution_mode,
                'payload' => [
                    'description' => $request->description,
                    'lines' => $request->lines,
                ],
            ], Auth::id());
            (new FlashService())->onSave();

            return redirect('accounting/recurring-journals');
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function pause($id)
    {
        $t = RecurringAccountingTemplate::findOrFail($id);
        $t->status = 'paused';
        $t->save();
        (new FlashService())->onSave();

        return redirect('accounting/recurring-journals');
    }

    public function resume($id)
    {
        $t = RecurringAccountingTemplate::findOrFail($id);
        $t->status = 'active';
        $t->save();
        (new FlashService())->onSave();

        return redirect('accounting/recurring-journals');
    }

    public function runNow($id)
    {
        try {
            $t = RecurringAccountingTemplate::findOrFail($id);
            $occ = ($t->next_run_on ?: now())->format('Y-m-d');
            RecurringAccountingService::executeOccurrence($t, $occ, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/recurring-journals');
    }
}
