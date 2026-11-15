<?php

namespace App\Http\Controllers;

use App\Models\CuttingBatch;
use App\Models\MaterialIssue;
use App\Models\ProductionLog;
use App\Models\QcCheck;
use App\Models\WashingTransfer;
use App\Models\ManufacturingJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManufacturingController extends Controller
{
    /**
     * Manufacturing overview – job counts by stage, recent jobs, quick links.
     */
    public function overview()
    {
        $totalJobs = ManufacturingJob::count();
        $fabricOrCutting = ManufacturingJob::whereIn('status', [
            ManufacturingJob::STATUS_FABRIC_ISSUED,
            ManufacturingJob::STATUS_SENT_TO_CUTTING,
            ManufacturingJob::STATUS_CUTTING_IN_PROGRESS,
            ManufacturingJob::STATUS_CUTTING_COMPLETED,
        ])->count();
        $lineAssigned = ManufacturingJob::whereIn('status', [
            ManufacturingJob::STATUS_LINE_ASSIGNED,
            ManufacturingJob::STATUS_LINE_IN_PROGRESS,
        ])->count();
        $washing = ManufacturingJob::where('status', ManufacturingJob::STATUS_WASHING_OUT)->count();
        $afterWashOrPacking = ManufacturingJob::whereIn('status', [
            ManufacturingJob::STATUS_AFTER_WASH_RECEIVED,
            ManufacturingJob::STATUS_PACKING_COMPLETED,
        ])->count();
        $warehouseOrSold = ManufacturingJob::whereIn('status', [
            ManufacturingJob::STATUS_WAREHOUSE_RECEIVED,
            ManufacturingJob::STATUS_SOLD,
            ManufacturingJob::STATUS_JOB_ENDED,
        ])->count();

        $recentJobs = ManufacturingJob::with(['product', 'materialIssues.material'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $pendingGatePasses = WashingTransfer::where('status', 'pending')->count();

        return view('erp.manufacturing.overview', compact(
            'totalJobs',
            'fabricOrCutting',
            'lineAssigned',
            'washing',
            'afterWashOrPacking',
            'warehouseOrSold',
            'recentJobs',
            'pendingGatePasses'
        ));
    }

    // Cutting Section
    // Cutting Section (Job Based)
    public function cuttingIndex()
    {
        $jobs = ManufacturingJob::whereIn('status', [
            ManufacturingJob::STATUS_SENT_TO_CUTTING,
            ManufacturingJob::STATUS_CUTTING_IN_PROGRESS,
        ])->orderByDesc('updated_at')->get();
        return view('erp.manufacturing.cutting', compact('jobs'));
    }

    public function storeCutting(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:manufacturing_jobs,id',
            'fabric_used_qty' => 'required|numeric|min:0',
            'fabric_waste_qty' => 'nullable|numeric|min:0',
            'total_cut_pieces' => 'required|integer|min:1',
            'cutting_reject_qty' => 'nullable|integer|min:0',
        ]);

        $job = ManufacturingJob::findOrFail($request->job_id);

        if (!in_array($job->status, [
            ManufacturingJob::STATUS_SENT_TO_CUTTING,
            ManufacturingJob::STATUS_CUTTING_IN_PROGRESS,
            ManufacturingJob::STATUS_CUTTING_COMPLETED,
            ManufacturingJob::STATUS_LINE_ASSIGNED,
            ManufacturingJob::STATUS_LINE_IN_PROGRESS,
            ManufacturingJob::STATUS_LINE_COMPLETED,
            ManufacturingJob::STATUS_WASHING_OUT,
            ManufacturingJob::STATUS_AFTER_WASH_RECEIVED,
            ManufacturingJob::STATUS_PACKING_COMPLETED,
        ], true)) {
            return back()->with('error', 'Send this job to cutting before entering cutting details.');
        }

        $assignedTotal = (int) $job->lineAssignments()->sum('assigned_quantity');
        if ($assignedTotal > 0 && (int) $request->total_cut_pieces < $assignedTotal) {
            return back()->with('error', "Total cut pieces cannot be less than already assigned to lines ({$assignedTotal}).");
        }

        $wasPastCutting = $job->hasCuttingData();
        $newStatus = $job->status;
        if (in_array($job->status, [ManufacturingJob::STATUS_SENT_TO_CUTTING, ManufacturingJob::STATUS_CUTTING_IN_PROGRESS], true)) {
            $newStatus = ManufacturingJob::STATUS_CUTTING_COMPLETED;
        }

        $job->update([
            'fabric_used_qty' => $request->fabric_used_qty,
            'fabric_waste_qty' => $request->fabric_waste_qty ?? 0,
            'total_cut_pieces' => $request->total_cut_pieces,
            'cutting_reject_qty' => $request->cutting_reject_qty ?? 0,
            'status' => $newStatus,
        ]);

        $message = $wasPastCutting
            ? 'Cutting details updated.'
            : 'Cutting completed. Job moved to next stage.';

        return redirect()->back()->with('success', $message);
    }

    // Production Lines
    public function productionIndex()
    {
        $logs = ProductionLog::latest()->get();
        return view('erp.manufacturing.production', compact('logs'));
    }

    public function storeProduction(Request $request)
    {
        $request->validate([
            'line_name' => 'required|in:Line I,Line II',
            'daily_output' => 'required|integer|min:1',
            'production_date' => 'required|date',
        ]);

        ProductionLog::create($request->all());

        return redirect()->back()->with('success', 'Production output recorded.');
    }

    // Washing Gate Pass
    public function washingIndex()
    {
        $withJob = ['job.product', 'job.materialIssues.material'];

        // Parent gate passes only — receipts (children) never appear in pending/received tabs.
        $incoming = WashingTransfer::where('status', 'pending')
            ->whereNull('parent_id')
            ->with($withJob)
            ->latest()
            ->get();
        $inProgress = WashingTransfer::where('status', 'received')
            ->whereNull('parent_id')
            ->with($withJob)
            ->latest()
            ->get();

        // Received Back tab shows individual receipt batches (children) plus any
        // legacy parent records that were marked received_back before the
        // multi-receipt feature was introduced.
        $receivedBack = WashingTransfer::where('status', 'received_back')
            ->with([...$withJob, 'parent'])
            ->latest()
            ->get();

        $completedRecent = WashingTransfer::where('status', 'washing_completed')
            ->with([...$withJob, 'parent'])
            ->latest()
            ->limit(12)
            ->get();

        return view('erp.manufacturing.washing', compact('incoming', 'inProgress', 'receivedBack', 'completedRecent'));
    }

    public function storeWashing(Request $request)
    {
        $request->validate([
            'job_id' => 'nullable|exists:manufacturing_jobs,id',
            'quantity_sent' => 'required|integer|min:1',
            'sent_from' => 'required|string|max:255',
            'washing_type' => 'required|string|in:' . implode(',', array_keys(WashingTransfer::washingTypeOptions())),
            'vehicle_number' => 'required|string|max:40',
            'washing_factory_name' => 'required|string|max:255',
            'admin_signatory' => 'required|string|max:255',
            'driver_signatory' => 'required|string|max:255',
            'factory_signatory' => 'required|string|max:255',
            'queue_no' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($request->job_id) {
            $job = \App\Models\ManufacturingJob::with(['hourlyProductions', 'washingTransfers', 'washingStock'])
                ->find($request->job_id);
            if ($job) {
                 $stockService = app(\App\Services\WashingStockService::class);
                 $onHand = $stockService->onHand($job);
                 $received = $stockService->received($job);
                 $alreadySent = $job->totalWashingSentQuantity();

                 if ($request->quantity_sent > $onHand) {
                     return back()->with('error', "Cannot send more than washing stock ($onHand pcs). In stock: $received, already on gate passes: $alreadySent. Deposit from line into Washing Stock first.");
                 }

                 \App\Services\WorkflowNotificationService::notifyTransfer($job, 'washing');
                 $job->update(['status' => \App\Models\ManufacturingJob::STATUS_WASHING_OUT]);
            }
        }

        $jobId = $request->job_id ?: null;
        $queueNo = trim((string) $request->input('queue_no', ''));
        if ($queueNo === '') {
            if ($jobId) {
                $existingCount = WashingTransfer::where('manufacturing_job_id', $jobId)
                    ->whereNull('parent_id')
                    ->count() + 1;
                $queueNo = 'Q' . str_pad((string) $existingCount, 2, '0', STR_PAD_LEFT);
            } else {
                $queueNo = 'Q' . now()->format('His');
            }
        }

        $transfer = WashingTransfer::create([
            'manufacturing_job_id' => $request->job_id ?? null,
            'quantity_sent' => $request->quantity_sent,
            'sent_from' => $request->sent_from,
            'washing_type' => $request->washing_type,
            'vehicle_number' => $request->vehicle_number,
            'washing_factory_name' => $request->washing_factory_name,
            'admin_signatory' => $request->admin_signatory,
            'driver_signatory' => $request->driver_signatory,
            'factory_signatory' => $request->factory_signatory,
            'remarks' => $request->remarks,
            'batch_no' => $queueNo,
            'status' => 'pending',
            'quantity_returned' => 0,
        ]);

        if ($request->job_id && isset($job) && $job) {
            app(\App\Services\WashingStockService::class)->recordGatePassOut($job, $transfer, auth()->id());
        }

        $message = $request->job_id ? 'Washing gate pass created. Job sent to washing section.' : 'Gate pass created.';

        return redirect()->back()
            ->with('success', $message)
            ->with('washing_print_url', route('manufacturing.washing.print', [
                'id' => $transfer->id,
                'format' => 'pdf',
            ]))
            ->with('washing_print_html', route('manufacturing.washing.print', [
                'id' => $transfer->id,
                'format' => 'html',
            ]));
    }

    public function printWashingGatePass(Request $request, $id)
    {
        $transfer = WashingTransfer::with('job.product')->findOrFail($id);
        $payload = app(\App\Services\WashingGatePassService::class)->printPayload($transfer);

        if ($request->get('format', 'pdf') === 'html') {
            return view('erp.manufacturing.washing_gatepass_print', $payload);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.manufacturing.washing_gatepass_pdf', $payload)
            ->setPaper('a4', 'portrait');

        $filename = 'washing-gate-pass-GP-' . $transfer->id . '.pdf';

        return $pdf->download($filename);
    }

    public function receiveWashing($id)
    {
        $this->ensureWashingSupervisor();
        $transfer = WashingTransfer::findOrFail($id);
        if ($transfer->status != 'pending') return back()->with('error', 'Already processed');

        $transfer->update(['status' => 'received']);
        
        return back()->with('success', 'Marked as sent to wash factory.');
    }
        
    public function receiveWashingByScan(Request $request)
    {
        $this->ensureWashingSupervisor();

        $request->validate([
            'barcode' => 'required|string',
        ]);

        $barcode = trim($request->barcode);
        $transferId = $this->extractTransferIdFromBarcode($barcode);
        if (!$transferId) {
            return back()->with('error', 'Invalid barcode format.');
        }

        $transfer = WashingTransfer::with('job.product')->find($transferId);
        if (!$transfer) {
            return back()->with('error', 'Gate pass not found for scanned barcode.');
        }

        if ($transfer->parent_id !== null) {
            return back()->with('error', 'This barcode points to a receipt batch, not a gate pass. Scan the original gate pass barcode.');
        }

        if (!in_array($transfer->status, ['received'], true)) {
            if ($transfer->status === 'fully_received') {
                return back()->with('error', 'All pieces already received back for this gate pass. Send each receipt to QC from Received Back tab.');
            }
            if ($transfer->status === 'received_back') {
                return back()->with('error', 'This gate pass is ready for QC. Send it from Received Back tab.');
            }
            if ($transfer->status === 'washing_completed') {
                return back()->with('error', 'This gate pass has already been sent to QC.');
            }
            return back()->with('error', 'This gate pass is not currently in wash factory stage.');
        }

        $alreadyReceived = (int) ($transfer->quantity_returned ?? 0);
        $quantitySent = (int) $transfer->quantity_sent;
        $remaining = max(0, $quantitySent - $alreadyReceived);

        if ($remaining <= 0) {
            $transfer->update(['status' => 'received_back']);
            return back()->with('success', 'All pieces already received. Marked ready for QC.');
        }

        return back()->with('scan_receipt', [
            'transfer_id'      => $transfer->id,
            'job_number'       => $transfer->job?->job_number,
            'product_name'     => $transfer->job?->product?->name,
            'queue_no'         => $transfer->batch_no,
            'quantity_sent'    => $quantitySent,
            'already_received' => $alreadyReceived,
            'remaining'        => $remaining,
            'barcode'          => $barcode,
        ]);
    }

    public function receiveWashingQuantity(Request $request, $id)
    {
        $this->ensureWashingSupervisor();

        $request->validate([
            'qty_received' => 'required|integer|min:1',
        ]);

        $transfer = WashingTransfer::findOrFail($id);

        // Receipts can only be created against the parent gate pass while it still has pieces in wash.
        if ($transfer->parent_id !== null) {
            return back()->with('error', 'This record is already a receipt batch and cannot accept further pieces.');
        }

        if ($transfer->status !== 'received') {
            return back()->with('error', 'This gate pass is no longer in wash factory stage.');
        }

        $alreadyReceived = (int) ($transfer->quantity_returned ?? 0);
        $quantitySent = (int) $transfer->quantity_sent;
        $remaining = max(0, $quantitySent - $alreadyReceived);

        $qty = (int) $request->qty_received;
        if ($qty > $remaining) {
            return back()->with('error', "Cannot receive more than remaining qty ($remaining pcs).");
        }

        $newTotal = $alreadyReceived + $qty;

        // Create a child receipt batch. This row lives in the "Received Back" tab and can
        // be sent to QC independently, while the parent gate pass keeps tracking what's
        // still out in the wash factory.
        $childReceiptNo = WashingTransfer::where('parent_id', $transfer->id)->count() + 1;
        $childBatchNo = ($transfer->batch_no ?: ('GP' . $transfer->id)) . '-R' . str_pad((string) $childReceiptNo, 2, '0', STR_PAD_LEFT);

        $child = WashingTransfer::create([
            'manufacturing_job_id' => $transfer->manufacturing_job_id,
            'parent_id'            => $transfer->id,
            'quantity_sent'        => $qty,
            'sent_from'            => $transfer->sent_from,
            'washing_type'         => $transfer->washing_type,
            'vehicle_number'       => $transfer->vehicle_number,
            'washing_factory_name' => $transfer->washing_factory_name,
            'admin_signatory'      => $transfer->admin_signatory,
            'driver_signatory'     => $transfer->driver_signatory,
            'factory_signatory'    => $transfer->factory_signatory,
            'remarks'              => $transfer->remarks,
            'batch_no'             => $childBatchNo,
            'status'               => 'received_back',
            'quantity_returned'    => $qty,
        ]);

        // Update parent's cumulative received count and flip its status when fully received.
        $parentUpdate = ['quantity_returned' => $newTotal];
        $fullyReceived = $newTotal >= $quantitySent;
        if ($fullyReceived) {
            // 'fully_received' is an archival status — parent disappears from active tabs,
            // its children remain in Received Back / Sent to QC as they progress.
            $parentUpdate['status'] = 'fully_received';
        }
        $transfer->update($parentUpdate);

        if ($fullyReceived) {
            return back()->with('success', "Received $qty pcs as batch {$child->batch_no}. All $newTotal/$quantitySent pcs are now received back. Send each receipt to QC from 'Received Back' tab.");
        }

        $remainingNow = $quantitySent - $newTotal;
        return back()->with('success', "Received $qty pcs as batch {$child->batch_no} (now in 'Received Back'). Total received: $newTotal/$quantitySent. Remaining $remainingNow pcs in wash — scan again when more arrive.");
    }

    public function completeWashing($id)
    {
        $this->ensureWashingSupervisor();
        $transfer = WashingTransfer::findOrFail($id);
        if ($transfer->status != 'received_back') return back()->with('error', 'Batch must be received back first');

        $transfer->update(['status' => 'washing_completed']);

        if ($transfer->job) {
            $transfer->job->update(['status' => \App\Models\ManufacturingJob::STATUS_AFTER_WASH_RECEIVED]);
            \App\Services\WorkflowNotificationService::notifyTransfer($transfer->job, 'finishing');
        }

        return back()->with('success', 'Batch sent to QC successfully.');
    }

    // QC Section
    public function qcIndex()
    {
        // Receipt batches (children) and legacy parent rows marked washing_completed
        $pendingTransfers = WashingTransfer::where('status', 'washing_completed')
            ->with(['job', 'parent'])
            ->latest()
            ->get();
        return view('erp.manufacturing.qc', compact('pendingTransfers'));
    }

    public function qcJobDetail($id)
    {
        $transfer = WashingTransfer::with(['job', 'job.materialIssues.material', 'qcCheck'])->findOrFail($id);
        
        // Fetch accessories available to add
        $accessories = \App\Models\Material::where('type', 'accessory')->get();

        // Get packing batches for THIS transfer specifically
        $goodBatch = \App\Models\PackingBatch::where('washing_transfer_id', $transfer->id)
            ->where('type', 'good')
            ->with(['materialIssues.material', 'variationLines'])
            ->first();

        $damageBatch = \App\Models\PackingBatch::where('washing_transfer_id', $transfer->id)
            ->where('type', 'damage')->with('materialIssues.material')->first();
        
        // Self-Healing: If QC is done but batches missing (due to prev bug), create them now
        if ($transfer->qcCheck) {
            if (!$goodBatch && $transfer->qcCheck->finished_good_qty > 0) {
                $goodBatch = \App\Models\PackingBatch::create([
                    'manufacturing_job_id' => $transfer->manufacturing_job_id,
                    'washing_transfer_id' => $transfer->id,
                    'type' => 'good',
                    'quantity' => $transfer->qcCheck->finished_good_qty,
                    'status' => 'packing'
                ]);
            }
            if (!$damageBatch && $transfer->qcCheck->damaged_qty > 0) {
                $damageBatch = \App\Models\PackingBatch::create([
                    'manufacturing_job_id' => $transfer->manufacturing_job_id,
                    'washing_transfer_id' => $transfer->id,
                    'type' => 'damage',
                    'quantity' => $transfer->qcCheck->damaged_qty,
                    'status' => 'packing'
                ]);
            }
            // Refresh relation loading if needed, though we have variables
            $goodBatch = $goodBatch ? $goodBatch->load(['materialIssues.material', 'variationLines']) : null;
            $damageBatch = $damageBatch ? $damageBatch->load('materialIssues.material') : null;
        }

        $jobProduct = null;
        $productVariations = collect();
        $isVariableProduct = false;
        if ($transfer->job?->product_id) {
            $jobProduct = \App\Models\Product::with('variations')->find($transfer->job->product_id);
            if ($jobProduct) {
                $productVariations = $jobProduct->variations()
                    ->orderBy('variation_name')
                    ->get(['id', 'variation_name', 'sub_sku', 'default_purchase_price', 'default_sell_price', 'wholesale_price', 'retail_price']);
                $isVariableProduct = $productVariations->isNotEmpty() || $jobProduct->product_type === 'variable';
            }
        }

        $packingVariationQtys = collect();
        if ($goodBatch) {
            $packingVariationQtys = $goodBatch->variationLines->keyBy('product_variation_id');
        }

        $goodOutputQty = (float) ($transfer->qcCheck?->finished_good_qty ?? ($goodBatch?->quantity ?? 0));

        $needsVariationQty = $productVariations->count() > 0 && $goodBatch && $transfer->qcCheck;
        $variationQtySaved = !$needsVariationQty
            || ($goodBatch && $goodBatch->variationLines()->where('quantity', '>', 0)->exists());

        $jobCostPerUnit = 0.0;
        if ($transfer->job) {
            $jobForCost = \App\Models\ManufacturingJob::with(['materialIssues.material', 'packingBatches.materialIssues.material', 'additionalCosts'])
                ->find($transfer->job->id);
            if ($jobForCost) {
                $costBreakdown = $jobForCost->costBreakdown();
                $jobCostPerUnit = (float) ($costBreakdown['cost_per_unit_with_overhead'] ?? $jobForCost->costPerUnit());
            }
        }

        return view('erp.manufacturing.qc_detail', compact(
            'transfer',
            'accessories',
            'goodBatch',
            'damageBatch',
            'jobProduct',
            'productVariations',
            'isVariableProduct',
            'packingVariationQtys',
            'goodOutputQty',
            'needsVariationQty',
            'variationQtySaved',
            'jobCostPerUnit'
        ));
    }

    public function savePackingVariationQty(Request $request)
    {
        $request->validate([
            'packing_batch_id' => 'required|exists:packing_batches,id',
            'quantities' => 'required|array',
            'quantities.*' => 'nullable|numeric|min:0',
        ]);

        $batch = \App\Models\PackingBatch::with('job')->findOrFail($request->packing_batch_id);

        if ($batch->type !== 'good') {
            return back()->with('error', 'Variation quantities apply to good packing batches only.');
        }

        if ($batch->status !== 'packing') {
            return back()->with('error', 'This batch can no longer be edited.');
        }

        $productId = $batch->job?->product_id;
        if (!$productId) {
            return back()->with('error', 'No product linked to this job.');
        }

        $lines = [];
        $total = 0;
        foreach ($request->quantities as $variationId => $qty) {
            $qty = round((float) $qty, 2);
            if ($qty <= 0) {
                continue;
            }

            $variation = \App\Models\ProductVariation::where('product_id', $productId)->find($variationId);
            if (!$variation) {
                return back()->with('error', 'Invalid variation selected.');
            }

            $lines[] = ['variation_id' => $variation->id, 'qty' => $qty];
            $total += $qty;
        }

        if ($total <= 0) {
            return back()->with('error', 'Enter quantity for at least one variation.');
        }

        if ($total > (float) $batch->quantity + 0.0001) {
            return back()->with('error', 'Total variation qty (' . $total . ') cannot exceed good output (' . $batch->quantity . ' pcs).');
        }

        if ($total < (float) $batch->quantity - 0.0001) {
            return back()->with('error', 'Total variation qty (' . $total . ') must equal good output (' . $batch->quantity . ' pcs).');
        }

        \DB::transaction(function () use ($batch, $lines) {
            $batch->variationLines()->delete();
            foreach ($lines as $line) {
                \App\Models\PackingBatchVariation::create([
                    'packing_batch_id' => $batch->id,
                    'product_variation_id' => $line['variation_id'],
                    'quantity' => $line['qty'],
                ]);
            }
        });

        return back()->with('success', 'Variation quantities saved (' . $total . ' pcs).');
    }

    public function addQcAccessory(Request $request)
    {
        $request->validate([
            'job_id' => 'required|exists:manufacturing_jobs,id',
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
            'packing_batch_id' => 'required|exists:packing_batches,id',
        ]);

        $batch = \App\Models\PackingBatch::where('id', $request->packing_batch_id)
            ->where('manufacturing_job_id', $request->job_id) // Security check
            ->firstOrFail();

        if ($batch->status !== 'packing') {
            return back()->with('error', 'This batch can no longer accept accessories.');
        }

        try {
            \DB::transaction(function () use ($request, $batch) {
                $material = \App\Models\Material::lockForUpdate()->findOrFail($request->material_id);
                if ($material->quantity < $request->quantity) {
                    throw new \RuntimeException("Insufficient stock. Available: {$material->quantity}");
                }

                $material->decrement('quantity', $request->quantity);

                MaterialIssue::create([
                    'manufacturing_job_id' => $request->job_id,
                    'packing_batch_id' => $batch->id,
                    'material_id' => $material->id,
                    'quantity_issued' => $request->quantity,
                    'issued_to' => 'Packing (' . ucfirst($batch->type) . ') - Job ' . $request->job_id,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Accessory added to ' . ucfirst($batch->type) . ' batch.');
    }

    public function completePacking(Request $request)
    {
        $request->validate(['packing_batch_id' => 'required|exists:packing_batches,id']);
        $batch = \App\Models\PackingBatch::with('job')->findOrFail($request->packing_batch_id);

        if ($batch->status !== 'packing') {
            return back()->with('error', 'This batch is already processed.');
        }

        if ($batch->type !== 'damage' && $batch->job?->product_id) {
            $product = \App\Models\Product::find($batch->job->product_id);
            if ($product && $product->variations()->exists()) {
                if ($product->product_type !== 'variable') {
                    $product->update(['product_type' => 'variable']);
                }
                if (!$batch->variationLines()->where('quantity', '>', 0)->exists()) {
                    return back()->with('error', 'පළමුව L/M/S වගේ variation එකකට qty save කරන්න (Save Variation Quantities), ඊට පස්සේ Move to Final Checking.');
                }
            }
        }

        \DB::transaction(function () use ($batch) {
            // Good batch -> move to Final Checking queue
            // Damage batch -> complete here and create/update DProduct stock immediately
            if ($batch->type === 'damage') {
                if ($batch->job && $batch->job->product_id) {
                    $originalProduct = \App\Models\Product::find($batch->job->product_id);
                    if ($originalProduct) {
                        $jobForCost = \App\Models\ManufacturingJob::with(['materialIssues.material', 'packingBatches.materialIssues.material', 'additionalCosts'])
                            ->find($batch->job->id);
                        $jobCostPerUnit = 0.0;
                        if ($jobForCost) {
                            $costBreakdown = $jobForCost->costBreakdown();
                            $jobCostPerUnit = (float) ($costBreakdown['cost_per_unit_with_overhead'] ?? $jobForCost->costPerUnit());
                        }

                        $damageProduct = \App\Models\Product::firstOrCreate(
                            [
                                'name' => 'Damage - ' . $originalProduct->name,
                                'classification' => 'damage',
                            ],
                            [
                                'sku' => ($originalProduct->sku ?: 'SKU-' . $originalProduct->id) . '-DMG',
                                'product_type' => 'simple',
                                'category_id' => $originalProduct->category_id,
                                'unit_id' => $originalProduct->unit_id,
                                'status' => 'draft',
                                'is_active' => true,
                                'selling_price' => 0,
                                'cost_price' => $jobCostPerUnit > 0 ? $jobCostPerUnit : 0,
                            ]
                        );

                        $damageUpdate = [
                            'classification' => 'damage',
                            'status' => 'draft',
                            'is_active' => true,
                        ];
                        if ($jobCostPerUnit > 0) {
                            $damageUpdate['cost_price'] = $jobCostPerUnit;
                        }
                        $damageProduct->update($damageUpdate);

                        \App\Models\StockLedger::create([
                            'product_id' => $damageProduct->id,
                            'channel' => 'wholesale',
                            'transaction_type' => 'transfer_in',
                            'quantity' => $batch->quantity,
                            'created_by' => auth()->id(),
                            'reference_type' => \App\Models\ManufacturingJob::class,
                            'reference_id' => $batch->job->id,
                            'notes' => 'Manufacturing damage → Wholesale stock',
                        ]);
                    }
                }

                $batch->update(['status' => 'completed']);
            } else {
                $batch->update(['status' => 'sent_to_final_check']);
            }

            $job = $batch->job;
            $allJobBatchesDone = $job && $job->packingBatches()->where('status', 'packing')->count() == 0;
            if ($allJobBatchesDone) {
                $job->update(['status' => \App\Models\ManufacturingJob::STATUS_PACKING_COMPLETED]);
            }

            $transfer = $batch->washing_transfer_id ? \App\Models\WashingTransfer::find($batch->washing_transfer_id) : null;
            if ($transfer) {
                $transferBatches = \App\Models\PackingBatch::where('washing_transfer_id', $transfer->id)->get();
                $allSent = $transferBatches->every(fn ($b) => in_array($b->status, ['sent_to_final_check', 'completed']));
                if ($allSent) {
                    $transfer->update(['status' => 'returned']);
                }
            }
        });

        $batch->refresh();
        $transfer = $batch->washing_transfer_id ? \App\Models\WashingTransfer::find($batch->washing_transfer_id) : null;
        $qcComplete = $transfer && $transfer->status === 'returned';

        $message = $batch->type === 'damage'
            ? 'Damage batch completed and stock updated.'
            : 'Good batch sent to Final Checking.';
        if ($qcComplete) {
            $message .= ' QC complete for this batch — job removed from QC list.';
        }
        return back()->with('success', $message);
    }
    public function storeQc(Request $request)
    {
        $request->validate([
            'washing_transfer_id' => 'required|exists:washing_transfers,id',
            'finished_good_qty' => 'required|numeric|min:0',
            'damaged_qty' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $transfer = WashingTransfer::findOrFail($request->washing_transfer_id);

        if ($transfer->status !== 'washing_completed') {
            return back()->with('error', 'This batch is not ready for QC yet.');
        }

        $goodQty = (float) $request->finished_good_qty;
        $damageQty = (float) $request->damaged_qty;
        $totalChecked = (float) $transfer->quantity_sent;

        if (abs(($goodQty + $damageQty) - $totalChecked) > 0.0001) {
            return back()->with('error', "Total ($goodQty + $damageQty) does not match received qty ($totalChecked).");
        }

        $packingAlreadyMoved = \App\Models\PackingBatch::where('washing_transfer_id', $transfer->id)
            ->whereIn('status', ['sent_to_final_check', 'completed'])
            ->exists();
        if ($packingAlreadyMoved) {
            return back()->with('error', 'QC cannot be changed after packing has been sent.');
        }

        $jobId = $transfer->job ? $transfer->job->id : null;

        \DB::transaction(function () use ($transfer, $totalChecked, $goodQty, $damageQty, $jobId) {
            QcCheck::updateOrCreate(
                ['washing_transfer_id' => $transfer->id],
                [
                    'total_checked' => $totalChecked,
                    'finished_good_qty' => $goodQty,
                    'damaged_qty' => $damageQty,
                ]
            );

            if ($goodQty > 0) {
                \App\Models\PackingBatch::updateOrCreate(
                    [
                        'washing_transfer_id' => $transfer->id,
                        'type' => 'good',
                    ],
                    [
                        'manufacturing_job_id' => $jobId,
                        'quantity' => $goodQty,
                        'status' => 'packing',
                    ]
                );
            } else {
                \App\Models\PackingBatch::where('washing_transfer_id', $transfer->id)
                    ->where('type', 'good')
                    ->where('status', 'packing')
                    ->delete();
            }

            if ($damageQty > 0) {
                \App\Models\PackingBatch::updateOrCreate(
                    [
                        'washing_transfer_id' => $transfer->id,
                        'type' => 'damage',
                    ],
                    [
                        'manufacturing_job_id' => $jobId,
                        'quantity' => $damageQty,
                        'status' => 'packing',
                    ]
                );
            } else {
                \App\Models\PackingBatch::where('washing_transfer_id', $transfer->id)
                    ->where('type', 'damage')
                    ->where('status', 'packing')
                    ->delete();
            }
        });

        return back()->with('success', 'QC Data Saved. Send good batch to Final Checking and mark damage done. Then job will leave QC list.');
    }

    public function printBarcode($id)
    {
        $batch = \App\Models\PackingBatch::with('job')->findOrFail($id);
        return view('erp.manufacturing.print_barcode', compact('batch'));
    }

    // Final Checking Section
    public function finalCheckingIndex()
    {
        // Get jobs that have at least one batch sent to final checking
        $batchJobIds = \App\Models\PackingBatch::where('status', 'sent_to_final_check')
            ->pluck('manufacturing_job_id')
            ->unique();
        
        $jobs = \App\Models\ManufacturingJob::whereIn('id', $batchJobIds)->get();

        return view('erp.manufacturing.final_checking', compact('jobs'));
    }

    public function finalCheckingDetail($jobId)
    {
        $job = \App\Models\ManufacturingJob::with([
            'packingBatches.materialIssues.material',
            'packingBatches.variationLines.productVariation',
            'materialIssues.material',
            'materialIssue.material',
            'additionalCosts.creator',
        ])->findOrFail($jobId);
        
        // Filter to show ONLY batches that have arrived at Final Checking
        $allowedStatuses = ['sent_to_final_check', 'completed'];
        
        $goodBatches = $job->packingBatches->where('type', 'good')->whereIn('status', $allowedStatuses);
        $damageBatches = $job->packingBatches->where('type', 'damage')->whereIn('status', $allowedStatuses);

        $productVariations = collect();
        $isVariableProduct = false;
        $finalCost = null;
        if ($job->product_id) {
            $product = \App\Models\Product::with('variations')->find($job->product_id);
            if ($product) {
                $productVariations = $product->variations()->orderBy('variation_name')->get();
                $isVariableProduct = $productVariations->isNotEmpty() || $product->product_type === 'variable';
            }
        }

        if (auth()->user()?->can_access('manufacturing.costs')) {
            $finalCost = app(\App\Services\ProductFinalCostReportService::class)->buildForJob($job);
        }

        return view('erp.manufacturing.final_checking_detail', compact('job', 'goodBatches', 'damageBatches', 'productVariations', 'isVariableProduct', 'finalCost'));
    }

    public function finalizeBatch(Request $request, $id)
    {
        $batch = \App\Models\PackingBatch::with('job')->findOrFail($id);

        if ($batch->status == 'completed') {
            return back()->with('error', 'Batch already finalized.');
        }

        if ($batch->type === 'good' && $batch->status !== 'sent_to_final_check') {
            return back()->with('error', 'Send this batch to Final Checking before converting to product.');
        }

        try {
            \DB::beginTransaction();

            $batch->update(['status' => 'completed']);

            $jobForCost = $batch->job
                ? \App\Models\ManufacturingJob::with(['materialIssues.material', 'packingBatches.materialIssues.material', 'additionalCosts', 'product'])->find($batch->job->id)
                : null;
            $costBreakdown = $jobForCost ? $jobForCost->costBreakdown() : [];
            $jobCostPerUnit = (float) ($costBreakdown['cost_per_unit_with_overhead'] ?? $jobForCost?->costPerUnit() ?? 0);

            // If it's a GOOD batch and linked to a Product, update Stock and set Draft
            if ($batch->type == 'good' && $batch->job && $batch->job->product_id) {
                $product = \App\Models\Product::find($batch->job->product_id);
                if ($product) {
                    $productUpdate = ['status' => 'draft'];
                    if ($jobCostPerUnit > 0) {
                        $productUpdate['cost_price'] = $jobCostPerUnit;
                    }
                    if ($product->variations()->exists()) {
                        $productUpdate['product_type'] = 'variable';
                    }
                    $product->update($productUpdate);

                    if ($product->variations()->exists()) {
                        $batch->load('variationLines.productVariation');
                        $lines = $batch->variationLines->filter(fn ($l) => (float) $l->quantity > 0);

                        if ($lines->isEmpty()) {
                            throw new \RuntimeException('Save variation quantities on QC & Packing before converting to product.');
                        }

                        // Apply manufacturing cost to ALL product variations (admin sets sell prices later).
                        if ($jobCostPerUnit > 0) {
                            $product->variations()->update(['default_purchase_price' => $jobCostPerUnit]);
                        }

                        foreach ($lines as $line) {
                            \App\Models\StockLedger::create([
                                'product_id' => $product->id,
                                'product_variation_id' => $line->product_variation_id,
                                'channel' => 'wholesale',
                                'transaction_type' => 'transfer_in',
                                'quantity' => $line->quantity,
                                'created_by' => auth()->id(),
                                'reference_type' => \App\Models\ManufacturingJob::class,
                                'reference_id' => $batch->job->id,
                                'notes' => 'Manufacturing → ' . ($line->productVariation?->variation_name ?? 'variation') . ' (Batch #' . $batch->id . ')',
                            ]);
                        }
                    } else {
                        \App\Models\StockLedger::create([
                            'product_id' => $product->id,
                            'product_variation_id' => null,
                            'channel' => 'wholesale',
                            'transaction_type' => 'transfer_in',
                            'quantity' => $batch->quantity,
                            'created_by' => auth()->id(),
                            'reference_type' => \App\Models\ManufacturingJob::class,
                            'reference_id' => $batch->job->id,
                            'notes' => 'Manufacturing complete → Wholesale stock (Batch #' . $batch->id . ')',
                        ]);
                    }
                }
            }
            
            // If damage, create or find "Damage" product
            if ($batch->type == 'damage' && $batch->job && $batch->job->product_id) {
                $originalProduct = \App\Models\Product::find($batch->job->product_id);
                if ($originalProduct) {
                    // Find or Create Damage Product
                    $damageProduct = \App\Models\Product::firstOrCreate(
                        [
                            'name' => 'Damage - ' . $originalProduct->name,
                            'classification' => 'damage'
                        ],
                        [
                            'sku' => ($originalProduct->sku ?: 'SKU-' . $originalProduct->id) . '-DMG',
                            'product_type' => 'simple',
                            'category_id' => $originalProduct->category_id,
                            'unit_id' => $originalProduct->unit_id,
                            'status' => 'draft',
                            'is_active' => true,
                            'selling_price' => 0,
                            'cost_price' => $jobCostPerUnit > 0 ? $jobCostPerUnit : 0,
                        ]
                    );

                    // Ensure it always appears in Dproducts tab AND keep cost up-to-date
                    // with the latest production run.
                    $damageUpdate = [
                        'classification' => 'damage',
                        'status' => 'draft',
                        'is_active' => true,
                    ];
                    if ($jobCostPerUnit > 0) {
                        $damageUpdate['cost_price'] = $jobCostPerUnit;
                    }
                    $damageProduct->update($damageUpdate);

                    \App\Models\StockLedger::create([
                        'product_id' => $damageProduct->id,
                        'channel' => 'wholesale',
                        'transaction_type' => 'transfer_in',
                        'quantity' => $batch->quantity,
                        'created_by' => auth()->id(),
                        'reference_type' => \App\Models\ManufacturingJob::class,
                        'reference_id' => $batch->job->id,
                        'notes' => 'Manufacturing damage → Wholesale stock',
                    ]);
                }
            }
            
            // If damage, maybe we log it elsewhere or just mark as completed for now

            \DB::commit();

            // Check if ALL batches for this job are completed
            $jobRef = $batch->job;
            if ($jobRef) {
                $incompleteBatches = $jobRef->packingBatches()->where('status', '!=', 'completed')->exists();
                if (!$incompleteBatches) {
                    $jobRef->update(['status' => \App\Models\ManufacturingJob::STATUS_WAREHOUSE_RECEIVED]);
                }
            }

            $message = 'Batch received to warehouse and stock updated.';
            if ($jobCostPerUnit > 0) {
                $message .= ' Manufacturing cost (LKR ' . number_format($jobCostPerUnit, 2) . '/pc) applied to product & variations.';
            }

            $productId = $batch->job?->product_id;
            if ($productId && $batch->type === 'good') {
                return redirect()
                    ->route('products.index', ['tab' => 'gproducts'])
                    ->with('success', $message . ' Admin: open product → set selling prices → Publish.');
            }

            if ($productId && $batch->type === 'damage') {
                return redirect()
                    ->route('products.index', ['tab' => 'dproducts'])
                    ->with('success', $message);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            \DB::rollback();
            return back()->with('error', 'Error finalizing batch: ' . $e->getMessage());
        }
    }

    /**
     * Ensure the current user is the supervisor of the washing section. Abort 403 otherwise.
     */
    protected function ensureWashingSupervisor(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if ($user->isSystemAdmin() || $user->can_access('manufacturing.washing')) {
            return;
        }
        abort(403, 'You need Washing / Gate Pass permission to receive or complete washing.');
    }

    protected function extractTransferIdFromBarcode(string $barcode): ?int
    {
        $parts = explode('-', $barcode);
        if (count($parts) < 2) {
            return null;
        }

        $last = trim((string) end($parts));
        return ctype_digit($last) ? (int) $last : null;
    }
}
