@extends('accounting::settings.layout')
@section('tab-title', 'Account Subtypes')

@section('tab-content')
@php
$typeStyles = [
    'asset'     => ['chip-asset',    'bi-bank',            '#eff6ff','#1d4ed8'],
    'income'    => ['chip-income',   'bi-graph-up-arrow',  '#f0fdf4','#15803d'],
    'expense'   => ['chip-expense',  'bi-receipt',         '#fef2f2','#b91c1c'],
    'liability' => ['chip-liability','bi-exclamation-circle','#fffbeb','#b45309'],
    'equity'    => ['chip-equity',   'bi-pie-chart',       '#f5f3ff','#6d28d9'],
];
@endphp

@foreach($account_types as $account_type)
@php [$chipCls, $icon, $bg, $clr] = $typeStyles[$account_type->id] ?? ['chip-gray','bi-tag','#f1f5f9','#475569']; @endphp
<div class="acct-card mb-4">
    <div class="acct-card-head">
        <div class="d-flex align-items-center gap-2">
            <div style="background:{{ $bg }};color:{{ $clr }};border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">
                <i class="bi {{ $icon }}"></i>
            </div>
            <h6 class="mb-0">{{ ucfirst($account_type->plural_name) }}</h6>
            <span class="chip {{ $chipCls }}">
                {{ $account_subtypes->where('account_type', $account_type->id)->count() }} subtypes
            </span>
        </div>
        <button type="button" class="btn-acct-save"
            data-bs-toggle="modal" data-bs-target="#createAccountSubtypeModal"
            style="padding:7px 16px;font-size:.82rem"
            onclick="setAccountType('{{ $account_type->id }}')">
            <i class="bi bi-plus-lg me-1"></i> Add Subtype
        </button>
    </div>
    <div class="table-responsive">
        <table class="acct-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Active</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @php $filtered = $account_subtypes->where('account_type', $account_type->id); @endphp
                @forelse($filtered as $sub)
                <tr>
                    <td style="font-weight:500;color:#1e293b">{{ $sub->name }}</td>
                    <td>
                        @if($sub->active)
                            <span class="badge-yes"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                        @else
                            <span class="badge-no"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($sub->is_default_type)
                            <span class="chip chip-gray">System Default</span>
                        @else
                            <span class="chip chip-blue">Custom</span>
                        @endif
                    </td>
                    <td>
                        @if(!$sub->is_default_type)
                        <div class="acct-dd">
                            <button class="acct-dd-btn" onclick="acctToggleMenu(this)">
                                Action <i class="bi bi-chevron-down" style="font-size:.65rem"></i>
                            </button>
                            <div class="acct-dd-menu">
                                <a href="{{ url('accounting/settings/account_subtypes/'.$sub->id.'/edit') }}">
                                    <i class="bi bi-pencil text-muted"></i> Edit
                                </a>
                                <div class="divider"></div>
                                <a href="#" class="danger"
                                   onclick="if(confirm('Delete this subtype?')){var f=document.getElementById('del-sub-{{ $sub->id }}');f.action='{{ url('accounting/settings/account_subtypes/'.$sub->id.'/destroy') }}';f.submit();}return false;">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                                <form id="del-sub-{{ $sub->id }}" method="POST" class="d-none">
                                    @csrf @method('DELETE')
                                </form>
                            </div>
                        </div>
                        @else
                        <span style="color:#94a3b8;font-size:.82rem">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted" style="font-size:.85rem">
                    No subtypes for {{ $account_type->plural_name }}.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endsection

@section('tab-modal-content')
@include('accounting::settings.account_subtypes.create')
@endsection

@section('tab-javascript')
<script>
function setAccountType(t){ document.getElementById('account_type_input').value=t; if(typeof app!=='undefined') app.account_type=t; }
const app = new Vue({
    el:'#vue-app-with-modal',
    data:{
        account_types:{!! json_encode($account_types) !!},
        account_type:"{{ old('account_subtype_id') }}",
        name:"{{ old('name') }}",
        description:"{{ old('description') }}",
        active:{{ old('active',1) }},
    },
    methods:{setAccountType(t){this.account_type=t;}}
});
</script>
@endsection
