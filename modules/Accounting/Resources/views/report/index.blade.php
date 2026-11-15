@extends('accounting::layouts.app')
@section('title', 'Accounting Reports')
@section('css')
@include('accounting::layouts._acct_theme')
<style>
.report-section{margin-bottom:28px}
.report-section-title{font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.report-section-title::after{content:'';flex:1;height:1px;background:#e9ecef}
.report-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.report-card{background:#fff;border:1.5px solid #e9ecef;border-radius:12px;padding:18px 20px;text-decoration:none;color:inherit;transition:all .18s;display:block}
.report-card:hover{border-color:#3b82f6;box-shadow:0 4px 16px rgba(59,130,246,.12);transform:translateY(-2px);text-decoration:none;color:inherit}
.report-card .r-icon{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:12px;flex-shrink:0}
.report-card .r-title{font-weight:600;font-size:.92rem;color:#1e293b;margin-bottom:5px;line-height:1.3}
.report-card .r-desc{font-size:.8rem;color:#64748b;line-height:1.5;margin:0}
.report-card:hover .r-title{color:#1d4ed8}

/* Section icon colours */
.icon-overview{background:#eff6ff;color:#1d4ed8}
.icon-bookkeeping{background:#f0fdf4;color:#15803d}
.icon-budget{background:#fffbeb;color:#b45309}
.icon-receivable{background:#f5f3ff;color:#6d28d9}
.icon-payable{background:#fef2f2;color:#b91c1c}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<div class="acct-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-graph-up"></i></div>
        <div>
            <h4>Accounting Reports</h4>
            <p>Financial statements, ledgers and business insights</p>
        </div>
    </div>
</div>

@php
$sectionIcons = [
    0 => ['icon'=>'bi-speedometer2',    'cls'=>'icon-overview'],
    1 => ['icon'=>'bi-journal-bookmark','cls'=>'icon-bookkeeping'],
    2 => ['icon'=>'bi-bar-chart-line',  'cls'=>'icon-budget'],
    3 => ['icon'=>'bi-people',          'cls'=>'icon-receivable'],
    4 => ['icon'=>'bi-credit-card',     'cls'=>'icon-payable'],
];
$reportIcons = [
    'trial' => 'bi-list-check',
    'ledger'=> 'bi-book',
    'profit'=> 'bi-graph-up-arrow',
    'loss'  => 'bi-graph-down-arrow',
    'balance'=>'bi-scale',
    'cash'  => 'bi-cash-stack',
    'budget'=> 'bi-pie-chart',
    'payable'=>'bi-credit-card',
    'receivable'=>'bi-arrow-return-left',
    'journal'=>'bi-journal-text',
    'income'=>'bi-graph-up-arrow',
    'expense'=>'bi-receipt',
];
function reportIcon($title){
    foreach(['trial','ledger','profit','balance','cash','budget','payable','receivable','journal','income','expense'] as $k)
        if(stripos($title,$k)!==false) return 'bi-'.$k;
    return 'bi-file-earmark-text';
}
@endphp

@foreach($reports as $idx => $section)
<div class="report-section">
    <div class="report-section-title">
        <i class="bi {{ $sectionIcons[$idx]['icon'] ?? 'bi-file-text' }} me-1"></i>
        {{ $section->section_title }}
    </div>
    <div class="report-grid">
        @foreach($section->items as $item)
        <a href="{{ $item->url }}" class="report-card">
            <div class="r-icon {{ $sectionIcons[$idx]['cls'] ?? 'icon-overview' }}">
                <i class="bi bi-file-earmark-bar-graph"></i>
            </div>
            <div class="r-title">{{ $item->title }}</div>
            <p class="r-desc">{{ $item->description }}</p>
        </a>
        @endforeach
    </div>
</div>
@endforeach
@endsection
