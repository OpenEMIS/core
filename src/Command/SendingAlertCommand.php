<?php

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Http\Client;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Mailer\Email;
use Cake\Log\Log;

class SendingAlertCommand extends \Cake\Command\Command
{
    use LocatorAwareTrait;

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->AlertLogs = $this->fetchTable('Alert.AlertLogs');

        $today = FrozenTime::now();
        $feature = $args->getArgumentAt(0);
        $alertLogId = $args->getArgumentAt(1);

        if (empty($feature) || empty($alertLogId) || !is_numeric($alertLogId)) {
            $io->err('⚠️  Missing or invalid arguments:');
            $io->err('Usage: bin/cake sending_alert <feature> <alertLogId>');
            $io->err('Example: bin/cake sending_alert Leave 123');

            return static::CODE_ERROR;
        }


        $alertLogsList = $this->AlertLogs->find()
            ->where([
                'feature' => $feature,
                'id' => $alertLogId
            ])
            ->all();
        foreach ($alertLogsList as $log) {
            $methods = array_map(
                fn($m) => strtolower(trim($m)),
                explode(',', $log->method)
            );
//            $io->out("Methods" . print_r($methods, true));
            if (intval($log->status) === 0) {
                // Process only if status is pending
                if (in_array('email', $methods)) {
                    $this->sendEmail($log, $io);
                }

                if (in_array('sms', $methods)) {
                    $this->sendSms($log, $io);
                }

                // Mark as processed
                $this->AlertLogs->updateAll(
                    ['status' => 1,
                        'processed_date' => $today],
                    ['id' => $log->id]
                );

                $io->out("✅ AlertLog ID {$log->id} sent successfully.");
            } else {
                // Already sent
                $io->out("ℹ️ AlertLog ID {$log->id} was already sent. Skipping.");
            }



        }
        if ($alertLogsList->isEmpty()) {
            $io->out("ℹ️ No AlertLog found for feature: '{$feature}' and ID: {$alertLogId}. Skipping.");
        }

        return static::CODE_SUCCESS;
    }

    private function sendEmail($log, ConsoleIo $io): void
    {
//        $io->out('Subject: ' . $log->subject);
//        $io->out('Message: ' . $log->message);

        $emailArray = explode(', ', $log->destination);
        $sendTo = [];

        foreach ($emailArray as $item) {
            if (strpos($item, '<') !== false) {
                [$name, $email] = explode('<', $item);
                $email = trim(str_replace('>', '', $email));
                $name = trim($name);

                if (str_ends_with($email, '.comz')) {
                    $io->out("🛑 Skipped fake email: $email ($name)");
                    continue;
                }

                $sendTo[$email] = $name;
            }
        }

        if (!empty($sendTo)) {
            $emailObj = new Email('openemis');
            $emailObj->setTo($sendTo)
                ->setSubject($log->subject)
                ->send($log->message);

            $io->out('📧 Email sent to: ' . implode(', ', array_keys($sendTo)));
        }
    }

    private function sendSms($log, ConsoleIo $io): void
    {
//        $io->out('Subject: ' . $log->subject);
//        $io->out('Message: ' . $log->message);
        $phones = array_map('trim', explode(',', $log->destination));

        foreach ($phones as $phone) {
            if (str_ends_with($phone, 'zz')) {
                $io->out("🛑 Skipped fake phone: $phone");
                continue;
            }

            $this->sendTwilioSms($phone, $log->message, $io);
        }
    }

    private function sendTwilioSms(string $to, string $message, ConsoleIo $io): void
    {
        $ConfigItems = $this->getTableLocator()->get('Configuration.ConfigItems');
        $enabled = $ConfigItems->value('external_alert_service_sms_twilio');

        if ($enabled != '1') {
            $io->err("Twilio disabled. Cannot send to $to");
            return;
        }

        if (!preg_match('/^\+\d{10,15}$/', $to)) {
            $io->err("Invalid phone format: $to");
            return;
        }

        $cleanMessage = $this->sanitizeMessage($message);

        $attributes = $this->getTableLocator()
            ->get('Configuration.ExternalDataSourceAttributes')
            ->find('list', ['keyField' => 'attribute_field', 'valueField' => 'value'])
            ->where(['external_data_source_type' => 'Twilio'])
            ->disableHydration()
            ->toArray();

        $sid = $attributes['sms_account_sid'] ?? null;
        $token = $attributes['sms_auth_token'] ?? null;
        $from = $attributes['sms_number'] ?? null;

        if (!$sid || !$token || !$from) {
            $io->err("Twilio credentials missing for $to");
            Log::warning('Missing Twilio config', compact('sid', 'token', 'from'));
            return;
        }

        try {
            $http = new Client();
            $response = $http->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                ['From' => $from, 'To' => $to, 'Body' => $cleanMessage],
                [
                    'auth' => ['username' => $sid, 'password' => $token],
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded']
                ]
            );

            if ($response->isOk()) {
                $io->out("SMS sent to: $to");
            } else {
                $errorBody = $response->getStringBody();
                $io->out("Failed to send SMS to $to: $errorBody");
                $io->err("Failed to send SMS to $to: $errorBody");

                // 🔥 Add logging here
                Log::error('Twilio send failure', [
                    'to' => $to,
                    'response' => $errorBody,
                    'status' => $response->getStatusCode()
                ]);
            }
        } catch (\Exception $e) {
            $io->err("Exception sending SMS to $to: " . $e->getMessage());
            Log::error('Twilio exception', ['message' => $e->getMessage()]);
        }
    }

    private function sanitizeMessage(string $message): string
    {
        $message = preg_replace('/[^\P{C}\n]+/u', '', $message);
        $message = trim($message);
        return mb_substr(mb_convert_encoding($message, 'UTF-8', 'auto'), 0, 1600);
    }

    public function buildOptionParser(\Cake\Console\ConsoleOptionParser $parser): \Cake\Console\ConsoleOptionParser
    {
        return $parser->setDescription('Processes queued alert logs (email and SMS).');
    }
}
