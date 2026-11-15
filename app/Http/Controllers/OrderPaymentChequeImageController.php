<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderPaymentChequeImageController extends Controller
{
    public function __invoke(Request $request, Payment $payment)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        if ($user->role === 'driver') {
            abort(403);
        }

        $payment->loadMissing('order');

        if ($user->role === 'sales_person' && (int) $payment->order->user_id !== (int) $user->id) {
            abort(403);
        }

        $path = $payment->cheque_image_path;
        if (!$path || strtolower((string) $payment->payment_method) !== 'cheque') {
            abort(404);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, basename($path), [
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}
