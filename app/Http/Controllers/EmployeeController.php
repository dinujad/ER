<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeBonus;
use App\Models\EmployeeChange;
use App\Models\EmployeeDocument;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
use App\Services\EmployeeMonthAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'defaultShift'])->orderBy('name');
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('first_name', 'like', '%'.$q.'%')
                    ->orWhere('last_name', 'like', '%'.$q.'%')
                    ->orWhere('hikvision_person_id', 'like', '%'.$q.'%')
                    ->orWhere('nic', 'like', '%'.$q.'%');
            });
        }
        $employees = $query->get();
        $roles = Role::orderBy('label')->get();
        return view('erp.employees.index', compact('employees', 'roles'));
    }

    public function show($id, EmployeeMonthAttendance $attendance)
    {
        $employee = Employee::with([
            'department',
            'designation',
            'defaultShift',
            'reportingManager',
            'productionLine',
            'documents',
            'changes.creator',
            'leaveRequests.leaveType',
            'payrollRunItems.payrollRun',
            'bonuses',
        ])->findOrFail($id);
        $month = Carbon::parse(request('month', now()->format('Y-m')).'-01')->startOfMonth();
        $summary = $attendance->summarize($employee, $month);
        $shifts = Shift::orderBy('name')->get();
        return view('erp.employees.show', compact('employee', 'summary', 'month', 'shifts'));
    }

    public function create()
    {
        $roles = Role::all();
        $lookups = $this->formLookups();
        return view('erp.employees.create', array_merge(compact('roles'), $lookups));
    }

    public function store(Request $request)
    {
        try {
            $this->normalizeHrInput($request);
            $this->fillNameFromParts($request);
            $request->merge([
                'hikvision_person_id' => Employee::normalizeHikvisionPersonId($request->input('hikvision_person_id')),
            ]);
            $request->validate(array_merge([
                'name' => 'required',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'gender' => 'nullable|in:male,female',
                'notes' => 'nullable|string|max:5000',
                'hikvision_person_id' => 'nullable|string|max:64|unique:employees,hikvision_person_id',
                'hikvision_valid_from' => 'nullable|date',
                'hikvision_valid_to' => 'nullable|date',
                'nic' => 'required|unique:employees,nic',
                'email' => 'nullable|email|unique:employees,email',
                'contact' => 'required',
                'birthday' => 'required|date|before:today',
                'role' => 'required',
                'join_date' => 'required|date',
                'photo' => 'nullable|image|max:2048',
            ], $this->hrRules()), [
                'birthday.required' => 'Birthday is required.',
                'birthday.before' => 'Birthday must be a valid past date.',
            ]);

            $data = array_merge($request->only([
                'name', 'first_name', 'last_name', 'gender', 'email', 'nic', 'contact', 'birthday', 'role', 'join_date', 'notes',
                'hikvision_person_id', 'hikvision_valid_from', 'hikvision_valid_to',
            ]), $this->hrPayload($request), ['status' => 'active']);
            if (! empty($data['hikvision_person_id'])) {
                $data['hikvision_source'] = Employee::HIK_SOURCE_HIK_CONNECT;
                $enrollTaken = Employee::query()->where('device_enroll_id', $data['hikvision_person_id'])->exists();
                if (! $enrollTaken) {
                    $data['device_enroll_id'] = $data['hikvision_person_id'];
                }
            }
            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('employees', 'public');
            }
            $employee = Employee::create($data);
            if (! $employee->employee_code) {
                $employee->update(['employee_code' => Employee::codeForId($employee->id)]);
            }

            // Auto-create User account for Supervisors
            if ($request->role === 'line_supervisor' && $request->email) {
                
                // Check if User already exists
                $existingUser = \App\Models\User::where('email', $request->email)->first();

                if ($existingUser) {
                    $employee->update(['user_id' => $existingUser->id]);
                } else {
                    $password = \Str::random(8);
                    
                    $user = \App\Models\User::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
                    ]);

                    $employee->update(['user_id' => $user->id]);

                    try {
                        \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\SupervisorWelcomeEmail($request->email, $password));
                    } catch (\Exception $ex) {
                         // Email fail is non-critical
                         \Log::error('Email failed: ' . $ex->getMessage());
                    }
                }
            }

            return redirect()->route('employees.show', $employee->id)->with('success', 'Employee created. Add documents or payroll details from this profile if they are still blank.');

        } catch (\Exception $e) {
            report($e);

            return back()->withInput()->with('error', 'Failed to create employee: ' . $e->getMessage());
        }
    }
    public function edit($id)
    {
        $employee = Employee::with(['documents', 'changes.creator'])->findOrFail($id);
        $roles = Role::orderBy('label')->get()->pluck('label', 'name')->toArray();
        $lookups = $this->formLookups($employee);
        return view('erp.employees.edit', array_merge(compact('employee', 'roles'), $lookups));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'device_enroll_id' => trim((string) ($request->input('device_enroll_id') ?? '')) !== '' ? trim($request->device_enroll_id) : null,
            'hikvision_person_id' => Employee::normalizeHikvisionPersonId($request->input('hikvision_person_id')),
        ]);
        $this->normalizeHrInput($request);
        $this->fillNameFromParts($request);
        $request->validate(array_merge([
            'name' => 'required',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'notes' => 'nullable|string|max:5000',
            'hikvision_valid_from' => 'nullable|date',
            'hikvision_valid_to' => 'nullable|date',
            'nic' => 'required|unique:employees,nic,' . $id,
            'email' => 'nullable|email|unique:employees,email,' . $id,
            'contact' => 'required',
            'birthday' => 'nullable|date|before:today',
            'role' => 'required',
            'join_date' => 'required|date',
            'device_enroll_id' => 'nullable|string|max:50|unique:employees,device_enroll_id,' . $id,
            'hikvision_person_id' => 'nullable|string|max:64|unique:employees,hikvision_person_id,' . $id,
            'photo' => 'nullable|image|max:2048',
        ], $this->hrRules($id)), [
            'birthday.before' => 'Birthday must be a valid past date.',
        ]);

        $employee = Employee::with(['department', 'designation', 'productionLine'])->findOrFail($id);
        $before = $employee->replicate();
        $data = array_merge($request->only([
            'name', 'first_name', 'last_name', 'gender', 'email', 'nic', 'contact', 'birthday', 'role', 'join_date', 'notes',
            'device_enroll_id', 'hikvision_person_id', 'hikvision_valid_from', 'hikvision_valid_to',
        ]), $this->hrPayload($request));
        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            $data['photo'] = $request->file('photo')->store('employees', 'public');
        }
        if (! $data['employee_code']) {
            $data['employee_code'] = $employee->employee_code ?: Employee::codeForId($employee->id);
        }
        if (! empty($data['hikvision_person_id'])) {
            $data['hikvision_source'] = $employee->hikvision_source ?: Employee::HIK_SOURCE_HIK_CONNECT;
        } else {
            $data['hikvision_person_id'] = null;
        }
        $employee->update($data);
        $this->recordProfileChanges($before, $employee->fresh(['department', 'designation', 'productionLine']));

        // If role changed to/from supervisor, we might need to handle User creation/deletion logic, 
        // but for now keeping it simple: just update profile. 
        // If email changed, we update linked User email too if exists.
        if ($employee->user) {
            $employee->user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
        }

        return redirect()->route('employees.show', $employee->id)->with('success', 'Employee profile saved.');
    }

    public function updateStatus(Request $request, Employee $employee)
    {
        $request->validate(['status' => 'required|in:active,inactive']);
        $employee->update(['status' => $request->status]);
        $label = $request->status === 'inactive' ? 'deactivated' : 'activated';

        return back()->with('success', ($employee->display_name ?: $employee->name).' '.$label.'.');
    }

    public function storeDocument(Request $request, Employee $employee)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $file = $request->file('file');
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'title' => $request->title,
            'file_path' => $file->store('employees/documents', 'public'),
            'original_name' => $file->getClientOriginalName(),
        ]);

        return redirect()->route('employees.edit', $employee->id)->with('success', 'Document added.');
    }

    public function destroyDocument(Employee $employee, EmployeeDocument $document)
    {
        if ((int) $document->employee_id !== (int) $employee->id) {
            abort(404);
        }
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    public function storeChange(Request $request, Employee $employee)
    {
        $request->validate([
            'change_type' => 'required|in:transfer,promotion,salary,other',
            'effective_date' => 'required|date',
            'summary' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        EmployeeChange::create([
            'employee_id' => $employee->id,
            'change_type' => $request->change_type,
            'effective_date' => $request->effective_date,
            'summary' => $request->summary,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('employees.show', $employee->id)->with('success', 'History entry added.');
    }

    public function assignShift(Request $request, Employee $employee)
    {
        $request->validate([
            'shift_id' => 'nullable|integer|exists:shifts,id',
        ]);
        $employee->update([
            'shift_id' => $request->filled('shift_id') ? (int) $request->shift_id : null,
        ]);

        return redirect()->route('employees.show', ['employee' => $employee->id, 'month' => $request->input('month')])
            ->with('success', 'Shift saved. Overtime on this profile uses that shift.');
    }

    public function storeBonus(Request $request, Employee $employee)
    {
        $request->validate([
            'type' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'bonus_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        EmployeeBonus::create([
            'employee_id' => $employee->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'bonus_date' => $request->bonus_date,
            'notes' => $request->notes,
        ]);
        return redirect()->route('employees.show', $employee->id)->with('success', 'Bonus added.');
    }

    public function updatePhoto(Request $request, Employee $employee)
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
        }
        $employee->update([
            'photo' => $request->file('photo')->store('employees', 'public'),
        ]);

        return redirect()->route('employees.show', $employee->id)->with('success', 'Photo updated.');
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        
        // Optional: Delete linked user if supervisor? 
        // Usually safer to keep user or soft delete, but for strict cleanup:
        if ($employee->user) {
            // $employee->user->delete(); // Uncomment if we want to delete login too
        }

        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    public function destroyBulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:employees,id',
        ]);
        $count = Employee::whereIn('id', $request->ids)->delete();
        return redirect()->route('employees.index')->with('success', $count . ' employee(s) deleted.');
    }

    private function fillNameFromParts(Request $request): void
    {
        $name = trim((string) $request->input('name'));
        if ($name !== '') {
            return;
        }
        $built = trim(((string) $request->input('first_name')).' '.((string) $request->input('last_name')));
        if ($built !== '') {
            $request->merge(['name' => $built]);
        }
    }

    private function formLookups(?Employee $employee = null): array
    {
        return [
            'departments' => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('title')->get(),
            'lines' => ProductionLine::orderBy('name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
            'managers' => Employee::query()
                ->when($employee, fn ($q) => $q->where('id', '!=', $employee->id))
                ->orderBy('name')
                ->get(['id', 'name', 'first_name', 'last_name', 'employee_code']),
        ];
    }

    private function hrRules(?int $ignoreId = null): array
    {
        return [
            'employee_code' => 'nullable|string|max:32|unique:employees,employee_code'.($ignoreId ? ','.$ignoreId : ''),
            'address' => 'nullable|string|max:2000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:40',
            'id_type' => 'nullable|in:nic,passport,driving_licence',
            'employment_type' => 'nullable|in:permanent,temporary,contract,trainee',
            'pay_type' => 'nullable|in:monthly,daily,hourly,piece_rate',
            'pay_rate' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'payroll_registration_no' => 'nullable|string|max:50',
            'department_id' => 'nullable|integer|exists:departments,id',
            'designation_id' => 'nullable|integer|exists:designations,id',
            'production_line_id' => 'nullable|integer|exists:production_lines,id',
            'shift_id' => 'nullable|integer|exists:shifts,id',
            'reporting_manager_id' => 'nullable|integer|exists:employees,id'.($ignoreId ? '|not_in:'.$ignoreId : ''),
        ];
    }

    private function normalizeHrInput(Request $request): void
    {
        $keys = [
            'employee_code', 'department_id', 'designation_id', 'production_line_id', 'shift_id',
            'reporting_manager_id', 'pay_rate', 'id_type', 'employment_type', 'pay_type', 'gender',
            'hikvision_valid_from', 'hikvision_valid_to',
        ];
        $merged = [];
        foreach ($keys as $key) {
            if ($request->input($key) === '' || $request->input($key) === null) {
                $merged[$key] = null;
            }
        }
        if (isset($merged['employee_code']) === false && is_string($request->input('employee_code'))) {
            $merged['employee_code'] = trim($request->input('employee_code')) ?: null;
        }
        $request->merge($merged);
    }

    private function hrPayload(Request $request): array
    {
        return [
            'employee_code' => $request->input('employee_code'),
            'address' => $request->input('address'),
            'emergency_contact_name' => $request->input('emergency_contact_name'),
            'emergency_contact_phone' => $request->input('emergency_contact_phone'),
            'id_type' => $request->input('id_type'),
            'employment_type' => $request->input('employment_type') ?: 'permanent',
            'pay_type' => $request->input('pay_type') ?: 'monthly',
            'pay_rate' => $request->input('pay_rate'),
            'bank_name' => $request->input('bank_name'),
            'bank_branch' => $request->input('bank_branch'),
            'bank_account_name' => $request->input('bank_account_name'),
            'bank_account_number' => $request->input('bank_account_number'),
            'payroll_registration_no' => $request->input('payroll_registration_no'),
            'department_id' => $request->input('department_id'),
            'designation_id' => $request->input('designation_id'),
            'production_line_id' => $request->input('production_line_id'),
            'shift_id' => $request->input('shift_id'),
            'reporting_manager_id' => $request->input('reporting_manager_id'),
        ];
    }

    private function recordProfileChanges(Employee $before, Employee $after): void
    {
        $lines = [];
        $payChanged = (string) $before->pay_type !== (string) $after->pay_type
            || (string) $before->pay_rate !== (string) $after->pay_rate;
        $titleChanged = (int) $before->designation_id !== (int) $after->designation_id;
        $placeChanged = (int) $before->department_id !== (int) $after->department_id
            || (int) $before->production_line_id !== (int) $after->production_line_id
            || (int) $before->reporting_manager_id !== (int) $after->reporting_manager_id;

        if ((int) $before->department_id !== (int) $after->department_id) {
            $lines[] = 'Department: '.($before->department?->name ?? '—').' → '.($after->department?->name ?? '—');
        }
        if ($titleChanged) {
            $lines[] = 'Designation: '.($before->designation?->title ?? '—').' → '.($after->designation?->title ?? '—');
        }
        if ((int) $before->production_line_id !== (int) $after->production_line_id) {
            $lines[] = 'Production line: '.($before->productionLine?->name ?? '—').' → '.($after->productionLine?->name ?? '—');
        }
        if ((int) $before->reporting_manager_id !== (int) $after->reporting_manager_id) {
            $lines[] = 'Reporting manager changed';
        }
        if ($payChanged) {
            $lines[] = 'Pay: '.($before->pay_type ?: '—').' '.($before->pay_rate ?? '—').' → '.($after->pay_type ?: '—').' '.($after->pay_rate ?? '—');
        }
        if ($lines === []) {
            return;
        }

        $kinds = (int) $payChanged + (int) $titleChanged + (int) $placeChanged;
        $type = 'other';
        if ($kinds === 1 && $payChanged) {
            $type = 'salary';
        } elseif ($kinds === 1 && $titleChanged) {
            $type = 'promotion';
        } elseif ($kinds === 1 && $placeChanged) {
            $type = 'transfer';
        }

        EmployeeChange::create([
            'employee_id' => $after->id,
            'change_type' => $type,
            'effective_date' => now()->toDateString(),
            'summary' => implode('; ', $lines),
            'created_by' => auth()->id(),
        ]);
    }
}
