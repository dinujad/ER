@extends('accounting::layouts.app')
@section('title')
Budget – {{ $financial_year }}
@endsection
@section('css')
@include('accounting::layouts._acct_theme')
<style>
.budget-tabs .nav-link{color:#475569;border-radius:9px;padding:9px 18px;font-weight:500;font-size:.9rem;border:none;display:flex;align-items:center;gap:7px}
.budget-tabs .nav-link.active{background:linear-gradient(135deg,#1e3a5f,#0d2137);color:#fff;box-shadow:0 2px 8px rgba(30,58,95,.25)}
.budget-tabs .nav-link:hover:not(.active){background:#f1f5f9;color:#1e293b}
.budget-tabs{background:#fff;border:1px solid #e9ecef;border-radius:12px;padding:8px;gap:6px;margin-bottom:18px}
.budget-table{width:100%;border-collapse:collapse;font-size:.875rem}
.budget-table thead th{background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600;padding:10px 14px;border-bottom:1px solid #e9ecef}
.budget-table tbody td{padding:10px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.budget-table tbody tr:last-child td{border-bottom:0}
.budget-table tbody tr:hover{background:#fafbfc}
.budget-input{border:1.5px solid #e2e8f0;border-radius:7px;padding:6px 10px;font-size:.875rem;width:100%;transition:border-color .18s}
.budget-input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12);outline:none}
.btn-edit-budget{background:#eff6ff;color:#1d4ed8;border:none;border-radius:7px;padding:4px 12px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s}
.btn-edit-budget:hover{background:#dbeafe}
.year-selector{display:flex;align-items:center;gap:10px}
.year-selector input{border:1.5px solid #e2e8f0;border-radius:8px;padding:7px 12px;font-size:.88rem;width:130px;outline:none}
.year-selector input:focus{border-color:#3b82f6}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<div class="acct-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-bar-chart-line"></i></div>
        <div>
            <h4>Budget — {{ $financial_year }}</h4>
            <p>Plan and track your financial budget by period</p>
        </div>
    </div>
    <div class="year-selector">
        <span style="color:rgba(255,255,255,.7);font-size:.85rem">Financial Year</span>
        <form action="{{ Request::url() }}" id="year_form">
            <input type="hidden" name="view" value="{{ Request::get('view') }}">
            <input type="number" name="year" id="year_input" class="acct-control"
                value="{{ $financial_year }}" min="2000" max="2100" style="background:rgba(255,255,255,.9);border-color:rgba(255,255,255,.3);color:#1e293b">
        </form>
    </div>
</div>

{{-- Period tabs --}}
<div id="vue-app">
<div class="d-flex budget-tabs">
    @php $view = Request::get('view', 'monthly'); @endphp
    <a href="{{ $url->monthly }}" class="nav-link {{ $view == 'monthly' ? 'active' : '' }}">
        <i class="bi bi-calendar-month"></i> Monthly
    </a>
    <a href="{{ $url->quarterly }}" class="nav-link {{ $view == 'quarterly' ? 'active' : '' }}">
        <i class="bi bi-calendar3"></i> Quarterly
    </a>
    <a href="{{ $url->yearly }}" class="nav-link {{ $view == 'yearly' ? 'active' : '' }}">
        <i class="bi bi-calendar-range"></i> Yearly
    </a>

    <div class="ms-auto d-flex align-items-center gap-2">
        <button type="button" class="btn-hero-action" style="background:#1e3a5f;border-color:#1e3a5f;color:#fff;font-size:.82rem;padding:7px 14px"
            data-bs-toggle="modal" data-bs-target="#settingsModal">
            <i class="bi bi-gear me-1"></i> Settings
        </button>
    </div>
</div>

{{-- Content --}}
<div class="acct-card">
    <div class="acct-card-head">
        <h6><i class="bi bi-bar-chart me-2 text-muted"></i>
            {{ ucfirst($view) }} Budget View
        </h6>
        <span class="chip chip-gray">{{ count($chart_of_accounts) }} accounts</span>
    </div>
    <div class="acct-card-body p-0">
        @if(!empty($chart_of_accounts) && count($chart_of_accounts) > 0)
            @switch($view)
                @case('monthly')
                    @include('accounting::budget.partials.monthly_view')
                    @break
                @case('quarterly')
                    @include('accounting::budget.partials.quarterly_view')
                    @break
                @case('yearly')
                    @include('accounting::budget.partials.yearly_view')
                    @break
                @default
                    <div class="p-4 text-center text-muted">Select a period tab above.</div>
            @endswitch
        @else
            <div class="p-5 text-center">
                <div style="font-size:3rem;color:#cbd5e1;margin-bottom:12px"><i class="bi bi-bar-chart-line"></i></div>
                <p style="color:#64748b;font-size:.95rem">No accounts found. Please
                    <a href="{{ url('accounting/chart_of_account/create') }}" style="color:#1e3a5f;font-weight:600">add a chart of account</a>
                    first.
                </p>
            </div>
        @endif
    </div>
</div>

@include('accounting::budget.modals.budget_settings_modal')
</div>{{-- end #vue-app --}}
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
<script>
// Year input auto-submit
document.getElementById('year_input').addEventListener('change', function(){
    document.getElementById('year_form').submit();
});

// Apply-for-all buttons
$(function(){
    $('.apply_for_all').on('click',function(){
        var id=$(this).attr('input_id');
        var val=$('#'+id).val();
        var view='{{ $view }}';
        if(view==='monthly') $('[id^=month_]').val(val);
        else if(view==='quarterly') $('[id^=quarter_]').val(val);
    });
});

// Vue for budget modals
new Vue({
    el:'#vue-app',
    data(){return{
        financial_year_start:'{{ $financial_year_start }}',
        financial_year:'{{ $financial_year }}',
        business_id:'{{ session("business.id") }}',
        view:'{{ $view }}',
        chart_of_account_id:'',
        chart_of_accounts:{!! json_encode($accounts_for_vue ?? []) !!},
        months:[],quarters:[],yearly_budget:0,eliminate_decimals:true
    }},
    computed:{
        chart_of_account(){
            var a=this.chart_of_accounts.find(x=>x.id==this.chart_of_account_id);
            return a||{name:''};
        }
    },
    methods:{
        onClickEditBudget(e){
            this.chart_of_account_id=e.target.getAttribute('chart_of_account_id');
            switch(this.view){
                case'monthly':!this.chart_of_account||!this.chart_of_account.budget?this.setMonthlyBudget({}):this.setMonthlyBudget(this.chart_of_account.budget);break;
                case'quarterly':!this.chart_of_account||!this.chart_of_account.budget?this.setQuarterlyBudget({}):this.setQuarterlyBudget(this.chart_of_account.budget);break;
                case'yearly':this.yearly_budget=!this.chart_of_account||!this.chart_of_account.budget?0:this.chart_of_account.budget.yearly;break;
            }
        },
        setMonthlyBudget(b){for(let i=1;i<=12;i++)this.$set(this.months,i,Object.keys(b).length?b['month_'+i]:0)},
        setQuarterlyBudget(b){for(let i=1;i<=4;i++)this.$set(this.quarters,i,Object.keys(b).length?(b.quarterly[i]||0):0)}
    }
});
</script>
@endsection
