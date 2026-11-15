@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <h4 class="mb-4 fw-bold">Roster / Shift Assignment</h4>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('roster.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-0">Week starting</label>
                    <input type="date" name="week_start" class="form-control form-control-sm" value="{{ $weekStart }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">View</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">Assign shift for a date</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('roster.store') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small mb-0">Employee</label>
                    <select name="employee_id" class="form-select form-select-sm" required>
                        <option value="">Select</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }} {{ $e->department ? '(' . $e->department->name . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Shift</label>
                    <select name="shift_id" class="form-select form-select-sm" required>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" required>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_off_day" value="1" class="form-check-input" id="off">
                        <label class="form-check-label small" for="off">Off day</label>
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = \Carbon\Carbon::parse($weekStart)->addDays($i);
        }
    @endphp

    <div class="card">
        <div class="card-body p-0 overflow-auto">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Shift</th>
                        @foreach($days as $d)
                            <th class="text-center">{{ $d->format('D d M') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($shifts as $shift)
                        <tr>
                            <td>{{ $shift->name }}<br><small class="text-muted">{{ $shift->start_time }} - {{ $shift->end_time }}</small></td>
                            @foreach($days as $d)
                                <td class="small">
                                    @php
                                        $entries = $roster->get($d->toDateString(), collect())->where('shift_id', $shift->id);
                                    @endphp
                                    @foreach($entries as $es)
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $es->employee?->name }}</span>
                                            @if(!$es->is_off_day)
                                                @canRoute('roster.destroy')
<form action="{{ route('roster.destroy', $es) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this roster entry?');">@csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-link btn-sm text-danger p-0">×</button>
                                                </form>
@endcanRoute
                                            @endif
                                        </div>
                                    @endforeach
                                    @if($entries->isEmpty())
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
