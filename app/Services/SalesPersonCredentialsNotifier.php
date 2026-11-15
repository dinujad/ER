<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class SalesPersonCredentialsNotifier
{
    public function __construct(
        protected TextLkSmsService $sms
    ) {}

    /**
     * @return array{ok: bool, error: string}
     */
    public function sendViaSms(User $user, string $password): array
    {
        $phone = trim((string) ($user->phone ?? ''));
        if ($phone === '') {
            return [
                'ok' => false,
                'error' => 'No phone number saved for this sales person. Add phone on edit and try again.',
            ];
        }

        if (!$this->isSmsConfigured()) {
            return [
                'ok' => false,
                'error' => 'SMS is not configured. Set TEXTLK_API_KEY and TEXTLK_URL in .env.',
            ];
        }

        $message = $this->buildSmsMessage($user, $password);
        $sent = $this->sms->sendSms($phone, $message);

        if ($sent) {
            Log::info('Sales person credentials sent via SMS', [
                'user_id' => $user->id,
                'phone' => $phone,
            ]);

            return ['ok' => true, 'error' => ''];
        }

        Log::warning('Sales person credentials SMS send failed', [
            'user_id' => $user->id,
            'phone' => $phone,
        ]);

        return [
            'ok' => false,
            'error' => 'SMS could not be sent. Check phone number and Text.lk settings (TEXTLK_API_KEY).',
        ];
    }

    protected function isSmsConfigured(): bool
    {
        return !empty(config('services.textlk.api_key')) && !empty(config('services.textlk.url'));
    }

    protected function buildSmsMessage(User $user, string $password): string
    {
        $app = config('app.name', 'DERMAS');
        $login = route('sales-person.login');

        return "Hello {$user->name}, your {$app} sales login — Email: {$user->email} Password: {$password} Login: {$login}";
    }
}
