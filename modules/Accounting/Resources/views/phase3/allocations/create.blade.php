@extends('accounting::phase2._page')
@php $pageTitle = 'New allocation'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/cost-allocations') }}">@csrf
    <div class="row g-2 mb-3">
        <div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
        <div class="col-md-2"><input type="date" name="allocation_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required></div>
        <div class="col-md-2"><input type="number" step="0.01" name="source_amount" id="srcAmt" class="form-control form-control-sm" placeholder="Total to split" required></div>
    </div>
    <table class="table table-sm" id="allocLines">
        <thead><tr><th>Cost center</th><th>Amount</th></tr></thead>
        <tbody>
        @for($i=0;$i<3;$i++)
        <tr>
            <td><select name="lines[{{ $i }}][cost_center_id]" class="form-select form-select-sm"><option value="">—</option>
                @foreach($centers as $c)<option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>@endforeach
            </select></td>
            <td><input type="number" step="0.01" name="lines[{{ $i }}][amount]" class="form-control form-control-sm line-amt"></td>
        </tr>
        @endfor
        </tbody>
    </table>
    <button class="btn btn-primary btn-sm">Post allocation</button>
</form>
@endsection