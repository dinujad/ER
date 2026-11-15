<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

/**
 * Time-limited signed URL so WhatsApp / SMS links open without a browser session.
 */
class OrderPaymentChequeSignedImageController extends Controller
{
    public function __invoke(Payment $payment)
    {
        $path = $payment->cheque_image_path;
        if (!$path || strtolower((string) $payment->payment_method) !== 'cheque') {
            abort(404);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, basename($path), [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }
}
