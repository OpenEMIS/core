<?php
namespace App\Shell;


use Cake\Console\Shell;
use Cake\Mailer\Email;
use Cake\Http\Client;
use Cake\I18n\FrozenTime;

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
                $phoneArray = explode(', ', $obj->destination);
                foreach ($phoneArray as $phone) {
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
        $sid = 'YOUR_TWILIO_SID';
        $token = 'YOUR_TWILIO_AUTH_TOKEN';
        $from = 'YOUR_TWILIO_PHONE_NUMBER';

        $http = new Client(['auth' => [$sid, $token]]);

        $response = $http->post(
            "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
            [
                'From' => $from,
                'To' => $to,
                'Body' => $message
            ]
        );

        if ($response->isOk()) {
            $this->out('SMS sent to: ' . $to);
        } else {
            $this->err('Failed to send SMS to ' . $to . ': ' . $response->getStringBody());
        }
    }
}
