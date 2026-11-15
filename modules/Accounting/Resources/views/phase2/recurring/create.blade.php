@extends('accounting::phase2._page')

@php $pageTitle = 'New Recurring '.ucfirst($docType); $pageSub = 'Draft by default'; @endphp

@section('page_body')
<form method="post" action="{{ url('accounting/recurring-journals') }}">
    @csrf
    <input type="hidden" name="document_type" value="{{ $docType }}">
    <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
        <div class="col-md-2">
            <label class="form-label">Frequency</label>
            <select name="frequency" class="form-select">
                @foreach(['daily','weekly','monthly','quarterly','yearly'] as $f)
                    <option value="{{ $f }}">{{ ucfirst($f) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><label class="form-label">Starts</label><input type="date" name="starts_on" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="col-md-2"><label class="form-label">Ends</label><input type="date" name="ends_on" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Max runs</label><input type="number" name="max_occurrences" class="form-control" min="1"></div>
        <div class="col-md-3">
            <label class="form-label">Mode</label>
            <select name="execution_mode" class="form-select">
                <option value="draft">Create draft</option>
                <option value="draft_notify">Draft + notify</option>
                @if($docType==='journal')<option value="auto_post">Auto post (journal only)</option>@endif
            </select>
        </div>
        <div class="col-md-9"><label class="form-label">Description</label><input name="description" class="form-control"></div>
    </div>

    @if($docType==='journal')
        <table class="table table-sm mb-3">
            <thead><tr><th>Account</th><th>Debit</th><th>Credit</th></tr></thead>
            <tbody>
            @for($i=0;$i<2;$i++)
                <tr>
                    <td>
                        <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm" required>
                            @foreach($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->gl_code }} — {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="lines[{{ $i }}][debit]" class="form-control form-control-sm" value="{{ $i===0 ? '1000' : '' }}"></td>
                    <td><input type="number" step="0.01" name="lines[{{ $i }}][credit]" class="form-control form-control-sm" value="{{ $i===1 ? '1000' : '' }}"></td>
                </tr>
            @endfor
            </tbody>
        </table>
    @elseif($docType==='invoice')
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select" required>
                    @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Amount (LKR)</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
        </div>
        <p class="small text-muted">Creates draft orders only — no stock reduction or revenue until manually finalized.</p>
    @else
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select" required>
                    @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Amount (LKR)</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
        </div>
        <p class="small text-muted">Creates draft purchases only — no inventory receive or AP posting until confirmed.</p>
    @endif

    <button class="btn btn-primary">Save Template</button>
</form>
@endsection
