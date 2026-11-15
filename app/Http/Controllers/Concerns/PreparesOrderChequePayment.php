<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait PreparesOrderChequePayment
{
    /**
     * Extra validation rules when payment_method is cheque.
     *
     * @return array<string, mixed>
     */
    protected function orderChequeValidationRules(Request $request): array
    {
        if (strtolower((string) $request->payment_method) !== 'cheque') {
            return [];
        }

        return $this->orderChequeFieldValidationRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderChequeFieldValidationRules(): array
    {
        return [
            'cheque_date' => 'required|date',
            'cheque_image' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:5120',
            'cheque_exception_requested' => 'nullable|in:0,1',
            'cheque_exception_reason' => 'nullable|string|max:5000',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderChequeValidationRulesForPrefix(Request $request, string $prefix): array
    {
        if (strtolower((string) $request->input("{$prefix}.payment_method")) !== 'cheque') {
            return [];
        }

        $rules = [];
        foreach ($this->orderChequeFieldValidationRules() as $field => $rule) {
            $rules["{$prefix}.{$field}"] = $rule;
        }

        return $rules;
    }

    /**
     * Cheque-specific columns for payments.create(). Non-cheque methods return nulls / false.
     *
     * @return array<string, mixed>|RedirectResponse
     */
    protected function prepareOrderChequePaymentFields(Request $request): array|RedirectResponse
    {
        if (strtolower((string) $request->payment_method) !== 'cheque') {
            return $this->emptyChequePaymentFields();
        }

        return $this->prepareChequePaymentFieldsFromInput(
            $request->cheque_date,
            $request->file('cheque_image'),
            $request->boolean('cheque_exception_requested'),
            (string) $request->input('cheque_exception_reason', ''),
            'cheque_date',
            'cheque_exception_reason'
        );
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    protected function prepareOrderChequePaymentFieldsForLine(Request $request, int $index): array|RedirectResponse
    {
        $prefix = "payments.{$index}";

        if (strtolower((string) $request->input("{$prefix}.payment_method")) !== 'cheque') {
            return $this->emptyChequePaymentFields();
        }

        return $this->prepareChequePaymentFieldsFromInput(
            $request->input("{$prefix}.cheque_date"),
            $request->file("{$prefix}.cheque_image"),
            $request->boolean("{$prefix}.cheque_exception_requested"),
            (string) $request->input("{$prefix}.cheque_exception_reason", ''),
            "{$prefix}.cheque_date",
            "{$prefix}.cheque_exception_reason"
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyChequePaymentFields(): array
    {
        return [
            'cheque_date' => null,
            'cheque_image_path' => null,
            'cheque_exception_requested' => false,
            'cheque_exception_reason' => null,
        ];
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    protected function prepareChequePaymentFieldsFromInput(
        mixed $chequeDateInput,
        mixed $chequeImageFile,
        bool $exceptionRequested,
        string $exceptionReasonRaw,
        string $dateErrorKey,
        string $reasonErrorKey
    ): array|RedirectResponse {
        if (!$chequeDateInput) {
            return back()->withErrors([
                $dateErrorKey => 'Cheque date is required for cheque payments.',
            ])->withInput();
        }

        $chequeDate = Carbon::parse($chequeDateInput)->startOfDay();
        $today = Carbon::today();
        $maxNormal = $today->copy()->addDays(45);

        if ($exceptionRequested) {
            $reason = trim($exceptionReasonRaw);
            if (strlen($reason) < 30) {
                return back()->withErrors([
                    $reasonErrorKey => 'Please write at least 30 characters in English explaining why a cheque date outside the standard 45-day window is required (for management review).',
                ])->withInput();
            }
            if ($chequeDate->gt($today->copy()->addDays(180))) {
                return back()->withErrors([
                    $dateErrorKey => 'With an exception request, the cheque date may not be more than 180 days from today.',
                ])->withInput();
            }
            if ($chequeDate->lt($today->copy()->subDays(30))) {
                return back()->withErrors([
                    $dateErrorKey => 'Cheque date cannot be more than 30 days before today.',
                ])->withInput();
            }
            $exceptionReason = $reason;
        } else {
            if ($chequeDate->lt($today) || $chequeDate->gt($maxNormal)) {
                return back()->withErrors([
                    $dateErrorKey => 'Cheque date must be between today and 45 days from today, unless you request an exception with an English explanation.',
                ])->withInput();
            }
            $exceptionReason = null;
        }

        return [
            'cheque_date' => $chequeDate,
            'cheque_image_path' => $chequeImageFile ? $chequeImageFile->store('order-cheques', 'public') : null,
            'cheque_exception_requested' => $exceptionRequested,
            'cheque_exception_reason' => $exceptionReason,
        ];
    }
}
