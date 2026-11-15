@extends('accounting::layouts.app')
@section('title')
    Accounting Settings — @yield('tab-title')
@endsection

@section('css')
@include('accounting::layouts._acct_theme')
<style>
.settings-shell{display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start}
.settings-sidebar{background:#fff;border:1px solid #e9ecef;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden}
.settings-sidebar-head{background:linear-gradient(135deg,#1e3a5f,#0d2137);padding:16px 18px;color:#fff}
.settings-sidebar-head h6{font-weight:700;font-size:.88rem;margin:0}
.settings-sidebar-head p{font-size:.76rem;opacity:.7;margin:3px 0 0}
.settings-nav a{display:flex;align-items:center;gap:10px;padding:11px 18px;font-size:.88rem;color:#374151;text-decoration:none;border-bottom:1px solid #f1f5f9;transition:all .15s}
.settings-nav a:last-child{border-bottom:0}
.settings-nav a:hover{background:#f8fafc;color:#1e3a5f}
.settings-nav a.active{background:#eff6ff;color:#1d4ed8;font-weight:600;border-right:3px solid #3b82f6}
.settings-nav a i{font-size:1rem;flex-shrink:0}
@media(max-width:767px){.settings-shell{grid-template-columns:1fr}.settings-sidebar{margin-bottom:10px}}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<div class="acct-hero mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-gear-wide-connected"></i></div>
        <div>
            <h4>Accounting Settings</h4>
            <p>Configure account subtypes, detail types and defaults</p>
        </div>
    </div>
</div>

<div class="settings-shell" id="vue-app-with-modal">
    {{-- Sidebar nav --}}
    <div class="settings-sidebar">
        <div class="settings-sidebar-head">
            <h6><i class="bi bi-sliders2 me-2"></i>Settings</h6>
            <p>Accounting configuration</p>
        </div>
        <nav class="settings-nav">
            @php
                $navItems = [
                    'accounting/settings/account_subtypes' => ['bi-layers','Account Subtypes'],
                    'accounting/settings/detail_types'     => ['bi-card-list','Detail Types'],
                ];
                $currentPath = request()->segment(1).'/'.request()->segment(2).'/'.request()->segment(3);
            @endphp
            @foreach($navItems as $url => [$icon, $label])
            <a href="{{ url($url) }}" class="{{ $currentPath === $url ? 'active' : '' }}">
                <i class="bi {{ $icon }}"></i> {{ $label }}
            </a>
            @endforeach
        </nav>
    </div>

    {{-- Content area --}}
    <div>
        @yield('tab-content')
    </div>
</div>

@yield('tab-modal-content')
@endsection

@section('javascript')
    @yield('tab-javascript')
@endsection
