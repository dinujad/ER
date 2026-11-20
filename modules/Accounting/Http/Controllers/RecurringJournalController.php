<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\RecurringAccountingRun;
use App\Models\Accounting\RecurringAccountingTemplate;
use App\Services\Accounting\RecurringAccountingService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Services\FlashService;

class RecurringJournalController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('document_type');

        if (! Schema::hasTable('accounting_recurring_templates')) {
            $templates = new LengthAwarePaginator([], 0, 25);

            return view('accounting::phase2.recurring.index', compact('templates', 'type'));
        }

        $q = RecurringAccountingTemplate::query()->orderByDesc('id');
        if ($type) {
            $q->where('document_type', $type);
        }
        $templates = $q->paginate(25)->withQueryString();

        return view('accounting::phase2.recurring.index', compact('templates', 'type'));
    }

    public function history()
    {
        if (! Schema::hasTable('accounting_recurring_runs')) {
            $runs = new LengthAwarePaginator([], 0, 50);

            return view('accounting::phase2.recurring.history', compact('runs'));
        }

        $runs = RecurringAccountingRun::query()->orderByDesc('id')->paginate(50);

        return view('accounting::phase2.recurring.history', compact('runs'));
    }

    public function create(Request $request)
    {
        $docType = $request->get('document_type', 'journal');
        $accounts = Schema::hasTable('chart_of_accounts')
            ? ChartOfAccount::query()->where('active', 1)->orderBy('gl_code')->get()
            : collect();
        $customers = Schema::hasTable('customers') ? DB::table('customers')->orderBy('name')->limit(500)->get(['id', 'name']) : collect();
        $suppliers = Schema::hasTable('suppliers') ? DB::table('suppliers')->orderBy('name')->limit(500)->get(['id', 'name']) : collect();

        return view('accounting::phase2.recurring.create', compact('accounts', 'docType', 'customers', 'suppliers'));
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('accounting_recurring_templates')) {
            return (new FlashService())
                ->onException(new \RuntimeException('Recurring templates table is missing. Run deploy migrate / accounting-schema.'))
                ->redirectBackWithInput();
        }

        $docType = $request->get('document_type', 'journal');
        $rules = [
            'name' => 'required|string|max:160',
            'document_type' => 'required|in:journal,invoice,bill',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'starts_on' => 'required|date',
            'execution_mode' => 'required|in:draft,draft_notify,auto_post',
            'max_occurrences' => 'nullable|integer|min:1',
        ];
        if ($docType === 'journal') {
            $rules['lines'] = 'required|array|min:2';
        }
        if ($docType === 'invoice') {
            $rules['customer_id'] = 'required|integer';
            $rules['amount'] = 'required|numeric|min:0.01';
        }
        if ($docType === 'bill') {
            $rules['supplier_id'] = 'required|integer';
            $rules['amount'] = 'required|numeric|min:0.01';
        }
        $request->validate($rules);

        try {
            $payload = ['description' => $request->description];
            if ($docType === 'journal') {
                $payload['lines'] = $request->lines;
                $payload['reference'] = $request->reference;
            } elseif ($docType === 'invoice') {
                $payload['customer_id'] = (int) $request->customer_id;
                $payload['amount'] = $request->amount;
            } else {
                $payload['supplier_id'] = (int) $request->supplier_id;
                $payload['amount'] = $request->amount;
            }

            RecurringAccountingService::createTemplate([
                'name' => $request->name,
                'document_type' => $docType,
                'frequency' => $request->frequency,
                'starts_on' => $request->starts_on,
                'ends_on' => $request->ends_on,
                'max_occurrences' => $request->max_occurrences,
                'execution_mode' => $request->execution_mode,
                'payload' => $payload,
            ], Auth::id());
            (new FlashService())->onSave();

            return redirect('accounting/recurring-journals?document_type='.$docType);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function pause($id)
    {
        if (! Schema::hasTable('accounting_recurring_templates')) {
            return redirect()->back();
        }

        $t = RecurringAccountingTemplate::findOrFail($id);
        $t->status = 'paused';
        $t->save();
        (new FlashService())->onSave();

        return redirect()->back();
    }

    public function resume($id)
    {
        if (! Schema::hasTable('accounting_recurring_templates')) {
            return redirect()->back();
        }

        $t = RecurringAccountingTemplate::findOrFail($id);
        $t->status = 'active';
        $t->save();
        (new FlashService())->onSave();

        return redirect()->back();
    }

    public function runNow($id)
    {
        if (! Schema::hasTable('accounting_recurring_templates')) {
            return redirect()->back();
        }

        try {
            $t = RecurringAccountingTemplate::findOrFail($id);
            $occ = ($t->next_run_on ?: now())->format('Y-m-d');
            RecurringAccountingService::executeOccurrence($t, $occ, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect()->back();
    }
}
