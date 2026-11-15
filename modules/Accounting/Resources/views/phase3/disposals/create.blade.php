@extends('accounting::phase2._page')
@php $pageTitle = 'Asset Disposal'; $pageSub = 'Gain/loss posting'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/asset-disposals') }}" class="row g-3">
    @csrf
    <div class="col-md-6">
        <label class="form-label">Asset</label>
        <select name="asset_id" class="form-select form-select-sm" required>
            @foreach($assets as $a)
                <option value="{{ $a->id }}" @selected(optional($asset)->id===$a->id)>{{ $a->asset_code }} — {{ $a->name }} (NBV {{ number_format((float)$a->net_book_value,2) }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Type</label>
        <select name="disposal_type" class="form-select form-select-sm">
            @foreach(['sale','scrap','write_off','donation'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Disposal date</label><input type="date" name="disposal_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><label class="form-label">Proceeds (LKR)</label><input type="number" step="0.01" name="proceeds" value="0" class="form-control form-control-sm"></div>
    <div class="col-12"><button class="btn btn-danger btn-sm">Post disposal</button></div>
</form>
@endsection