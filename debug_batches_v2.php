<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$batches = \App\Models\PackingBatch::where('manufacturing_job_id', 5)->get();
foreach($batches as $batch) {
    echo "ID: {$batch->id} | Type: {$batch->type} | Qty: {$batch->quantity} | WashingID: " . ($batch->washing_transfer_id ?? 'NULL') . " | Created: {$batch->created_at}\n";
}
