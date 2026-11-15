@extends('sales_person.layout')

@section('title', 'My Expenses - ' . config('app.name'))

@section('content')
<style>
    .expenses-hero { background: linear-gradient(135deg, #0d6efd 0%, #1e3a5f 100%); color: #fff; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
</style>

<div class="expenses-hero d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <h1 class="h5 mb-0 fw-bold"><i class="bi bi-receipt-cutoff me-2"></i>My Expenses</h1>
        <p class="mb-0 small opacity-90">Field expenses — fuel, travel, food & more</p>
    </div>
    <a href="{{ route('sales-person.dashboard') }}" class="btn btn-sm btn-light">← Dashboard</a>
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label class="form-label small mb-1">Month</label>
        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($monthOptions as $opt)
                <option value="{{ $opt['value'] }}" @selected($month === $opt['value'])>{{ $opt['label'] }}</option>
            @endforeach
        </select>
    </div>
</form>

@php $redirectTo = route('sales-person.expenses.index', ['month' => $month], false) . '#expenses'; @endphp

@include('sales_person.partials.expense-form', ['redirectTo' => $redirectTo])

@include('sales_person.partials.expense-list', [
    'expenses' => $expenses,
    'monthTotal' => $monthTotal,
    'listTitle' => $start->format('F Y') . ' expenses',
    'redirectTo' => $redirectTo,
])

@endsection

@section('scripts')
<script>
(function () {
    document.querySelectorAll('.sp-expense-type').forEach(function (sel) {
        var row = sel.closest('form');
        if (!row) return;
        var desc = row.querySelector('.sp-expense-desc');
        if (!desc) return;
        var labels = { fuel: 'Fuel', travel: 'Travel', food: 'Food', accommodation: 'Accommodation', supplies: 'Supplies', others: 'Other expense' };
        sel.addEventListener('change', function () {
            var v = labels[sel.value];
            if (v && (!desc.value || Object.values(labels).indexOf(desc.value) >= 0)) {
                desc.value = v;
            }
        });
    });
})();
</script>
@endsection
