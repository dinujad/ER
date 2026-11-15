@extends('accounting::phase2._page')
@php $pageTitle = 'New budget version'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/budget-versions') }}">@csrf
    <div class="row g-2 mb-3">
        <div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
        <div class="col-md-2"><input name="financial_year" value="{{ date('Y') }}" class="form-control form-control-sm" required></div>
        <div class="col-md-2"><input type="number" name="version_no" value="{{ $nextVersion }}" class="form-control form-control-sm"></div>
    </div>
    <p class="small text-muted">Enter month 1–3 amounts per account (extend in ERP as needed).</p>
    <table class="table table-sm">
        <thead><tr><th>Account</th><th>M1</th><th>M2</th><th>M3</th></tr></thead>
        <tbody>
        @foreach($accounts->take(15) as $i => $acct)
        <tr>
            <td><input type="hidden" name="lines[{{ $i }}][chart_of_account_id]" value="{{ $acct->id }}">{{ $acct->gl_code }} {{ $acct->name }}</td>
            <td><input type="number" step="0.01" name="lines[{{ $i }}][month_1]" class="form-control form-control-sm" value="0"></td>
            <td><input type="number" step="0.01" name="lines[{{ $i }}][month_2]" class="form-control form-control-sm" value="0"></td>
            <td><input type="number" step="0.01" name="lines[{{ $i }}][month_3]" class="form-control form-control-sm" value="0"></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <button class="btn btn-primary btn-sm">Create draft</button>
</form>
@endsection