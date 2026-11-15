<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostGrapWhatsAppService
{
    public function isConfigured(): bool
    {
        return $this->email() !== null
            && $this->email() !== ''
            && $this->apiKey() !== null
            && $this->apiKey() !== '';
    }

    public function sendText(string $recipient, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('HostGrap WhatsApp: missing email or API key (ERP settings or .env).');

            return false;
        }

        $phone = $this->normalizePhone($recipient);
        if ($phone === '') {
            return false;
        }

        $url = config('services.hostgrap.send_message_url');

        $response = Http::asForm()->timeout(30)->post($url, [
            'email' => $this->email(),
            'api_key' => $this->apiKey(),
            'phone' => $phone,
            'message' => $message,
        ]);

        if (!$response->successful()) {
            Log::warning('HostGrap WhatsApp send failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send a photo by public HTTPS URL (HostGrap fetches image_url).
     *
     * @see https://wa-api.hostgrap.com/api/send-image.php
     */
    public function sendImage(string $recipient, string $imageUrl, string $caption = ''): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('HostGrap WhatsApp: missing email or API key (ERP settings or .env).');

            return false;
        }

        $phone = $this->normalizePhone($recipient);
        if ($phone === '') {
            return false;
        }

        $url = config('services.hostgrap.send_image_url');

        $response = Http::asForm()->timeout(60)->post($url, [
            'email' => $this->email(),
            'api_key' => $this->apiKey(),
            'phone' => $phone,
            'image_url' => $imageUrl,
            'caption' => $caption,
        ]);

        if (!$response->successful()) {
            Log::warning('HostGrap WhatsApp send-image failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    protected function email(): ?string
    {
        return AppSetting::get(AppSetting::KEY_HOSTGRAP_EMAIL) ?: config('services.hostgrap.email');
    }

    protected function apiKey(): ?string
    {
        return AppSetting::getDecryptedApiKey() ?: config('services.hostgrap.api_key');
    }

    /**
     * Normalize to digits; HostGrap expects 947XXXXXXXX style for LK.
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if ($phone === '') {
            return '';
        }
        if (str_starts_with($phone, '0')) {
            $phone = '94'.substr($phone, 1);
        } elseif (!str_starts_with($phone, '94')) {
            $phone = '94'.$phone;
        }

        return $phone;
    }
}
