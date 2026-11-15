@extends('erp.layout')

@section('content')
<h2>Production Output</h2>
<div class="row">
    <div class="col-md-6">
        <div class="card p-3 mb-3">
            <h4>Record Daily Output</h4>
            <form action="{{ route('manufacturing.cutting.save') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label>Production Line</label>
                    <select name="line_name" class="form-select" required>
                        <option value="Line I">Line I</option>
                        <option value="Line II">Line II</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Daily Output (Qty)</label>
                    <input type="number" name="daily_output" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Date</label>
                    <input type="date" name="production_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <button type="submit" class="btn btn-success">Save Output</button>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <h4>Recent Logs</h4>
        <ul class="list-group">
            @foreach($logs as $log)
                <li class="list-group-item">
                    {{ $log->production_date }}: {{ $log->line_name }} - {{ $log->daily_output }} pcs
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
