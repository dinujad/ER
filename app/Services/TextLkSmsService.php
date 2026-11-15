<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextLkSmsService
{
    public function sendSms(string $recipient, string $message): bool
    {
        $url = config('services.textlk.url');
        $apiKey = config('services.textlk.api_key');
        $senderId = config('services.textlk.sender_id');

        if (empty($apiKey) || empty($url)) {
            Log::warning('Text.lk SMS: Missing TEXTLK_API_KEY or TEXTLK_URL');
            return false;
        }

        $phone = $this->normalizePhone($recipient);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($url, [
            'recipient' => $phone,
            'sender_id' => $senderId ?: config('app.name'),
            'type' => 'plain',
            'message' => $message,
        ]);

        if (!$response->successful()) {
            Log::warning('Text.lk SMS failed', [
                'recipient' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Normalize Sri Lankan phone to 94XXXXXXXXX format.
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '94' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '94')) {
            $phone = '94' . $phone;
        }
        return $phone;
    }
}
