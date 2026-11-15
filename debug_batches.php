<?php
use App\Models\PackingBatch;

$batches = PackingBatch::where('manufacturing_job_id', 5)->get();
foreach($batches as $batch) {
    echo "ID: {$batch->id} | Type: {$batch->type} | Qty: {$batch->quantity} | WashingID: {$batch->washing_transfer_id} | Created: {$batch->created_at}\n";
}
