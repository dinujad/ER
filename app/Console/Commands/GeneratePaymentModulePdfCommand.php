<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GeneratePaymentModulePdfCommand extends Command
{
    protected $signature = 'docs:payment-module-pdf {--output= : Output path relative to project root}';

    protected $description = 'Generate client PDF guide for Payment & Cheque module';

    public function handle(): int
    {
        $output = $this->option('output')
            ?: 'docs/DERMAS_Payment_Cheque_Module_Guide.pdf';

        $fullPath = base_path($output);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf = Pdf::loadView('docs.payment-cheque-module-pdf')
            ->setPaper('a4', 'portrait');

        file_put_contents($fullPath, $pdf->output());

        $this->info('PDF generated: ' . $fullPath);

        return self::SUCCESS;
    }
}
