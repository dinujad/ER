<hr>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="mb-1">Other Costs</h6>
        <p class="text-xs text-muted mb-0">Materials වලට අයත් නොවන වියදම් — <strong>+ Add Other Cost</strong> click කරලා row එකක් එකක් add කරන්න.</p>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" id="addOtherCostBtn">+ Add Other Cost</button>
</div>
<div class="table-responsive mb-3">
    <table class="table table-bordered mb-0" id="otherCostsTable">
        <thead>
            <tr>
                <th style="width:55%">Description (cost එක මොකද්ද?)</th>
                <th style="width:30%">Cost (LKR)</th>
                <th style="width:15%"></th>
            </tr>
        </thead>
        <tbody id="otherCostsBody"></tbody>
        <tfoot>
            <tr class="bg-light">
                <td class="text-end font-weight-bold">Other Costs Total</td>
                <td colspan="2"><span id="otherCostsTotal" class="font-weight-bold">0.00</span></td>
            </tr>
        </tfoot>
    </table>
</div>
