@extends('accounting::phase2._page')
@php
    $pageTitle = 'Tax Period Summary';
    $lines = $summary['lines'] ?? [];
    $net = collect($lines)->sum(fn ($l) => (float) ($l['net_payable'] ?? 0));
    $pageSub = ($summary['period_name'] ?? 'Tax period').' · Net payable LKR '.number_format($net, 2);
    $kpis = [
        ['label' => 'Tax lines', 'value' => (string) count($lines)],
        ['label' => 'Net payable', 'value' => 'LKR '.number_format($net, 2), 'tone' => $net >= 0 ? 'negative' : 'positive'],
        ['label' => 'Currency', 'value' => 'LKR', 'meta' => 'Posted journals only'],
    ];
    $chartLabels = collect($lines)->pluck('tax_code')->take(6)->values()->all() ?: ['None'];
    $chartValues = collect($lines)->pluck('net_payable')->take(6)->map(fn ($v) => (float) $v)->values()->all() ?: [0];
    $chartColors = ['#2563eb', '#059669', '#dc2626', '#d97706', '#7c3aed', '#94a3b8'];
@endphp

@section('page_body')
<x-accounting::advanced_report
    title="Tax Summary"
    :subtitle="$pageSub"
    :show-customize="true"
    chart-id="taxAdvChart"
    chart-type="bar"
    :chart-labels="$chartLabels"
    :chart-values="$chartValues"
    :chart-colors="$chartColors"
    :kpis="$kpis"
>
    <x-slot:filters>
        <div class="small text-muted align-self-center">Period-scoped tax summary from posted amounts (not estimates).</div>
    </x-slot:filters>
    <x-slot:table>
        @include('accounting::phase4._report_table', [
            'columns' => ['Code', 'Name', 'Rate', 'Net payable'],
            'rows' => collect($lines)->map(fn ($l) => [$l['tax_code'], $l['name'], $l['rate'], $l['net_payable']])->all(),
        ])
    </x-slot:table>
</x-accounting::advanced_report>
@endsection
