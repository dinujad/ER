<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use App\Models\Payment;
use App\Services\SaleReturnClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

trait StoresOrderMultiPayment
{
    protected function storeOrderMultiPayment(Request $request, Order $order, string $redirectUrl): RedirectResponse
    {
        if (!$order->canAcceptPayments()) {
            return redirect()->to($redirectUrl)
                ->with('error', 'Payments can only be added after Order Check is completed (order must be After Checking).');
        }

        if ($order->due_amount <= 0) {
            return redirect()->to($redirectUrl)
                ->with('error', 'This order is already fully paid.');
        }

        $activeLines = [];
        foreach ($request->input('payments', []) as $index => $line) {
            $settlement = (float) ($line['settlement_amount'] ?? $line['amount'] ?? 0);
            if ($settlement <= 0) {
                continue;
            }
            $activeLines[$index] = [
                'payment_method' => (string) ($line['payment_method'] ?? ''),
                'settlement_amount' => $settlement,
                'discount_type' => $line['discount_type'] ?? 'fixed',
                'discount_value' => (float) ($line['discount_value'] ?? 0),
            ];
        }

        $claimRequested = $request->boolean('claim_return');
        $claimAmount = $claimRequested ? round((float) $request->input('claim_amount', 0), 2) : 0.0;
        $transportAmount = round(max(0, (float) $request->input('transport_amount', 0)), 2);
        if ($claimRequested && $claimAmount <= 0) {
            return redirect()->to($redirectUrl)
                ->withErrors(['claim_amount' => 'Enter the return credit amount to claim.'])
                ->withInput();
        }

        if ($activeLines === [] && $claimAmount <= 0 && $transportAmount <= 0) {
            return redirect()->to($redirectUrl)
                ->withErrors(['payments' => 'Add at least one payment row with a settlement amount.'])
                ->withInput();
        }

        $rules = [
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'transport_amount' => 'nullable|numeric|min:0',
        ];

        foreach (array_keys($activeLines) as $index) {
            $rules["payments.{$index}.payment_method"] = 'required|string|in:cash,bank_transfer,cheque,card';
            $rules["payments.{$index}.settlement_amount"] = 'required|numeric|min:0.01';
            $rules["payments.{$index}.discount_type"] = 'nullable|in:fixed,percentage';
            $rules["payments.{$index}.discount_value"] = 'nullable|numeric|min:0';
            $rules = array_merge(
                $rules,
                $this->orderChequeValidationRulesForPrefix($request, "payments.{$index}")
            );
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->to($redirectUrl)
                ->withErrors($validator)
                ->withInput();
        }

        $preparedLines = [];
        $totalSettlement = 0.0;
        $totalReceived = 0.0;

        foreach ($activeLines as $index => $line) {
            $totals = Payment::computeLineTotals(
                $line['settlement_amount'],
                $line['discount_type'],
                $line['discount_value'],
                strtolower($line['payment_method']) === 'cash'
            );

            if ($totals['amount'] <= 0) {
                return redirect()->to($redirectUrl)
                    ->withErrors([
                        "payments.{$index}.settlement_amount" => 'Discount cannot equal or exceed the settlement amount.',
                    ])
                    ->withInput();
            }

            $chequeFields = $this->prepareOrderChequePaymentFieldsForLine($request, $index);
            if ($chequeFields instanceof RedirectResponse) {
                $session = $chequeFields->getSession();
                $redirect = redirect()->to($redirectUrl);
                if ($session && $session->has('_old_input')) {
                    $redirect = $redirect->withInput($session->get('_old_input'));
                }
                if ($session && $session->has('errors')) {
                    $redirect = $redirect->withErrors($session->get('errors'));
                }

                return $redirect;
            }

            $preparedLines[] = array_merge($line, $totals, $chequeFields);
            $totalSettlement += $totals['settlement_amount'];
            $totalReceived += $totals['amount'];
        }

        if ($transportAmount > 0) {
            $preparedLines[] = array_merge($this->emptyChequePaymentFields(), [
                'payment_method' => 'transport',
                'amount' => $transportAmount,
                'settlement_amount' => $transportAmount,
                'discount_type' => null,
                'discount_value' => null,
                'discount_amount' => 0,
            ]);
            $totalSettlement += $transportAmount;
            $totalReceived += $transportAmount;
        }

        $totalSettlement = round($totalSettlement, 2);
        $dueAmount = round((float) $order->due_amount, 2);

        if ($claimAmount > $dueAmount + 0.009) {
            return redirect()->to($redirectUrl)
                ->withErrors([
                    'claim_amount' => 'Claim cannot exceed the amount still due (Rs ' . number_format($dueAmount, 2) . ').',
                ])
                ->withInput();
        }

        if ($totalSettlement + $claimAmount > $dueAmount + 0.009) {
            return redirect()->to($redirectUrl)
                ->withErrors([
                    'payments' => 'Payment, transport, and return claim together (Rs ' . number_format($totalSettlement + $claimAmount, 2) . ') cannot exceed the amount still due (Rs ' . number_format($dueAmount, 2) . ').',
                ])
                ->withInput();
        }

        $sharedReference = trim((string) $request->reference_number);
        $sharedNotes = trim((string) $request->notes);
        $batchLabel = 'Batch ' . now()->format('Y-m-d H:i');
        $createdCount = count($preparedLines);

        try {
            DB::transaction(function () use ($request, $order, $preparedLines, $sharedReference, $sharedNotes, $batchLabel, $claimAmount) {
                if ($claimAmount > 0) {
                    app(SaleReturnClaimService::class)->applyToOrder($order, $claimAmount, auth()->id());
                }
            foreach ($preparedLines as $line) {
                $lineNotes = strtolower((string) $line['payment_method']) === 'transport' ? 'Transport' : $batchLabel;
                if ($sharedReference !== '') {
                    $lineNotes .= ' | Ref: ' . $sharedReference;
                }
                if ($sharedNotes !== '') {
                    $lineNotes .= ' | ' . $sharedNotes;
                }
                if (($line['discount_amount'] ?? 0) > 0) {
                    if (strtolower((string) $line['payment_method']) === 'cash') {
                        $lineNotes .= sprintf(
                            ' | Cash discount %s reduced the next payment by Rs %s',
                            $line['discount_type'] === 'percentage'
                                ? number_format($line['discount_value'], 2).'%'
                                : 'Rs '.number_format($line['discount_value'], 2),
                            number_format($line['discount_amount'], 2)
                        );
                    } else {
                        $lineNotes .= sprintf(
                            ' | Discount: %s %s (Rs %s)',
                            $line['discount_type'] === 'percentage'
                                ? number_format($line['discount_value'], 2) . '%'
                                : 'Fixed',
                            $line['discount_type'] === 'percentage' ? '' : number_format($line['discount_value'], 2),
                            number_format($line['discount_amount'], 2)
                        );
                    }
                }

                $order->payments()->create([
                    'amount' => $line['amount'],
                    'settlement_amount' => $line['settlement_amount'],
                    'discount_type' => ($line['discount_amount'] ?? 0) > 0 ? $line['discount_type'] : null,
                    'discount_value' => ($line['discount_amount'] ?? 0) > 0 ? $line['discount_value'] : null,
                    'discount_amount' => $line['discount_amount'] ?? 0,
                    'payment_date' => $request->payment_date,
                    'payment_method' => $line['payment_method'],
                    'reference_number' => $sharedReference ?: null,
                    'notes' => $lineNotes,
                    'cheque_date' => $line['cheque_date'],
                    'cheque_image_path' => $line['cheque_image_path'],
                    'cheque_exception_requested' => $line['cheque_exception_requested'],
                    'cheque_exception_reason' => $line['cheque_exception_reason'],
                    'cheque_status' => strtolower((string) $line['payment_method']) === 'cheque'
                        ? Payment::CHEQUE_RECEIVED
                        : null,
                ]);
            }
            });
        } catch (InvalidArgumentException $e) {
            return redirect()->to($redirectUrl)
                ->withErrors(['claim_amount' => $e->getMessage()])
                ->withInput();
        }

        $discountTotal = round(collect($preparedLines)->sum('discount_amount'), 2);
        $message = $createdCount === 0
            ? sprintf('Return claim of Rs %s applied.', number_format($claimAmount, 2))
            : sprintf(
                'Payments saved. Received: Rs %s (%d line%s)',
                number_format($totalReceived, 2),
                $createdCount,
                $createdCount === 1 ? '' : 's'
            );
        if ($discountTotal > 0) {
            $message .= sprintf('; payment discount: Rs %s', number_format($discountTotal, 2));
        }
        if ($claimAmount > 0) {
            $message .= sprintf('; return claim: Rs %s', number_format($claimAmount, 2));
        }
        if ($transportAmount > 0) {
            $message .= sprintf('; transport: Rs %s', number_format($transportAmount, 2));
        }

        return redirect()->to($redirectUrl)->with('success', $message);
    }
}
