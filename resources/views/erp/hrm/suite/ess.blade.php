@extends('erp.layout')

@section('content')
<h4 class="mb-3">My HR</h4>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(!$employee)
    <div class="alert alert-warning">This login is not linked to an employee. Set the employee user on the employee record to open self-service.</div>
@else
    <p class="text-muted">{{ $employee->display_name ?: $employee->name }} · {{ $employee->employee_code }}</p>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm"><div class="card-header"><h6 class="mb-0">Attendance</h6></div>
                <table class="table mb-0"><tbody>
                    @forelse($attendance as $row)
                        <tr><td>{{ $row->date->format('d M') }}</td><td>{{ $row->status }}</td><td>{{ $row->first_in?->format('H:i') }}</td><td>{{ $row->last_out?->format('H:i') ?? '—' }}</td></tr>
                    @empty
                        <tr><td class="text-muted">No attendance yet.</td></tr>
                    @endforelse
                </tbody></table>
                <div class="card-footer bg-white">{{ $attendance->links() }}</div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-3"><div class="card-header"><h6 class="mb-0">Leave and OT</h6></div>
                <ul class="list-group list-group-flush">
                    @foreach($leaves as $leave)<li class="list-group-item">Leave {{ $leave->start_date->format('d M') }} · {{ $leave->status }}</li>@endforeach
                    @foreach($ots as $ot)<li class="list-group-item">OT {{ $ot->work_date->format('d M') }} · {{ $ot->planned_hours }}h · {{ $ot->status }}</li>@endforeach
                </ul>
                <div class="card-footer bg-white">{{ $leaves->links() }} {{ $ots->links() }}</div>
            </div>
            <div class="card border-0 shadow-sm"><div class="card-header"><h6 class="mb-0">Payslips and letters</h6></div>
                <ul class="list-group list-group-flush">
                    @foreach($payslips as $item)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $item->payrollRun?->title }}</span><a href="{{ route('payroll-runs.payslip', [$item->payroll_run_id, $item->id]) }}">Open</a></li>
                    @endforeach
                    @foreach($letters as $letter)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $letter->title }} · {{ $letter->kind }}{{ $letter->acknowledged ? ' · acknowledged' : '' }}</span>
                            @if($letter->employee_id && ! $letter->acknowledged)
                                <form method="POST" action="{{ route('hr.letters.acknowledge', $letter->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Acknowledge</button></form>
                            @endif
                        </li>
                    @endforeach
                    @foreach($reviews as $review)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Review {{ $review->period }} · {{ $review->recommendation }}</span>
                            @if(! $review->acknowledged)
                                <form method="POST" action="{{ route('hr.reviews.acknowledge', $review->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Acknowledge</button></form>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="card-footer bg-white">{{ $payslips->links() }} {{ $letters->links() }} {{ $reviews->links() }}</div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('leave-requests.create') }}">Request leave</a>
        <a class="btn btn-outline-primary" href="{{ route('ot-requests.create') }}">Request OT</a>
        <a class="btn btn-outline-secondary" href="{{ route('roster.index') }}">Roster</a>
        <a class="btn btn-outline-secondary" href="{{ route('announcements.index') }}">Notices</a>
    </div>
    <div class="card border-0 shadow-sm mt-3" style="max-width: 640px;">
        <div class="card-header"><h6 class="mb-0">Request a change</h6></div>
        <div class="card-body">
            <form method="POST" action="{{ route('hr.self.request') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="change_type" class="form-select">
                        <option value="personal">Personal details</option>
                        <option value="attendance_correction">Attendance correction</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">What should change</label>
                    <input name="summary" class="form-control" required value="{{ old('summary') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Details</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
                <button class="btn btn-primary">Send to HR</button>
            </form>
        </div>
    </div>
@endif
@endsection
