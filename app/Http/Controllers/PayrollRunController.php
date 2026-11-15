<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePayDeduction;
use App\Models\EmployeeSalary;
use App\Models\IncentiveClaim;
use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\PayrollCorrection;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\SalaryComponent;
use App\Services\EmployeeMonthAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'payrolls');
        $perPage = (int) $request->get('per_page', 25);
        $perPage = $perPage >= 10 && $perPage <= 100 ? $perPage : 25;

        $items = null;
        $runs = null;

        if ($tab === 'payrolls') {
            $q = PayrollRunItem::with(['payrollRun', 'employee.department', 'employee.designation'])
                ->orderByDesc('id');
            if ($request->filled('month')) {
                $q->whereHas('payrollRun', fn ($r) => $r->whereMonth('period_start', $request->month));
            }
            if ($request->filled('year')) {
                $q->whereHas('payrollRun', fn ($r) => $r->whereYear('period_start', $request->year));
            }
            if ($request->filled('status') && in_array($request->status, ['due', 'paid', 'partial'])) {
                $q->where('payment_status', $request->status);
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $q->whereHas('employee', fn ($e) => $e->where('name', 'like', "%{$s}%"));
            }
            if ($request->get('export') === 'csv') {
                $items = $q->with(['payrollRun', 'employee.department', 'employee.designation'])->limit(5000)->get();
                return $this->exportCsv($items);
            }
            $items = $q->paginate($perPage)->withQueryString();
        } else {
            $runs = PayrollRun::with('creator')->orderByDesc('period_start')->paginate($perPage)->withQueryString();
        }

        return view('erp.hrm.salary.payroll.index', compact('tab', 'items', 'runs', 'perPage'));
    }

    public function markAsPaid(Request $request, PayrollRunItem $payroll_run_item)
    {
        $request->validate([
            'payment_reference' => 'nullable|string|max:100',
        ]);
        $payroll_run_item->update([
            'payment_status' => PayrollRunItem::PAYMENT_PAID,
            'paid_at' => now(),
            'payment_reference' => $request->payment_reference,
        ]);
        return back()->with('success', 'Marked as paid.');
    }

    protected function exportCsv($items)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="payroll-' . date('Y-m-d') . '.csv"',
        ];
        $callback = function () use ($items) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Department', 'Designation', 'Month/Year', 'Reference No', 'Total amount', 'Payment Status']);
            foreach ($items as $item) {
                fputcsv($out, [
                    $item->employee?->name ?? '',
                    $item->employee?->department?->name ?? '',
                    $item->employee?->designation?->title ?? '',
                    $item->payrollRun ? $item->payrollRun->period_start->format('F Y') : '',
                    $item->reference_no ?? '',
                    $item->net_salary ?? 0,
                    $item->payment_status ?? 'due',
                ]);
            }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $employees = Employee::with('department')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'resigned');
            })
            ->orderBy('name')
            ->get();
        return view('erp.hrm.salary.payroll.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020|max:2100',
            'employee_id' => 'required|integer|exists:employees,id',
        ]);
        $employeeIds = [(int) $request->employee_id];
        $start = Carbon::createFromDate($request->period_year, $request->period_month, 1);
        $end = $start->copy()->endOfMonth();
        $title = 'Payroll for ' . $start->format('F Y');
        $existingRun = PayrollRun::where('period_start', $start->toDateString())->first();
        if ($existingRun) {
            return redirect()->route('payroll-runs.create')
                ->withInput()
                ->with('error', 'Payroll run for ' . $start->format('F Y') . ' already exists.')
                ->with('existing_payroll_run_id', $existingRun->id);
        }
        $run = PayrollRun::create([
            'title' => $title,
            'period_start' => $start,
            'period_end' => $end,
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);
        $employees = Employee::whereIn('id', $employeeIds)->get();
        foreach ($employees as $emp) {
            $attendance = app(EmployeeMonthAttendance::class)->summarize($emp, $start);
            $pay = $this->payAdjustments($emp, $start, $end, $attendance);
            $daysPresent = $attendance['present'];
            $daysAbsent = $attendance['absent'];
            $leaveDays = (float) LeaveRequest::where('employee_id', $emp->id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                        ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_date', '<=', $start)->where('end_date', '>=', $end);
                        });
                })
                ->sum('days');
        $basic = 0;
        $allowances = 0;
        $deductions = 0;
        $details = [];
        $gross = 0;
        $net = 0;
        $item = PayrollRunItem::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $emp->id,
            'days_present' => $daysPresent,
            'days_absent' => $daysAbsent,
            'leave_days' => $leaveDays,
            'ot_minutes' => $pay['ot_minutes'],
            'ot_amount' => $pay['ot_amount'],
            'nopay_amount' => $pay['nopay_amount'],
            'epf_amount' => 0,
            'etf_amount' => 0,
            'work_duration' => 1,
            'work_duration_unit' => 'Month',
            'amount_per_unit' => 0,
            'basic_salary' => 0,
            'gross_salary' => round($pay['ot_amount'] + $pay['incentive'], 2),
            'total_allowances' => $pay['incentive'],
            'total_deductions' => $pay['deductions'],
            'net_salary' => $pay['net'],
            'details' => $pay['details'],
            'payment_status' => PayrollRunItem::PAYMENT_DUE,
        ]);
            $item->update(['reference_no' => $run->period_start->format('Y') . '/' . str_pad((string) $item->id, 4, '0', STR_PAD_LEFT)]);
            if ($pay['claim_ids'] !== []) {
                IncentiveClaim::whereIn('id', $pay['claim_ids'])->update(['payroll_run_item_id' => $item->id]);
            }
        }
        return redirect()->route('payroll-runs.show', $run)->with('success', 'Payroll created. Edit each row to adjust attendance/work duration and save; then print paysheets.');
    }

    public function show(PayrollRun $payrollRun)
    {
        $payrollRun->load(['items.employee', 'creator']);
        $existingIds = $payrollRun->items->pluck('employee_id')->filter()->values()->all();
        $employeesAvailable = Employee::with('department')->whereNotIn('id', $existingIds)->orderBy('name')->get();
        return view('erp.hrm.salary.payroll.show', compact('payrollRun', 'employeesAvailable'));
    }

    public function addEmployee(Request $request, PayrollRun $payrollRun)
    {
        if (! $payrollRun->isEditable()) {
            return back()->with('error', 'Only draft or reviewed payrolls can be edited.');
        }
        $request->validate(['employee_id' => 'required|integer|exists:employees,id']);
        $empId = (int) $request->employee_id;
        if ($payrollRun->items()->where('employee_id', $empId)->exists()) {
            return back()->with('error', 'That employee is already in this payroll.');
        }
        $start = $payrollRun->period_start;
        $end = $payrollRun->period_end;
        $emp = Employee::findOrFail($empId);
        $attendance = app(EmployeeMonthAttendance::class)->summarize($emp, $start);
        $pay = $this->payAdjustments($emp, $start, $end, $attendance);
        $daysPresent = $attendance['present'];
        $daysAbsent = $attendance['absent'];
        $leaveDays = (float) LeaveRequest::where('employee_id', $emp->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start)->where('end_date', '>=', $end);
                    });
            })
            ->sum('days');
        $details = [];
        $item = PayrollRunItem::create([
            'payroll_run_id' => $payrollRun->id,
            'employee_id' => $emp->id,
            'days_present' => $daysPresent,
            'days_absent' => $daysAbsent,
            'leave_days' => $leaveDays,
            'ot_minutes' => $pay['ot_minutes'],
            'ot_amount' => $pay['ot_amount'],
            'nopay_amount' => $pay['nopay_amount'],
            'epf_amount' => 0,
            'etf_amount' => 0,
            'work_duration' => 1,
            'work_duration_unit' => 'Month',
            'amount_per_unit' => 0,
            'basic_salary' => 0,
            'gross_salary' => round($pay['ot_amount'] + $pay['incentive'], 2),
            'total_allowances' => $pay['incentive'],
            'total_deductions' => $pay['deductions'],
            'net_salary' => $pay['net'],
            'details' => $pay['details'],
            'payment_status' => PayrollRunItem::PAYMENT_DUE,
        ]);
        $item->update(['reference_no' => $payrollRun->period_start->format('Y') . '/' . str_pad((string) $item->id, 4, '0', STR_PAD_LEFT)]);
        if ($pay['claim_ids'] !== []) {
            IncentiveClaim::whereIn('id', $pay['claim_ids'])->update(['payroll_run_item_id' => $item->id]);
        }
        return back()->with('success', $emp->name . ' added to payroll.');
    }

    public function review(PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== PayrollRun::STATUS_DRAFT) {
            return back()->with('error', 'Only a draft can be sent for review.');
        }
        $payrollRun->update(['status' => PayrollRun::STATUS_REVIEWED]);

        return back()->with('success', 'Payroll sent for review.');
    }

    public function approveRun(PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== PayrollRun::STATUS_REVIEWED) {
            return back()->with('error', 'Review the payroll before approval.');
        }
        $payrollRun->update(['status' => PayrollRun::STATUS_APPROVED]);

        return back()->with('success', 'Payroll approved. Finalize to lock it.');
    }

    public function finalize(PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== PayrollRun::STATUS_APPROVED) {
            return back()->with('error', 'Approve the payroll before finalizing.');
        }
        $payrollRun->load('items');
        foreach ($payrollRun->items as $item) {
            foreach ($item->details ?? [] as $line) {
                if (empty($line['deduction_id'])) {
                    continue;
                }
                $plan = EmployeePayDeduction::find($line['deduction_id']);
                if ($plan) {
                    $plan->update([
                        'balance' => max(0, (float) $plan->balance - (float) ($line['value'] ?? 0)),
                    ]);
                }
            }
        }
        $payrollRun->update(['status' => PayrollRun::STATUS_FINALIZED, 'finalized_at' => now()]);
        return back()->with('success', 'Payroll finalized and locked. Loan balances were reduced.');
    }

    public function exportBank(PayrollRun $payrollRun)
    {
        if (! in_array($payrollRun->status, [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_FINALIZED], true)) {
            return back()->with('error', 'Bank export is available after approval.');
        }
        $payrollRun->load('items.employee');
        $lines = ["employee_code,name,bank,branch,account_name,account_number,net"];
        foreach ($payrollRun->items as $item) {
            $e = $item->employee;
            $lines[] = implode(',', [
                $e?->employee_code,
                '"'.str_replace('"', '', $e?->display_name ?: $e?->name).'"',
                '"'.str_replace('"', '', (string) $e?->bank_name).'"',
                '"'.str_replace('"', '', (string) $e?->bank_branch).'"',
                '"'.str_replace('"', '', (string) $e?->bank_account_name).'"',
                $e?->bank_account_number,
                $item->net_salary,
            ]);
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payroll-'.$payrollRun->id.'.csv"',
        ]);
    }

    public function editItem(PayrollRun $payrollRun, PayrollRunItem $payroll_run_item)
    {
        if ($payroll_run_item->payroll_run_id !== $payrollRun->id || ! $payrollRun->isEditable()) {
            abort(404);
        }
        $payroll_run_item->load('employee.department');
        return view('erp.hrm.salary.payroll.edit_item', compact('payrollRun', 'payroll_run_item'));
    }

    public function updateItem(Request $request, PayrollRun $payrollRun, PayrollRunItem $payroll_run_item)
    {
        if ($payroll_run_item->payroll_run_id !== $payrollRun->id || ! $payrollRun->isEditable()) {
            abort(404);
        }
        $request->validate([
            'days_present' => 'required|integer|min:0',
            'days_absent' => 'required|integer|min:0',
            'leave_days' => 'required|numeric|min:0',
            'work_duration' => 'required|numeric|min:0',
            'work_duration_unit' => 'required|string|max:30',
            'amount_per_unit' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:2000',
            'earnings' => 'nullable|array',
            'earnings.*.description' => 'nullable|string|max:255',
            'earnings.*.amount_type' => 'nullable|string|max:30',
            'earnings.*.amount' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|array',
            'deductions.*.description' => 'nullable|string|max:255',
            'deductions.*.amount_type' => 'nullable|string|max:30',
            'deductions.*.amount' => 'nullable|numeric|min:0',
        ]);
        $workDuration = (float) $request->work_duration;
        $amountPerUnit = (float) $request->amount_per_unit;
        $basic = round($workDuration * $amountPerUnit, 2);
        $details = [];
        $allowances = 0;
        $deductions = 0;
        foreach ($request->input('earnings', []) as $e) {
            $val = (float) ($e['amount'] ?? 0);
            $details[] = [
                'name' => $e['description'] ?? '',
                'type' => SalaryComponent::TYPE_ALLOWANCE,
                'amount_type' => $e['amount_type'] ?? 'Fixed',
                'value' => $val,
            ];
            $allowances += $val;
        }
        foreach ($request->input('deductions', []) as $d) {
            $val = (float) ($d['amount'] ?? 0);
            $details[] = [
                'name' => $d['description'] ?? '',
                'type' => SalaryComponent::TYPE_DEDUCTION,
                'amount_type' => $d['amount_type'] ?? 'Fixed',
                'value' => $val,
            ];
            $deductions += $val;
        }
        foreach (collect($payroll_run_item->details ?? [])->where('amount_type', 'system') as $line) {
            $details[] = $line;
            if (($line['type'] ?? '') === SalaryComponent::TYPE_DEDUCTION) {
                $deductions += (float) ($line['value'] ?? 0);
            } elseif (($line['type'] ?? '') === SalaryComponent::TYPE_ALLOWANCE && ($line['name'] ?? '') !== 'Production incentive') {
                $allowances += (float) ($line['value'] ?? 0);
            } elseif (($line['name'] ?? '') === 'Production incentive') {
                $allowances += (float) ($line['value'] ?? 0);
            }
        }
        $otAmount = round((float) $payroll_run_item->ot_amount, 2);
        $epf = round($basic * 0.08, 2);
        $etf = round($basic * 0.03, 2);
        $gross = round($basic + $allowances + $otAmount, 2);
        $net = round($gross - $deductions - $epf, 2);
        PayrollCorrection::create([
            'payroll_run_id' => $payrollRun->id,
            'payroll_run_item_id' => $payroll_run_item->id,
            'summary' => 'Updated '.$payroll_run_item->employee?->name.' basic '.number_format($payroll_run_item->basic_salary, 2).' to '.number_format($basic, 2).', net '.number_format($net, 2),
            'created_by' => auth()->id(),
        ]);
        $payroll_run_item->update([
            'days_present' => (int) $request->days_present,
            'days_absent' => (int) $request->days_absent,
            'leave_days' => (float) $request->leave_days,
            'work_duration' => $workDuration,
            'work_duration_unit' => $request->work_duration_unit,
            'amount_per_unit' => $amountPerUnit,
            'basic_salary' => $basic,
            'gross_salary' => $gross,
            'total_allowances' => $allowances,
            'total_deductions' => $deductions,
            'epf_amount' => $epf,
            'etf_amount' => $etf,
            'net_salary' => $net,
            'details' => $details,
            'note' => $request->note,
        ]);
        return redirect()->route('payroll-runs.show', $payrollRun)->with('success', 'Payroll item updated.');
    }

    public function payslip(PayrollRun $payrollRun, PayrollRunItem $payroll_run_item)
    {
        if ((int) $payroll_run_item->payroll_run_id !== (int) $payrollRun->id) {
            abort(404);
        }
        $payroll_run_item->load('employee.designation', 'employee.department', 'employee.productionSection');
        $netSalaryInWords = $this->amountToWords((float) $payroll_run_item->net_salary);
        return view('erp.hrm.salary.payroll.payslip', [
            'payrollRun' => $payrollRun,
            'item' => $payroll_run_item,
            'netSalaryInWords' => $netSalaryInWords,
        ]);
    }

    /**
     * Approved OT, unpaid leave, loans, and unpaid incentives for one employee.
     * Incentive claims are returned so the payroll line can lock them and they are not paid twice.
     *
     * @return array{ot_minutes: int, ot_amount: float, nopay_amount: float, deductions: float, details: array<int, array<string, mixed>>, net: float, claim_ids: array<int, int>}
     */
    protected function payAdjustments(Employee $emp, Carbon $start, Carbon $end, array $attendance): array
    {
        $approvedOt = OtRequest::query()
            ->where('employee_id', $emp->id)
            ->where('status', 'approved')
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();
        if ($approvedOt->isNotEmpty()) {
            $otAmount = round((float) $approvedOt->sum('amount'), 2);
            $otMinutes = (int) round($approvedOt->sum(fn ($row) => (float) $row->approved_hours * 60));
        } else {
            $otAmount = (float) ($attendance['ot_amount'] ?? 0);
            $otMinutes = (int) ($attendance['ot_minutes'] ?? 0);
        }

        $unpaidDays = (float) LeaveRequest::query()
            ->where('employee_id', $emp->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->sum('days');
        $salary = EmployeeSalary::currentForEmployee($emp->id, $end);
        $daily = 0.0;
        if (in_array($emp->pay_type, ['daily', 'piece_rate'], true)) {
            $daily = (float) ($emp->pay_rate ?? 0);
        } elseif ($emp->pay_type === 'hourly') {
            $daily = (float) ($emp->pay_rate ?? 0) * 8;
        } elseif ($salary) {
            $daily = round(((float) $salary->basic_salary) / 30, 2);
        }
        $nopay = round($unpaidDays * $daily, 2);

        $details = [];
        $deductions = 0.0;
        if ($nopay > 0) {
            $details[] = ['name' => 'Unpaid leave', 'type' => SalaryComponent::TYPE_DEDUCTION, 'amount_type' => 'system', 'value' => $nopay];
            $deductions += $nopay;
        }
        $plans = EmployeePayDeduction::query()
            ->where('employee_id', $emp->id)
            ->where('is_active', true)
            ->where('balance', '>', 0)
            ->get();
        foreach ($plans as $plan) {
            $take = min((float) $plan->balance, (float) $plan->monthly_amount);
            if ($take <= 0) {
                continue;
            }
            $details[] = [
                'name' => $plan->title,
                'type' => SalaryComponent::TYPE_DEDUCTION,
                'amount_type' => 'system',
                'value' => $take,
                'deduction_id' => $plan->id,
            ];
            $deductions += $take;
        }

        $claims = IncentiveClaim::query()
            ->where('employee_id', $emp->id)
            ->where('status', 'approved')
            ->whereNull('payroll_run_item_id')
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();
        $incentive = round((float) $claims->sum('amount'), 2);
        if ($incentive > 0) {
            $details[] = ['name' => 'Production incentive', 'type' => SalaryComponent::TYPE_ALLOWANCE, 'amount_type' => 'system', 'value' => $incentive];
        }

        return [
            'ot_minutes' => $otMinutes,
            'ot_amount' => round($otAmount, 2),
            'incentive' => $incentive,
            'nopay_amount' => $nopay,
            'deductions' => round($deductions, 2),
            'details' => $details,
            'net' => round($otAmount + $incentive - $deductions, 2),
            'claim_ids' => $claims->pluck('id')->all(),
        ];
    }

    protected function amountToWords(float $amount): string
    {
        $whole = (int) floor($amount);
        if ($whole === 0) {
            return 'Zero Rupees only.';
        }
        $words = '';
        if ($whole >= 10000000) {
            $words .= $this->amountToWordsUnder1000((int) floor($whole / 10000000)) . ' Crore ';
            $whole %= 10000000;
        }
        if ($whole >= 100000) {
            $words .= $this->amountToWordsUnder1000((int) floor($whole / 100000)) . ' Lakh ';
            $whole %= 100000;
        }
        if ($whole >= 1000) {
            $words .= $this->amountToWordsUnder1000((int) floor($whole / 1000)) . ' Thousand ';
            $whole %= 1000;
        }
        if ($whole > 0) {
            $words .= $this->amountToWordsUnder1000($whole);
        }
        return trim($words) . ' Rupees only.';
    }

    protected function amountToWordsUnder1000(int $n): string
    {
        if ($n === 0) {
            return '';
        }
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        if ($n < 20) {
            return $ones[$n];
        }
        if ($n < 100) {
            return trim($tens[(int) floor($n / 10)] . ' ' . $ones[$n % 10]);
        }
        return trim($ones[(int) floor($n / 100)] . ' Hundred ' . $this->amountToWordsUnder1000($n % 100));
    }
}
