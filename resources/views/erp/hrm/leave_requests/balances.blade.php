@extends('erp.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Leave balances {{ $year }}</h4>
    <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-secondary">Back</a>
</div>
<form method="GET" class="mb-3 d-flex gap-2">
    <input type="number" name="year" class="form-control" style="width: 120px;" value="{{ $year }}">
    <button class="btn btn-outline-primary">Show</button>
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    @foreach($types as $type)
                        <th>{{ $type->name }}@if(!$type->is_paid) <span class="text-muted">unpaid</span>@endif</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td>{{ $row['employee']->display_name ?: $row['employee']->name }}<div class="small text-muted">{{ $row['employee']->department?->name }}</div></td>
                    @foreach($row['balances'] as $balance)
                        <td>{{ $balance['type']->days_per_year ? number_format($balance['balance'], 1) : '—' }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
