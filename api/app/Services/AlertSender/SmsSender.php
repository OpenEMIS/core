<?php

// POCOR-9509: SMS sender service for asynchronous alerts queue
namespace App\Services\AlertSender;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsSender
{
    // POCOR-9509: Send SMS alert with validation
    public function send(string $destination, string $message): void
    {
        $phones = array_map('trim', explode(',', $destination));

        foreach ($phones as $phone) {
            // Block test phone numbers
            if (str_ends_with($phone, 'zz')) {
                Log::info('Skipped fake phone number', [
                    'reason' => 'blocked_test_number',
                    'phone' => $phone,
                    'message_length' => mb_strlen($message),
                    'channel' => 'sms',
                ]);
                continue;
            }

            // Validate E.164 format
            if (!preg_match('/^\+\d{10,15}$/', $phone)) {
                Log::warning('Invalid phone format', [
                    'reason' => 'invalid_e164_format',
                    'phone' => $phone,
                    'message_length' => mb_strlen($message),
                    'channel' => 'sms',
                ]);
                continue;
            }

            $this->sendViaTwilio($phone, $message);
        }
    }

    // POCOR-9509: Send via Twilio API with proper error handling
    private function sendViaTwilio(string $to, string $message): void
    {
        if (!config('alerts.twilio.enabled')) {
            throw new \RuntimeException('Twilio is not enabled. Set TWILIO_ENABLED=true in .env');
        }

        $sid = config('alerts.twilio.sid');
        $token = config('alerts.twilio.token');
        $from = config('alerts.twilio.from');

        if (!$sid || !$token || !$from) {
            throw new \RuntimeException('Twilio credentials missing. Check TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, and TWILIO_FROM_NUMBER in .env');
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->timeout(10)
            ->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message,
                ]
            );

        if (!$response->successful()) {
            $errorMessage = 'Twilio send failed: ' . $response->body();
            Log::error($errorMessage, [
                'to' => $to,
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new \RuntimeException($errorMessage);
        }

        Log::info('SMS sent successfully', [
            'to' => $to,
            'message_length' => mb_strlen($message),
        ]);
    }
}
