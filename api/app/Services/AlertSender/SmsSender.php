<?php

// POCOR-9509: SMS sender service for asynchronous alerts queue
namespace App\Services\AlertSender;

use Illuminate\Support\Facades\DB;
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

    // POCOR-9509: Load Twilio credentials from external_data_source_attributes (same source as CakePHP sender)
    private function getTwilioCredentials(): array
    {
        $rows = DB::table('external_data_source_attributes')
            ->where('external_data_source_type', 'Twilio')
            ->whereIn('attribute_field', ['sms_account_sid', 'sms_auth_token', 'sms_number'])
            ->pluck('value', 'attribute_field')
            ->toArray();

        return [
            'sid'   => $rows['sms_account_sid'] ?? null,
            'token' => $rows['sms_auth_token']  ?? null,
            'from'  => $rows['sms_number']       ?? null,
        ];
    }

    // POCOR-9509: Send via Twilio API with proper error handling
    private function sendViaTwilio(string $to, string $message): void
    {
        $credentials = $this->getTwilioCredentials();
        $sid   = $credentials['sid'];
        $token = $credentials['token'];
        $from  = $credentials['from'];

        if (!$sid || !$token || !$from) {
            throw new \RuntimeException('Twilio credentials missing. Configure them via Administration > System Configuration > External Alert Service SMS.');
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->timeout(10)
            ->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                [
                    'From' => $from,
                    'To'   => $to,
                    'Body' => $message,
                ]
            );

        if (!$response->successful()) {
            $errorMessage = 'Twilio send failed: ' . $response->body();
            Log::error($errorMessage, [
                'to'          => $to,
                'status_code' => $response->status(),
                'response'    => $response->body(),
            ]);
            throw new \RuntimeException($errorMessage);
        }

        Log::info('SMS sent successfully', [
            'to'             => $to,
            'message_length' => mb_strlen($message),
        ]);
    }
}
