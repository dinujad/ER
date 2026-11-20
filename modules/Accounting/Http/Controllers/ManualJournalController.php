<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\ManualJournal;
use App\Services\Accounting\ManualJournalService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Services\FlashService;

class ManualJournalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');

        if (! Schema::hasTable('accounting_manual_journals')) {
            $journals = new LengthAwarePaginator([], 0, 25);

            return view('accounting::phase2.manual_journals.index', compact('journals', 'status'));
        }

        $q = ManualJournal::query()->with('lines')->orderByDesc('id');
        if ($status) {
            $q->where('status', $status);
        }
        if ($search = $request->get('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('journal_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $journals = $q->paginate(25)->withQueryString();

        return view('accounting::phase2.manual_journals.index', compact('journals', 'status'));
    }

    public function create()
    {
        $accounts = Schema::hasTable('chart_of_accounts')
            ? ChartOfAccount::query()->where('active', 1)->orderBy('gl_code')->get()
            : collect();

        return view('accounting::phase2.manual_journals.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('accounting_manual_journals')) {
            return (new FlashService())
                ->onException(new \RuntimeException('Manual journals table is missing. Run deploy accounting-schema.'))
                ->redirectBackWithInput();
        }

        $request->validate([
            'journal_date' => 'required|date',
            'description' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:120',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        try {
            $journal = ManualJournalService::createDraft([
                'business_id' => 0,
                'journal_date' => $request->journal_date,
                'description' => $request->description,
                'reference' => $request->reference,
                'location_id' => $request->location_id,
            ], $request->lines, Auth::id());

            (new FlashService())->onSave();

            return redirect('accounting/manual-journals/'.$journal->id);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function show($id)
    {
        $journal = ManualJournal::with('lines.account')->findOrFail($id);

        return view('accounting::phase2.manual_journals.show', compact('journal'));
    }

    public function edit($id)
    {
        $journal = ManualJournal::with('lines')->findOrFail($id);
        if (! in_array($journal->status, ['draft', 'rejected'], true)) {
            return redirect('accounting/manual-journals/'.$id)->withErrors('Only draft/rejected journals can be edited.');
        }
        $accounts = ChartOfAccount::query()->where('active', 1)->orderBy('gl_code')->get();

        return view('accounting::phase2.manual_journals.edit', compact('journal', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        $journal = ManualJournal::findOrFail($id);
        $request->validate([
            'journal_date' => 'required|date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
        ]);

        try {
            ManualJournalService::updateDraft($journal, $request->only(['journal_date', 'description', 'reference', 'location_id']), $request->lines, Auth::id());
            (new FlashService())->onSave();

            return redirect('accounting/manual-journals/'.$id);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function submit($id)
    {
        try {
            ManualJournalService::submit(ManualJournal::findOrFail($id), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/manual-journals/'.$id);
    }

    public function approve($id)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.manage') && ! $user->can_access('accounting.journals.approve'))) {
            abort(403);
        }

        try {
            ManualJournalService::approve(ManualJournal::findOrFail($id), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/manual-journals/'.$id);
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.manage') && ! $user->can_access('accounting.journals.approve'))) {
            abort(403);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        try {
            ManualJournalService::reject(ManualJournal::findOrFail($id), $request->reason, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/manual-journals/'.$id);
    }

    public function post($id)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.manage') && ! $user->can_access('accounting.journals.post'))) {
            abort(403);
        }

        $requireApproval = ! config('accounting.manual_journal_skip_approval', false);

        try {
            ManualJournalService::post(ManualJournal::findOrFail($id), $requireApproval, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/manual-journals/'.$id);
    }

    public function reverse($id)
    {
        $user = Auth::user();
        if (! $user || (! $user->isSystemAdmin() && ! $user->can_access('accounting.manage') && ! $user->can_access('accounting.journals.reverse'))) {
            abort(403);
        }

        try {
            ManualJournalService::reverse(ManualJournal::findOrFail($id), Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/manual-journals/'.$id);
    }

    public function approvalQueue()
    {
        if (! Schema::hasTable('accounting_manual_journals')) {
            $journals = new LengthAwarePaginator([], 0, 25);

            return view('accounting::phase2.manual_journals.index', [
                'journals' => $journals,
                'status' => 'submitted',
                'title' => 'Approval Queue',
            ]);
        }

        $journals = ManualJournal::query()->with('lines')->where('status', 'submitted')->orderBy('submitted_at')->paginate(25);

        return view('accounting::phase2.manual_journals.index', [
            'journals' => $journals,
            'status' => 'submitted',
            'title' => 'Approval Queue',
        ]);
    }
}
