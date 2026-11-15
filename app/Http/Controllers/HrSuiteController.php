<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDaily;
use App\Models\Employee;
use App\Models\EmployeePayDeduction;
use App\Models\HourlyProduction;
use App\Models\IncentiveClaim;
use App\Models\LeaveRequest;
use App\Models\OtRequest;
use App\Models\PayrollRun;
use App\Models\ProductionLine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HrSuiteController extends Controller
{
    public function incentives()
    {
        $rows = IncentiveClaim::with(['employee', 'productionLine'])
            ->when($this->supervisorLineId(), fn ($q, $lineId) => $q->where('production_line_id', $lineId))
            ->latest('work_date');

        return view('erp.hrm.suite.board', [
            'title' => 'Production incentives',
            'intro' => 'Actual quantity is taken from hourly production for that employee and date. Approved claims are added to payroll once, then locked.',
            'store' => route('hr.incentives.store'),
            'fields' => $this->fields([
                ['employee_id', 'Employee', 'employee'],
                ['production_line_id', 'Line', 'line'],
                ['work_date', 'Date', 'date'],
                ['operation', 'Operation / skill', 'text'],
                ['mode', 'Mode', 'select', ['individual' => 'Individual', 'team' => 'Team']],
                ['rate', 'Piece rate (Rs)', 'number'],
                ['target_qty', 'Line target', 'number'],
                ['rejected_qty', 'Rejected pieces', 'number'],
            ]),
            'columns' => ['Employee', 'Date', 'Mode', 'Target', 'Actual', 'Rejected', 'Amount', 'Payroll'],
            'records' => $this->pageOf($rows, ['*'], fn ($row) => [
                $row->employee?->display_name ?: $row->employee?->name,
                $row->work_date->format('d M Y'),
                $row->mode,
                $row->target_qty,
                $row->actual_qty,
                $row->rejected_qty,
                'Rs '.number_format($row->amount, 2).' · '.$row->status,
                $row->payroll_run_item_id ? 'Paid on item #'.$row->payroll_run_item_id : ($row->status === 'pending' ? '<form method="POST" action="'.route('hr.incentives.approve', $row).'">'.csrf_field().method_field('PATCH').'<button class="btn btn-sm btn-outline-primary">Approve</button></form>' : 'Waiting payroll'),
            ]),
            'links' => [
                ['Hourly production', route('hourly-production.create')],
                ['Payroll', route('payroll-runs.index')],
            ],
        ]);
    }

    public function storeIncentive(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'production_line_id' => 'nullable|exists:production_lines,id',
            'work_date' => 'required|date',
            'operation' => 'nullable|string|max:255',
            'mode' => 'required|in:individual,team',
            'rate' => 'required|numeric|min:0',
            'target_qty' => 'nullable|integer|min:0',
            'rejected_qty' => 'nullable|integer|min:0',
        ]);
        $already = IncentiveClaim::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('work_date', $data['work_date'])
            ->where('operation', $data['operation'] ?? null)
            ->where('status', '!=', 'rejected')
            ->exists();
        if ($already) {
            return back()->with('error', 'This output is already on an incentive. It will not be paid twice.');
        }
        $actual = (int) HourlyProduction::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('production_date', $data['work_date'])
            ->sum('quantity');
        $rejected = (int) ($data['rejected_qty'] ?? 0);
        $payable = max(0, $actual - $rejected);
        IncentiveClaim::create($data + [
            'target_qty' => (int) ($data['target_qty'] ?? 0),
            'actual_qty' => $actual,
            'rejected_qty' => $rejected,
            'amount' => round($payable * (float) $data['rate'], 2),
            'status' => 'pending',
        ]);
        $this->audit('Created incentive claim');

        return back()->with('success', 'Incentive saved from production output. Approve it before payroll.');
    }

    public function approveIncentive(IncentiveClaim $incentiveClaim)
    {
        if ($incentiveClaim->payroll_run_item_id) {
            return back()->with('error', 'This output is already on a payroll.');
        }
        $incentiveClaim->update(['status' => 'approved']);
        $this->audit('Approved incentive #'.$incentiveClaim->id);

        return back()->with('success', 'Incentive approved for the next payroll.');
    }

    public function skills()
    {
        $rows = DB::table('employee_skills')->join('employees', 'employees.id', '=', 'employee_skills.employee_id')->orderByDesc('employee_skills.id');
        $multi = DB::table('employee_skills')->select('employee_id')->groupBy('employee_id')->havingRaw('count(*) >= 2')->get()->count();

        return view('erp.hrm.suite.board', [
            'title' => 'Skills and training',
            'intro' => $multi.' employee(s) have more than one skill. Training attendance and safety certificates are stored with the same people.',
            'store' => route('hr.skills.store'),
            'fields' => $this->fields([
                ['employee_id', 'Employee', 'employee'],
                ['category', 'Category', 'select', ['sewing' => 'Sewing', 'cutting' => 'Cutting', 'qc' => 'QC', 'packing' => 'Packing', 'other' => 'Other']],
                ['machine', 'Machine', 'text'],
                ['operation', 'Operation', 'text'],
                ['level', 'Level 1-5', 'number'],
                ['assessed_on', 'Assessed on', 'date'],
            ]),
            'columns' => ['Employee', 'Category', 'Machine', 'Operation', 'Level'],
            'records' => $this->pageOf($rows, ['employees.name', 'employee_skills.*'], fn ($row) => [$row->name, $row->category, $row->machine, $row->operation, $row->level]),
            'links' => [['Training', route('hr.training.index')], ['Manpower', route('hr.manpower.index')]],
        ]);
    }

    public function storeSkill(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'category' => 'required|string|max:50',
            'machine' => 'nullable|string|max:255',
            'operation' => 'nullable|string|max:255',
            'level' => 'required|integer|min:1|max:5',
            'assessed_on' => 'nullable|date',
        ]);
        DB::table('employee_skills')->insert($data + ['created_at' => now(), 'updated_at' => now()]);
        $this->audit('Recorded skill');

        return back()->with('success', 'Skill saved.');
    }

    public function training()
    {
        $rows = DB::table('training_records')->join('employees', 'employees.id', '=', 'training_records.employee_id')->orderByDesc('session_date');

        return view('erp.hrm.suite.board', [
            'title' => 'Training sessions',
            'intro' => 'Safety training uses kind Safety. Expiry dates show on the documents report.',
            'store' => route('hr.training.store'),
            'fields' => $this->fields([
                ['employee_id', 'Employee', 'employee'],
                ['title', 'Session', 'text'],
                ['kind', 'Kind', 'select', ['skill' => 'Skill', 'safety' => 'Safety']],
                ['session_date', 'Date', 'date'],
                ['certificate_no', 'Certificate', 'text'],
                ['expires_on', 'Expires', 'date'],
            ]),
            'columns' => ['Employee', 'Session', 'Kind', 'Date', 'Certificate', 'Expires'],
            'records' => $this->pageOf($rows, ['employees.name', 'training_records.*'], fn ($row) => [$row->name, $row->title, $row->kind, $row->session_date, $row->certificate_no, $row->expires_on]),
            'links' => [['Skills', route('hr.skills.index')], ['Safety', route('hr.safety.index')]],
        ]);
    }

    public function storeTraining(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'title' => 'required|string|max:255',
            'kind' => 'required|in:skill,safety',
            'session_date' => 'required|date',
            'certificate_no' => 'nullable|string|max:100',
            'expires_on' => 'nullable|date',
        ]);
        DB::table('training_records')->insert($data + ['attended' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->audit('Recorded training');

        return back()->with('success', 'Training attendance saved.');
    }

    public function manpower()
    {
        $today = now()->toDateString();
        $requirements = DB::table('line_requirements')->join('production_lines', 'production_lines.id', '=', 'line_requirements.production_line_id')->get(['line_requirements.*', 'production_lines.name as line_name']);
        $presentIds = AttendanceDaily::query()->whereDate('date', $today)->where(function ($q) {
            $q->whereIn('status', ['present', 'late'])->orWhereNotNull('first_in');
        })->pluck('employee_id');
        $lines = [];
        foreach ($requirements as $req) {
            $available = Employee::query()->where('production_line_id', $req->production_line_id)->where('status', '!=', 'inactive')->whereIn('id', $presentIds)->count();
            $skilled = $req->skill_name ? DB::table('employee_skills')
                ->join('employees', 'employees.id', '=', 'employee_skills.employee_id')
                ->where('employees.production_line_id', $req->production_line_id)
                ->where(function ($q) use ($req) {
                    $q->where('employee_skills.category', $req->skill_name)->orWhere('employee_skills.operation', $req->skill_name);
                })
                ->distinct()
                ->count('employee_skills.employee_id') : null;
            $lines[] = [$req->line_name, $req->required_count, $available, max(0, $req->required_count - $available), $req->skill_name ?: '—', $skilled ?? '—'];
        }
        $assignments = DB::table('staff_assignments')->join('employees', 'employees.id', '=', 'staff_assignments.employee_id')->leftJoin('production_lines', 'production_lines.id', '=', 'staff_assignments.production_line_id')->orderByDesc('work_date');

        return view('erp.hrm.suite.board', [
            'title' => 'Manpower',
            'intro' => 'Required headcount is compared with employees marked present or late today on that line. Absent cover is a replacement assignment.',
            'store' => route('hr.manpower.store'),
            'fields' => $this->fields([
                ['production_line_id', 'Line', 'line'],
                ['required_count', 'Required employees', 'number'],
                ['skill_name', 'Required skill', 'text'],
                ['employee_id', 'Assign employee', 'employee'],
                ['work_date', 'Date', 'date'],
                ['kind', 'Assignment', 'select', ['replacement' => 'Replacement', 'temporary' => 'Temporary', 'transfer' => 'Transfer']],
            ]),
            'columns' => ['Line', 'Required', 'Present today', 'Short', 'Skill', 'People with skill'],
            'records' => $this->pageList($lines),
            'extraTitle' => 'Assignments',
            'extraColumns' => ['Employee', 'Line', 'Date', 'Kind'],
            'extraRecords' => $this->pageOf($assignments, ['employees.name', 'production_lines.name as line_name', 'staff_assignments.*'], fn ($row) => [$row->name, $row->line_name, $row->work_date, $row->kind], 'extra'),
            'links' => [['Attendance', route('attendance.index')], ['Roster', route('roster.index')]],
        ]);
    }

    public function storeManpower(Request $request)
    {
        $data = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'required_count' => 'required|integer|min:0',
            'skill_name' => 'nullable|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'work_date' => 'nullable|date',
            'kind' => 'nullable|in:replacement,temporary,transfer',
        ]);
        DB::table('line_requirements')->updateOrInsert(
            ['production_line_id' => $data['production_line_id'], 'skill_name' => $data['skill_name']],
            ['required_count' => $data['required_count'], 'updated_at' => now(), 'created_at' => now()]
        );
        if (! empty($data['employee_id'])) {
            DB::table('staff_assignments')->insert([
                'employee_id' => $data['employee_id'],
                'production_line_id' => $data['production_line_id'],
                'work_date' => $data['work_date'] ?: now()->toDateString(),
                'kind' => $data['kind'] ?: 'temporary',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($data['kind'] === 'transfer') {
                Employee::where('id', $data['employee_id'])->update(['production_line_id' => $data['production_line_id']]);
            }
        }
        $this->audit('Updated manpower');

        return back()->with('success', 'Line requirement saved.');
    }

    public function recruitment()
    {
        $vacancies = DB::table('job_vacancies')->leftJoin('production_lines', 'production_lines.id', '=', 'job_vacancies.production_line_id')->orderByDesc('job_vacancies.id');
        $applicants = DB::table('job_applicants')->join('job_vacancies', 'job_vacancies.id', '=', 'job_applicants.job_vacancy_id')->orderByDesc('job_applicants.id');

        return view('erp.hrm.suite.board', [
            'title' => 'Recruitment',
            'intro' => 'Selected applicants can be opened in Add Employee. Joining documents stay on the employee profile.',
            'store' => route('hr.recruitment.store'),
            'fields' => [
                ['name' => 'title', 'label' => 'Vacancy', 'type' => 'text'],
                ['name' => 'production_line_id', 'label' => 'Line', 'type' => 'line', 'options' => $this->lineOptions()],
                ['name' => 'skill_name', 'label' => 'Skill', 'type' => 'text'],
                ['name' => 'applicant_name', 'label' => 'Applicant', 'type' => 'text'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                ['name' => 'stage', 'label' => 'Stage', 'type' => 'select', 'options' => ['applied' => 'Applied', 'interview' => 'Interview', 'practical' => 'Practical test', 'selected' => 'Selected', 'rejected' => 'Rejected']],
            ],
            'columns' => ['Vacancy', 'Line', 'Skill', 'Status'],
            'records' => $this->pageOf($vacancies, ['job_vacancies.*', 'production_lines.name as line_name'], fn ($row) => [$row->title, $row->line_name, $row->skill_name, $row->status]),
            'extraTitle' => 'Applicants',
            'extraColumns' => ['Name', 'Vacancy', 'Stage', ''],
            'extraRecords' => $this->pageOf($applicants, ['job_applicants.*', 'job_vacancies.title'], fn ($row) => [
                $row->name,
                $row->title,
                $row->stage,
                $row->stage === 'selected' ? '<a class="btn btn-sm btn-outline-primary" href="'.route('employees.create', ['name' => $row->name, 'contact' => $row->phone]).'">Create employee</a>' : '',
            ], 'extra'),
            'links' => [['Employees', route('employees.index')]],
        ]);
    }

    public function storeRecruitment(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'production_line_id' => 'nullable|exists:production_lines,id',
            'skill_name' => 'nullable|string|max:255',
            'applicant_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:40',
            'stage' => 'nullable|string|max:30',
        ]);
        $vacancyId = DB::table('job_vacancies')->insertGetId([
            'title' => $data['title'],
            'production_line_id' => $data['production_line_id'] ?? null,
            'skill_name' => $data['skill_name'] ?? null,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if (! empty($data['applicant_name'])) {
            DB::table('job_applicants')->insert([
                'job_vacancy_id' => $vacancyId,
                'name' => $data['applicant_name'],
                'phone' => $data['phone'] ?? null,
                'stage' => $data['stage'] ?: 'applied',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->audit('Recruitment entry');

        return back()->with('success', 'Vacancy saved.');
    }

    public function performance()
    {
        $rows = DB::table('performance_reviews')->join('employees', 'employees.id', '=', 'performance_reviews.employee_id')->orderByDesc('performance_reviews.id');

        return view('erp.hrm.suite.board', [
            'title' => 'Performance',
            'intro' => 'Scores are 1 to 5. A promotion or increment recommendation is kept with the review history.',
            'store' => route('hr.performance.store'),
            'fields' => $this->fields([
                ['employee_id', 'Employee', 'employee'],
                ['period', 'Period', 'text'],
                ['attendance_score', 'Attendance', 'number'],
                ['quality_score', 'Quality', 'number'],
                ['productivity_score', 'Productivity', 'number'],
                ['teamwork_score', 'Teamwork', 'number'],
                ['recommendation', 'Recommendation', 'select', ['none' => 'None', 'promotion' => 'Promotion', 'increment' => 'Increment']],
                ['comments', 'Comments', 'textarea'],
            ]),
            'columns' => ['Employee', 'Period', 'Att', 'Quality', 'Output', 'Team', 'Recommendation'],
            'records' => $this->pageOf($rows, ['employees.name', 'performance_reviews.*'], fn ($row) => [$row->name, $row->period, $row->attendance_score, $row->quality_score, $row->productivity_score, $row->teamwork_score, $row->recommendation]),
            'links' => [['Employees', route('employees.index')]],
        ]);
    }

    public function storePerformance(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period' => 'required|string|max:20',
            'attendance_score' => 'required|integer|min:1|max:5',
            'quality_score' => 'required|integer|min:1|max:5',
            'productivity_score' => 'required|integer|min:1|max:5',
            'teamwork_score' => 'required|integer|min:1|max:5',
            'recommendation' => 'required|in:none,promotion,increment',
            'comments' => 'nullable|string|max:2000',
        ]);
        DB::table('performance_reviews')->insert($data + ['reviewer_id' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
        $this->audit('Performance review');

        return back()->with('success', 'Review saved.');
    }

    public function welfare()
    {
        $rows = DB::table('welfare_records')->join('employees', 'employees.id', '=', 'welfare_records.employee_id')->orderByDesc('welfare_records.id');

        return view('erp.hrm.suite.board', [
            'title' => 'Welfare',
            'intro' => 'A deduction amount is added to payroll as a loan-style balance so it is taken once per month until cleared.',
            'store' => route('hr.welfare.store'),
            'fields' => $this->fields([
                ['employee_id', 'Employee', 'employee'],
                ['kind', 'Benefit', 'select', ['transport' => 'Transport', 'meal' => 'Meal', 'uniform' => 'Uniform', 'ppe' => 'PPE', 'medical' => 'Medical', 'hostel' => 'Hostel', 'item' => 'Company item']],
                ['detail', 'Detail', 'text'],
                ['issued_on', 'Issued', 'date'],
                ['returned_on', 'Returned', 'date'],
                ['deduction_amount', 'Monthly deduction', 'number'],
            ]),
            'columns' => ['Employee', 'Kind', 'Detail', 'Issued', 'Returned', 'Deduction'],
            'records' => $this->pageOf($rows, ['employees.name', 'welfare_records.*'], fn ($row) => [$row->name, $row->kind, $row->detail, $row->issued_on, $row->returned_on, $row->deduction_amount]),
            'links' => [['Payroll', route('payroll-runs.index')]],
        ]);
    }

    public function storeWelfare(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'kind' => 'required|string|max:30',
            'detail' => 'required|string|max:255',
            'issued_on' => 'required|date',
            'returned_on' => 'nullable|date',
            'deduction_amount' => 'nullable|numeric|min:0',
        ]);
        DB::table('welfare_records')->insert($data + ['deduction_amount' => $data['deduction_amount'] ?? 0, 'created_at' => now(), 'updated_at' => now()]);
        if ((float) ($data['deduction_amount'] ?? 0) > 0) {
            EmployeePayDeduction::create([
                'employee_id' => $data['employee_id'],
                'kind' => 'welfare',
                'title' => ucfirst($data['kind']).': '.$data['detail'],
                'total_amount' => $data['deduction_amount'],
                'monthly_amount' => $data['deduction_amount'],
                'balance' => $data['deduction_amount'],
                'is_active' => true,
            ]);
        }
        $this->audit('Welfare record');

        return back()->with('success', 'Welfare record saved.');
    }

    public function safety()
    {
        $this->sensitiveOrAbort();
        $rows = DB::table('safety_incidents')->leftJoin('employees', 'employees.id', '=', 'safety_incidents.employee_id')->orderByDesc('incident_date');

        return view('erp.hrm.suite.board', [
            'title' => 'Safety and incidents',
            'intro' => 'Medical notes are visible only to HR admins and payroll officers.',
            'store' => route('hr.safety.store'),
            'fields' => $this->fields([
                ['incident_date', 'Date', 'date'],
                ['place', 'Place', 'text'],
                ['employee_id', 'Employee', 'employee'],
                ['kind', 'Type', 'select', ['accident' => 'Accident', 'near_miss' => 'Near miss']],
                ['first_aid', 'First aid', 'textarea'],
                ['follow_up', 'Follow-up', 'textarea'],
                ['corrective_action', 'Corrective action', 'textarea'],
                ['responsible_name', 'Responsible person', 'text'],
                ['medical_note', 'Medical note', 'textarea'],
            ]),
            'columns' => ['Date', 'Place', 'Employee', 'Type', 'Follow-up'],
            'records' => $this->pageOf($rows, ['employees.name', 'safety_incidents.*'], fn ($row) => [$row->incident_date, $row->place, $row->name, $row->kind, $row->follow_up]),
            'links' => [['Training', route('hr.training.index')]],
        ]);
    }

    public function storeSafety(Request $request)
    {
        $this->sensitiveOrAbort();
        $data = $request->validate([
            'incident_date' => 'required|date',
            'place' => 'required|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'kind' => 'required|in:accident,near_miss',
            'first_aid' => 'nullable|string|max:2000',
            'follow_up' => 'nullable|string|max:2000',
            'corrective_action' => 'nullable|string|max:2000',
            'responsible_name' => 'nullable|string|max:255',
            'medical_note' => 'nullable|string|max:2000',
        ]);
        DB::table('safety_incidents')->insert($data + ['created_at' => now(), 'updated_at' => now()]);
        $this->audit('Safety incident');

        return back()->with('success', 'Incident recorded.');
    }

    public function grievances()
    {
        $this->sensitiveOrAbort();
        $rows = DB::table('grievances')->join('employees', 'employees.id', '=', 'grievances.employee_id')->orderByDesc('grievances.id');

        return view('erp.hrm.suite.board', [
            'title' => 'Grievances and discipline',
            'intro' => 'These records are limited to HR admins and payroll officers.',
            'store' => route('hr.grievances.store'),
            'fields' => $this->fields([
                ['employee_id', 'Employee', 'employee'],
                ['summary', 'Complaint or warning', 'textarea'],
                ['status', 'Status', 'select', ['open' => 'Open', 'investigating' => 'Investigating', 'warning' => 'Warning', 'resolved' => 'Resolved']],
                ['resolution', 'Resolution', 'textarea'],
                ['employee_response', 'Employee response', 'textarea'],
            ]),
            'columns' => ['Employee', 'Status', 'Summary'],
            'records' => $this->pageOf($rows, ['employees.name', 'grievances.*'], fn ($row) => [$row->name, $row->status, $row->summary]),
            'links' => [],
        ]);
    }

    public function storeGrievance(Request $request)
    {
        $this->sensitiveOrAbort();
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'summary' => 'required|string|max:2000',
            'status' => 'required|string|max:30',
            'resolution' => 'nullable|string|max:2000',
            'employee_response' => 'nullable|string|max:2000',
        ]);
        DB::table('grievances')->insert($data + ['handler_id' => auth()->id(), 'created_at' => now(), 'updated_at' => now()]);
        $this->audit('Grievance');

        return back()->with('success', 'Record saved.');
    }

    public function letters()
    {
        $rows = DB::table('hr_letters')->leftJoin('employees', 'employees.id', '=', 'hr_letters.employee_id')->orderByDesc('hr_letters.id');

        return view('erp.hrm.suite.board', [
            'title' => 'Documents and letters',
            'intro' => 'Contracts, confirmations, salary revisions, and policies. Expiring items are listed in Reports.',
            'store' => route('hr.letters.store'),
            'fields' => $this->fields([
                ['employee_id', 'Employee', 'employee'],
                ['kind', 'Type', 'select', ['contract' => 'Contract', 'appointment' => 'Appointment', 'confirmation' => 'Confirmation', 'salary' => 'Salary revision', 'promotion' => 'Promotion', 'service' => 'Service letter', 'policy' => 'Policy']],
                ['title', 'Title', 'text'],
                ['issued_on', 'Issued', 'date'],
                ['expires_on', 'Expires', 'date'],
                ['body', 'Body', 'textarea'],
            ]),
            'columns' => ['Employee', 'Type', 'Title', 'Issued', 'Expires'],
            'records' => $this->pageOf($rows, ['employees.name', 'hr_letters.*'], fn ($row) => [$row->name, $row->kind, $row->title, $row->issued_on, $row->expires_on]),
            'links' => [['Employees', route('employees.index')]],
        ]);
    }

    public function storeLetter(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'kind' => 'required|string|max:30',
            'title' => 'required|string|max:255',
            'issued_on' => 'required|date',
            'expires_on' => 'nullable|date',
            'body' => 'nullable|string|max:5000',
        ]);
        DB::table('hr_letters')->insert($data + ['created_at' => now(), 'updated_at' => now()]);
        $this->audit('HR letter');

        return back()->with('success', 'Letter saved.');
    }

    public function resignations()
    {
        $rows = DB::table('resignations')->join('employees', 'employees.id', '=', 'resignations.employee_id')->orderByDesc('resignations.id');

        return view('erp.hrm.suite.board', [
            'title' => 'Resignation and offboarding',
            'intro' => 'Confirming a resignation deactivates the employee and keeps their attendance and payroll history.',
            'store' => route('hr.resignations.store'),
            'fields' => $this->fields([
                ['employee_id', 'Employee', 'employee'],
                ['requested_on', 'Requested', 'date'],
                ['last_day', 'Last working day', 'date'],
                ['exit_notes', 'Exit interview', 'textarea'],
            ]),
            'columns' => ['Employee', 'Last day', 'Assets', 'Loans', 'Access', 'Status'],
            'records' => $this->pageOf($rows, ['employees.name', 'employees.status as employee_status', 'resignations.*'], fn ($row) => [
                $row->name,
                $row->last_day,
                $row->assets_returned ? 'Yes' : 'No',
                $row->loans_cleared ? 'Yes' : 'No',
                $row->access_disabled ? 'Disabled' : 'Open',
                $row->confirmed ? 'Deactivated' : '<form method="POST" action="'.route('hr.resignations.confirm', $row->id).'">'.csrf_field().method_field('PATCH').'<button class="btn btn-sm btn-outline-primary">Confirm exit</button></form>',
            ]),
            'links' => [['Employees', route('employees.index')], ['Payroll', route('payroll-runs.index')]],
        ]);
    }

    public function storeResignation(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'requested_on' => 'required|date',
            'last_day' => 'required|date',
            'exit_notes' => 'nullable|string|max:2000',
        ]);
        $openLoans = EmployeePayDeduction::where('employee_id', $data['employee_id'])->where('balance', '>', 0)->exists();
        DB::table('resignations')->insert($data + [
            'loans_cleared' => ! $openLoans,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->audit('Resignation request');

        return back()->with('success', $openLoans ? 'Resignation saved. Outstanding loans are still open.' : 'Resignation saved.');
    }

    public function confirmResignation(int $id)
    {
        $row = DB::table('resignations')->where('id', $id)->first();
        abort_unless($row, 404);
        DB::table('resignations')->where('id', $id)->update([
            'confirmed' => true,
            'assets_returned' => true,
            'access_disabled' => true,
            'updated_at' => now(),
        ]);
        $employee = Employee::find($row->employee_id);
        $employee?->update(['status' => 'inactive']);
        if ($employee?->user_id) {
            User::where('id', $employee->user_id)->update(['password' => Hash::make(Str::random(40))]);
            DB::table('sessions')->where('user_id', $employee->user_id)->delete();
        }
        $this->audit('Deactivated employee #'.$row->employee_id);

        return back()->with('success', 'Employee deactivated. History is kept.');
    }

    public function selfService()
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (! $employee) {
            return view('erp.hrm.suite.ess', ['employee' => null]);
        }
        $attendance = AttendanceDaily::where('employee_id', $employee->id)->latest('date')->paginate(12, ['*'], 'attendance')->withQueryString();
        $leaves = LeaveRequest::with('leaveType')->where('employee_id', $employee->id)->latest()->paginate(12, ['*'], 'leave')->withQueryString();
        $ots = OtRequest::where('employee_id', $employee->id)->latest('work_date')->paginate(12, ['*'], 'ot')->withQueryString();
        $payslips = $employee->payrollRunItems()->with('payrollRun')->latest()->paginate(12, ['*'], 'pay')->withQueryString();
        $letters = DB::table('hr_letters')->where(function ($q) use ($employee) {
            $q->where('employee_id', $employee->id)->orWhereNull('employee_id');
        })->latest('issued_on')->paginate(12, ['*'], 'letter')->withQueryString();
        $reviews = DB::table('performance_reviews')->where('employee_id', $employee->id)->latest()->paginate(12, ['*'], 'review')->withQueryString();

        return view('erp.hrm.suite.ess', compact('employee', 'attendance', 'leaves', 'ots', 'payslips', 'letters', 'reviews'));
    }

    public function storeSelfRequest(Request $request)
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        abort_unless($employee, 403);
        $data = $request->validate([
            'change_type' => 'required|in:personal,attendance_correction',
            'summary' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        DB::table('employee_changes')->insert([
            'employee_id' => $employee->id,
            'change_type' => $data['change_type'],
            'effective_date' => now()->toDateString(),
            'summary' => $data['summary'],
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->audit('Employee request: '.$data['change_type']);

        return back()->with('success', 'Request sent to HR.');
    }

    public function acknowledgeReview(int $id)
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        abort_unless($employee, 403);
        $updated = DB::table('performance_reviews')->where('id', $id)->where('employee_id', $employee->id)
            ->update(['acknowledged' => true, 'updated_at' => now()]);
        abort_unless($updated, 404);

        return back()->with('success', 'Review acknowledged.');
    }

    public function acknowledgeLetter(int $id)
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        abort_unless($employee, 403);
        $updated = DB::table('hr_letters')->where('id', $id)->where('employee_id', $employee->id)
            ->update(['acknowledged' => true, 'updated_at' => now()]);
        abort_unless($updated, 404);

        return back()->with('success', 'Document acknowledged.');
    }

    public function reports()
    {
        $headcount = Employee::where('status', '!=', 'inactive')->count();
        $today = now()->toDateString();
        $present = AttendanceDaily::whereDate('date', $today)->where(function ($q) {
            $q->whereIn('status', ['present', 'late'])->orWhereNotNull('first_in');
        })->distinct('employee_id')->count('employee_id');
        $absent = AttendanceDaily::whereDate('date', $today)->where('status', 'absent')->distinct('employee_id')->count('employee_id');
        $late = AttendanceDaily::whereDate('date', $today)->where('status', 'late')->distinct('employee_id')->count('employee_id');
        $missing = AttendanceDaily::whereDate('date', $today)->whereNotNull('first_in')->whereNull('last_out')->count();
        $otCost = (float) OtRequest::where('status', 'approved')->whereMonth('work_date', now()->month)->sum('amount');
        $otHours = (float) OtRequest::where('status', 'approved')->whereMonth('work_date', now()->month)->sum('approved_hours');
        $turnover = DB::table('resignations')->where('confirmed', true)->whereYear('last_day', now()->year)->count();
        $incentives = (float) IncentiveClaim::where('status', 'approved')->whereMonth('work_date', now()->month)->sum('amount');
        $early = AttendanceDaily::whereDate('date', $today)->where('early_leave_minutes', '>', 0)->distinct()->count('employee_id');
        $epf = (float) DB::table('payroll_run_items')->whereMonth('created_at', now()->month)->sum('epf_amount');
        $etf = (float) DB::table('payroll_run_items')->whereMonth('created_at', now()->month)->sum('etf_amount');
        $vacancies = DB::table('job_vacancies')->where('status', 'open')->count();
        $training = DB::table('training_records')->whereMonth('session_date', now()->month)->count();
        $expiring = DB::table('hr_letters')->whereNotNull('expires_on')->whereDate('expires_on', '<=', now()->addDays(30)->toDateString())->count();
        $labour = DB::table('payroll_run_items')
            ->join('employees', 'employees.id', '=', 'payroll_run_items.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->selectRaw("coalesce(departments.name, 'No department') as department, sum(payroll_run_items.net_salary) as cost")
            ->groupBy('departments.name')
            ->orderByDesc('cost')
            ->limit(8)
            ->get();

        return view('erp.hrm.suite.reports', compact('headcount', 'present', 'absent', 'late', 'missing', 'otCost', 'otHours', 'turnover', 'incentives', 'early', 'epf', 'etf', 'vacancies', 'training', 'expiring', 'labour'));
    }

    public function exportReport(string $type)
    {
        $lines = ['report,value'];
        if ($type === 'headcount') {
            foreach (Employee::with('department', 'productionLine')->where('status', '!=', 'inactive')->orderBy('name')->get() as $employee) {
                $lines[] = implode(',', [$employee->employee_code, '"'.($employee->display_name ?: $employee->name).'"', '"'.($employee->department?->name).'"', '"'.($employee->productionLine?->name).'"']);
            }
        } elseif ($type === 'attendance') {
            $lines = ['date,employee,status,in,out'];
            foreach (AttendanceDaily::with('employee')->whereDate('date', now()->toDateString())->get() as $row) {
                $lines[] = implode(',', [$row->date->toDateString(), '"'.($row->employee?->name).'"', $row->status, optional($row->first_in)->format('H:i'), optional($row->last_out)->format('H:i')]);
            }
        } else {
            $lines = ['metric,value', 'ot_cost,'.OtRequest::where('status', 'approved')->sum('amount'), 'incentive,'.IncentiveClaim::where('status', 'approved')->sum('amount')];
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="hr-'.$type.'.csv"',
        ]);
    }

    public function settings()
    {
        $logs = DB::table('hr_audit_logs')->leftJoin('users', 'users.id', '=', 'hr_audit_logs.user_id')->orderByDesc('hr_audit_logs.id')->paginate(12, ['hr_audit_logs.*', 'users.name'], 'log')->withQueryString();
        $imports = DB::table('attendance_imports')->orderByDesc('id')->paginate(12, ['*'], 'import')->withQueryString();

        return view('erp.hrm.suite.settings', [
            'timezone' => config('app.timezone'),
            'logs' => $logs,
            'imports' => $imports,
        ]);
    }

    private function fields(array $defs): array
    {
        return array_map(function (array $def) {
            $field = ['name' => $def[0], 'label' => $def[1], 'type' => $def[2]];
            if ($def[2] === 'select') {
                $field['options'] = $def[3];
            }
            if ($def[2] === 'employee') {
                $field['options'] = $this->employeeOptions();
            }
            if ($def[2] === 'line') {
                $field['options'] = $this->lineOptions();
            }

            return $field;
        }, $defs);
    }

    private function employeeOptions(): array
    {
        return Employee::query()->where('status', '!=', 'inactive')
            ->when($this->supervisorLineId(), fn ($q, $lineId) => $q->where('production_line_id', $lineId))
            ->orderBy('name')->get()
            ->mapWithKeys(fn ($employee) => [$employee->id => $employee->display_name ?: $employee->name])
            ->all();
    }

    private function lineOptions(): array
    {
        return ProductionLine::query()
            ->when($this->supervisorLineId(), fn ($q, $lineId) => $q->where('id', $lineId))
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    private function supervisorLineId(): ?int
    {
        $employee = auth()->user()?->employee;
        if ($employee && $employee->role === 'line_supervisor' && $employee->production_line_id) {
            return (int) $employee->production_line_id;
        }

        return null;
    }

    private function canSeeSensitive(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSystemAdmin() || $user->can_access('hrm.payroll') || $user->can_access('employees.create'));
    }

    private function sensitiveOrAbort(): void
    {
        abort_unless($this->canSeeSensitive(), 403);
    }

    private function pageOf($query, array $columns, callable $map, string $pageName = 'page')
    {
        $page = $query->paginate(12, $columns, $pageName)->withQueryString();
        $page->setCollection($page->getCollection()->map($map)->values());

        return $page;
    }

    private function pageList(iterable $items, string $pageName = 'page'): LengthAwarePaginator
    {
        $items = collect($items)->values();
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return (new LengthAwarePaginator(
            $items->forPage($page, 12)->values(),
            $items->count(),
            12,
            $page,
            ['path' => request()->url(), 'pageName' => $pageName]
        ))->withQueryString();
    }

    private function audit(string $action): void
    {
        DB::table('hr_audit_logs')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject' => 'hr',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
