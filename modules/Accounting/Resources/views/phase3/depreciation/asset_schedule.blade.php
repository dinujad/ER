@extends('accounting::phase2._page')
@php $pageTitle = 'Depreciation schedule'; $pageSub = $asset->asset_code; @endphp
@section('page_actions')
    <a href="{{ url('accounting/fixed-assets/'.$asset->id) }}" class="btn btn-outline-secondary btn-sm">Back to asset</a>
@endsection
@section('page_body')
<table class="table table-sm">
    <thead><tr><th>Period end</th><th class="text-end">Amount</th><th class="text-end">NBV after</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($asset->depreciationSchedules as $s)
        <tr>
            <td>{{ optional($s->period_end)->format('Y-m-d') }}</td>
            <td class="text-end">{{ number_format((float)$s->scheduled_amount,2) }}</td>
            <td class="text-end">{{ number_format((float)$s->nbv_after,2) }}</td>
            <td>{{ $s->status }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-muted">No schedule — capitalize the asset to generate.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection