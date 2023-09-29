<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Mailer\Email;

class SendingAlertShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Alert.AlertRules');
    }

    public function main()
    {
        // sending the email to the destination and update the alertLogs status.
        $today = Time::now();
        $todayDate = Date::now();

        $feature = !empty($this->args[0]) ? $this->args[0] : 0;
        $alertLogId = !empty($this->args[1]) ? $this->args[1] : 0;

        $alertLogsList = $this->AlertLogs->find()
            ->where([
                'status' => 0,
                'feature' => $feature,
                'id' => $alertLogId
            ]) // pending
            ->all();

        foreach ($alertLogsList as $obj) {
            $emailArray = explode(', ', $obj->destination); // also can used

            $sendTo = [];
            foreach ($emailArray as $item) {
                list($name, $email) = explode('<', $item);
                $name = trim($name);
                $email = str_replace('>', '', $email);
                $sendTo[$email] = $name;
            }

            // sending Email if the destination email is exist
            $emailObj = new Email('openemis');
            $emailObj
                ->to($sendTo)
                ->subject($obj->subject)
                ->send($obj->message);

            // update the alertLog
            $this->AlertLogs->updateAll(
                ['status' => 1, 'processed_date' => $today],
                ['id' => $obj->id]
            );
        }
    }
}
