<?php
namespace App\Shell;


use Cake\Console\Shell;
use Cake\Mailer\Email;
use Cake\Http\Client;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Log\Log;

class SendingAlertShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Alert.AlertLogs');
    }

    public function main()
    {
        $today = FrozenTime::now();

        $feature = !empty($this->args[0]) ? $this->args[0] : 0;
        $alertLogId = !empty($this->args[1]) ? $this->args[1] : 0;

        $alertLogsList = $this->AlertLogs->find()
            ->where([
                'status' => 0,
                'feature' => $feature,
                'id' => $alertLogId
            ])
            ->all();

        foreach ($alertLogsList as $obj) {
            $methods = array_map('trim', explode(',', $obj->method));

            if (in_array('Email', $methods)) {
                $emailArray = explode(', ', $obj->destination);

                $sendTo = [];
                foreach ($emailArray as $item) {
                    list($name, $email) = explode('<', $item);
                    $name = trim($name);
                    $email = str_replace('>', '', $email);
                    $sendTo[$email] = $name;
                }

                $emailObj = new Email('openemis');
                $emailObj
                    ->setTo($sendTo)
                    ->setSubject($obj->subject)
                    ->send($obj->message);

                $this->out('Email sent to: ' . implode(', ', array_keys($sendTo)));
            }

            if (in_array('SMS', $methods)) {
                $phoneArray = explode(',', $obj->destination);
                foreach ($phoneArray as $phone) {
                    $phone = trim($phone);
                    $this->sendTwilioSms($phone, $obj->message);
                }
            }

            $this->AlertLogs->updateAll(
                ['status' => 1, 'processed_date' => $today],
                ['id' => $obj->id]
            );

        }
    }

    public function sendTwilioSms($to, $message)
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');

        $twilioEnabled = $ConfigItems->value('external_alert_service_sms_twilio');

        if($twilioEnabled != "1"){
            $this->err('Failed to send SMS to ' . $to . ': Check Twilio Enabled');
        }

        // Validate phone number format (basic E.164 check)
        if (!preg_match('/^\+\d{10,15}$/', $to)) {
            $this->err('Invalid phone number format: ' . $to);
            return;
        }

        $cleanMessage = $this->sanitizeMessage($message);

        $ExternalDataSourceAttributesTable = TableRegistry::getTableLocator()->get('Configuration.ExternalDataSourceAttributes');
        $twilioAttributes = $ExternalDataSourceAttributesTable
            ->find('list', [
                'keyField' => 'attribute_field',
                'valueField' => 'value'
            ])
            ->disableHydration()
            ->where([
                $ExternalDataSourceAttributesTable->aliasField('external_data_source_type') => 'Twilio'
            ])
            ->toArray();

        $sid = $twilioAttributes['account_sid'] ?? null;
        $token = $twilioAttributes['auth_token'] ?? null;
        $from = $twilioAttributes['twilio_number'] ?? null;

        if ($sid && $token && $from) {
            // Initialize CakePHP HTTP Client
            $http = new Client();

            // Temporary log to inspect outgoing SMS payload before sending
//            Log::info('Preparing to send SMS via Twilio' . print_r([
//                'to' => $to,
//                'from' => $from,
//                'message' => $cleanMessage
//            ], true));

            try {
                // Send POST request to Twilio API
                $response = $http->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                    [
                        'From' => $from,
                        'To' => $to,
                        'Body' => $cleanMessage
                    ],
                    [
                        'auth' => [
                            'username' => $sid,
                            'password' => $token
                        ],
                        'headers' => [
                            'Content-Type' => 'application/x-www-form-urlencoded'
                        ]
                    ]
                );

                // Check response status
                if ($response->isOk()) {
                    $this->out('SMS sent to: ' . $to);
                } else {
                    $this->err('Failed to send SMS to ' . $to . ': ' . $response->getStringBody());
                }
            } catch (\Exception $e) {
                // Log and report exception if the request fails
                $this->err('Exception when sending SMS: ' . $e->getMessage());
                Log::error('Twilio SMS exception', ['message' => $e->getMessage()]);
            }
        } else {
            // Log if credentials/configs are missing
            $this->err('Failed to send SMS to ' . $to . ': Check Twilio SMS Configuration');
            Log::warning('Missing Twilio configuration', compact('sid', 'token', 'from'));
        }
    }

    private function sanitizeMessage($message)
    {
        // Strip control characters (except newline)
        $message = preg_replace('/[^\P{C}\n]+/u', '', $message);

        // Trim extra whitespace
        $message = trim($message);

        // Ensure UTF-8 encoding (Twilio expects this)
        if (!mb_check_encoding($message, 'UTF-8')) {
            $message = mb_convert_encoding($message, 'UTF-8', 'auto');
        }

        // Optional: limit to 1600 chars (Twilio max)
        return mb_substr($message, 0, 1600);
    }
}
