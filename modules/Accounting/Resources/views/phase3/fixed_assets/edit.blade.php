@extends('accounting::phase2._page')
@php $pageTitle = 'Edit '.$asset->asset_code; $pageSub = 'Draft only'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/fixed-assets/'.$asset->id) }}" class="row g-3">
    @csrf
    @include('accounting::phase3.fixed_assets._fields', ['asset' => $asset, 'categories' => $categories, 'costCenters' => $costCenters])
    <div class="col-12"><button class="btn btn-primary btn-sm">Update</button></div>
</form>
@endsection