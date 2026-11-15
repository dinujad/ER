@extends('accounting::phase2._page')

@php $pageTitle = 'New Manual Journal'; $pageSub = 'Draft → Submit → Approve → Post'; @endphp

@section('page_body')
<form method="post" action="{{ url('accounting/manual-journals') }}" id="mjForm">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">Journal date</label>
            <input type="date" name="journal_date" class="form-control" value="{{ old('journal_date', date('Y-m-d')) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Reference</label>
            <input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" value="{{ old('description') }}">
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-sm" id="linesTable">
            <thead><tr><th>Account</th><th class="text-end">Debit (LKR)</th><th class="text-end">Credit (LKR)</th><th>Memo</th><th></th></tr></thead>
            <tbody>
            @for($i=0;$i<4;$i++)
                <tr>
                    <td>
                        <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm">
                            <option value="">—</option>
                            @foreach($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->gl_code }} — {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control form-control-sm text-end line-debit"></td>
                    <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control form-control-sm text-end line-credit"></td>
                    <td><input type="text" name="lines[{{ $i }}][memo]" class="form-control form-control-sm"></td>
                    <td></td>
                </tr>
            @endfor
            </tbody>
            <tfoot>
                <tr>
                    <th>Totals</th>
                    <th class="text-end" id="totDebit">0.00</th>
                    <th class="text-end" id="totCredit">0.00</th>
                    <th colspan="2"><span id="balMsg" class="small"></span></th>
                </tr>
            </tfoot>
        </table>
    </div>
    <button type="submit" class="btn btn-primary">Save Draft</button>
    <a href="{{ url('accounting/manual-journals') }}" class="btn btn-link">Cancel</a>
</form>
<script>
(function(){
  function sum(sel){let t=0;document.querySelectorAll(sel).forEach(i=>{t+=parseFloat(i.value||0)||0});return t.toFixed(2)}
  function recalc(){
    const d=sum('.line-debit'), c=sum('.line-credit');
    document.getElementById('totDebit').textContent=d;
    document.getElementById('totCredit').textContent=c;
    const m=document.getElementById('balMsg');
    m.textContent = d===c && parseFloat(d)>0 ? 'Balanced' : 'Unbalanced';
    m.className = 'small ' + (d===c && parseFloat(d)>0 ? 'text-success' : 'text-danger');
  }
  document.getElementById('mjForm').addEventListener('input', recalc);
  recalc();
})();
</script>
@endsection
