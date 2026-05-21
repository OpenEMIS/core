<?php

// POCOR-9509: Email sender service for asynchronous alerts queue
namespace App\Services\AlertSender;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailSender
{
    // POCOR-9509: Send email alert with HTML support
    public function send(string $recipient, ?string $subject, string $message, bool $isHtml = false): void
    {
        $parsed = $this->parseRecipients($recipient, $subject, $message);

        if (empty($parsed)) {
            return;
        }

        foreach ($parsed as $email => $name) {
            if ($isHtml) {
                Mail::html($message, function ($mail) use ($email, $name, $subject) {
                    $mail->to($email, $name)
                        ->subject($subject ?? '');
                });
            } else {
                Mail::raw($message, function ($mail) use ($email, $name, $subject) {
                    $mail->to($email, $name)
                        ->subject($subject ?? '');
                });
            }
        }
    }

    // POCOR-9509: Parse recipient string with validation
    private function parseRecipients(string $destination, ?string $subject, string $message): array
    {
        $result = [];
        $items = explode(',', $destination);

        foreach ($items as $item) {
            $item = trim($item);

            // Handle format: "Name <email@domain.com>"
            if (strpos($item, '<') !== false) {
                [$name, $email] = explode('<', $item);
                $email = trim(str_replace('>', '', $email));
                $name = trim($name);
            } else {
                // Handle plain format: "email@domain.com"
                $email = $item;
                $name = '';
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid email format', [
                    'reason' => 'invalid_email_format',
                    'email' => $email,
                    'channel' => 'email',
                ]);
                continue;
            }

            // Block test domains (e.g., .comz)
            if (str_ends_with($email, '.comz')) {
                Log::info('Skipped fake email address', [
                    'reason' => 'blocked_test_domain',
                    'email' => $email,
                    'name' => $name,
                    'subject' => $subject ?? null,
                    'message_length' => mb_strlen($message),
                    'channel' => 'email',
                ]);
                continue;
            }

            $result[$email] = $name;
        }

        return $result;
    }
}
