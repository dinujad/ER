@extends('accounting::phase2._page')
@php $pageTitle = 'Asset Categories'; $pageSub = 'GL mapping & defaults'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/fixed-asset-categories') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-md-2"><input name="code" class="form-control form-control-sm" placeholder="Code" required></div>
    <div class="col-md-3"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
    <div class="col-md-2"><input type="number" name="default_useful_life_months" value="60" class="form-control form-control-sm" title="Life months"></div>
    <div class="col-md-2">
        <select name="default_depreciation_method" class="form-select form-select-sm">
            <option value="straight_line">Straight line</option>
            <option value="declining_balance">Declining</option>
            <option value="none">None</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary">Add category</button></div>
</form>
<table class="table table-sm">
    <thead><tr><th>Code</th><th>Name</th><th>Life</th><th>Method</th><th></th></tr></thead>
    <tbody>
    @forelse($categories as $cat)
        <tr>
            <form method="post" action="{{ url('accounting/fixed-asset-categories/'.$cat->id) }}">
                @csrf
                <td><input name="code" value="{{ $cat->code }}" class="form-control form-control-sm"></td>
                <td><input name="name" value="{{ $cat->name }}" class="form-control form-control-sm"></td>
                <td><input type="number" name="default_useful_life_months" value="{{ $cat->default_useful_life_months }}" class="form-control form-control-sm"></td>
                <td>
                    <select name="default_depreciation_method" class="form-select form-select-sm">
                        @foreach(['straight_line','declining_balance','none'] as $m)
                            <option value="{{ $m }}" @selected($cat->default_depreciation_method===$m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </td>
                <td><button class="btn btn-sm btn-outline-secondary">Save</button></td>
            </form>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No categories.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $categories->links() }}
@endsection