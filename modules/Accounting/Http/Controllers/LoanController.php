<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\AcctLoan;
use App\Models\Accounting\AcctLoanPayment;
use App\Services\Accounting\LoanAccountingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class LoanController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $loans = Schema::hasTable('acct_loans')
            ? AcctLoan::query()->orderByDesc('id')->paginate(25)
            : new LengthAwarePaginator([], 0, 25);

        return view('accounting::phase3.loans.index', compact('loans'));
    }

    public function create()
    {
        $suggestedNumber = Schema::hasTable('acct_loans') ? LoanAccountingService::nextLoanNumber() : '';

        return view('accounting::phase3.loans.create', compact('suggestedNumber'));
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'lender_name' => 'required|string|max:160',
            'loan_number' => 'nullable|string|max:40',
            'principal' => 'required|numeric|min:0.01',
            'annual_interest_rate' => 'nullable|numeric|min:0',
            'term_months' => 'required|integer|min:1|max:600',
            'disbursement_date' => 'nullable|date',
            'first_due_date' => 'nullable|date',
        ]);

        try {
            $loan = LoanAccountingService::createLoan($data, Auth::id());
            if ($request->filled('first_due_date')) {
                LoanAccountingService::buildEqualInstallmentSchedule($loan, $request->first_due_date);
            }
            (new FlashService())->onSave();

            return redirect('accounting/loans/'.$loan->id);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }
    }

    public function show($id)
    {
        $loan = AcctLoan::with(['scheduleLines', 'payments'])->findOrFail($id);

        return view('accounting::phase3.loans.show', compact('loan'));
    }

    public function generateSchedule(Request $request, $id)
    {
        $this->requireManage();
        $loan = AcctLoan::findOrFail($id);
        $request->validate(['first_due_date' => 'nullable|date']);

        try {
            LoanAccountingService::buildEqualInstallmentSchedule($loan, $request->get('first_due_date'));
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/loans/'.$id);
    }

    public function disburse(Request $request, $id)
    {
        $this->requireManage();
        $request->validate(['disbursement_date' => 'required|date']);
        $loan = AcctLoan::findOrFail($id);

        try {
            LoanAccountingService::disburse($loan, $request->disbursement_date, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/loans/'.$id);
    }

    public function paymentForm($id)
    {
        $loan = AcctLoan::with('scheduleLines')->findOrFail($id);
        $nextLine = $loan->scheduleLines->firstWhere('status', 'scheduled');

        return view('accounting::phase3.loans.payment', compact('loan', 'nextLine'));
    }

    public function recordPayment(Request $request, $id)
    {
        $this->requireManage();
        $data = $request->validate([
            'payment_date' => 'required|date',
            'principal_paid' => 'required|numeric|min:0',
            'interest_paid' => 'required|numeric|min:0',
            'schedule_line_id' => 'nullable|integer',
        ]);

        $loan = AcctLoan::findOrFail($id);

        try {
            LoanAccountingService::recordPayment(
                $loan,
                $data['payment_date'],
                (float) $data['principal_paid'],
                (float) $data['interest_paid'],
                $data['schedule_line_id'] ?? null,
                Auth::id()
            );
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/loans/'.$id);
    }

    public function statement($id)
    {
        $loan = AcctLoan::with(['scheduleLines', 'payments'])->findOrFail($id);
        $paidTotal = AcctLoanPayment::query()->where('loan_id', $loan->id)->sum('total_paid');
        $interestPaid = AcctLoanPayment::query()->where('loan_id', $loan->id)->sum('interest_paid');
        $principalPaid = AcctLoanPayment::query()->where('loan_id', $loan->id)->sum('principal_paid');

        return view('accounting::phase3.loans.statement', compact('loan', 'paidTotal', 'interestPaid', 'principalPaid'));
    }
}