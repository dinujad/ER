@extends('accounting::phase2._page')

@php $pageTitle = 'Search'; $pageSub = 'Invoices, bills, customers, suppliers, payments, credit notes and journals — by number, name, phone, TIN, cheque no. or exact amount'; @endphp

@section('page_body')
<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="e.g. 26NOV-DERM-1042, Perera, 0771234567, 12095.00" autofocus>
        <button class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
    </div>
</form>

@if(mb_strlen($q) >= 2)
    @forelse($groups as $label => $rows)
        <div class="card mb-2">
            <div class="card-header py-1"><strong>{{ $label }}</strong> <span class="text-muted small">({{ count($rows) }})</span></div>
            <div class="list-group list-group-flush">
                @foreach($rows as $r)
                    @if($r['url'])
                        <a href="{{ $r['url'] }}" class="list-group-item list-group-item-action py-1">
                            <div class="fw-semibold">{{ $r['title'] }}</div><div class="small text-muted">{{ $r['detail'] }}</div>
                        </a>
                    @else
                        <div class="list-group-item py-1"><div class="fw-semibold">{{ $r['title'] }}</div><div class="small text-muted">{{ $r['detail'] }}</div></div>
                    @endif
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-muted">Nothing found for “{{ $q }}”.</div>
    @endforelse
@elseif($q !== '')
    <div class="text-muted">Type at least 2 characters.</div>
@endif
@endsection
