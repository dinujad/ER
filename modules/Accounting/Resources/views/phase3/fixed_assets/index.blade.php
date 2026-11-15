@extends('accounting::phase2._page')
@php $pageTitle = 'Fixed Asset Register'; $pageSub = 'Capitalization & depreciation'; @endphp
@section('page_actions')
    <a href="{{ url('accounting/fixed-assets/create') }}" class="btn btn-primary btn-sm">New Asset</a>
@endsection
@section('page_body')
<form class="row g-2 mb-3" method="get">
    <div class="col-auto"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search code or name"></div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(['draft','submitted','approved','active','fully_depreciated','inactive','disposed'] as $s)
                <option value="{{ $s }}" @selected(($status ?? '')===$s)>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    @if(!empty($categories))
    <div class="col-auto">
        <select name="category_id" class="form-select form-select-sm">
            <option value="">All categories</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
</form>
<table class="table table-sm table-hover">
    <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Status</th><th class="text-end">NBV</th><th></th></tr></thead>
    <tbody>
    @forelse($assets as $a)
        <tr>
            <td>{{ $a->asset_code }}</td>
            <td>{{ $a->name }}</td>
            <td>{{ $a->category->name ?? '—' }}</td>
            <td><span class="badge text-bg-secondary">{{ $a->status }}</span></td>
            <td class="text-end">{{ number_format((float)$a->net_book_value, 2) }}</td>
            <td><a href="{{ url('accounting/fixed-assets/'.$a->id) }}">View</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No assets recorded.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $assets->links() }}
@endsection